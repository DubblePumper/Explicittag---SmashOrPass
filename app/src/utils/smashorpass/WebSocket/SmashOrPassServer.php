<?php

namespace Sander\App\Utils\SmashOrPass\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Sander\App\Utils\SmashOrPass\DatabaseFunctions;

class SmashOrPassServer implements MessageComponentInterface {
    protected $clients;
    protected $dbFunctions;
    protected $clientSessions = [];

    public function __construct(DatabaseFunctions $dbFunctions) {
        $this->clients = new \SplObjectStorage;
        $this->dbFunctions = $dbFunctions;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
        
        // Generate a session ID for this connection
        $sessionId = bin2hex(random_bytes(16));
        $this->clientSessions[$conn->resourceId] = $sessionId;
        
        echo "New connection! ({$conn->resourceId})\n";
        
        // Send initial performers
        $this->sendRandomPerformers($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!isset($data['action'])) {
            return;
        }
        
        switch ($data['action']) {
            case 'choice':
                $this->handleChoice($from, $data);
                break;
                
            case 'get_preferences':
                $this->sendPreferences($from);
                break;
                
            case 'next_pair':
                $this->sendRandomPerformers($from);
                break;
                
            case 'set_filter':
                // Handle filter setting (e.g., gender preference)
                if (isset($data['filter'])) {
                    $this->setUserFilter($from, $data['filter']);
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Remove the connection
        $this->clients->detach($conn);
        
        // Remove the session ID
        if (isset($this->clientSessions[$conn->resourceId])) {
            unset($this->clientSessions[$conn->resourceId]);
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
    
    protected function handleChoice(ConnectionInterface $from, array $data) {
        if (!isset($data['chosen_id']) || !isset($data['rejected_id'])) {
            return;
        }
        
        $sessionId = $this->clientSessions[$from->resourceId] ?? '';
        if (empty($sessionId)) {
            return;
        }
        
        // Save the user's choice
        $this->dbFunctions->saveUserChoice(
            $sessionId,
            $data['chosen_id'],
            $data['rejected_id']
        );
        
        // Send new performers for the next round
        $this->sendRandomPerformers($from);
    }
    
    protected function sendRandomPerformers(ConnectionInterface $conn) {
        // Get current user filter if any
        $filter = $this->getUserFilter($conn) ?? [];
        $gender = isset($filter['gender']) ? trim(strtolower($filter['gender'])) : null;
        
        // Log the filter being applied
        error_log("Applying filter for performers: " . json_encode($filter));
        
        // Get 2 random performers
        $performers = $this->dbFunctions->getRandomPerformers(2, [], $gender);
        
        // Verify that the performers match the requested gender
        if ($gender && count($performers) > 0) {
            foreach ($performers as $performer) {
                if (strtolower(trim($performer['gender'])) !== $gender) {
                    error_log("WARNING: Performer {$performer['id']} with gender {$performer['gender']} doesn't match requested gender {$gender}");
                }
            }
        }
        
        if (count($performers) < 2) {
            // Not enough performers found
            error_log("Not enough performers found with filter: " . json_encode($filter));
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Not enough performers available. Try changing your filters.'
            ]));
            return;
        }
        
        // Get images for each performer
        foreach ($performers as &$performer) {
            $performer['images'] = $this->dbFunctions->getPerformerImages($performer['id'], 1);
            
            // Debug for image URLs
            error_log("Images for performer {$performer['id']}: " . json_encode($performer['images']));
            
            // Ensure we have valid image URLs
            if (empty($performer['images']) || empty($performer['images'][0])) {
                $performer['images'] = ['/assets/images/placeholder-profile.jpg'];
                error_log("Using placeholder for performer {$performer['id']}");
            }
        }
        
        // Debug the full data being sent
        error_log("Sending performers data: " . json_encode($performers));
        
        // Send the performers to the client
        $conn->send(json_encode([
            'type' => 'performers',
            'performers' => $performers
        ]));
    }
    
    protected function sendPreferences(ConnectionInterface $conn) {
        $sessionId = $this->clientSessions[$conn->resourceId] ?? '';
        if (empty($sessionId)) {
            return;
        }
        
        // Get the user's preference profile
        $preferences = $this->dbFunctions->getUserPreferenceProfile($sessionId);
        
        // Send the preferences to the client
        $conn->send(json_encode([
            'type' => 'preferences',
            'preferences' => $preferences
        ]));
    }
    
    protected function setUserFilter(ConnectionInterface $conn, array $filter) {
        // Log the filter being set
        error_log("Setting filter for connection {$conn->resourceId}: " . json_encode($filter));
        
        // Normalize gender value to lowercase if present
        if (isset($filter['gender']) && is_string($filter['gender'])) {
            $filter['gender'] = trim(strtolower($filter['gender']));
            
            // If empty string, set to null to avoid filtering on empty value
            if ($filter['gender'] === '') {
                $filter['gender'] = null;
            }
            
            error_log("Normalized gender filter to: " . ($filter['gender'] ?? 'null'));
        }
        
        // Store filter for this connection
        $this->clientSessions[$conn->resourceId . '_filter'] = $filter;
    }
    
    protected function getUserFilter(ConnectionInterface $conn) {
        $filter = $this->clientSessions[$conn->resourceId . '_filter'] ?? null;
        error_log("Getting filter for connection {$conn->resourceId}: " . json_encode($filter));
        return $filter;
    }
}
