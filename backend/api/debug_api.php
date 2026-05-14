<?php
// /backend/api/debug.php
$autoload = __DIR__ . '/../../vendor/autoload.php';
require_once $autoload;
echo json_encode([
    'autoload_exists'    => file_exists($autoload),
    'phpmailer_installed' => class_exists('PHPMailer\PHPMailer\PHPMailer'),
    'php_version'        => PHP_VERSION,
]);