<?php
/**
 * Forgot Password API  — session-based OTP (no extra DB table required)
 * POST /backend/api/auth/forgot_password_api.php
 *
 * Actions:
 *   send_code      — validate email, verify hCaptcha, generate & email OTP (stored in session)
 *   verify_code    — check OTP from session, issue a short-lived reset token in session
 *   reset_password — verify reset token from session, update password hash
 */

// ── Autoloader (PHPMailer) ────────────────────────────────────
$_autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
if (file_exists($_autoloadPath)) {
    require_once $_autoloadPath;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

session_start();

require_once '../../config/database.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = sanitize($input['action'] ?? '');

switch ($action) {
    case 'send_code':      handleSendCode($input);      break;
    case 'verify_code':    handleVerifyCode($input);    break;
    case 'reset_password': handleResetPassword($input); break;
    default:               jsonResponse(false, 'Invalid action.', [], 400);
}

// ═══════════════════════════════════════════════════════════════
// STEP 1 — Send OTP to email
// ═══════════════════════════════════════════════════════════════
function handleSendCode(array $input): void
{
    $email    = sanitize($input['email'] ?? '');
    $captcha  = $input['captcha'] ?? '';
    $isResend = !empty($input['resend']);

    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'A valid email address is required.', [], 422);
    }

    // Verify hCaptcha (skip on resend — user already passed it)
    if (!$isResend) {
        if (empty($captcha)) {
            jsonResponse(false, 'Please complete the human verification.', [], 422);
        }
        if (!verifyCaptcha($captcha)) {
            jsonResponse(false, 'Captcha verification failed. Please try again.', [], 422);
        }
    }

    // Rate-limit: max 3 sends per 15 min per session
    $rateCount  = $_SESSION['fp_rate_count']  ?? 0;
    $rateWindow = $_SESSION['fp_rate_window'] ?? 0;

    if ((time() - $rateWindow) > 900) {
        $rateCount  = 0;
        $rateWindow = time();
    }

    if ($rateCount >= 3) {
        jsonResponse(false, 'Too many requests. Please wait 15 minutes before trying again.', [], 429);
    }

    // Look up user — same generic response whether found or not (prevent email enumeration)
    $user = dbFetchOne(
        "SELECT user_id, full_name, email FROM qa_users WHERE email = ? AND is_active = 1 LIMIT 1",
        's',
        [$email]
    );

    // Generate OTP regardless
    $otp     = sprintf('%06d', random_int(0, 999999));
    $expires = time() + 600; // 10 minutes (same as profile_api.php)

    if ($user) {
        // Store hashed OTP in session — same pattern as profile_api.php
        $_SESSION['fp_otp_hash']     = password_hash($otp, PASSWORD_BCRYPT);
        $_SESSION['fp_otp_expires']  = $expires;
        $_SESSION['fp_otp_attempts'] = 0;
        $_SESSION['fp_email']        = $email;
        unset($_SESSION['fp_reset_token']); // clear any stale token

        sendOtpEmail($email, $user['full_name'], $otp);
    }

    // Update rate limit counters
    $_SESSION['fp_rate_count']  = $rateCount + 1;
    $_SESSION['fp_rate_window'] = $rateWindow;

    jsonResponse(true, 'If that email is registered, a verification code has been sent.');
}

