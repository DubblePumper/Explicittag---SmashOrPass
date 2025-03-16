<?php
// Define global variables
$siteName = "ExplicitTags";
$baseUrl = "http://localhost:8000";
$adminEmail = "admin@example.com";

// Any other global variables can be defined here

/**
 * Global functions and constants used across the application
 */

/**
 * Generate a random gradient class for UI styling
 * @param bool $returnAll Whether to return all gradient properties
 * @return string|array Gradient class string or array of properties
 */
function getRandomGradientClass($returnAll = false) {
    $gradients = [
        [
            'class' => 'gradient-primairy',
            'from' => 'from-secondary',
            'to' => 'to-tertery',
            'text_from' => 'text-secondary',
            'text_to' => 'text-tertery',
        ],
        [
            'class' => 'gradient-secondary',
            'from' => 'from-secondary',
            'to' => 'to-secondaryTerteryMix',
            'text_from' => 'text-secondary',
            'text_to' => 'text-secondaryTerteryMix',
        ],
        [
            'class' => 'gradient-tertery',
            'from' => 'from-tertery',
            'to' => 'to-primairy',
            'text_from' => 'text-tertery',
            'text_to' => 'text-primairy',
        ],
    ];
    
    $randomIndex = rand(0, count($gradients) - 1);
    
    return $returnAll ? $gradients[$randomIndex] : $gradients[$randomIndex]['class'];
}

/**
 * Format file size for display
 * @param int $bytes Size in bytes
 * @param int $precision Number of decimal places
 * @return string Formatted size with unit
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Check if a user is authenticated
 * @return bool True if user is logged in
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current URL of the page
 * @return string Current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Generate a site-wide unique token for forms
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
?>
