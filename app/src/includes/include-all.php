<?php

// Check if the file is accessed directly
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

ob_start();

// Include necessary configuration files
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/globals.php'; // Fixed path to globals.php\
include_once BASE_PATH . '/src/includes/head.php';

// Set cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

ob_end_flush();