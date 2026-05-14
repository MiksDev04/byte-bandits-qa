<?php
$srcPath = __DIR__ . '/../../vendor/phpmailer/phpmailer/src';
echo json_encode([
    'src_exists'   => is_dir($srcPath),
    'src_contents' => is_dir($srcPath) ? scandir($srcPath) : [],
]);