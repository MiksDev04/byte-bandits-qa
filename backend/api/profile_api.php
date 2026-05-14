<?php
/**
 * Profile API - View and Update Current User's Profile
 * backend/api/profile_api.php
 */

// Load Composer autoloader + PHPMailer (use statements must be at file top level)
$_autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($_autoloadPath)) {
    require_once $_autoloadPath;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

session_start();

// Auth guard
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

function getProfile(): void {
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) { jsonResponse(false, 'Invalid session'); }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT user_id, username, full_name, email, role, is_active, created_at FROM qa_users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) { jsonResponse(false, 'User not found', [], 404); }

    $user['activity'] = getActivitySummary($userId);

    $envPath = __DIR__ . '/../.env';
    $env     = parseEnvFile($envPath);
    $gmailConnected = !empty(trim($env['MAIL_USERNAME'] ?? ''))
                   && !empty(trim($env['MAIL_PASSWORD'] ?? ''));
    $user['gmail_connected'] = $gmailConnected;
    $user['gmail_address']   = $gmailConnected ? ($env['MAIL_FROM_ADDRESS'] ?? $env['MAIL_USERNAME']) : null;

    jsonResponse(true, 'Profile loaded', ['data' => $user]);
}

function getActivitySummary(int $userId): array {
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

function updateProfileInfo(array $data): void {
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) { jsonResponse(false, 'Invalid session'); }

    $errors = validateProfileInfo($data);
    if (!empty($errors)) { jsonResponse(false, 'Validation failed', ['errors' => $errors]); }

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

function changePassword(array $data): void {
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) { jsonResponse(false, 'Invalid session'); }

    // Require prior OTP verification (valid for 15 minutes)
    $verifiedAt = $_SESSION['pwd_change_verified_at'] ?? 0;
    if (empty($_SESSION['pwd_change_verified']) || (time() - $verifiedAt) > 900) {
        unset($_SESSION['pwd_change_verified'], $_SESSION['pwd_change_verified_at']);
        jsonResponse(false, 'Identity not verified. Please request a verification code first.', [], 403);
        return;
    }

    $errors = validatePasswordChange($data);
    if (!empty($errors)) { jsonResponse(false, 'Validation failed', ['errors' => $errors]); }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT password_hash FROM qa_users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) { jsonResponse(false, 'User not found', [], 404); }

    if (!password_verify($data['current_password'], $row['password_hash'])) {
        jsonResponse(false, 'Validation failed', ['errors' => ['current_password' => 'Current password is incorrect']]);
    }

    $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
    $stmt    = $conn->prepare("UPDATE qa_users SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param('si', $newHash, $userId);

    if ($stmt->execute()) {
        $stmt->close();
        // Clear the verification flag after a successful password change
        unset($_SESSION['pwd_change_verified'], $_SESSION['pwd_change_verified_at']);
        jsonResponse(true, 'Password changed successfully');
    } else {
        $stmt->close();
        jsonResponse(false, 'Failed to change password');
    }
}

function sendVerificationCode(): void {
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) { jsonResponse(false, 'Invalid session'); }

    // Fetch the user's email address
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT email FROM qa_users WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || empty(trim($row['email'] ?? ''))) {
        jsonResponse(false, 'No email address found for your account. Please connect a Gmail account first.');
        return;
    }

    // Require Gmail to be configured in .env
    $envPath = __DIR__ . '/../.env';
    $env     = parseEnvFile($envPath);
    if (empty(trim($env['MAIL_USERNAME'] ?? '')) || empty(trim($env['MAIL_PASSWORD'] ?? ''))) {
        jsonResponse(false, 'No Gmail account connected. Please connect one in the Email Address section first.');
        return;
    }

    if (!class_exists(PHPMailer::class)) {
        jsonResponse(false, 'PHPMailer is not installed. Run: php composer.phar require phpmailer/phpmailer');
        return;
    }

    // Generate a 6-digit OTP and store hashed in session (expires in 10 minutes)
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['pwd_verify_code']      = password_hash($code, PASSWORD_BCRYPT);
    $_SESSION['pwd_verify_expires']   = time() + 600;
    $_SESSION['pwd_verify_attempts']  = 0;
    unset($_SESSION['pwd_change_verified'], $_SESSION['pwd_change_verified_at']);

    // Send the OTP via Gmail SMTP
    try {
        $mail             = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['MAIL_USERNAME'];
        $mail->Password   = $env['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 10;
        $mail->setFrom(
            $env['MAIL_FROM_ADDRESS'] ?? $env['MAIL_USERNAME'],
            $env['MAIL_FROM_NAME']    ?? 'QA System'
        );
        $mail->addAddress($row['email']);
        $mail->Subject = 'Your Password Change Verification Code';
        $mail->Body    =
            "Hello,\n\n" .
            "Your verification code is: {$code}\n\n" .
            "This code expires in 10 minutes.\n\n" .
            "If you did not request a password change, you can safely ignore this email.\n\n" .
            "— QA System";
        $mail->send();
    } catch (MailerException $e) {
        error_log('sendVerificationCode mailer error: ' . $e->getMessage());
        jsonResponse(false, 'Failed to send verification email. Please try again later.');
        return;
    }

    jsonResponse(true, 'Verification code sent.', ['data' => ['email' => $row['email']]]);
}

