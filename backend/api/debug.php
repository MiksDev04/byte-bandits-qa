<?php
$src = '/var/www/html/vendor/phpmailer/phpmailer/src';
echo json_encode([
    'src_exists'        => is_dir($src),
    'src_contents'      => is_dir($src) ? scandir($src) : [],
    'phpmailer_php'     => file_exists($src . '/PHPMailer.php'),
    'smtp_php'          => file_exists($src . '/SMTP.php'),
    'exception_php'     => file_exists($src . '/Exception.php'),
]);