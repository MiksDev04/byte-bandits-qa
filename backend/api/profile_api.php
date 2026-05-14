<?php

/**
 * Profile API - View and Update Current User's Profile
 * backend/api/profile_api.php
 */
session_start();

// 1. Load database config FIRST (defines jsonResponse)
require_once __DIR__ . '/../config/database.php';
require_once '../config/api_auth.php'; // ← add
requireApiKey();  

// 3. Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 4. Auth guard
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
            } elseif ($postAction === 'verify_gmail') {
                verifyGmail($data);
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
        // Avoid spam flags — always include a reply_to on transactional mail
        'reply_to' => ['email' => $replyTo, 'name' => $fromName],
        'subject'  => $subject,
        'content'  => [
            ['type' => 'text/plain', 'value' => $plainBody],
            ['type' => 'text/html',  'value' => $htmlBody],
        ],
        // Disable click & open tracking — SendGrid rewrites links through its
        // own tracking domain, which spam filters flag on transactional mail
        'tracking_settings' => [
            'click_tracking' => ['enable' => false],
            'open_tracking'  => ['enable' => false],
        ],
        // Mark as transactional so SendGrid bypasses unsubscribe list
        // management — required for password/verification emails
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
// Build the styled HTML email (mirrors the forgot-password template)
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
// verifyGmail — validates env vars are set (no SMTP test needed)
// ─────────────────────────────────────────────────────────────────────────────
function verifyGmail(array $data): void
{
    $apiKey      = getenv('SENDGRID_API_KEY');
    $fromAddress = getenv('MAIL_FROM_ADDRESS');

    if (!$apiKey || !$fromAddress) {
        jsonResponse(false, 'SendGrid is not configured on the server. Please contact the administrator.');
        return;
    }

    jsonResponse(true, 'Email service is configured and ready.', [
        'gmail_address' => $fromAddress,
    ]);
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

    $gmailConnected          = !empty(getenv('SENDGRID_API_KEY')) && !empty(getenv('MAIL_FROM_ADDRESS'));
    $user['gmail_connected'] = $gmailConnected;
    $user['gmail_address']   = $gmailConnected ? getenv('MAIL_FROM_ADDRESS') : null;

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
        jsonResponse(false, 'Validation failed', ['errors' => ['current_password' => 'Current password is incorrect']]);
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

    // Use bcrypt for the stored hash (this is a user-initiated flow, not
    // time-sensitive like login — the slight delay on verify is acceptable)
    $_SESSION['pwd_verify_code']     = password_hash($code, PASSWORD_BCRYPT);
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

    if (empty($_SESSION['pwd_verify_code']) || empty($_SESSION['pwd_verify_expires'])) {
        jsonResponse(
            false,
            'No pending verification code. Please request a new one.',
            ['errors' => ['code' => 'Request a new verification code.']]
        );
        return;
    }

    if (time() > $_SESSION['pwd_verify_expires']) {
        unset($_SESSION['pwd_verify_code'], $_SESSION['pwd_verify_expires'], $_SESSION['pwd_verify_attempts']);
        jsonResponse(
            false,
            'Verification code has expired. Please request a new one.',
            ['errors' => ['code' => 'Code expired. Request a new one.']]
        );
        return;
    }

    $_SESSION['pwd_verify_attempts'] = ($_SESSION['pwd_verify_attempts'] ?? 0) + 1;
    if ($_SESSION['pwd_verify_attempts'] > 5) {
        unset($_SESSION['pwd_verify_code'], $_SESSION['pwd_verify_expires'], $_SESSION['pwd_verify_attempts']);
        jsonResponse(
            false,
            'Too many failed attempts. Please request a new verification code.',
            ['errors' => ['code' => 'Too many attempts. Request a new code.']]
        );
        return;
    }

    if (!password_verify($code, $_SESSION['pwd_verify_code'])) {
        $left = max(0, 5 - $_SESSION['pwd_verify_attempts']);
        jsonResponse(
            false,
            'Incorrect code.' . ($left > 0 ? " {$left} attempt(s) remaining." : ''),
            ['errors' => ['code' => 'Incorrect verification code.']]
        );
        return;
    }

    unset($_SESSION['pwd_verify_code'], $_SESSION['pwd_verify_expires'], $_SESSION['pwd_verify_attempts']);
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