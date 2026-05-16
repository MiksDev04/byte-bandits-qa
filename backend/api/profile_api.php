<?php

/**
 * Profile API - View and Update Current User's Profile
 * backend/api/profile_api.php
 */
session_start();

// 1. Load database config FIRST (defines jsonResponse)
require_once __DIR__ . '/../config/database.php';
require_once '../config/api_auth.php';
requireApiKey();

// 2. Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 3. Auth guard
if (empty($_SESSION['logged_in'])) {
    jsonResponse(false, 'Unauthorized access', [], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'get') {
                getProfile();
            } else {
                jsonResponse(false, 'Invalid action');
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                jsonResponse(false, 'Invalid JSON data');
            }

            $postAction = $data['action'] ?? null;

            if ($postAction === 'update_info') {
                updateProfileInfo($data);
            } elseif ($postAction === 'change_password') {
                changePassword($data);
            } elseif ($postAction === 'send_verification_code') {
                sendVerificationCode();
            } elseif ($postAction === 'verify_code') {
                verifyPasswordCode($data);
            } elseif ($postAction === 'send_email_change_code') {
                sendEmailChangeCode($data);
            } elseif ($postAction === 'verify_email_change_code') {
                verifyEmailChangeCode($data);
            } else {
                jsonResponse(false, 'Invalid action');
            }
            break;

        default:
            jsonResponse(false, 'Method not allowed', [], 405);
    }
} catch (Exception $e) {
    error_log('Profile API Error: ' . $e->getMessage());
    jsonResponse(false, 'Server error occurred', [], 500);
}