function verifyPasswordCode(array $data): void {
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId <= 0) { jsonResponse(false, 'Invalid session'); }

    $code = trim($data['code'] ?? '');

    if (empty($_SESSION['pwd_verify_code']) || empty($_SESSION['pwd_verify_expires'])) {
        jsonResponse(false, 'No pending verification code. Please request a new one.',
            ['errors' => ['code' => 'Request a new verification code.']]);
        return;
    }

    if (time() > $_SESSION['pwd_verify_expires']) {
        unset($_SESSION['pwd_verify_code'], $_SESSION['pwd_verify_expires'], $_SESSION['pwd_verify_attempts']);
        jsonResponse(false, 'Verification code has expired. Please request a new one.',
            ['errors' => ['code' => 'Code expired. Request a new one.']]);
        return;
    }

    // Brute-force guard: max 5 attempts
    $_SESSION['pwd_verify_attempts'] = ($_SESSION['pwd_verify_attempts'] ?? 0) + 1;
    if ($_SESSION['pwd_verify_attempts'] > 5) {
        unset($_SESSION['pwd_verify_code'], $_SESSION['pwd_verify_expires'], $_SESSION['pwd_verify_attempts']);
        jsonResponse(false, 'Too many failed attempts. Please request a new verification code.',
            ['errors' => ['code' => 'Too many attempts. Request a new code.']]);
        return;
    }

    if (!password_verify($code, $_SESSION['pwd_verify_code'])) {
        $left = max(0, 5 - $_SESSION['pwd_verify_attempts']);
        jsonResponse(false, 'Incorrect code.' . ($left > 0 ? " {$left} attempt(s) remaining." : ''),
            ['errors' => ['code' => 'Incorrect verification code.']]);
        return;
    }

    // ✓ Code is correct — mark session as verified
    unset($_SESSION['pwd_verify_code'], $_SESSION['pwd_verify_expires'], $_SESSION['pwd_verify_attempts']);
    $_SESSION['pwd_change_verified']    = true;
    $_SESSION['pwd_change_verified_at'] = time();

    jsonResponse(true, 'Identity verified. You may now change your password.');
}

function verifyGmail(array $data): void {
    $gmailUser = trim($data['gmail_username'] ?? '');
    $appPass   = str_replace(' ', '', trim($data['gmail_password'] ?? ''));
    $fromName  = trim($data['gmail_from_name'] ?? 'QA System');
    $errors    = [];

    if ($gmailUser === '') {
        $errors['gmail_username'] = 'Gmail address is required';
    } elseif (!filter_var($gmailUser, FILTER_VALIDATE_EMAIL)) {
        $errors['gmail_username'] = 'Enter a valid Gmail address';
    }

    if ($appPass === '') {
        $errors['gmail_password'] = 'App password is required';
    } elseif (strlen($appPass) !== 16) {
        $errors['gmail_password'] = 'App passwords are exactly 16 characters (no spaces)';
    }

    if (!empty($errors)) {
        jsonResponse(false, 'Validation failed', ['errors' => $errors]);
        return;
    }

    if (!class_exists(PHPMailer::class)) {
        jsonResponse(false, 'PHPMailer not installed. Run: php composer.phar require phpmailer/phpmailer from your qa-app-main folder.');
        return;
    }

    try {
        $mail             = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $gmailUser;
        $mail->Password   = $appPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 10;
        $mail->SMTPDebug  = 0;

        if (!$mail->SmtpConnect()) {
            jsonResponse(false, 'Could not connect to Gmail SMTP. Check your credentials.',
                ['errors' => ['gmail_password' => 'SMTP connection failed. Verify the App Password.']]);
            return;
        }
        $mail->SmtpClose();

    } catch (MailerException $e) {
        jsonResponse(false, 'Gmail SMTP error: ' . $e->getMessage(),
            ['errors' => ['gmail_password' => $e->getMessage()]]);
        return;
    }

    $envPath = __DIR__ . '/../.env';
    $saved   = updateEnvValues($envPath, [
        'MAIL_USERNAME'     => $gmailUser,
        'MAIL_PASSWORD'     => $appPass,
        'MAIL_FROM_NAME'    => $fromName,
        'MAIL_FROM_ADDRESS' => $gmailUser,
    ]);

    if (!$saved) {
        jsonResponse(false, 'Credentials verified but could not save to .env. Check file permissions.');
        return;
    }

    jsonResponse(true, 'Gmail connected successfully! Notifications and password reset emails are now active.',
        ['data' => ['gmail_address' => $gmailUser]]);
}

function validateProfileInfo(array $data): array {
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

function validatePasswordChange(array $data): array {
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
    if (isset($data['new_password']) && isset($data['current_password'])
        && $data['new_password'] === $data['current_password']) {
        $errors['new_password'] = 'New password must be different from the current password';
    }
    return $errors;
}

function parseEnvFile(string $path): array {
    $result = [];
    if (!file_exists($path)) return $result;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $result[trim($key)] = trim($val, " \t\"'");
    }
    return $result;
}

function updateEnvValues(string $path, array $updates): bool {
    $lines   = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
    $touched = array_fill_keys(array_keys($updates), false);

    foreach ($lines as &$line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) continue;
        [$key] = explode('=', $trimmed, 2);
        $key   = trim($key);
        if (array_key_exists($key, $updates)) {
            $line          = $key . '=' . $updates[$key];
            $touched[$key] = true;
        }
    }
    unset($line);

    foreach ($updates as $key => $val) {
        if (!$touched[$key]) {
            $lines[] = $key . '=' . $val;
        }
    }

    return file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL) !== false;
}