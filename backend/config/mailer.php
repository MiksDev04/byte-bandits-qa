<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('MAIL_USERNAME') ?: '';
    $mail->Password   = getenv('MAIL_PASSWORD') ?: '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $fromName    = getenv('MAIL_FROM_NAME')    ?: 'QA System';
    $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_USERNAME') ?: '';

    $mail->setFrom($fromAddress, $fromName);
    $mail->isHTML(true);
    $mail->addReplyTo($fromAddress, $fromName);

    return $mail;
}