/**
 * SmashOrPass Game Logic
 * Handles the UI and game logic for the Smash or Pass feature
 */
document.addEventListener('DOMContentLoaded', async function() {
    // Elements
    const gameContainer = document.getElementById('game-container');
    const resultsContainer = document.getElementById('results-container');
    const performerACard = document.getElementById('performer-a');
    const performerBCard = document.getElementById('performer-b');
    const performerAImage = document.getElementById('performer-a-image');
    const performerBImage = document.getElementById('performer-b-image');
    const performerAName = document.getElementById('performer-a-name');
    const performerBName = document.getElementById('performer-b-name');
    const performerADetails = document.getElementById('performer-a-details');
    const performerBDetails = document.getElementById('performer-b-details');
    const smashButtons = document.querySelectorAll('.smash-button');
    const passBothButton = document.getElementById('pass-both');
    const viewPreferencesButton = document.getElementById('view-preferences');
    const continuePlayingButton = document.getElementById('continue-playing');
    const preferencesData = document.getElementById('preferences-data');
    const choiceCount = document.getElementById('choice-count');
    const progressBar = document.getElementById('progress-bar');
    const genderFilter = document.getElementById('gender-filter');
    const blurToggle = document.getElementById('blur-toggle');
    const blurToggleHandle = document.getElementById('blur-toggle-handle');
    
    // Game state
    let currentPerformers = [];
    let userChoices = 0;
    let gameActive = true;
    let isProcessingChoice = false; // Flag to prevent multiple rapid choices
    let blurEnabled = localStorage.getItem('blurEnabled') !== 'false'; // Default to enabled
    
    // Initialize blur state
    updateBlurState();
    
    // Load the WebSocket manager script and initialize it
    await loadWebSocketManager();
    
    // Initialize WebSocket and fetch first performers
    await initGame();
    
    /**
     * Load WebSocket Manager script
     */
    async function loadWebSocketManager() {
        // Check if WebSocket manager is already loaded
        if (window.wsManager) {
            console.log('WebSocket manager already loaded');
            return;
        }
        
        return new Promise((resolve, reject) => {
            // Create script element
            const script = document.createElement('script');
            script.src = '/assets/js/utils/websocket.js';
            script.async = true;
            
            // Set up load and error handlers
            script.onload = () => {
                console.log('WebSocket manager script loaded successfully');
                resolve();
            };
            
            script.onerror = (error) => {
                console.error('Failed to load WebSocket manager script:', error);
                reject(error);
            };
            
            // Add script to document
            document.head.appendChild(script);
        });
    }
    
    /**
     * Initialize the game
     */
    async function initGame() {
        try {
            // Initialize WebSocket (will automatically use AJAX fallback if needed)
            if (window.wsManager) {
                await wsManager.init();
                console.log('Attempting to connect to WebSocket on', wsManager.config?.url);
                
                // Set up WebSocket event listeners
                wsManager.on('performers', handlePerformersData);
                wsManager.on('preferences', handlePreferencesData);
                wsManager.on('error', handleWebSocketError);
                wsManager.on('connect', () => {
                    console.log('WebSocket connected successfully');
                });
                wsManager.on('fallback', (data) => {
                    console.log('Using AJAX fallback due to:', data.reason);
                });
            } else {
                console.error('WebSocket manager not available, will use direct AJAX fallback');
            }
            
            // Load initial performers
            await fetchPerformers();
        } catch (error) {
            console.error('Error initializing game:', error);
            setErrorState('Failed to initialize the game. Please refresh the page and try again.');
        }
    }
    
    // Event listeners
    
    // Handle blur toggle
    blurToggle.addEventListener('click', function() {
        blurEnabled = !blurEnabled;
        localStorage.setItem('blurEnabled', blurEnabled);
        updateBlurState();
    });
    
    // Handle smash button click
    smashButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (isProcessingChoice) return; // Prevent multiple rapid clicks
            
            const option = this.getAttribute('data-option');
            let chosenId, rejectedId;
            
            if (option === 'a') {
                chosenId = performerACard.getAttribute('data-performer-id');
                rejectedId = performerBCard.getAttribute('data-performer-id');
            } else {
                chosenId = performerBCard.getAttribute('data-performer-id');
                rejectedId = performerACard.getAttribute('data-performer-id');
            }
            
            makeChoice(chosenId, rejectedId);
        });
    });
    
    // Handle pass both button click
    passBothButton.addEventListener('click', function() {
        if (isProcessingChoice) return;
        fetchPerformers();
    });
    
    // Handle view preferences button click
    viewPreferencesButton.addEventListener('click', function() {
        fetchPreferences();
    });
    
    // Handle continue playing button click
    continuePlayingButton.addEventListener('click', function() {
        gameContainer.style.display = 'block';
        resultsContainer.style.display = 'none';
        gameActive = true;
        
        // If we don't have performers loaded, fetch new ones
        if (currentPerformers.length < 2) {
            fetchPerformers();
        }
    });
    
    // Handle gender filter change
    genderFilter.addEventListener('change', function() {
        const gender = this.value;
        console.log('Applying gender filter:', gender);
        applyFilters({ gender });
    });
    
    // Core game functions
    
    /**
     * Update blur state based on user preference
     */
    function updateBlurState() {
        // Update the toggle button appearance
        if (blurEnabled) {
            blurToggle.classList.add('bg-tertery');
            blurToggle.classList.remove('bg-BgDark');
            blurToggleHandle.classList.add('translate-x-6');
            blurToggleHandle.classList.remove('translate-x-1');
            document.body.classList.remove('blur-disabled');
        } else {
            blurToggle.classList.remove('bg-tertery');
            blurToggle.classList.add('bg-BgDark');
            blurToggleHandle.classList.remove('translate-x-6');
            blurToggleHandle.classList.add('translate-x-1');
            document.body.classList.add('blur-disabled');
        }
    }
    
    /**
     * Make a choice between performers
     */
    async function makeChoice(chosenId, rejectedId) {
        if (!chosenId || !rejectedId || isProcessingChoice) {
            console.error('Invalid choice or already processing a choice');
            return;
        }
        
        try {
            isProcessingChoice = true;
            
            // Add UI feedback - optional styling to indicate processing
            const buttons = document.querySelectorAll('.smash-button, #pass-both');
            buttons.forEach(btn => btn.classList.add('opacity-50'));
            
            // Send choice via WebSocket or AJAX fallback
            if (window.wsManager) {
                await wsManager.send({
                    action: 'choice',
                    chosen_id: chosenId,
                    rejected_id: rejectedId
                });
            } else {
                // Direct AJAX fallback if WebSocket manager isn't available
                await fetch('/api/performers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'save_choice',
                        chosen_id: chosenId,
                        rejected_id: rejectedId
                    })
                });
            }
            
            // Update UI state
            userChoices++;
            choiceCount.textContent = userChoices;
            updateProgressBar();
            
            // Fetch new performers
            await fetchPerformers();
        } catch (error) {
            console.error('Error making choice:', error);
            setErrorState('Failed to save your choice. Please try again.');
        } finally {
            isProcessingChoice = false;
            
            // Remove UI feedback
            const buttons = document.querySelectorAll('.smash-button, #pass-both');
            buttons.forEach(btn => btn.classList.remove('opacity-50'));
        }
    }
    
    /**
     * Apply filters to performers
     */
    async function applyFilters(filters) {
        try {
            // Show loading state
            setLoadingState(true);
            
            if (window.wsManager) {
                if (wsManager.isWebSocketAvailable()) {
                    await wsManager.send({
                        action: 'set_filter',
                        filter: filters
                    });
                } else {
                    console.log('WebSocket not available, using AJAX fallback');
                }
                
                // Fetch new performers with applied filters
                await fetchPerformers(filters);
            } else {
                // Direct AJAX fallback
                await fetchPerformers(filters);
            }
        } catch (error) {
            console.error('Error applying filters:', error);
            setErrorState('Failed to apply filters. Please try again.');
        } finally {
            setLoadingState(false);
        }
    }
    
    /**
     * Fetch random performers
     */
    async function fetchPerformers(filters = null) {
        try {
            // Show loading state
            setLoadingState(true);
            
            // Get current gender filter if not provided
            if (!filters && genderFilter) {
                const gender = genderFilter.value;
                filters = { gender };
            }
            
            let response;
            
            // Use WebSocket manager to get performers if available
            if (window.wsManager) {
                response = await wsManager.send({
                    action: 'random_performers',
                    filter: filters
                });
            } else {
                // Direct AJAX fallback
                let url = '/api/performers.php?action=random_performers';
                if (filters?.gender) {
                    url += `&gender=${encodeURIComponent(filters.gender)}`;
                }
                
                console.log('Fetching performers from:', url);
                const fetchResponse = await fetch(url);
                response = await fetchResponse.json();
            }
            
            console.log('Received performer data:', response);
            
            // Process the response
            if (response && response.performers && response.performers.length >= 2) {
                handlePerformersData(response);
            } else {
                console.error('Invalid response data:', response);
                setErrorState('Could not load performers. Please try again or change your filters.');
            }
        } catch (error) {
            console.error('Error fetching performers:', error);
            setErrorState('Network error. Please check your connection and try again.');
        } finally {
            setLoadingState(false);
        }
    }
    
    /**
     * Fetch user preferences
     */
    async function fetchPreferences() {
        try {
            setLoadingState(true);
            
            let response;
            
            if (window.wsManager) {
                response = await wsManager.send({
                    action: 'get_preferences'
                });
            } else {
                // Direct AJAX fallback
                const fetchResponse = await fetch('/api/performers.php?action=get_preferences');
                response = await fetchResponse.json();
            }
            
            if (response && response.preferences) {
                handlePreferencesData(response);
                
                // Show results container
                gameContainer.style.display = 'none';
                resultsContainer.style.display = 'block';
                gameActive = false;
            } else {
                console.error('Invalid preferences data:', response);
                setErrorState('Could not load your preferences. Please try again.');
            }
        } catch (error) {
            console.error('Error fetching preferences:', error);
            setErrorState('Failed to load your preferences. Please try again.');
        } finally {
            setLoadingState(false);
        }
    }
    
    /**
     * Handle performers data from WebSocket or AJAX
     */
    function handlePerformersData(data) {
        if (!data.performers || data.performers.length < 2) {
            console.error('Not enough performers in the response');
            return;
        }
        
        currentPerformers = data.performers;
        console.log('Displaying performers:', currentPerformers);
        
        // Update UI with performer A
        performerACard.setAttribute('data-performer-id', currentPerformers[0].id);
        performerAName.textContent = currentPerformers[0].name || 'Unknown Performer';
        
        // Set image for performer A
        if (currentPerformers[0].images && currentPerformers[0].images.length > 0) {
            const imageUrl = currentPerformers[0].images[0];
            console.log('Setting image A to:', imageUrl);
            performerAImage.src = imageUrl;
            performerAImage.alt = currentPerformers[0].name || 'Performer A';
            
            // Pre-load image to get its dimensions
            const img = new Image();
            img.onload = function() {
                // Add appropriate class based on image dimensions
                if (img.height > img.width) {
                    performerAImage.classList.add('max-h-96');
                    performerAImage.classList.remove('max-w-full');
                } else {
                    performerAImage.classList.add('max-w-full');
                    performerAImage.classList.remove('max-h-96');
                }
            };
            img.src = imageUrl;
        } else {
            performerAImage.src = '/assets/images/placeholder-profile.jpg';
        }
        
        // Set details for performer A
        const detailsA = [];
        if (currentPerformers[0].gender) detailsA.push(currentPerformers[0].gender);
        if (currentPerformers[0].ethnicity) detailsA.push(currentPerformers[0].ethnicity);
        if (currentPerformers[0].measurements) detailsA.push(currentPerformers[0].measurements);
        performerADetails.textContent = detailsA.join(' • ') || 'No details available';
        
        // Update UI with performer B
        performerBCard.setAttribute('data-performer-id', currentPerformers[1].id);
        performerBName.textContent = currentPerformers[1].name || 'Unknown Performer';
        
        // Set image for performer B
        if (currentPerformers[1].images && currentPerformers[1].images.length > 0) {
            const imageUrl = currentPerformers[1].images[0];
            console.log('Setting image B to:', imageUrl);
            performerBImage.src = imageUrl;
            performerBImage.alt = currentPerformers[1].name || 'Performer B';
            
            // Pre-load image to get its dimensions
            const img = new Image();
            img.onload = function() {
                // Add appropriate class based on image dimensions
                if (img.height > img.width) {
                    performerBImage.classList.add('max-h-96');
                    performerBImage.classList.remove('max-w-full');
                } else {
                    performerBImage.classList.add('max-w-full');
                    performerBImage.classList.remove('max-h-96');
                }
            };
            img.src = imageUrl;
        } else {
            performerBImage.src = '/assets/images/placeholder-profile.jpg';
        }
        
        // Set details for performer B
        const detailsB = [];
        if (currentPerformers[1].gender) detailsB.push(currentPerformers[1].gender);
        if (currentPerformers[1].ethnicity) detailsB.push(currentPerformers[1].ethnicity);
        if (currentPerformers[1].measurements) detailsB.push(currentPerformers[1].measurements);
        performerBDetails.textContent = detailsB.join(' • ') || 'No details available';
    }
    
    /**
     * Handle preferences data
     */
    function handlePreferencesData(data) {
        if (!data.preferences) {
            console.error('No preferences data found');
            return;
        }
        
        const preferences = data.preferences;
        
        // Clear previous preferences data
        preferencesData.innerHTML = '';
        
        // Helper function to create preference cards
        const createPreferenceCard = (title, content) => {
            const card = document.createElement('div');
            card.className = 'bg-darkPrimairy rounded-lg p-4 shadow';
            
            const titleEl = document.createElement('h3');
            titleEl.className = 'text-lg font-semibold mb-2 text-secondary';
            titleEl.textContent = title;
            
            const contentEl = document.createElement('div');
            contentEl.className = 'text-TextWhite';
            contentEl.innerHTML = content;
            
            card.appendChild(titleEl);
            card.appendChild(contentEl);
            
            return card;
        };
        
        // Process categorical preferences (gender, ethnicity, hair_color, eye_color, cup_size)
        ['gender', 'ethnicity', 'hair_color', 'eye_color', 'cup_size'].forEach(attr => {
            if (preferences[attr] && Object.keys(preferences[attr]).length > 0) {
                let content = '<ul class="list-disc list-inside">';
                
                // Get total count for percentage calculation
                const total = Object.values(preferences[attr]).reduce((sum, count) => sum + count, 0);
                
                // Sort by count (highest first)
                const sorted = Object.entries(preferences[attr])
                    .sort((a, b) => b[1] - a[1])
                    .slice(0, 3); // Top 3 preferences
                
                sorted.forEach(([value, count]) => {
                    const percentage = Math.round((count / total) * 100);
                    content += `<li>${value} <span class="text-gray-400">(${percentage}%)</span></li>`;
                });
                
                content += '</ul>';
                
                // Format title (capitalize first letter, replace underscores with spaces)
                const title = attr.charAt(0).toUpperCase() + attr.slice(1).replace('_', ' ');
                
                preferencesData.appendChild(createPreferenceCard(title, content));
            }
        });
        
        // Process numerical preferences (height, weight)
        ['height', 'weight'].forEach(attr => {
            if (preferences[attr]) {
                let content = `<p class="text-center text-xl">${preferences[attr]}`;
                
                // Add units
                if (attr === 'height') content += ' cm';
                if (attr === 'weight') content += ' kg';
                
                content += '</p>';
                
                const title = attr.charAt(0).toUpperCase() + attr.slice(1);
                preferencesData.appendChild(createPreferenceCard(title, content));
            }
        });
        
        // Process boolean preferences (fake_boobs)
        if ('fake_boobs' in preferences) {
            const percentage = preferences.fake_boobs;
            let content = `<p class="text-center text-xl">${percentage}%</p>`;
            preferencesData.appendChild(createPreferenceCard('Fake Boobs Preference', content));
        }
    }
    
    // Helper functions
    
    /**
     * Update progress bar
     */
    function updateProgressBar() {
        // Calculate progress (arbitrary scale - 100 choices = 100%)
        const maxChoices = 100;
        const progress = Math.min(100, (userChoices / maxChoices) * 100);
        progressBar.style.width = `${progress}%`;
    }
    
    /**
     * Set loading state
     */
    function setLoadingState(isLoading) {
        if (isLoading) {
            // Add loading class to game container
            gameContainer.classList.add('opacity-60');
            // Disable buttons
            document.querySelectorAll('button').forEach(btn => {
                btn.disabled = true;
            });
        } else {
            // Remove loading class
            gameContainer.classList.remove('opacity-60');
            // Enable buttons
            document.querySelectorAll('button').forEach(btn => {
                btn.disabled = false;
            });
        }
    }
    
    /**
     * Set error state
     */
    function setErrorState(message) {
        // Display error message to user
        // This could be implemented as a toast, alert, or inline message
        console.error('Error:', message);
        
        // Simple alert for now - could be replaced with nicer UI
        if (message) {
            alert(message);
        }
    }
    
    /**
     * Handle WebSocket errors
     */
    function handleWebSocketError(error) {
        console.error('WebSocket error:', error);
        // Most errors will be handled by the WebSocket manager with automatic fallback
    }
});