// ═══════════════════════════════════════════════════════════════
// STEP 2 — Verify OTP, issue reset token
// ═══════════════════════════════════════════════════════════════
function handleVerifyCode(array $input): void
{
    $email = sanitize($input['email'] ?? '');
    $code  = preg_replace('/\D/', '', $input['code'] ?? '');

    if (strlen($code) !== 6) {
        jsonResponse(false, 'Please enter the 6-digit verification code.', [], 422);
    }

    // Check session has a pending OTP
    if (empty($_SESSION['fp_otp_hash']) || empty($_SESSION['fp_otp_expires'])) {
        jsonResponse(false, 'No pending code. Please request a new one.', [], 401);
    }

    // Email must match what the code was sent to
    if (($_SESSION['fp_email'] ?? '') !== $email) {
        jsonResponse(false, 'Invalid request. Please start over.', [], 401);
    }

    // Expired?
    if (time() > (int)$_SESSION['fp_otp_expires']) {
        unset($_SESSION['fp_otp_hash'], $_SESSION['fp_otp_expires'],
              $_SESSION['fp_otp_attempts'], $_SESSION['fp_email']);
        jsonResponse(false, 'This code has expired. Please request a new one.', [], 401);
    }

    // Brute-force guard: max 5 attempts (same as profile_api.php)
    $_SESSION['fp_otp_attempts'] = ($_SESSION['fp_otp_attempts'] ?? 0) + 1;
    if ($_SESSION['fp_otp_attempts'] > 5) {
        unset($_SESSION['fp_otp_hash'], $_SESSION['fp_otp_expires'],
              $_SESSION['fp_otp_attempts'], $_SESSION['fp_email']);
        jsonResponse(false, 'Too many failed attempts. Please request a new code.', [], 429);
    }

    // Verify OTP
    if (!password_verify($code, $_SESSION['fp_otp_hash'])) {
        $remaining = max(0, 5 - (int)$_SESSION['fp_otp_attempts']);
        jsonResponse(false, 'Incorrect code. ' . $remaining . ' attempt(s) remaining.', [], 401);
    }

    // Correct — clear OTP, issue short-lived reset token in session
    unset($_SESSION['fp_otp_hash'], $_SESSION['fp_otp_expires'], $_SESSION['fp_otp_attempts']);

    $resetToken = bin2hex(random_bytes(32));
    $_SESSION['fp_reset_token']        = $resetToken;
    $_SESSION['fp_reset_token_email']  = $email;
    $_SESSION['fp_reset_token_expiry'] = time() + 600; // 10 minutes to complete

    jsonResponse(true, 'Code verified successfully.', ['token' => $resetToken]);
}

// ═══════════════════════════════════════════════════════════════
// STEP 3 — Reset password
// ═══════════════════════════════════════════════════════════════
function handleResetPassword(array $input): void
{
    $token    = $input['token']    ?? '';
    $password = $input['password'] ?? '';

    if (empty($token) || empty($password)) {
        jsonResponse(false, 'Reset token and new password are required.', [], 422);
    }

    if (strlen($password) < 8) {
        jsonResponse(false, 'Password must be at least 8 characters.', [], 422);
    }

    // Validate session reset token
    if (empty($_SESSION['fp_reset_token']) ||
        !hash_equals($_SESSION['fp_reset_token'], $token)) {
        jsonResponse(false, 'Invalid or expired reset session. Please start over.', [], 401);
    }

    if (time() > (int)($_SESSION['fp_reset_token_expiry'] ?? 0)) {
        unset($_SESSION['fp_reset_token'], $_SESSION['fp_reset_token_email'],
              $_SESSION['fp_reset_token_expiry'], $_SESSION['fp_email']);
        jsonResponse(false, 'Your reset session has expired. Please start over.', [], 401);
    }

    $email = $_SESSION['fp_reset_token_email'] ?? '';

    // Find the user
    $user = dbFetchOne(
        "SELECT user_id FROM qa_users WHERE email = ? AND is_active = 1 LIMIT 1",
        's',
        [$email]
    );

    if (!$user) {
        jsonResponse(false, 'Account not found or deactivated.', [], 404);
    }

    // Update password — same as profile_api.php
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    dbExecute(
        "UPDATE qa_users SET password_hash = ? WHERE user_id = ?",
        'si',
        [$newHash, $user['user_id']]
    );

    // Clean up all forgot-password session keys
    unset(
        $_SESSION['fp_reset_token'],
        $_SESSION['fp_reset_token_email'],
        $_SESSION['fp_reset_token_expiry'],
        $_SESSION['fp_email'],
        $_SESSION['fp_rate_count'],
        $_SESSION['fp_rate_window']
    );

    jsonResponse(true, 'Your password has been reset successfully.');
}

// ═══════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════

/**
 * Verify hCaptcha token against hCaptcha's siteverify API.
 * Reads HCAPTCHA_SECRET from .env — falls back to hCaptcha's always-pass test secret.
 */
