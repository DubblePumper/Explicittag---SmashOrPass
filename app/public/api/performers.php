<?php
// Define base path
define('BASE_PATH', dirname(__DIR__, 2));

// Include necessary files from the new structure
require_once BASE_PATH . '/config/config.php';

// Set content type to JSON right away
header('Content-Type: application/json');

// Try to include either the real DatabaseFunctions or the fallback
if (file_exists(BASE_PATH . '/src/utils/smashorpass/databaseFunctions/DatabaseFunctions.php')) {
    require_once BASE_PATH . '/src/utils/smashorpass/databaseFunctions/DatabaseFunctions.php';
    $usesFallback = false;
} else {
    require_once BASE_PATH . '/src/utils/smashorpass/databaseFunctions/DatabaseFunctionsFallback.php';
    $usesFallback = true;
}

// Import the classes
use Sander\App\Utils\SmashOrPass\DatabaseFunctions;
use Sander\App\Utils\SmashOrPass\DatabaseFunctionsFallback;

// Start or resume session
session_start();

// Ensure we have a session ID for tracking preferences
if (!isset($_SESSION['smash_or_pass_session'])) {
    $_SESSION['smash_or_pass_session'] = bin2hex(random_bytes(16));
}

try {
    // Try to get database connection
    $pdo = testDBConnection();

    if (!$pdo) {
        // Use fallback if database connection fails
        $dbFunctions = new DatabaseFunctionsFallback(new \PDO('sqlite::memory:'));
        $usesFallback = true;
    } else {
        // Use real implementation if database connection works
        $dbFunctions = $usesFallback ? new DatabaseFunctionsFallback($pdo) : new DatabaseFunctions($pdo);
    }

    // Handle API requests
    $action = $_GET['action'] ?? 'random_performers';

    switch ($action) {
        case 'random_performers':
            // Get gender filter if provided and normalize it
            $gender = isset($_GET['gender']) ? trim(strtolower($_GET['gender'])) : null;
            
            // If gender is an empty string, treat it as null
            if ($gender === '') {
                $gender = null;
            }
            
            // Log the gender filter for debugging
            error_log("API: Requested gender filter: " . ($gender ?? 'null'));
            
            // Get random performers
            $performers = $dbFunctions->getRandomPerformers(2, [], $gender);
            
            // Verify the genders match the filter (for debugging)
            if ($gender && !empty($performers)) {
                foreach ($performers as $index => $performer) {
                    $performerGender = strtolower(trim($performer['gender'] ?? ''));
                    if ($performerGender !== $gender) {
                        error_log("WARNING: API - Performer at index $index (ID: {$performer['id']}) has gender '$performerGender' which doesn't match filter '$gender'");
                    }
                }
            }
            
            // For the real implementation, get images for each performer
            if (!$usesFallback) {
                foreach ($performers as &$performer) {
                    $performer['images'] = $dbFunctions->getPerformerImages($performer['id'], 1);
                    // Log images for debugging
                    error_log("Performer {$performer['id']} images: " . json_encode($performer['images']));
                }
            }
            
            echo json_encode([
                'type' => 'performers',
                'performers' => $performers,
                'fallback_mode' => $usesFallback,
                'applied_filter' => ['gender' => $gender]  // Include filter in response for debugging
            ]);
            break;
            
        case 'save_choice':
            // Get POST data
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['chosen_id']) || !isset($data['rejected_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                exit;
            }
            
            // Save the user's choice
            $success = $dbFunctions->saveUserChoice(
                $_SESSION['smash_or_pass_session'],
                $data['chosen_id'],
                $data['rejected_id']
            );
            
            echo json_encode(['success' => $success]);
            break;
            
        case 'get_preferences':
            // Get user's preference profile
            $preferences = $dbFunctions->getUserPreferenceProfile($_SESSION['smash_or_pass_session']);
            
            echo json_encode([
                'type' => 'preferences',
                'preferences' => $preferences,
                'fallback_mode' => $usesFallback
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
