<?php
/**
 * Forgot Password API  — session-based OTP (no extra DB table required)
 * POST /backend/api/auth/forgot_password_api.php
 */

session_start();

require_once '../../config/database.php';
require_once '../config/api_auth.php'; // ← add
requireApiKey();  

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

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'A valid email address is required.', [], 422);
    }

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

    if (!getenv('SENDGRID_API_KEY')) {
        jsonResponse(false, 'Mail server is not configured. Please contact the administrator.');
    }

    $user = dbFetchOne(
        "SELECT user_id, full_name, email FROM qa_users WHERE email = ? AND is_active = 1 LIMIT 1",
        's',
        [$email]
    );

    $otp     = sprintf('%06d', random_int(0, 999999));
    $expires = time() + 600;

    if ($user) {
        // ✅ FIX 1: Use HMAC-SHA256 instead of bcrypt — sha256 is instant,
        //    bcrypt is intentionally slow (150-300ms). OTPs are already
        //    protected by attempt limits, so bcrypt's cost buys nothing here.
        $otpSalt = bin2hex(random_bytes(16));
        $_SESSION['fp_otp_hash']     = hash_hmac('sha256', $otp, $otpSalt);
        $_SESSION['fp_otp_salt']     = $otpSalt;
        $_SESSION['fp_otp_expires']  = $expires;
        $_SESSION['fp_otp_attempts'] = 0;
        $_SESSION['fp_email']        = $email;
        unset($_SESSION['fp_reset_token']);

        sendOtpEmail($email, $user['full_name'], $otp);
    }

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

    if (empty($_SESSION['fp_otp_hash']) || empty($_SESSION['fp_otp_expires'])) {
        jsonResponse(false, 'No pending code. Please request a new one.', [], 401);
    }

    if (($_SESSION['fp_email'] ?? '') !== $email) {
        jsonResponse(false, 'Invalid request. Please start over.', [], 401);
    }

    if (time() > (int)$_SESSION['fp_otp_expires']) {
        unset($_SESSION['fp_otp_hash'], $_SESSION['fp_otp_salt'],
              $_SESSION['fp_otp_expires'], $_SESSION['fp_otp_attempts'],
              $_SESSION['fp_email']);
        jsonResponse(false, 'This code has expired. Please request a new one.', [], 401);
    }

    $_SESSION['fp_otp_attempts'] = ($_SESSION['fp_otp_attempts'] ?? 0) + 1;
    if ($_SESSION['fp_otp_attempts'] > 5) {
        unset($_SESSION['fp_otp_hash'], $_SESSION['fp_otp_salt'],
              $_SESSION['fp_otp_expires'], $_SESSION['fp_otp_attempts'],
              $_SESSION['fp_email']);
        jsonResponse(false, 'Too many failed attempts. Please request a new code.', [], 429);
    }

    // ✅ FIX 1 (continued): Verify using HMAC-SHA256 + hash_equals (timing-safe)
    $expectedHash = hash_hmac('sha256', $code, $_SESSION['fp_otp_salt'] ?? '');
    if (!hash_equals($expectedHash, $_SESSION['fp_otp_hash'])) {
        $remaining = max(0, 5 - (int)$_SESSION['fp_otp_attempts']);
        jsonResponse(false, 'Incorrect code. ' . $remaining . ' attempt(s) remaining.', [], 401);
    }

    unset($_SESSION['fp_otp_hash'], $_SESSION['fp_otp_salt'],
          $_SESSION['fp_otp_expires'], $_SESSION['fp_otp_attempts']);

    $resetToken = bin2hex(random_bytes(32));
    $_SESSION['fp_reset_token']        = $resetToken;
    $_SESSION['fp_reset_token_email']  = $email;
    $_SESSION['fp_reset_token_expiry'] = time() + 600;

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

    $user = dbFetchOne(
        "SELECT user_id FROM qa_users WHERE email = ? AND is_active = 1 LIMIT 1",
        's',
        [$email]
    );

    if (!$user) {
        jsonResponse(false, 'Account not found or deactivated.', [], 404);
    }

    $newHash = password_hash($password, PASSWORD_BCRYPT);
    dbExecute(
        "UPDATE qa_users SET password_hash = ? WHERE user_id = ?",
        'si',
        [$newHash, $user['user_id']]
    );

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
function verifyCaptcha(string $token): bool
{
    $secret = getenv('HCAPTCHA_SECRET') ?: '0x0000000000000000000000000000000000000000';

    $ch = curl_init('https://hcaptcha.com/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['secret' => $secret, 'response' => $token]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('[ForgotPassword] hCaptcha curl error: ' . $curlError);
        return false;
    }

    $data = json_decode($response, true);
    // ✅ Removed temp debug log — don't log tokens in production
    return !empty($data['success']);
}

function sendOtpEmail(string $toEmail, string $toName, string $otp): void
{
    $apiKey      = getenv('SENDGRID_API_KEY');
    $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: getenv('SENDGRID_FROM_EMAIL') ?: '';
    $fromName    = getenv('MAIL_FROM_NAME') ?: 'QA System';
    $replyTo     = getenv('MAIL_REPLY_TO') ?: $fromAddress;

    if (!$apiKey || !$fromAddress) {
        error_log('[ForgotPassword] SendGrid not configured — set SENDGRID_API_KEY and MAIL_FROM_ADDRESS.');
        return;
    }

    $year    = date('Y');
    $subject = 'Your Password Reset Code — QA Management System';

    $htmlBody = <<<HTML
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

    $plainBody = "Hi {$toName},\n\nYour password reset code is: {$otp}\n\nThis code expires in 10 minutes.\n\nIf you did not request this, you can safely ignore this email.\n\n— QA System";

    $payload = json_encode([
        'personalizations' => [
            ['to' => [['email' => $toEmail, 'name' => $toName]]],
        ],
        'from'     => ['email' => $fromAddress, 'name' => $fromName],
        // ✅ FIX 2: Add reply_to — missing this is a spam signal
        'reply_to' => ['email' => $replyTo, 'name' => $fromName],
        'subject'  => $subject,
        'content'  => [
            ['type' => 'text/plain', 'value' => $plainBody],
            ['type' => 'text/html',  'value' => $htmlBody],
        ],
        // ✅ FIX 3: Disable click & open tracking.
        //    By default SendGrid rewrites all links through its own tracking
        //    domain — spam filters see this as suspicious for transactional mail.
        'tracking_settings' => [
            'click_tracking' => ['enable' => false],
            'open_tracking'  => ['enable' => false],
        ],
        // ✅ FIX 4: Mark as transactional so SendGrid bypasses
        //    unsubscribe list management (correct for password reset emails)
        'mail_settings' => [
            'bypass_list_management' => ['enable' => true],
        ],
    ]);

    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
    ]);

    $response   = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('[ForgotPassword] SendGrid curl error: ' . $curlError);
    } elseif ($statusCode < 200 || $statusCode >= 300) {
        error_log('[ForgotPassword] SendGrid HTTP ' . $statusCode . ': ' . $response);
    }
}