<?php

namespace Sander\App\Utils\SmashOrPass\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Sander\App\Utils\SmashOrPass\DatabaseFunctions;

class SmashOrPassServer implements MessageComponentInterface {
    protected $clients;
    protected $dbFunctions;
    protected $clientSessions = [];
    protected $clientPreloadCache = [];
    protected $preloadBatchSize = 5;

    public function __construct(DatabaseFunctions $dbFunctions) {
        $this->clients = new \SplObjectStorage;
        $this->dbFunctions = $dbFunctions;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        
        // Generate a session ID for this connection
        $sessionId = bin2hex(random_bytes(16));
        $this->clientSessions[$conn->resourceId] = $sessionId;
        
        // Initialize preload cache for this client
        $this->clientPreloadCache[$conn->resourceId] = [];
        
        echo "New connection! ({$conn->resourceId})\n";
        
        // Send initial performers and preload batch
        $this->sendInitialPerformers($conn);
    }

    protected function sendInitialPerformers(ConnectionInterface $conn) {
        // Get initial batch of performers with filter
        $filter = $this->getUserFilter($conn) ?? [];
        $gender = isset($filter['gender']) ? trim(strtolower($filter['gender'])) : null;
        
        // Get performers for initial display plus preload
        $performers = $this->dbFunctions->getRandomPerformers($this->preloadBatchSize, [], $gender);
        
        if (empty($performers)) {
            $this->sendError($conn, 'No performers available. Try changing your filters.');
            return;
        }
        
        // Split performers into current and preload
        $currentPerformer = array_shift($performers);
        $this->clientPreloadCache[$conn->resourceId] = $performers;
        
        // Send initial data
        $conn->send(json_encode([
            'type' => 'performers',
            'performers' => [$currentPerformer], // Send only one performer
            'next_batch' => $performers // Send rest as preload batch
        ]));
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
                
            case 'preload_next_batch':
                $this->sendPreloadBatch($from);
                break;
                
            case 'set_filter':
                if (isset($data['filter'])) {
                    $this->setUserFilter($from, $data['filter']);
                    // Send new batch with updated filter
                    $this->sendInitialPerformers($from);
                }
                break;
        }
    }

    protected function handleChoice(ConnectionInterface $from, array $data) {
        $sessionId = $this->clientSessions[$from->resourceId] ?? '';
        if (empty($sessionId)) return;
        
        // Save the user's choice
        if (isset($data['chosen_id'])) {
            $this->dbFunctions->saveUserChoice(
                $sessionId,
                $data['chosen_id'],
                null // No rejected ID in single image mode
            );
        } else if (isset($data['rejected_id'])) {
            $this->dbFunctions->saveUserChoice(
                $sessionId,
                null,
                $data['rejected_id']
            );
        }
        
        // Check if we need to send more preloaded performers
        $this->checkAndReplenishPreloadCache($from);
        
        // Send next performer from preload cache if available
        if (!empty($this->clientPreloadCache[$from->resourceId])) {
            $nextPerformer = array_shift($this->clientPreloadCache[$from->resourceId]);
            $from->send(json_encode([
                'type' => 'performers',
                'performers' => [$nextPerformer],
                'next_batch' => $this->clientPreloadCache[$from->resourceId]
            ]));
        } else {
            // If cache is empty, fetch new performers
            $this->sendInitialPerformers($from);
        }
    }

    protected function checkAndReplenishPreloadCache(ConnectionInterface $conn) {
        $cacheSize = count($this->clientPreloadCache[$conn->resourceId] ?? []);
        if ($cacheSize < 3) { // Threshold for replenishment
            $filter = $this->getUserFilter($conn) ?? [];
            $gender = isset($filter['gender']) ? trim(strtolower($filter['gender'])) : null;
            
            // Get new batch of performers
            $newPerformers = $this->dbFunctions->getRandomPerformers(
                $this->preloadBatchSize - $cacheSize,
                array_column($this->clientPreloadCache[$conn->resourceId], 'id'),
                $gender
            );
            
            // Add to cache
            if (!empty($newPerformers)) {
                $this->clientPreloadCache[$conn->resourceId] = array_merge(
                    $this->clientPreloadCache[$conn->resourceId],
                    $newPerformers
                );
            }
        }
    }

    protected function sendPreloadBatch(ConnectionInterface $conn) {
        $this->checkAndReplenishPreloadCache($conn);
        
        if (!empty($this->clientPreloadCache[$conn->resourceId])) {
            $conn->send(json_encode([
                'type' => 'preload_batch',
                'performers' => $this->clientPreloadCache[$conn->resourceId]
            ]));
        }
    }

    protected function sendPreferences(ConnectionInterface $conn) {
        $sessionId = $this->clientSessions[$conn->resourceId] ?? '';
        if (empty($sessionId)) return;
        
        $preferences = $this->dbFunctions->getUserPreferenceProfile($sessionId);
        
        $conn->send(json_encode([
            'type' => 'preferences',
            'preferences' => $preferences
        ]));
    }

    protected function setUserFilter(ConnectionInterface $conn, array $filter) {
        if (isset($filter['gender']) && is_string($filter['gender'])) {
            $filter['gender'] = trim(strtolower($filter['gender']));
            if ($filter['gender'] === '') {
                $filter['gender'] = null;
            }
        }
        
        $this->clientSessions[$conn->resourceId . '_filter'] = $filter;
        
        // Clear preload cache when filter changes
        $this->clientPreloadCache[$conn->resourceId] = [];
    }

    protected function getUserFilter(ConnectionInterface $conn) {
        return $this->clientSessions[$conn->resourceId . '_filter'] ?? null;
    }

    protected function sendError(ConnectionInterface $conn, string $message) {
        $conn->send(json_encode([
            'type' => 'error',
            'message' => $message
        ]));
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Clean up session and cache
        unset($this->clientSessions[$conn->resourceId]);
        unset($this->clientPreloadCache[$conn->resourceId]);
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
