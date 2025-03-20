<?php
/**
 * WebSocket Configuration
 * This file contains configuration settings for WebSocket functionality.
 */

// Define base path if not defined already
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 4));
}

// Include global config file
require_once BASE_PATH . '/config/config.php';

/**
 * Get WebSocket URL
 * 
 * @return string Full WebSocket URL including protocol, host, port, and path
 */
function getWebSocketURL() {
    global $wsConfig;
    
    $protocol = $wsConfig['protocol'];
    $host = $wsConfig['host'];
    $port = $wsConfig['port'];
    $path = $wsConfig['path'];
    
    return "$protocol://$host:$port$path";
}

/**
 * Check if WebSocket is enabled
 * 
 * @return bool True if WebSocket is enabled, false otherwise
 */
function isWebSocketEnabled() {
    global $wsConfig;
    return isset($wsConfig['enabled']) && $wsConfig['enabled'];
}

/**
 * Get WebSocket fallback endpoint
 * 
 * @return string URL for AJAX fallback when WebSocket is not available
 */
function getWebSocketFallbackEndpoint() {
    global $wsConfig;
    return isset($wsConfig['fallback_endpoint']) ? $wsConfig['fallback_endpoint'] : '/api/performers.php';
}
