<?php

namespace Sander\App\Utils\SmashOrPass\Recommendation;

class RecommendationEngine {
    private $pdo;
    private $sessionId;
    private $userProfile;
    private $exploreRate = 0.2; // 20% random exploration

    public function __construct(\PDO $pdo, string $sessionId) {
        $this->pdo = $pdo;
        $this->sessionId = $sessionId;
        $this->userProfile = null;
    }

    /**
     * Get recommended performers based on user's previous choices
     * 
     * @param int $limit Number of performers to recommend
     * @param array $excludeIds IDs to exclude
     * @param string|null $gender Gender filter (optional)
     * @return array Recommended performers
     */
    public function getRecommendedPerformers($limit = 4, $excludeIds = [], $gender = null) {
        // If user has less than 5 choices, return random performers
        $choiceCount = $this->getUserChoiceCount();
        
        if ($choiceCount < 5) {
            return $this->getRandomPerformers($limit, $excludeIds, $gender);
        }

        // Apply exploration rate (sometimes return random performers)
        if (mt_rand(1, 100) <= ($this->exploreRate * 100)) {
            return $this->getRandomPerformers($limit, $excludeIds, $gender);
        }

        // Get user profile
        $this->loadUserProfile();
        
        // Get scored performers
        return $this->getScoredPerformers($limit, $excludeIds, $gender);
    }

    /**
     * Count user's previous choices
     * 
     * @return int Number of choices made
     */
    private function getUserChoiceCount() {
        $sql = "SELECT COUNT(*) FROM user_choices WHERE session_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->sessionId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get random performers (fallback)
     * 
     * @param int $limit Number of performers to get
     * @param array $excludeIds IDs to exclude
     * @param string|null $gender Gender filter (optional)
     * @return array Random performers
     */
    private function getRandomPerformers($limit, $excludeIds, $gender) {
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
        
        $sql = "SELECT id, name, gender, ethnicity, hair_color, eye_color, 
                       fake_boobs, height, weight, measurements, cup_size
                FROM performers 
                WHERE image_amount > 0 $whereClause
                ORDER BY RAND() 
                LIMIT ?";
        
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Load user profile based on previous choices
     */
    private function loadUserProfile() {
        if ($this->userProfile !== null) {
            return; // Already loaded
        }

        // Get chosen performers
        $sql = "SELECT p.* 
                FROM performers p
                JOIN user_choices uc ON p.id = uc.chosen_performer_id
                WHERE uc.session_id = ?
                ORDER BY uc.choice_time DESC
                LIMIT 50"; // Consider only recent choices
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->sessionId]);
        $chosenPerformers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Get rejected performers for comparison
        $sqlRejected = "SELECT p.* 
                FROM performers p
                JOIN user_choices uc ON p.id = uc.rejected_performer_id
                WHERE uc.session_id = ?
                ORDER BY uc.choice_time DESC
                LIMIT 50";
                
        $stmt = $this->pdo->prepare($sqlRejected);
        $stmt->execute([$this->sessionId]);
        $rejectedPerformers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Build profile
        $this->userProfile = $this->buildUserProfile($chosenPerformers, $rejectedPerformers);
    }
    
    /**
     * Build user profile from chosen and rejected performers
     * 
     * @param array $chosenPerformers Performers the user chose
     * @param array $rejectedPerformers Performers the user rejected
     * @return array User profile
     */
    private function buildUserProfile($chosenPerformers, $rejectedPerformers) {
        $profile = [
            'gender' => [],
            'ethnicity' => [],
            'hair_color' => [],
            'eye_color' => [],
            'fake_boobs' => ['true' => 0, 'false' => 0],
            'cup_size' => [],
            // Height and weight will be arrays of values
            'height' => [],
            'weight' => [],
            // Track which attributes are most predictive
            'attribute_weights' => [
                'gender' => 5,
                'ethnicity' => 3,
                'hair_color' => 2,
                'eye_color' => 1,
                'fake_boobs' => 2,
                'cup_size' => 2,
                'height' => 1,
                'weight' => 1
            ]
        ];
        
        // Process chosen performers (positive signals)
        foreach ($chosenPerformers as $performer) {
            $this->addPerformerToProfile($profile, $performer, 1);
        }
        
        // Process rejected performers (negative signals)
        foreach ($rejectedPerformers as $performer) {
            $this->addPerformerToProfile($profile, $performer, -0.5);
        }
        
        // Calculate preferences based on chosen vs rejected
        $profile = $this->normalizeProfile($profile);
        
        return $profile;
    }
    
