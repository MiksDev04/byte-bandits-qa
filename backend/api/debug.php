<?php
$autoload = __DIR__ . '/../../vendor/autoload.php';
require_once $autoload;

echo json_encode([
    'autoload_exists'     => file_exists($autoload),
    'phpmailer_installed' => class_exists('PHPMailer\PHPMailer\PHPMailer'),
    'phpmailer_dir_exists'=> is_dir(__DIR__ . '/../../vendor/phpmailer'),
    'vendor_contents'     => scandir(__DIR__ . '/../../vendor'),
    'php_version'         => PHP_VERSION,
]);