<?php
session_start();

require_once '../config/api_auth.php';
requireApiKey();

if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file received']);
    exit;
}

if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error code: ' . $_FILES['file']['error']]);
    exit;
}

// ── ImageKit credentials (store in .env) ──────────────────────────────────
$privateKey  = getenv('IMAGEKIT_PRIVATE_KEY');   // e.g. private_xxxxxxxxxxxx
$urlEndpoint = getenv('IMAGEKIT_URL_ENDPOINT');  // e.g. https://ik.imagekit.io/yourname
// ─────────────────────────────────────────────────────────────────────────

if (!$privateKey || !$urlEndpoint) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing ImageKit credentials',
        'debug'   => [
            'private_key'  => $privateKey  ? 'set' : 'MISSING',
            'url_endpoint' => $urlEndpoint ? 'set' : 'MISSING',
        ]
    ]);
    exit;
}

// ImageKit uses HTTP Basic Auth — private key as username, empty password
$auth = base64_encode($privateKey . ':');

// Use original filename, sanitized
$fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['file']['name']);

$ch = curl_init('https://upload.imagekit.io/api/v1/files/upload');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Basic ' . $auth,
    ],
    CURLOPT_POSTFIELDS     => [
        'file'     => new CURLFile(
                            $_FILES['file']['tmp_name'],
                            $_FILES['file']['type'] ?: 'application/pdf',
                            $fileName
                       ),
        'fileName' => $fileName,
        'folder'   => '/qa_policies',   // organizes uploads in ImageKit dashboard
    ]
]);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'cURL error: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['url'])) {
    echo json_encode([
        'success'   => false,
        'message'   => $data['message'] ?? 'ImageKit rejected the upload',
        'http_code' => $httpCode,
        'raw'       => $data
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'url'     => $data['url']   // always public, no auth needed
]);