    /**
     * Add a performer's attributes to the profile
     * 
     * @param array &$profile User profile to update
     * @param array $performer Performer data
     * @param float $weight Positive for chosen, negative for rejected
     */
    private function addPerformerToProfile(&$profile, $performer, $weight) {
        // Process categorical attributes
        foreach (['gender', 'ethnicity', 'hair_color', 'eye_color', 'cup_size'] as $attr) {
            if (!empty($performer[$attr])) {
                $value = trim(strtolower($performer[$attr]));
                if (!isset($profile[$attr][$value])) {
                    $profile[$attr][$value] = 0;
                }
                $profile[$attr][$value] += $weight;
            }
        }
        
        // Process boolean attributes
        if (isset($performer['fake_boobs'])) {
            $key = $performer['fake_boobs'] ? 'true' : 'false';
            $profile['fake_boobs'][$key] += $weight;
        }
        
        // Process numerical attributes
        foreach (['height', 'weight'] as $attr) {
            if (!empty($performer[$attr])) {
                // Extract number from string like "170 cm"
                $value = (int) preg_replace('/[^0-9]/', '', $performer[$attr]);
                if ($value > 0) {
                    $profile[$attr][] = $value;
                }
            }
        }
    }
    
    /**
     * Normalize profile values for better comparison
     * 
     * @param array $profile Raw profile
     * @return array Normalized profile
     */
    private function normalizeProfile($profile) {
        $normalized = $profile;
        
        // Normalize categorical attributes
        foreach (['gender', 'ethnicity', 'hair_color', 'eye_color', 'cup_size'] as $attr) {
            if (!empty($profile[$attr])) {
                // Sort by score (highest first)
                arsort($normalized[$attr]);
                
                // Normalize to range [0, 1]
                $total = array_sum(array_map('abs', $profile[$attr]));
                if ($total > 0) {
                    foreach ($normalized[$attr] as $key => $value) {
                        $normalized[$attr][$key] = max(0, $value / $total);
                    }
                }
            }
        }
        
        // Normalize boolean attributes
        $totalBoobs = abs($profile['fake_boobs']['true']) + abs($profile['fake_boobs']['false']);
        if ($totalBoobs > 0) {
            $normalized['fake_boobs']['true'] = max(0, $profile['fake_boobs']['true'] / $totalBoobs);
            $normalized['fake_boobs']['false'] = max(0, $profile['fake_boobs']['false'] / $totalBoobs);
        }
        
        // Calculate mean and std dev for numerical attributes
        foreach (['height', 'weight'] as $attr) {
            if (!empty($profile[$attr])) {
                $normalized[$attr] = [
                    'mean' => array_sum($profile[$attr]) / count($profile[$attr]),
                    'std_dev' => $this->standardDeviation($profile[$attr])
                ];
            } else {
                $normalized[$attr] = [
                    'mean' => 0,
                    'std_dev' => 0
                ];
            }
        }
        
        return $normalized;
    }
    
    /**
     * Calculate standard deviation
     * 
     * @param array $values Array of values
     * @return float Standard deviation
     */
    private function standardDeviation($values) {
        $n = count($values);
        if ($n === 0) {
            return 0;
        }
        
        $mean = array_sum($values) / $n;
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return sqrt($variance / $n);
    }
    
