<?php

namespace Sander\App\Utils\SmashOrPass;

class DatabaseFunctionsFallback {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get random performers with minimal data for fallback API
     */
    public function getRandomPerformers($limit = 2, $excludeIds = [], $gender = null) {
        // Simplified implementation that doesn't rely on complex database structure
        $results = [
            [
                'id' => 'demo-1',
                'name' => 'Female Performer A',
                'gender' => 'female',
                'ethnicity' => 'Caucasian',
                'height' => '170 cm',
                'images' => ['/assets/images/placeholder-profile.jpg']
            ],
            [
                'id' => 'demo-2',
                'name' => 'Female Performer B',
                'gender' => 'female',
                'ethnicity' => 'Caucasian',
                'height' => '175 cm',
                'images' => ['/assets/images/placeholder-profile.jpg']
            ],
            [
                'id' => 'demo-3',
                'name' => 'Male Performer A',
                'gender' => 'male',
                'ethnicity' => 'Caucasian',
                'height' => '185 cm',
                'images' => ['/assets/images/placeholder-profile.jpg']
            ],
            [
                'id' => 'demo-4',
                'name' => 'Male Performer B',
                'gender' => 'male',
                'ethnicity' => 'African',
                'height' => '180 cm',
                'images' => ['/assets/images/placeholder-profile.jpg']
            ],
            [
                'id' => 'demo-5',
                'name' => 'Trans Performer',
                'gender' => 'transgender',
                'ethnicity' => 'Asian',
                'height' => '172 cm',
                'images' => ['/assets/images/placeholder-profile.jpg']
            ]
        ];
        
        // Normalize gender for case-insensitive comparison
        $normalizedGender = $gender ? strtolower(trim($gender)) : null;
        
        // Debug log the requested gender
        error_log("getRandomPerformers requested gender: " . ($normalizedGender ?? 'null'));
        
        // Filter by gender if specified - do this BEFORE shuffling
        if ($normalizedGender) {
            $filtered = array_filter($results, function($performer) use ($normalizedGender) {
                $performerGender = strtolower(trim($performer['gender']));
                error_log("Comparing performer gender: '{$performerGender}' with requested gender: '{$normalizedGender}'");
                return $performerGender === $normalizedGender;
            });
            
            // Convert to indexed array (not associative)
            $filtered = array_values($filtered);
            
            // Log the filter results
            error_log("Fallback filter by '$normalizedGender' - Before: " . count($results) . ", After: " . count($filtered));
            
            $results = $filtered;
        }
        
        // Add more performers of the same gender if we don't have enough
        if (count($results) < $limit && $normalizedGender) {
            error_log("Adding additional performers of gender: $normalizedGender");
            for ($i = count($results); $i < $limit; $i++) {
                $results[] = [
                    'id' => 'demo-extra-' . $i,
                    'name' => ucfirst($normalizedGender) . ' Performer Extra ' . $i,
                    'gender' => $normalizedGender,
                    'ethnicity' => 'Mixed',
                    'height' => '173 cm',
                    'images' => ['/assets/images/placeholder-profile.jpg']
                ];
            }
        }
        
        // Shuffle ONLY AFTER filtering by gender
        shuffle($results);
        $finalResults = array_slice($results, 0, $limit);
        
        // Final verification log
        foreach ($finalResults as $index => $performer) {
            error_log("Fallback result $index: ID={$performer['id']}, Gender={$performer['gender']}");
        }
        
        return $finalResults;
    }
    
    /**
     * Get images for a performer (fallback implementation)
     */
    public function getPerformerImages($performerId, $limit = 1) {
        return ['/assets/images/placeholder-profile.jpg'];
    }
    
    /**
     * Save user choice (fallback implementation)
     */
    public function saveUserChoice($sessionId, $chosenId, $rejectedId) {
        return true;
    }
    
    /**
     * Get preference profile (fallback implementation)
     */
    public function getUserPreferenceProfile($sessionId) {
        return [
            'gender' => ['female' => 3, 'male' => 1],
            'ethnicity' => ['Caucasian' => 4],
            'hair_color' => ['Blonde' => 2, 'Brunette' => 2],
            'eye_color' => ['Blue' => 3, 'Brown' => 1],
            'fake_boobs' => 50,
            'height' => 172,
            'cup_size' => ['D' => 2, 'C' => 1, 'B' => 1]
        ];
    }
}
