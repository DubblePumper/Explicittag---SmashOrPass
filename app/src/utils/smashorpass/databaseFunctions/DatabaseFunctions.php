<?php

namespace Sander\App\Utils\SmashOrPass;

class DatabaseFunctions {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get random performers for Smash or Pass game
     * @param int $limit Number of performers to fetch
     * @param array $excludeIds IDs to exclude
     * @param string $gender Filter by gender (optional)
     * @return array Array of performer data
     */
    public function getRandomPerformers($limit = 2, $excludeIds = [], $gender = null) {
        $params = [];
        $whereClause = '';
        
        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $whereClause .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $excludeIds);
        }
        
        // Improve gender filtering with exact match
        if ($gender) {
            // Convert to lowercase for consistent comparison and strip any whitespace
            $gender = trim(strtolower($gender));
            
            // Log the requested gender filter for debugging
            error_log("Applying gender filter: " . $gender);
            
            // Use exact match (BINARY ensures case sensitivity if needed)
            $whereClause .= " AND LOWER(TRIM(gender)) = ?";
            $params[] = $gender;
        }
        
        $sql = "SELECT id, name, gender, ethnicity, hair_color, eye_color, 
                       fake_boobs, height, weight, measurements, cup_size
                FROM performers 
                WHERE image_amount > 0 $whereClause
                ORDER BY RAND() 
                LIMIT ?";
        
        $params[] = $limit;
        
        // Log the SQL query and parameters for debugging
        error_log("SQL Query: " . $sql);
        error_log("Parameters: " . implode(', ', $params));
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Log the number of results returned and their genders
            error_log("Found " . count($results) . " performers with gender filter: " . $gender);
            foreach ($results as $index => $performer) {
                error_log("Result $index: ID={$performer['id']}, Name={$performer['name']}, Gender={$performer['gender']}");
            }
            
            return $results;
        } catch (\PDOException $e) {
            error_log("Database error in getRandomPerformers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get random images for a performer
     * @param string $performerId Performer ID
     * @param int $limit Number of images to fetch
     * @return array Array of image URLs
     */
    public function getPerformerImages($performerId, $limit = 1) {
        $sql = "SELECT image_url FROM performer_images 
                WHERE performer_id = ? 
                ORDER BY RAND() 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$performerId, $limit]);
        
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $imageUrls = array_column($result, 'image_url');
        
        // Debug the original URLs
        error_log("Original URLs for performer $performerId: " . print_r($imageUrls, true));
        
        // Fix image URLs to use GitHub CDN
        foreach ($imageUrls as &$imageUrl) {
            if (!empty($imageUrl)) {
                // Extract the relative path part that we need
                $pattern = '/[\\\\\/]([^\\\\\/]+[\\\\\/][^\\\\\/]+\.[a-zA-Z]+)$/';
                if (preg_match($pattern, $imageUrl, $matches)) {
                    $relativePath = str_replace('\\', '/', $matches[1]);
                    $imageUrl = 'https://cdn.jsdelivr.net/gh/DubblePumper/porn_ai_analyser@main/app/datasets/pornstar_images/' . $relativePath;
                    error_log("Matched and processed URL: $imageUrl");
                } else {
                    // Fallback to the old method if pattern doesn't match
                    $imageUrl = str_replace('\\', '/', $imageUrl);
                    $imageUrl = preg_replace('/^.*pornstar_images[\\\\\/]/', '', $imageUrl);
                    $imageUrl = 'https://cdn.jsdelivr.net/gh/DubblePumper/porn_ai_analyser@main/app/datasets/pornstar_images/' . $imageUrl;
                    error_log("Fallback processed URL: $imageUrl");
                }
            } else {
                $imageUrl = '/assets/images/placeholder-profile.jpg';
                error_log("Using placeholder for empty URL");
            }
        }
        
        // Log final URLs for debugging
        error_log("Final URLs: " . print_r($imageUrls, true));
        
        return $imageUrls;
    }
    
    /**
     * Save user choice in Smash or Pass game
     * @param string $sessionId User session ID
     * @param string $chosenId ID of chosen performer
     * @param string $rejectedId ID of rejected performer
     * @return bool Success status
     */
    public function saveUserChoice($sessionId, $chosenId, $rejectedId) {
        try {
            // First check if table exists, create if not
            $this->createUserChoicesTableIfNotExists();
            
            $sql = "INSERT INTO user_choices (session_id, chosen_performer_id, rejected_performer_id, choice_time) 
                    VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessionId, $chosenId, $rejectedId]);
            
            return true;
        } catch (\PDOException $e) {
            error_log("Error saving user choice: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user preference profile based on their choices
     * @param string $sessionId User session ID
     * @return array User preference profile
     */
    public function getUserPreferenceProfile($sessionId) {
        // Create choices table if it doesn't exist
        $this->createUserChoicesTableIfNotExists();
        
        // Get all chosen performers
        $sql = "SELECT p.* 
                FROM performers p
                JOIN user_choices uc ON p.id = uc.chosen_performer_id
                WHERE uc.session_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$sessionId]);
        $chosenPerformers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Calculate preferences
        $profile = $this->calculatePreferences($chosenPerformers);
        
        return $profile;
    }
    
    /**
     * Calculate preferences based on chosen performers
     * @param array $performers Array of chosen performers
     * @return array Preference profile
     */
    private function calculatePreferences($performers) {
        if (empty($performers)) {
            return [];
        }
        
        $profile = [
            'gender' => [],
            'ethnicity' => [],
            'hair_color' => [],
            'eye_color' => [],
            'fake_boobs' => 0,
            'height' => 0,
            'weight' => 0,
            'cup_size' => []
        ];
        
        $count = count($performers);
        
        // Count occurrences of each attribute
        foreach ($performers as $performer) {
            // Process categorical attributes
            foreach (['gender', 'ethnicity', 'hair_color', 'eye_color', 'cup_size'] as $attr) {
                if (!empty($performer[$attr])) {
                    if (!isset($profile[$attr][$performer[$attr]])) {
                        $profile[$attr][$performer[$attr]] = 0;
                    }
                    $profile[$attr][$performer[$attr]]++;
                }
            }
            
            // Process boolean attributes
            if (isset($performer['fake_boobs'])) {
                $profile['fake_boobs'] += (int)$performer['fake_boobs'];
            }
            
            // Process numerical attributes (height, weight)
            foreach (['height', 'weight'] as $attr) {
                if (!empty($performer[$attr])) {
                    // Convert from string (e.g., "170 cm") to number
                    $value = (int)preg_replace('/[^0-9]/', '', $performer[$attr]);
                    if ($value > 0) {
                        $profile[$attr] += $value;
                    }
                }
            }
        }
        
        // Calculate average for numerical attributes
        foreach (['height', 'weight'] as $attr) {
            if ($profile[$attr] > 0) {
                $profile[$attr] = round($profile[$attr] / $count);
            }
        }
        
        // Calculate percentage for boolean attributes
        $profile['fake_boobs'] = round(($profile['fake_boobs'] / $count) * 100);
        
        // Sort categorical preferences by frequency
        foreach (['gender', 'ethnicity', 'hair_color', 'eye_color', 'cup_size'] as $attr) {
            if (!empty($profile[$attr])) {
                arsort($profile[$attr]);
            }
        }
        
        return $profile;
    }
    
    /**
     * Create user_choices table if it doesn't exist
     */
    private function createUserChoicesTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS user_choices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) NOT NULL,
            chosen_performer_id VARCHAR(100) NOT NULL,
            rejected_performer_id VARCHAR(100) NOT NULL,
            choice_time DATETIME NOT NULL,
            INDEX (session_id),
            INDEX (chosen_performer_id),
            INDEX (rejected_performer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Get the number of choices a user has made
     * 
     * @param string $sessionId User session ID
     * @return int Number of choices
     */
    public function getUserChoiceCount($sessionId) {
        try {
            $sql = "SELECT COUNT(*) FROM user_choices WHERE session_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessionId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Error getting user choice count: " . $e->getMessage());
            return 0;
        }
    }
}