<?php

namespace Sander\App\Utils\SmashOrPass;

class DatabaseFunctions {
    private $pdo;
    private $imageCache = [];
    private $performerCache = [];
    private $maxCacheSize = 100;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get random performers with optimized query and caching
     */
    public function getRandomPerformers($limit = 1, $excludeIds = [], $gender = null) {
        $params = [];
        $whereClause = '';
        
        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $whereClause .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $excludeIds);
        }
        
        if ($gender) {
            $gender = trim(strtolower($gender));
            $whereClause .= " AND LOWER(TRIM(gender)) = ?";
            $params[] = $gender;
        }
        
        // Optimize random selection for better performance
        $sql = "SELECT p.id, p.name, p.gender, p.ethnicity, p.hair_color, p.eye_color, 
                       p.fake_boobs, p.height, p.weight, p.measurements, p.cup_size,
                       COUNT(pi.id) as image_count
                FROM performers p
                LEFT JOIN performer_images pi ON p.id = pi.performer_id
                WHERE p.image_amount > 0 $whereClause
                GROUP BY p.id
                HAVING image_count > 0
                ORDER BY RAND() * (
                    CASE 
                        WHEN p.rating IS NOT NULL THEN p.rating 
                        ELSE 0.5 
                    END
                ) DESC
                LIMIT ?";
        
        $params[] = $limit;
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $performers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Cache performers and fetch images
            foreach ($performers as &$performer) {
                $this->performerCache[$performer['id']] = $performer;
                $performer['images'] = $this->getPerformerImages($performer['id'], 1);
            }
            
            // Maintain cache size
            if (count($this->performerCache) > $this->maxCacheSize) {
                $this->performerCache = array_slice($this->performerCache, -$this->maxCacheSize, null, true);
            }
            
            return $performers;
        } catch (\PDOException $e) {
            error_log("Database error in getRandomPerformers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get performer images with caching
     */
    public function getPerformerImages($performerId, $limit = 1) {
        // Check cache first
        $cacheKey = "{$performerId}_$limit";
        if (isset($this->imageCache[$cacheKey])) {
            return $this->imageCache[$cacheKey];
        }
        
        $sql = "SELECT image_url FROM performer_images 
                WHERE performer_id = ? 
                ORDER BY RAND() 
                LIMIT ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$performerId, $limit]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $imageUrls = array_column($result, 'image_url');
            
            // Process and cache image URLs
            $processedUrls = [];
            foreach ($imageUrls as $imageUrl) {
                if (!empty($imageUrl)) {
                    $pattern = '/[\\\\\/]([^\\\\\/]+[\\\\\/][^\\\\\/]+\.[a-zA-Z]+)$/';
                    if (preg_match($pattern, $imageUrl, $matches)) {
                        $relativePath = str_replace('\\', '/', $matches[1]);
                        $processedUrls[] = 'https://cdn.jsdelivr.net/gh/DubblePumper/porn_ai_analyser@main/app/datasets/pornstar_images/' . $relativePath;
                    } else {
                        $processedUrls[] = '/assets/images/placeholder-profile.jpg';
                    }
                } else {
                    $processedUrls[] = '/assets/images/placeholder-profile.jpg';
                }
            }
            
            // Cache the results
            $this->imageCache[$cacheKey] = $processedUrls;
            
            // Maintain cache size
            if (count($this->imageCache) > $this->maxCacheSize) {
                $this->imageCache = array_slice($this->imageCache, -$this->maxCacheSize, null, true);
            }
            
            return $processedUrls;
        } catch (\PDOException $e) {
            error_log("Error getting performer images: " . $e->getMessage());
            return ['/assets/images/placeholder-profile.jpg'];
        }
    }
    
    /**
     * Save user choice with optimized single-image logic
     */
    public function saveUserChoice($sessionId, $chosenId, $rejectedId) {
        try {
            $this->createUserChoicesTableIfNotExists();
            
            $sql = "INSERT INTO user_choices (session_id, chosen_performer_id, rejected_performer_id, choice_time) 
                    VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$sessionId, $chosenId, $rejectedId]);
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Error saving user choice: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user choice count
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
    
    /**
     * Get user preference profile with optimized calculation
     */
    public function getUserPreferenceProfile($sessionId) {
        try {
            $this->createUserChoicesTableIfNotExists();
            
            // Get preferences from chosen performers with weighted scoring
            $sql = "SELECT p.*, 
                           CASE 
                               WHEN uc.chosen_performer_id IS NOT NULL THEN 1 
                               WHEN uc.rejected_performer_id IS NOT NULL THEN -0.5
                           END as choice_weight
                    FROM performers p
                    JOIN user_choices uc ON (p.id = uc.chosen_performer_id OR p.id = uc.rejected_performer_id)
                    WHERE uc.session_id = ?
                    ORDER BY uc.choice_time DESC
                    LIMIT 50";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessionId]);
            $performers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $this->calculatePreferences($performers);
        } catch (\PDOException $e) {
            error_log("Error getting user preferences: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate preferences with weighted choices
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
            'height' => ['sum' => 0, 'count' => 0],
            'weight' => ['sum' => 0, 'count' => 0],
            'cup_size' => []
        ];
        
        $totalWeight = 0;
        
        foreach ($performers as $performer) {
            $weight = $performer['choice_weight'];
            $totalWeight += abs($weight);
            
            // Process categorical attributes
            foreach (['gender', 'ethnicity', 'hair_color', 'eye_color', 'cup_size'] as $attr) {
                if (!empty($performer[$attr])) {
                    $value = strtolower(trim($performer[$attr]));
                    if (!isset($profile[$attr][$value])) {
                        $profile[$attr][$value] = 0;
                    }
                    $profile[$attr][$value] += $weight;
                }
            }
            
            // Process boolean attributes
            if (isset($performer['fake_boobs'])) {
                $profile['fake_boobs'] += (int)$performer['fake_boobs'] * $weight;
            }
            
            // Process numerical attributes
            foreach (['height', 'weight'] as $attr) {
                if (!empty($performer[$attr])) {
                    $value = (int)preg_replace('/[^0-9]/', '', $performer[$attr]);
                    if ($value > 0) {
                        $profile[$attr]['sum'] += $value * $weight;
                        $profile[$attr]['count'] += abs($weight);
                    }
                }
            }
        }
        
        // Normalize results
        foreach (['gender', 'ethnicity', 'hair_color', 'eye_color', 'cup_size'] as $attr) {
            if (!empty($profile[$attr])) {
                arsort($profile[$attr]);
                foreach ($profile[$attr] as &$value) {
                    $value = max(0, ($value / $totalWeight + 1) / 2 * 100);
                }
            }
        }
        
        // Calculate averages for numerical attributes
        foreach (['height', 'weight'] as $attr) {
            if ($profile[$attr]['count'] > 0) {
                $profile[$attr] = round($profile[$attr]['sum'] / $profile[$attr]['count']);
            } else {
                unset($profile[$attr]);
            }
        }
        
        // Normalize fake boobs preference to percentage
        if ($totalWeight > 0) {
            $profile['fake_boobs'] = max(0, round(($profile['fake_boobs'] / $totalWeight + 1) / 2 * 100));
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
}