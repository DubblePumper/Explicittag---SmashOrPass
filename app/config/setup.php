<?php
// Setup script to create necessary directories and files

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Directories to create
$directories = [
    BASE_PATH . '/public/assets/images',
    BASE_PATH . '/public/api'
];

// Create directories if they don't exist
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    } else {
        echo "Directory already exists: $dir\n";
    }
}

// Check if placeholder image exists
$placeholderPath = BASE_PATH . '/public/assets/images/placeholder-profile.jpg';
if (!file_exists($placeholderPath)) {
    // Create a simple placeholder image
    $image = imagecreatetruecolor(400, 400);
    $bgColor = imagecolorallocate($image, 18, 20, 58); // Primary color
    $textColor = imagecolorallocate($image, 224, 224, 224); // TextWhite color
    
    imagefill($image, 0, 0, $bgColor);
    imagestring($image, 5, 130, 190, 'No Image Available', $textColor);
    
    imagejpeg($image, $placeholderPath, 90);
    imagedestroy($image);
    
    echo "Created placeholder image: $placeholderPath\n";
} else {
    echo "Placeholder image already exists: $placeholderPath\n";
}

echo "Setup complete.\n";
