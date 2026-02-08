<?php
/**
 * Door Connect - JSON API endpoint
 * Authenticates with BBSLink and returns a signed WebSocket token.
 */

header('Content-Type: application/json');

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Must be logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Verify CSRF token
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Validate door code
$door = $_POST['door'] ?? '';
require_once __DIR__ . '/../door_manager.php';
$door_manager = new DoorManager();

if (!$door_manager->isValidDoor($door)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid door code']);
    exit;
}

// Authenticate with BBSLink
require_once __DIR__ . '/../bbslink_auth.php';
$bbslink = new BBSLinkAuth();
$result = $bbslink->authenticate($door, get_current_user_id());

if (!$result['success']) {
    http_response_code(502);
    echo json_encode(['error' => $result['error']]);
    exit;
}

// Generate HMAC-signed token with BBSLink connection info
$secret = $_ENV['DOOR_PROXY_SECRET'] ?? '';
$proxyPort = $_ENV['DOOR_PROXY_PORT'] ?? '7682';

$payload = [
    'host' => $result['host'],
    'port' => $result['port'],
    'door' => $door,
    'user' => get_current_user_id(),
    'exp' => (time() + 30) * 1000, // 30 seconds, in milliseconds for JS
];

$payloadB64 = base64_encode(json_encode($payload));
$signature = hash_hmac('sha256', $payloadB64, $secret);
$token = $payloadB64 . '.' . $signature;

// Build WebSocket URL
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'wss' : 'ws';
$host = $_SERVER['HTTP_HOST'];
$wsUrl = "{$scheme}://{$host}/ws/door/";

echo json_encode([
    'ws_url' => $wsUrl,
    'token' => $token,
]);