function verifyCaptcha(string $token): bool
{
    $envPath = __DIR__ . '/../../.env';
    $secret  = '0x0000000000000000000000000000000000000000'; // test secret (always passes)

    if (file_exists($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'HCAPTCHA_SECRET=')) {
                $secret = trim(substr($line, strlen('HCAPTCHA_SECRET=')), " \t\"'");
                break;
            }
        }
    }

    $ch = curl_init('https://hcaptcha.com/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['secret' => $secret, 'response' => $token]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return false;
    $data = json_decode($response, true);
    return !empty($data['success']);
}

/**
 * Send OTP email via PHPMailer.
 * Reads SMTP credentials from .env — exactly the same way profile_api.php does.
 */
function sendOtpEmail(string $toEmail, string $toName, string $otp): void
{
    // Parse .env (same logic as profile_api.php's parseEnvFile)
    $envPath = __DIR__ . '/../../.env';
    $env     = [];

    if (file_exists($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $val] = explode('=', $line, 2);
            $env[trim($key)] = trim($val, " \t\"'");
        }
    }

    $mailUser = $env['MAIL_USERNAME']     ?? '';
    $mailPass = $env['MAIL_PASSWORD']     ?? '';
    $fromName = $env['MAIL_FROM_NAME']    ?? 'QA System';
    $fromAddr = $env['MAIL_FROM_ADDRESS'] ?? $mailUser;

    if (empty($mailUser) || empty($mailPass)) {
        error_log('[ForgotPassword] Mailer not configured — set MAIL_USERNAME/MAIL_PASSWORD in .env');
        return;
    }

    if (!class_exists(PHPMailer::class)) {
        error_log('[ForgotPassword] PHPMailer not found.');
        return;
    }

    try {
        $mail             = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $env['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUser;
        $mail->Password   = $mailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($env['MAIL_PORT'] ?? 587);
        $mail->Timeout    = 10;
        $mail->SMTPDebug  = 0;

        $mail->setFrom($fromAddr, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = 'Your Password Reset Code — QA Management System';
        $mail->isHTML(true);

        $year = date('Y');
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f3ff;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f3ff;padding:40px 0;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(108,92,231,.10);">

        <tr>
          <td style="background:linear-gradient(135deg,#6c5ce7,#a78bfa);padding:32px 40px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">🔑</div>
            <h1 style="color:#fff;margin:0;font-size:1.2rem;font-weight:700;">Password Reset Request</h1>
            <p style="color:rgba(255,255,255,.85);margin:6px 0 0;font-size:.83rem;">QA Management System</p>
          </td>
        </tr>

        <tr>
          <td style="padding:32px 40px;">
            <p style="color:#374151;margin:0 0 14px;font-size:.95rem;">Hi <strong>{$toName}</strong>,</p>
            <p style="color:#374151;margin:0 0 24px;font-size:.9rem;line-height:1.6;">
              We received a request to reset your password. Use the verification code below.
              This code is valid for <strong>10 minutes</strong>.
            </p>
            <div style="background:#f5f3ff;border:2px solid #ede9fd;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;">
              <p style="color:#6b7280;margin:0 0 8px;font-size:.75rem;text-transform:uppercase;letter-spacing:1px;font-weight:600;">Your Verification Code</p>
              <div style="font-size:2.4rem;font-weight:800;letter-spacing:12px;color:#6c5ce7;font-family:'Courier New',monospace;">{$otp}</div>
            </div>
            <p style="color:#6b7280;font-size:.82rem;margin:0 0 6px;">If you did not request this, please ignore this email — your account remains safe.</p>
            <p style="color:#6b7280;font-size:.82rem;margin:0;"><strong>Never share this code</strong> with anyone.</p>
          </td>
        </tr>

        <tr>
          <td style="background:#f9fafb;border-top:1px solid #f3f4f6;padding:14px 40px;text-align:center;">
            <p style="color:#9ca3af;font-size:.73rem;margin:0;">&copy; {$year} Quality Assurance Management System &bull; All rights reserved</p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        $mail->AltBody = "Hi {$toName},\n\nYour password reset code is: {$otp}\n\nThis code expires in 10 minutes.\n\nIf you did not request this, you can safely ignore this email.\n\n— QA System";

        $mail->send();

    } catch (MailerException $e) {
        error_log('[ForgotPassword] Mailer error: ' . $e->getMessage());
    }
}