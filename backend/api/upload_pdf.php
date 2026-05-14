<?php
session_start();

require_once '../config/api_auth.php'; // ← add
requireApiKey();  
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file']);
    exit;
}

$cloudName   = getenv('CLOUDINARY_CLOUD_NAME');
$apiKey      = getenv('CLOUDINARY_API_KEY');
$apiSecret   = getenv('CLOUDINARY_API_SECRET');
$timestamp   = time();
$signature   = sha1("resource_type=raw&timestamp={$timestamp}{$apiSecret}");

$ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/raw/upload");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'file'          => new CURLFile($_FILES['file']['tmp_name'], 'application/pdf', $_FILES['file']['name']),
        'api_key'       => $apiKey,
        'timestamp'     => $timestamp,
        'signature'     => $signature,
        'resource_type' => 'raw',
    ]
]);

$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);

echo json_encode([
    'success' => isset($data['secure_url']),
    'url'     => $data['secure_url'] ?? null,
    'message' => $data['error']['message'] ?? 'Upload failed'
]);