    /**
     * Get performers scored by their match to user profile
     * 
     * @param int $limit Number of performers to get
     * @param array $excludeIds IDs to exclude
     * @param string|null $gender Gender filter (optional)
     * @return array Scored performers
     */
    private function getScoredPerformers($limit, $excludeIds, $gender) {
        // Start with a broad selection of performers
        $potentialPerformers = $this->getPotentialPerformers($limit * 5, $excludeIds, $gender);
        
        // Score each performer
        $scoredPerformers = [];
        foreach ($potentialPerformers as $performer) {
            $score = $this->scorePerformer($performer);
            $scoredPerformers[] = [
                'performer' => $performer,
                'score' => $score
            ];
        }
        
        // Sort by score (highest first)
        usort($scoredPerformers, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Take the top performers
        $result = [];
        for ($i = 0; $i < min($limit, count($scoredPerformers)); $i++) {
            $result[] = $scoredPerformers[$i]['performer'];
        }
        
        return $result;
    }
    
    /**
     * Get potential performers for scoring
     * 
     * @param int $limit Number of performers to get
     * @param array $excludeIds IDs to exclude
     * @param string|null $gender Gender filter (optional)
     * @return array Potential performers
     */
    private function getPotentialPerformers($limit, $excludeIds, $gender) {
        $params = [];
        $whereClause = '';
        
        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $whereClause .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $excludeIds);
        }
        
        // Apply gender filter if specified
        if ($gender) {
            $gender = trim(strtolower($gender));
            $whereClause .= " AND LOWER(TRIM(gender)) = ?";
            $params[] = $gender;
        }
        // Try to match top gender preference if no filter specified and we have preferences
        else if (!empty($this->userProfile['gender'])) {
            // Get top gender preference
            reset($this->userProfile['gender']);
            $topGender = key($this->userProfile['gender']);
            $topScore = current($this->userProfile['gender']);
            
            // Only apply if strong preference (>0.6)
            if ($topScore > 0.6) {
                $whereClause .= " AND LOWER(TRIM(gender)) = ?";
                $params[] = $topGender;
            }
        }
        
        // Get performers that might match profile
        $sql = "SELECT id, name, gender, ethnicity, hair_color, eye_color, 
                       fake_boobs, height, weight, measurements, cup_size
                FROM performers 
                WHERE image_amount > 0 $whereClause
                ORDER BY RAND() 
                LIMIT ?";
        
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Score a performer based on how well they match the user profile
     * 
     * @param array $performer Performer data
     * @return float Score (higher is better match)
     */
    private function scorePerformer($performer) {
        if ($this->userProfile === null) {
            return 0;
        }
        
        $score = 0;
        $totalWeight = 0;
        
        // Score categorical attributes
        foreach (['gender', 'ethnicity', 'hair_color', 'eye_color', 'cup_size'] as $attr) {
            $attrWeight = $this->userProfile['attribute_weights'][$attr];
            $totalWeight += $attrWeight;
            
            if (!empty($performer[$attr]) && !empty($this->userProfile[$attr])) {
                $value = trim(strtolower($performer[$attr]));
                if (isset($this->userProfile[$attr][$value])) {
                    $score += $this->userProfile[$attr][$value] * $attrWeight;
                }
            }
        }
        
        // Score boolean attributes
        $attrWeight = $this->userProfile['attribute_weights']['fake_boobs'];
        $totalWeight += $attrWeight;
        
        if (isset($performer['fake_boobs'])) {
            $key = $performer['fake_boobs'] ? 'true' : 'false';
            $score += $this->userProfile['fake_boobs'][$key] * $attrWeight;
        }
        
        // Score numerical attributes
        foreach (['height', 'weight'] as $attr) {
            $attrWeight = $this->userProfile['attribute_weights'][$attr];
            $totalWeight += $attrWeight;
            
            if (!empty($performer[$attr]) && isset($this->userProfile[$attr]['mean'])) {
                // Extract number from string like "170 cm"
                $value = (int) preg_replace('/[^0-9]/', '', $performer[$attr]);
                
                if ($value > 0 && $this->userProfile[$attr]['std_dev'] > 0) {
                    // Calculate z-score (how many std devs from mean)
                    $zScore = abs($value - $this->userProfile[$attr]['mean']) / $this->userProfile[$attr]['std_dev'];
                    
                    // Convert to similarity score (1 = identical, 0 = very different)
                    // Using a bell curve where values within 1 std dev get high scores
                    $similarity = exp(-0.5 * pow($zScore, 2));
                    $score += $similarity * $attrWeight;
                }
            }
        }
        
        // Normalize final score
        return $totalWeight > 0 ? $score / $totalWeight : 0;
    }
}
