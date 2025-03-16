<?php
// Force HTTPS when needed
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Define base path if not defined already
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Define storage paths
define('STORAGE_PATH', dirname(__DIR__) . '/storage');
define('UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('VIDEOS_PATH', UPLOADS_PATH . '/videos');
define('LOGS_PATH', STORAGE_PATH . '/logs');

// Environment settings (development, staging, production)
$environment = 'development';

// Database configuration
$dbConfig = [
    'host' => '',
    'dbname' => '',
    'username' => '',
    'password' => '',
    'charset' => 'utf8mb4',
];


// Application settings
$appConfig = [
    'name' => 'ExplicitTags',
    'version' => '1.0.0',
    'debug' => ($environment === 'development'),
    'timezone' => 'UTC',
];

// Set timezone
date_default_timezone_set($appConfig['timezone']);

// Error handling based on environment
if ($environment === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// API settings
$apiConfig = [
    'baseUrl' => '/api',
    'timeout' => 30,
];

// Storage paths (using BASE_PATH constant)
$storageConfig = [
    'uploads' => BASE_PATH . '/storage/uploads',
    'logs' => BASE_PATH . '/storage/logs',
];

// Make sure each directory exists
foreach ($storageConfig as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Make sure directories exist
$directories = [STORAGE_PATH, UPLOADS_PATH, VIDEOS_PATH, LOGS_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbConfig['charset']}",
    // Add connection timeout
    PDO::ATTR_TIMEOUT => 5
];

// Test database connection
function testDBConnection() {
    global $dsn, $dbConfig, $options;
    try {
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }
}
?>
