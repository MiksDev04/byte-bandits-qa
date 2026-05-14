<?php
$autoload = __DIR__ . '/../../vendor/autoload.php';
require_once $autoload;

$psr4File = __DIR__ . '/../../vendor/composer/autoload_psr4.php';
$psr4 = file_exists($psr4File) ? require $psr4File : [];

echo json_encode([
    'phpmailer_installed'    => class_exists('PHPMailer\PHPMailer\PHPMailer'),
    'phpmailer_dir_contents' => scandir(__DIR__ . '/../../vendor/phpmailer'),
    'psr4_map'               => $psr4,
    'autoload_psr4_exists'   => file_exists($psr4File),
]);