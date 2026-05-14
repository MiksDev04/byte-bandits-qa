<?php
/**
 * API Key Authentication
 * config/api_auth.php
 *
 * Validates the X-API-Key header sent by $.ajaxSetup on every jQuery request.
 *
 * HOW TO USE
 * ──────────
 * 1. Add APP_API_KEY=your-key to your .env file.
 *    Generate a key:  php -r "echo bin2hex(random_bytes(32));"
 *
 * 2. In every API file, add TWO lines after session_start():
 *      require_once '../../config/api_auth.php';
 *      requireApiKey();
 *
 * 3. In your PHP layout/header, embed the key in a meta tag:
 *      <meta name="x-api-key"
 *            content="<?= htmlspecialchars(getenv('APP_API_KEY'), ENT_QUOTES, 'UTF-8') ?>">
 *
 * 4. In your main JS file, add $.ajaxSetup ONCE:
 *      const API_KEY = document.querySelector('meta[name="x-api-key"]')?.content ?? '';
 *      $.ajaxSetup({ headers: { 'X-API-Key': API_KEY } });
 *
 * That's it — every $.ajax call sends the key automatically.
 */

/**
 * Call at the top of every API file.
 * Responds with 401 JSON and exits if the key is missing or wrong.
 */
function requireApiKey(): void
{
    $expected = getenv('APP_API_KEY');

    // Misconfigured server → hard stop (don't silently allow through)
    if (empty($expected)) {
        error_log('[ApiAuth] FATAL: APP_API_KEY is not set in environment.');
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Server misconfiguration. Contact the administrator.',
        ]);
        exit;
    }

    // jQuery sends custom headers, which arrive as HTTP_X_API_KEY in PHP
    $provided = $_SERVER['HTTP_X_API_KEY'] ?? '';

    // hash_equals prevents timing attacks — never use === for secrets
    if (empty($provided) || !hash_equals($expected, $provided)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Valid API key required.',
        ]);
        exit;
    }
}