// ─────────────────────────────────────────────────────────────────────────────
// SendGrid helper — HTML + plain-text, anti-spam hardened
// ─────────────────────────────────────────────────────────────────────────────
function sendEmailViaSendGrid(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $plainBody
): bool {
    $apiKey = getenv('SENDGRID_API_KEY');
    if (!$apiKey) {
        error_log('[ProfileAPI] SendGrid: SENDGRID_API_KEY is not set.');
        return false;
    }

    $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: getenv('SENDGRID_FROM_EMAIL') ?: '';
    $fromName    = getenv('MAIL_FROM_NAME') ?: 'QA System';
    $replyTo     = getenv('MAIL_REPLY_TO') ?: $fromAddress;

    if (!$fromAddress) {
        error_log('[ProfileAPI] SendGrid: MAIL_FROM_ADDRESS is not set.');
        return false;
    }

    $payload = json_encode([
        'personalizations' => [
            ['to' => [['email' => $toEmail, 'name' => $toName]]],
        ],
        'from'     => ['email' => $fromAddress, 'name' => $fromName],
        'reply_to' => ['email' => $replyTo, 'name' => $fromName],
        'subject'  => $subject,
        'content'  => [
            ['type' => 'text/plain', 'value' => $plainBody],
            ['type' => 'text/html',  'value' => $htmlBody],
        ],
        'tracking_settings' => [
            'click_tracking' => ['enable' => false],
            'open_tracking'  => ['enable' => false],
        ],
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
        error_log('[ProfileAPI] SendGrid curl error: ' . $curlError);
        return false;
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        error_log('[ProfileAPI] SendGrid HTTP ' . $statusCode . ': ' . $response);
        return false;
    }

    return true;
}

// ─────────────────────────────────────────────────────────────────────────────
// Build the styled HTML email — password change
// ─────────────────────────────────────────────────────────────────────────────
function buildVerificationEmail(string $toName, string $code): array
{
    $year = date('Y');

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f3ff;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f3ff;padding:40px 0;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(108,92,231,.10);">
        <tr>
          <td style="background:linear-gradient(135deg,#6c5ce7,#a78bfa);padding:32px 40px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">🔒</div>
            <h1 style="color:#fff;margin:0;font-size:1.2rem;font-weight:700;">Password Change Verification</h1>
            <p style="color:rgba(255,255,255,.85);margin:6px 0 0;font-size:.83rem;">QA Management System</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px 40px;">
            <p style="color:#374151;margin:0 0 14px;font-size:.95rem;">Hi <strong>{$toName}</strong>,</p>
            <p style="color:#374151;margin:0 0 24px;font-size:.9rem;line-height:1.6;">
              We received a request to change your account password. Use the verification
              code below to confirm your identity. This code is valid for <strong>10 minutes</strong>.
            </p>
            <div style="background:#f5f3ff;border:2px solid #ede9fd;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;">
              <p style="color:#6b7280;margin:0 0 8px;font-size:.75rem;text-transform:uppercase;letter-spacing:1px;font-weight:600;">Your Verification Code</p>
              <div style="font-size:2.4rem;font-weight:800;letter-spacing:12px;color:#6c5ce7;font-family:'Courier New',monospace;">{$code}</div>
            </div>
            <p style="color:#6b7280;font-size:.82rem;margin:0 0 6px;">If you did not request a password change, please ignore this email — your account remains safe.</p>
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

    $plain = "Hi {$toName},\n\n"
        . "Your password change verification code is: {$code}\n\n"
        . "This code expires in 10 minutes.\n\n"
        . "If you did not request a password change, you can safely ignore this email.\n\n"
        . "— QA System";

    return ['html' => $html, 'plain' => $plain];
}

// ─────────────────────────────────────────────────────────────────────────────
// Build the styled HTML email — email address change
// ─────────────────────────────────────────────────────────────────────────────
function buildEmailChangeVerificationEmail(string $toName, string $code, string $newEmail): array
{
    $year       = date('Y');
    $safeNew    = htmlspecialchars($newEmail, ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#e8f5fe;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#e8f5fe;padding:40px 0;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(30,136,229,.10);">
        <tr>
          <td style="background:linear-gradient(135deg,#1e88e5,#42a5f5);padding:32px 40px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">✉️</div>
            <h1 style="color:#fff;margin:0;font-size:1.2rem;font-weight:700;">Email Address Change</h1>
            <p style="color:rgba(255,255,255,.85);margin:6px 0 0;font-size:.83rem;">QA Management System</p>
          </td>
        </tr>
        <tr>
          <td style="padding:32px 40px;">
            <p style="color:#374151;margin:0 0 14px;font-size:.95rem;">Hi <strong>{$toName}</strong>,</p>
            <p style="color:#374151;margin:0 0 14px;font-size:.9rem;line-height:1.6;">
              A request was made to change your account's email address to:
            </p>
            <p style="background:#f0f7ff;border:1px solid #90caf9;border-radius:8px;padding:10px 16px;
                      font-size:.92rem;font-weight:700;color:#1565c0;margin:0 0 20px;word-break:break-all;">
              {$safeNew}
            </p>
            <p style="color:#374151;margin:0 0 24px;font-size:.9rem;line-height:1.6;">
              Enter the verification code below to confirm this change.
              This code is valid for <strong>10 minutes</strong>.
            </p>
            <div style="background:#e8f5fe;border:2px solid #90caf9;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;">
              <p style="color:#6b7280;margin:0 0 8px;font-size:.75rem;text-transform:uppercase;letter-spacing:1px;font-weight:600;">Your Verification Code</p>
              <div style="font-size:2.4rem;font-weight:800;letter-spacing:12px;color:#1e88e5;font-family:'Courier New',monospace;">{$code}</div>
            </div>
            <p style="color:#6b7280;font-size:.82rem;margin:0 0 6px;">If you did not request an email change, please ignore this message — your account remains safe.</p>
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

    $plain = "Hi {$toName},\n\n"
        . "A request was made to change your account email address to: {$newEmail}\n\n"
        . "Your verification code is: {$code}\n\n"
        . "This code expires in 10 minutes.\n\n"
        . "If you did not request this change, you can safely ignore this email.\n\n"
        . "— QA System";

    return ['html' => $html, 'plain' => $plain];
}

// ─────────────────────────────────────────────────────────────────────────────
// STEP 1 — Validate new email, send OTP to OLD email
// ─────────────────────────────────────────────────────────────────────────────
function sendEmailChangeCode(array $data): void
{
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) {
        jsonResponse(false, 'Invalid session');
        return;
    }

    // Validate new email
    $newEmail = trim($data['new_email'] ?? '');
    if (!$newEmail || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Validation failed', ['errors' => ['gmail_username' => 'Please enter a valid email address.']]);
        return;
    }

    // Domain MX check
    $domain = substr(strrchr($newEmail, '@'), 1);
    if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
        jsonResponse(false, 'The domain "' . $domain . '" doesn\'t appear to exist. Please use a real email address.', ['warn' => true]);
        return;
    }

    $conn = getDBConnection();

    // Check uniqueness (new email not already taken by another user)
    $stmt = $conn->prepare("SELECT user_id FROM qa_users WHERE email = ? AND user_id != ?");
    $stmt->bind_param('si', $newEmail, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        jsonResponse(false, 'Validation failed', ['errors' => ['gmail_username' => 'This email is already in use by another account.']]);
        return;
    }
    $stmt->close();

    // Fetch current email and name
    $stmt = $conn->prepare("SELECT full_name, email FROM qa_users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $currentEmail = trim($row['email'] ?? '');

    // ── No existing email: first-time setup — save directly, no OTP needed ──
    if (!$currentEmail) {
        $stmt = $conn->prepare("UPDATE qa_users SET email = ? WHERE user_id = ?");
        $stmt->bind_param('si', $newEmail, $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            jsonResponse(false, 'Failed to save email address. Please try again.');
            return;
        }
        $stmt->close();
        $_SESSION['email'] = $newEmail;
        jsonResponse(true, 'Email address saved successfully.', [
            'direct'        => true,
            'gmail_address' => $newEmail,
        ]);
        return;
    }

    // ── Existing email: require OTP ──
    if (!getenv('SENDGRID_API_KEY')) {
        jsonResponse(false, 'Mail server is not configured on the server. Please contact the administrator.');
        return;
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $salt = bin2hex(random_bytes(16));

    // Store OTP + pending new address in session
    $_SESSION['email_change_code']     = hash('sha256', $salt . $code);
    $_SESSION['email_change_salt']     = $salt;
    $_SESSION['email_change_expires']  = time() + 600;
    $_SESSION['email_change_attempts'] = 0;
    $_SESSION['email_change_new']      = $newEmail; // committed only after OTP passes

    $toName  = $row['full_name'] ?: 'User';
    $toEmail = $currentEmail; // ← sent to the OLD address

    ['html' => $htmlBody, 'plain' => $plainBody] = buildEmailChangeVerificationEmail($toName, $code, $newEmail);

    $sent = sendEmailViaSendGrid(
        $toEmail,
        $toName,
        'Email Address Change Verification — QA Management System',
        $htmlBody,
        $plainBody
    );

    if (!$sent) {
        jsonResponse(false, 'Failed to send verification email. Please try again later.');
        return;
    }

    // Mask the old email address for the response (e.g. us****@gmail.com)
    $masked = maskEmail($currentEmail);

    jsonResponse(true, 'Verification code sent.', [
        'data' => [
            'sent_to' => $masked,   // shown in UI ("code sent to us****@gmail.com")
        ],
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// STEP 2 — Verify OTP and commit the new email address
// ─────────────────────────────────────────────────────────────────────────────
function verifyEmailChangeCode(array $data): void
{
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) {
        jsonResponse(false, 'Invalid session');
        return;
    }

    $code = trim($data['code'] ?? '');

    if (
        empty($_SESSION['email_change_code']) ||
        empty($_SESSION['email_change_expires']) ||
        empty($_SESSION['email_change_salt'])
    ) {
        jsonResponse(false, 'No pending verification. Please request a new code.', [
            'errors' => ['code' => 'Request a new verification code.'],
        ]);
        return;
    }

    if (time() > $_SESSION['email_change_expires']) {
        unset(
            $_SESSION['email_change_code'],
            $_SESSION['email_change_salt'],
            $_SESSION['email_change_expires'],
            $_SESSION['email_change_attempts'],
            $_SESSION['email_change_new']
        );
        jsonResponse(false, 'Verification code has expired. Please request a new one.', [
            'errors' => ['code' => 'Code expired. Request a new one.'],
        ]);
        return;
    }

    $_SESSION['email_change_attempts'] = ($_SESSION['email_change_attempts'] ?? 0) + 1;
    if ($_SESSION['email_change_attempts'] > 5) {
        unset(
            $_SESSION['email_change_code'],
            $_SESSION['email_change_salt'],
            $_SESSION['email_change_expires'],
            $_SESSION['email_change_attempts'],
            $_SESSION['email_change_new']
        );
        jsonResponse(false, 'Too many failed attempts. Please request a new code.', [
            'errors' => ['code' => 'Too many attempts. Request a new code.'],
        ]);
        return;
    }

    $salt         = $_SESSION['email_change_salt'];
    $expectedHash = hash('sha256', $salt . $code);

    if (!hash_equals($expectedHash, $_SESSION['email_change_code'])) {
        $left = max(0, 5 - $_SESSION['email_change_attempts']);
        jsonResponse(
            false,
            'Incorrect code.' . ($left > 0 ? " {$left} attempt(s) remaining." : ''),
            ['errors' => ['code' => 'Incorrect verification code.']]
        );
        return;
    }

    // OTP is correct — retrieve stashed new address
    $newEmail = $_SESSION['email_change_new'] ?? '';
    unset(
        $_SESSION['email_change_code'],
        $_SESSION['email_change_salt'],
        $_SESSION['email_change_expires'],
        $_SESSION['email_change_attempts'],
        $_SESSION['email_change_new']
    );

    if (!$newEmail) {
        jsonResponse(false, 'Session expired. Please start the process again.');
        return;
    }

    $conn = getDBConnection();

    // Final uniqueness check (another user might have claimed it during the OTP window)
    $stmt = $conn->prepare("SELECT user_id FROM qa_users WHERE email = ? AND user_id != ?");
    $stmt->bind_param('si', $newEmail, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        jsonResponse(false, 'This email address was claimed by another account while you were verifying. Please try a different address.');
        return;
    }
    $stmt->close();

    // Commit
    $stmt = $conn->prepare("UPDATE qa_users SET email = ? WHERE user_id = ?");
    $stmt->bind_param('si', $newEmail, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        jsonResponse(false, 'Failed to save email address. Please try again.');
        return;
    }
    $stmt->close();

    $_SESSION['email'] = $newEmail;

    jsonResponse(true, 'Email address updated successfully.', [
        'gmail_address' => $newEmail,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Helper: mask an email address for display  e.g. user@gmail.com → us**@gmail.com
// ─────────────────────────────────────────────────────────────────────────────
function maskEmail(string $email): string
{
    [$local, $domain] = explode('@', $email, 2);
    $visible = min(2, strlen($local));
    return substr($local, 0, $visible) . str_repeat('*', max(0, strlen($local) - $visible)) . '@' . $domain;
}

// ─────────────────────────────────────────────────────────────────────────────

function getProfile(): void
{
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) {
        jsonResponse(false, 'Invalid session');
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT user_id, username, full_name, email, role, is_active, created_at FROM qa_users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        jsonResponse(false, 'User not found', [], 404);
    }

    $user['activity'] = getActivitySummary($userId);

    // Email and gmail_connected both come from the database
    $gmailConnected          = !empty(getenv('SENDGRID_API_KEY')) && !empty($user['email']);
    $user['gmail_connected'] = $gmailConnected;
    $user['gmail_address']   = $gmailConnected ? $user['email'] : null;

    jsonResponse(true, 'Profile loaded', ['data' => $user]);
}

function getActivitySummary(int $userId): array
{
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM qa_surveys WHERE created_by = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $surveysCreated = (int) $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM qa_reports WHERE generated_by = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $reportsGenerated = (int) $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    return ['surveys_created' => $surveysCreated, 'reports_generated' => $reportsGenerated];
}

function updateProfileInfo(array $data): void
{
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) {
        jsonResponse(false, 'Invalid session');
    }

    $errors = validateProfileInfo($data);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }

    $fullName = trim($data['full_name']);
    $email    = trim($data['email']);
    $conn     = getDBConnection();

    $stmt = $conn->prepare("SELECT user_id FROM qa_users WHERE email = ? AND user_id != ?");
    $stmt->bind_param('si', $email, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        jsonResponse(false, 'Validation failed', ['errors' => ['email' => 'Email is already in use by another account']]);
    }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE qa_users SET full_name = ?, email = ? WHERE user_id = ?");
    $stmt->bind_param('ssi', $fullName, $email, $userId);

    if ($stmt->execute()) {
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email']     = $email;
        $stmt->close();
        jsonResponse(true, 'Profile updated successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to update profile');
    }
}

function changePassword(array $data): void
{
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) {
        jsonResponse(false, 'Invalid session');
    }

    $verifiedAt = $_SESSION['pwd_change_verified_at'] ?? 0;
    if (empty($_SESSION['pwd_change_verified']) || (time() - $verifiedAt) > 900) {
        unset($_SESSION['pwd_change_verified'], $_SESSION['pwd_change_verified_at']);
        jsonResponse(false, 'Identity not verified. Please request a verification code first.', [], 403);
        return;
    }

    $errors = validatePasswordChange($data);
    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT password_hash FROM qa_users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        jsonResponse(false, 'User not found', [], 404);
    }
    if (!password_verify($data['current_password'], $row['password_hash'])) {
        jsonResponse(false, 'Current password is incorrect');
    }

    $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
    $stmt    = $conn->prepare("UPDATE qa_users SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param('si', $newHash, $userId);

    if ($stmt->execute()) {
        $stmt->close();
        unset($_SESSION['pwd_change_verified'], $_SESSION['pwd_change_verified_at']);
        jsonResponse(true, 'Password changed successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to change password');
    }
}

function sendVerificationCode(): void
{
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) {
        jsonResponse(false, 'Invalid session');
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT full_name, email FROM qa_users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || empty(trim($row['email'] ?? ''))) {
        jsonResponse(false, 'No email address found for your account.');
        return;
    }

    if (!getenv('SENDGRID_API_KEY')) {
        jsonResponse(false, 'Mail server is not configured. Please contact the administrator.');
        return;
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $salt = bin2hex(random_bytes(16));
    $_SESSION['pwd_verify_code']     = hash('sha256', $salt . $code);
    $_SESSION['pwd_verify_salt']     = $salt;
    $_SESSION['pwd_verify_expires']  = time() + 600;
    $_SESSION['pwd_verify_attempts'] = 0;
    unset($_SESSION['pwd_change_verified'], $_SESSION['pwd_change_verified_at']);

    $toName  = $row['full_name'] ?: 'User';
    $toEmail = $row['email'];

    ['html' => $htmlBody, 'plain' => $plainBody] = buildVerificationEmail($toName, $code);

    $sent = sendEmailViaSendGrid(
        $toEmail,
        $toName,
        'Your Password Change Verification Code — QA Management System',
        $htmlBody,
        $plainBody
    );

    if (!$sent) {
        jsonResponse(false, 'Failed to send verification email. Please try again later.');
        return;
    }

    jsonResponse(true, 'Verification code sent.', ['data' => ['email' => $toEmail]]);
}

function verifyPasswordCode(array $data): void
{
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) {
        jsonResponse(false, 'Invalid session');
    }

    $code = trim($data['code'] ?? '');

    if (empty($_SESSION['pwd_verify_code']) || empty($_SESSION['pwd_verify_expires']) || empty($_SESSION['pwd_verify_salt'])) {
        jsonResponse(
            false,
            'No pending verification code. Please request a new one.',
            ['errors' => ['code' => 'Request a new verification code.']]
        );
        return;
    }

    if (time() > $_SESSION['pwd_verify_expires']) {
        unset($_SESSION['pwd_verify_code'], $_SESSION['pwd_verify_salt'], $_SESSION['pwd_verify_expires'], $_SESSION['pwd_verify_attempts']);
        jsonResponse(
            false,
            'Verification code has expired. Please request a new one.',
            ['errors' => ['code' => 'Code expired. Request a new one.']]
        );
        return;
    }

    $_SESSION['pwd_verify_attempts'] = ($_SESSION['pwd_verify_attempts'] ?? 0) + 1;
    if ($_SESSION['pwd_verify_attempts'] > 5) {
        unset($_SESSION['pwd_verify_code'], $_SESSION['pwd_verify_salt'], $_SESSION['pwd_verify_expires'], $_SESSION['pwd_verify_attempts']);
        jsonResponse(
            false,
            'Too many failed attempts. Please request a new verification code.',
            ['errors' => ['code' => 'Too many attempts. Request a new code.']]
        );
        return;
    }

    $salt         = $_SESSION['pwd_verify_salt'];
    $expectedHash = hash('sha256', $salt . $code);

    if (!hash_equals($expectedHash, $_SESSION['pwd_verify_code'])) {
        $left = max(0, 5 - $_SESSION['pwd_verify_attempts']);
        jsonResponse(
            false,
            'Incorrect code.' . ($left > 0 ? " {$left} attempt(s) remaining." : ''),
            ['errors' => ['code' => 'Incorrect verification code.']]
        );
        return;
    }

    unset($_SESSION['pwd_verify_code'], $_SESSION['pwd_verify_salt'], $_SESSION['pwd_verify_expires'], $_SESSION['pwd_verify_attempts']);
    $_SESSION['pwd_change_verified']    = true;
    $_SESSION['pwd_change_verified_at'] = time();

    jsonResponse(true, 'Identity verified. You may now change your password.');
}

function validateProfileInfo(array $data): array
{
    $errors = [];
    if (empty($data['full_name']) || trim($data['full_name']) === '') {
        $errors['full_name'] = 'Full name is required';
    } elseif (strlen($data['full_name']) > 100) {
        $errors['full_name'] = 'Full name must not exceed 100 characters';
    }
    if (empty($data['email']) || trim($data['email']) === '') {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (strlen($data['email']) > 100) {
        $errors['email'] = 'Email must not exceed 100 characters';
    }
    return $errors;
}

function validatePasswordChange(array $data): array
{
    $errors = [];
    if (empty($data['current_password'])) {
        $errors['current_password'] = 'Current password is required';
    }
    if (empty($data['new_password'])) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($data['new_password']) < 8) {
        $errors['new_password'] = 'New password must be at least 8 characters';
    }
    if (empty($data['confirm_password'])) {
        $errors['confirm_password'] = 'Please confirm your new password';
    } elseif (isset($data['new_password']) && $data['new_password'] !== $data['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    if (
        isset($data['new_password']) && isset($data['current_password'])
        && $data['new_password'] === $data['current_password']
    ) {
        $errors['new_password'] = 'New password must be different from the current password';
    }
    return $errors;
}