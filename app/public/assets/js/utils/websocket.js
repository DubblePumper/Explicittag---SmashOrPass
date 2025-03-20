/**
 * WebSocket Manager
 * Handles WebSocket connections with robust fallback to AJAX when WebSocket is unavailable
 */
class WebSocketManager {
    constructor() {
        this.socket = null;
        this.connected = false;
        this.config = null;
        this.connectionAttempts = 0;
        this.maxConnectionAttempts = 3;
        this.reconnectDelay = 2000; // 2 seconds
        this.messageQueue = [];
        this.eventListeners = {};
        this.connectionPromise = null;
        this.isInitialized = false;
    }

    /**
     * Initialize WebSocket manager
     * @returns {Promise} Resolves when initialization is complete
     */
    async init() {
        if (this.isInitialized) {
            return this.connectionPromise;
        }

        this.isInitialized = true;
        this.connectionPromise = new Promise(async (resolve) => {
            try {
                // Fetch WebSocket configuration
                const response = await fetch('/api/websocket-info.php');
                this.config = await response.json();
                
                console.log('WebSocket configuration:', this.config);
                
                if (this.config.enabled) {
                    this.connectWebSocket();
                } else {
                    console.log('WebSocket is disabled in server configuration');
                }
                
                resolve();
            } catch (error) {
                console.error('Failed to fetch WebSocket configuration:', error);
                resolve();
            }
        });
        
        return this.connectionPromise;
    }

