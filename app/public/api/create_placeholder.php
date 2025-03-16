<?php
// Create a simple placeholder image
$width = 400;
$height = 600;
$image = imagecreatetruecolor($width, $height);

// Background color (dark blue)
$bgColor = imagecolorallocate($image, 18, 20, 58);
imagefill($image, 0, 0, $bgColor);

// Add some text
$textColor = imagecolorallocate($image, 224, 224, 224);
$text = "No Image Available";
$font = 5; // Built-in font size

// Calculate position for centered text
$textWidth = strlen($text) * imagefontwidth($font);
$textHeight = imagefontheight($font);
$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;

imagestring($image, $font, $x, $y, $text, $textColor);

// Set the content type header
header('Content-Type: image/jpeg');

// Output the image
imagejpeg($image);

// Free up memory
imagedestroy($image);
