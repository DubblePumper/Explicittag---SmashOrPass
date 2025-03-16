<?php
// Script to check if server is properly set up

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Check if vendor directory exists
if (!is_dir(BASE_PATH . '/vendor')) {
    echo "ERROR: Vendor directory not found. Please run 'composer install'.\n";
} else {
    echo "✓ Vendor directory found.\n";
}

// Check if config files exist
if (!file_exists(BASE_PATH . '/config/config.php')) {
    echo "ERROR: Config file not found.\n";
} else {
    echo "✓ Config file found.\n";
}

// Check if .env file exists
if (!file_exists(BASE_PATH . '/.env')) {
    echo "WARNING: .env file not found. Using default settings.\n";
} else {
    echo "✓ .env file found.\n";
}

// Check if key directories exist
$directories = [
    '/public',
    '/public/api',
    '/public/assets',
    '/public/assets/images',
    '/src/utils/smashorpass',
    '/src/includes'
];

foreach ($directories as $dir) {
    if (!is_dir(BASE_PATH . $dir)) {
        echo "ERROR: Directory {$dir} not found.\n";
    } else {
        echo "✓ Directory {$dir} found.\n";
    }
}

// Try to create placeholder image
$placeholderPath = BASE_PATH . '/public/assets/images/placeholder-profile.jpg';
if (!file_exists($placeholderPath)) {
    echo "Creating placeholder image...\n";
    $image = imagecreatetruecolor(400, 400);
    $bgColor = imagecolorallocate($image, 18, 20, 58);
    $textColor = imagecolorallocate($image, 224, 224, 224);
    
    imagefill($image, 0, 0, $bgColor);
    imagestring($image, 5, 130, 190, 'No Image Available', $textColor);
    
    if (imagejpeg($image, $placeholderPath, 90)) {
        echo "✓ Placeholder image created successfully.\n";
    } else {
        echo "ERROR: Failed to create placeholder image.\n";
    }
    imagedestroy($image);
} else {
    echo "✓ Placeholder image already exists.\n";
}

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    echo "WARNING: PHP version is below recommended 7.4.0.\n";
}

// Check for required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'json'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        echo "ERROR: Required PHP extension {$ext} is not installed.\n";
    } else {
        echo "✓ PHP extension {$ext} is installed.\n";
    }
}

// Try to connect to database
echo "Trying to connect to database...\n";
require_once BASE_PATH . '/config/config.php';
$pdo = testDBConnection();
if ($pdo) {
    echo "✓ Database connection successful.\n";
    
    // Check for required tables
    $tables = ['performers', 'performer_images', 'user_choices'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Table {$table} exists.\n";
            } else {
                echo "ERROR: Table {$table} does not exist.\n";
            }
        } catch (PDOException $e) {
            echo "ERROR: Could not check for table {$table}: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "ERROR: Database connection failed.\n";
}

echo "\nServer check complete.\n";
