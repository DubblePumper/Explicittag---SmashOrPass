<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Sander\App\Utils\SmashOrPass\WebSocket\SmashOrPassServer;
use Sander\App\Utils\SmashOrPass\DatabaseFunctions;

require dirname(__DIR__) . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Load config
require_once dirname(__DIR__) . '/config/config.php';

// Database connection
$dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=" . getenv('DB_CHARSET');
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'), $options);
    
    // Database functions for SmashOrPass
    $dbFunctions = new DatabaseFunctions($pdo);
    
    // Get WebSocket port from configuration
    $wsPort = isset($wsConfig['port']) ? (int)$wsConfig['port'] : 8080;
    
    // Create WebSocket server
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new SmashOrPassServer($dbFunctions)
            )
        ),
        $wsPort  // Use port from configuration
    );

    echo "WebSocket server started on port {$wsPort}\n";
    $server->run();
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
