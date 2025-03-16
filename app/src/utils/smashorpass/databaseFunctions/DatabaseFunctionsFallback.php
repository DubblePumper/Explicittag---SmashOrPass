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
                'name' => 'Performer A',
                'gender' => 'female',
                'ethnicity' => 'Caucasian',
                'height' => '170 cm',
                'images' => ['/assets/images/placeholder-profile.jpg']
            ],
            [
                'id' => 'demo-2',
                'name' => 'Performer B',
                'gender' => 'female',
                'ethnicity' => 'Caucasian',
                'height' => '175 cm',
                'images' => ['/assets/images/placeholder-profile.jpg']
            ],
            [
                'id' => 'demo-3',
                'name' => 'Performer C',
                'gender' => 'male',
                'ethnicity' => 'Caucasian',
                'height' => '185 cm',
                'images' => ['/assets/images/placeholder-profile.jpg']
            ]
        ];
        
        // Filter by gender if specified
        if ($gender) {
            $results = array_filter($results, function($performer) use ($gender) {
                return $performer['gender'] === $gender;
            });
            $results = array_values($results); // Re-index the array
        }
        
        // Shuffle and take the first $limit items
        shuffle($results);
        return array_slice($results, 0, $limit);
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