    /**
     * Connect to the WebSocket server
     */
    connectWebSocket() {
        if (this.connectionAttempts >= this.maxConnectionAttempts) {
            console.log(`Maximum connection attempts (${this.maxConnectionAttempts}) reached. Using AJAX fallback.`);
            return;
        }

        try {
            this.connectionAttempts++;
            console.log(`Attempting to connect to WebSocket (attempt ${this.connectionAttempts}/${this.maxConnectionAttempts}): ${this.config.url}`);
            
            this.socket = new WebSocket(this.config.url);
            
            this.socket.onopen = () => {
                console.log('WebSocket connection established');
                this.connected = true;
                this.connectionAttempts = 0;
                
                // Send any queued messages
                this.processMessageQueue();
                
                // Trigger connect event for listeners
                this.triggerEvent('connect', { connected: true });
            };
            
            this.socket.onmessage = (event) => {
                this.handleMessage(event.data);
            };
            
            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.triggerEvent('error', error);
            };
            
            this.socket.onclose = (event) => {
                console.log(`WebSocket connection closed. Code: ${event.code}, Reason: ${event.reason}`);
                this.connected = false;
                
                // Try to reconnect if not reaching max attempts
                if (this.connectionAttempts < this.maxConnectionAttempts) {
                    console.log(`Reconnecting in ${this.reconnectDelay/1000} seconds...`);
                    setTimeout(() => this.connectWebSocket(), this.reconnectDelay);
                } else {
                    console.log('Maximum reconnection attempts reached. Using AJAX fallback.');
                    this.triggerEvent('fallback', { reason: 'Max reconnection attempts reached' });
                }
            };
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.triggerEvent('fallback', { reason: 'Connection creation failed', error });
        }
    }

    /**
     * Send a message through WebSocket or fallback to AJAX
     * @param {Object} data Data to send
     * @returns {Promise} Promise that resolves with the response
     */
    async send(data) {
        // Make sure WebSocket is initialized
        if (!this.isInitialized) {
            await this.init();
        }
        
        // Clone the data to avoid modifying the original
        const messageData = { ...data };
        
        // Add timestamp for debugging
        messageData.timestamp = Date.now();
        
        // Use WebSocket if connected
        if (this.connected && this.socket && this.socket.readyState === WebSocket.OPEN) {
            return new Promise((resolve) => {
                // Add a one-time message handler for this specific message
                const messageId = Date.now() + Math.random().toString(36).substr(2, 9);
                messageData.messageId = messageId;
                
                const handler = (response) => {
                    const parsedResponse = typeof response === 'string' ? JSON.parse(response) : response;
                    if (parsedResponse.messageId === messageId || !messageId) {
                        resolve(parsedResponse);
                        return true; // Return true to remove this one-time handler
                    }
                    return false;
                };
                
                this.addOneTimeMessageHandler(handler);
                this.socket.send(JSON.stringify(messageData));
            });
        } else {
            // Queue the message if WebSocket is connecting
            if (this.socket && this.socket.readyState === WebSocket.CONNECTING) {
                return new Promise((resolve) => {
                    this.messageQueue.push({
                        data: messageData,
                        resolve
                    });
                });
            }
            
            // Fallback to AJAX if WebSocket is unavailable
            console.log('WebSocket not available, using AJAX fallback');
            return this.ajaxFallback(messageData);
        }
    }

    /**
     * Add a one-time message handler
     * @param {Function} handler Handler function
     */
    addOneTimeMessageHandler(handler) {
        if (!this.eventListeners['one-time-message']) {
            this.eventListeners['one-time-message'] = [];
        }
        this.eventListeners['one-time-message'].push(handler);
    }

    /**
     * Process message queue
     */
    processMessageQueue() {
        if (this.messageQueue.length === 0) return;
        
        console.log(`Processing ${this.messageQueue.length} queued messages`);
        
        const queue = [...this.messageQueue];
        this.messageQueue = [];
        
        queue.forEach(async (item) => {
            try {
                const response = await this.send(item.data);
                item.resolve(response);
            } catch (error) {
                console.error('Error processing queued message:', error);
                item.resolve({ error: 'Failed to process queued message' });
            }
        });
    }

    /**
     * AJAX fallback when WebSocket is unavailable
     * @param {Object} data Data to send
     * @returns {Promise} Promise that resolves with the response
     */
    async ajaxFallback(data) {
        if (!this.config) {
            throw new Error('WebSocket configuration not loaded');
        }
        
        const endpoint = this.config.fallback_endpoint;
        const action = data.action || 'random_performers';
        
        let url = `${endpoint}?action=${action}`;
        
        // Add any additional query parameters
        if (data.filter) {
            Object.keys(data.filter).forEach(key => {
                if (data.filter[key] !== null && data.filter[key] !== undefined) {
                    url += `&${key}=${encodeURIComponent(data.filter[key])}`;
                }
            });
        }
        
        console.log(`Fetching performers from: ${url}`);
        
        try {
            // For actions that would normally be POST in WebSocket
            if (['save_choice', 'choice'].includes(action)) {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                return await response.json();
            } else {
                // For GET actions
                const response = await fetch(url);
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                return await response.json();
            }
        } catch (error) {
            console.error('AJAX fallback error:', error);
            return { error: error.message || 'AJAX fallback failed' };
        }
    }

    /**
     * Handle incoming WebSocket message
     * @param {string} rawData Raw message data
     */
    handleMessage(rawData) {
        try {
            const data = typeof rawData === 'string' ? JSON.parse(rawData) : rawData;
            
            // Process one-time message handlers
            const oneTimeHandlers = this.eventListeners['one-time-message'] || [];
            const remainingHandlers = oneTimeHandlers.filter(handler => !handler(data));
            this.eventListeners['one-time-message'] = remainingHandlers;
            
            // Trigger type-specific event
            if (data.type) {
                this.triggerEvent(data.type, data);
            }
            
            // Trigger general message event
            this.triggerEvent('message', data);
        } catch (error) {
            console.error('Error handling WebSocket message:', error, rawData);
        }
    }

    /**
     * Add event listener
     * @param {string} event Event name
     * @param {Function} callback Callback function
     */
    on(event, callback) {
        if (!this.eventListeners[event]) {
            this.eventListeners[event] = [];
        }
        this.eventListeners[event].push(callback);
    }

    /**
     * Remove event listener
     * @param {string} event Event name
     * @param {Function} callback Callback function
     */
    off(event, callback) {
        if (!this.eventListeners[event]) return;
        
        this.eventListeners[event] = this.eventListeners[event].filter(
            listener => listener !== callback
        );
    }

    /**
     * Trigger event for listeners
     * @param {string} event Event name
     * @param {any} data Event data
     */
    triggerEvent(event, data) {
        const listeners = this.eventListeners[event] || [];
        listeners.forEach(listener => {
            try {
                listener(data);
            } catch (error) {
                console.error(`Error in ${event} event listener:`, error);
            }
        });
    }

    /**
     * Check if WebSocket is available
     * @returns {boolean} True if WebSocket is supported and enabled
     */
    isWebSocketAvailable() {
        return this.connected && this.socket && this.socket.readyState === WebSocket.OPEN;
    }
}

// Create a singleton instance
const wsManager = new WebSocketManager();
