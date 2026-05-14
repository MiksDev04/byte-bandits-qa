<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';

echo json_encode([
    'autoload_path'  => $autoload,
    'autoload_found' => file_exists($autoload),
    'phpmailer_ok'   => file_exists($autoload) 
                        ? (require_once $autoload) && class_exists('PHPMailer\PHPMailer\PHPMailer')
                        : false,
    'session_ok'     => session_status() === PHP_SESSION_ACTIVE,
    'mail_user'      => getenv('MAIL_USERNAME') ?: 'NOT SET',
    'mail_pass_set'  => !empty(getenv('MAIL_PASSWORD')),
    'php_version'    => PHP_VERSION,
]);