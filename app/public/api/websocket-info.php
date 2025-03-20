<?php
/**
 * WebSocket configuration endpoint
 * Returns the current WebSocket configuration settings for client-side use
 */

// Define base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

// Include the WebSocket configuration
require_once BASE_PATH . '/src/utils/smashorpass/WebSocket/config.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Return WebSocket configuration
echo json_encode([
    'enabled' => isWebSocketEnabled(),
    'url' => getWebSocketURL(),
    'fallback_endpoint' => getWebSocketFallbackEndpoint(),
    'timestamp' => time()
]);
