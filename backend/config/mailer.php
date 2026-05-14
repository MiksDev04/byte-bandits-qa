<?php
/**
 * Mailer Configuration – Gmail SMTP via PHPMailer
 * backend/config/mailer.php
 *
 * Usage:
 *   require_once __DIR__ . '/mailer.php';
 *   $mail = createMailer();
 *   $mail->addAddress('recipient@example.com', 'Recipient Name');
 *   $mail->Subject = 'Subject here';
 *   $mail->Body    = '<p>HTML body here</p>';
 *   $mail->send();
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ── Auto-load PHPMailer (Composer) ───────────────────────────────────────────
// Make sure you ran: composer require phpmailer/phpmailer
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload)) {
    throw new RuntimeException(
        'PHPMailer not found. Run: composer require phpmailer/phpmailer'
    );
}
require_once $autoload;

// ── Load .env values ─────────────────────────────────────────────────────────
// We read the .env manually so we don't need a separate dotenv library.
function loadEnvFile(string $envPath): void
{
    if (!file_exists($envPath)) return;
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

loadEnvFile(__DIR__ . '/../.env');

/**
 * Returns a pre-configured PHPMailer instance ready to send.
 * Caller only needs to set addAddress(), Subject, and Body.
 */
function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true); // true = throw exceptions

    // ── SMTP settings ────────────────────────────────────────
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'] ?? '';   // e.g. yourapp@gmail.com
    $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? '';   // Gmail App Password (16-char)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // ── Sender ───────────────────────────────────────────────
    $fromName    = $_ENV['MAIL_FROM_NAME']    ?? 'QA System';
    $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? ($_ENV['MAIL_USERNAME'] ?? '');
    $mail->setFrom($fromAddress, $fromName);
    $mail->isHTML(true);

    // ── Optional: reply-to same address ──────────────────────
    $mail->addReplyTo($fromAddress, $fromName);

    return $mail;
}