// WebSocket connection for Smash or Pass
// Avoid re-declaring variables if they might be declared elsewhere
if (typeof socket === 'undefined') {
    var socket;
}
if (typeof choiceCount === 'undefined') {
    var choiceCount = 0;
}
if (typeof currentPerformers === 'undefined') {
    var currentPerformers = {};
}
if (typeof isConnecting === 'undefined') {
    var isConnecting = false;
}
if (typeof reconnectAttempts === 'undefined') {
    var reconnectAttempts = 0;
}

document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on the smash or pass page (with the right elements)
    if (document.getElementById('performer-a') && document.getElementById('performer-b')) {
        initSmashOrPass();
    }
});

function initSmashOrPass() {
    connectWebSocket();
    
    // Set up event listeners
    document.querySelectorAll('.smash-button').forEach(button => {
        button.addEventListener('click', function() {
            const option = this.getAttribute('data-option');
            makeChoice(option);
        });
    });
    
    const passBothBtn = document.getElementById('pass-both');
    if (passBothBtn) {
        passBothBtn.addEventListener('click', function() {
            makeChoice('pass');
        });
    }
    
    // Auto-apply filter when gender is selected
    const genderFilterSelect = document.getElementById('gender-filter');
    if (genderFilterSelect) {
        genderFilterSelect.addEventListener('change', function() {
            applyFilters();
        });
    }
    
    // Keep the Apply Filters button for accessibility
    const applyFiltersBtn = document.getElementById('apply-filters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            applyFilters();
        });
    }
    
    const viewPreferencesBtn = document.getElementById('view-preferences');
    if (viewPreferencesBtn) {
        viewPreferencesBtn.addEventListener('click', function() {
            socket.send(JSON.stringify({
                action: 'get_preferences'
            }));
            
            document.getElementById('game-container').classList.add('hidden');
            document.getElementById('results-container').classList.remove('hidden');
        });
    }
    
    const continuePlayingBtn = document.getElementById('continue-playing');
    if (continuePlayingBtn) {
        continuePlayingBtn.addEventListener('click', function() {
            document.getElementById('results-container').classList.add('hidden');
            document.getElementById('game-container').classList.remove('hidden');
        });
    }
}

function connectWebSocket() {
    // Prevent multiple connection attempts
    if (isConnecting) return;
    isConnecting = true;
    
    // Check if we've tried too many times
    if (reconnectAttempts > 5) {
        console.error("Too many reconnection attempts, using fallback mode");
        showFallbackMode();
        return;
    }
    
    reconnectAttempts++;
    
    // Use secure WebSocket if page is loaded over HTTPS
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const host = window.location.hostname;
    
    try {
        console.log(`Attempting to connect to WebSocket on ${protocol}//${host}:8090`); // Updated to port 8090
        socket = new WebSocket(`${protocol}//${host}:8090`); // Updated to port 8090
        
        socket.onopen = function(e) {
            console.log("WebSocket connection established");
            isConnecting = false;
            reconnectAttempts = 0; // Reset counter on successful connection
        };
        
        socket.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                
                if (data.type === 'performers') {
                    displayPerformers(data.performers);
                } else if (data.type === 'preferences') {
                    displayPreferences(data.preferences);
                } else if (data.type === 'error') {
                    alert(data.message);
                }
            } catch (e) {
                console.error("Error handling message:", e);
            }
        };
        
        socket.onclose = function(event) {
            console.log("WebSocket connection closed");
            isConnecting = false;
            
            // Try to reconnect after 3 seconds
            if (reconnectAttempts < 6) {
                console.log(`Reconnection attempt ${reconnectAttempts} scheduled in 3 seconds...`);
                setTimeout(connectWebSocket, 3000);
            } else {
                showFallbackMode();
            }
        };
        
        socket.onerror = function(error) {
            console.error("WebSocket error:", error);
            isConnecting = false;
        };
    } catch (e) {
        console.error("Error creating WebSocket:", e);
        isConnecting = false;
        
        // Try again after a delay
        if (reconnectAttempts < 6) {
            setTimeout(connectWebSocket, 3000);
        } else {
            showFallbackMode();
        }
    }
}

function displayPerformers(performers) {
    if (performers.length < 2) {
        alert("Not enough performers available. Try changing your filters.");
        return;
    }
    
    console.log("Displaying performers:", performers);
    
    // Store current performers
    currentPerformers = {
        a: performers[0],
        b: performers[1]
    };
    
    // Update performer A
    document.getElementById('performer-a').dataset.performerId = performers[0].id;
    document.getElementById('performer-a-name').textContent = performers[0].name;
    
    const imageA = document.getElementById('performer-a-image');
    if (performers[0].images && performers[0].images[0]) {
        console.log("Setting image A to:", performers[0].images[0]);
        imageA.src = performers[0].images[0];
    } else {
        console.log("Using placeholder for image A");
        imageA.src = '/assets/images/placeholder-profile.jpg';
    }
    
    // Add error handling for image A
    imageA.onerror = function() {
        console.error("Failed to load image A:", this.src);
        this.src = '/assets/images/placeholder-profile.jpg';
    };
    
    let detailsA = [];
    if (performers[0].gender) detailsA.push(performers[0].gender);
    if (performers[0].ethnicity) detailsA.push(performers[0].ethnicity);
    if (performers[0].height) detailsA.push(performers[0].height);
    document.getElementById('performer-a-details').textContent = detailsA.join(' • ');
    
    // Update performer B
    document.getElementById('performer-b').dataset.performerId = performers[1].id;
    document.getElementById('performer-b-name').textContent = performers[1].name;
    
    const imageB = document.getElementById('performer-b-image');
    if (performers[1].images && performers[1].images[0]) {
        console.log("Setting image B to:", performers[1].images[0]);
        imageB.src = performers[1].images[0];
    } else {
        console.log("Using placeholder for image B");
        imageB.src = '/assets/images/placeholder-profile.jpg';
    }
    
    // Add error handling for image B
    imageB.onerror = function() {
        console.error("Failed to load image B:", this.src);
        this.src = '/assets/images/placeholder-profile.jpg';
    };
    
    let detailsB = [];
    if (performers[1].gender) detailsB.push(performers[1].gender);
    if (performers[1].ethnicity) detailsB.push(performers[1].ethnicity);
    if (performers[1].height) detailsB.push(performers[1].height);
    document.getElementById('performer-b-details').textContent = detailsB.join(' • ');
}

function makeChoice(option) {
    if (!currentPerformers.a || !currentPerformers.b) {
        return;
    }
    
    let chosenId, rejectedId;
    
    if (option === 'a') {
        chosenId = currentPerformers.a.id;
        rejectedId = currentPerformers.b.id;
    } else if (option === 'b') {
        chosenId = currentPerformers.b.id;
        rejectedId = currentPerformers.a.id;
    } else {
        // Pass both
        // We'll still request new performers but not record a preference
        socket.send(JSON.stringify({
            action: 'next_pair'
        }));
        return;
    }
    
    // Send choice to server
    socket.send(JSON.stringify({
        action: 'choice',
        chosen_id: chosenId,
        rejected_id: rejectedId
    }));
    
    
    // Update progress bar (max out at 100%)
    // Change multiplier from 2 to 1 to increment progress by 1% per choice
    const progressPercent = Math.min(choiceCount, 100);
    document.getElementById('progress-bar').style.width = progressPercent + '%';
}

function applyFilters() {
    const genderFilterSelect = document.getElementById('gender-filter');
    const genderFilter = genderFilterSelect ? genderFilterSelect.value : '';
    
    // Add visual feedback to the dropdown
    if (genderFilterSelect) {
        genderFilterSelect.classList.add('changed');
        setTimeout(() => {
            genderFilterSelect.classList.remove('changed');
        }, 1000);
    }
    
    // Log the filter being applied
    console.log("Applying gender filter:", genderFilter);
    
    // Set the filter if socket is available
    if (socket && socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({
            action: 'set_filter',
            filter: {
                gender: genderFilter
            }
        }));
        
        // Get new performers with the applied filter
        socket.send(JSON.stringify({
            action: 'next_pair'
        }));
    } else {
        console.warn("WebSocket not available, using AJAX fallback");
        ajaxGetPerformers(genderFilter);
    }
}

function displayPreferences(preferences) {
    // Show results container
    document.getElementById('game-container').classList.add('hidden');
    document.getElementById('results-container').classList.remove('hidden');
    
    const preferencesContainer = document.getElementById('preferences-data');
    preferencesContainer.innerHTML = '';
    
    // Display preferences data
    const preferenceSections = [
        { key: 'gender', title: 'Gender' },
        { key: 'ethnicity', title: 'Ethnicity' },
        { key: 'hair_color', title: 'Hair Color' },
        { key: 'eye_color', title: 'Eye Color' },
        { key: 'cup_size', title: 'Cup Size' }
    ];
    
    // Loop through and display categorical preferences
    preferenceSections.forEach(section => {
        if (preferences[section.key] && Object.keys(preferences[section.key]).length > 0) {
            const topValue = Object.keys(preferences[section.key])[0];
            const percent = Math.round((preferences[section.key][topValue] / choiceCount) * 100);
            
            const el = document.createElement('div');
            el.className = 'preference-item bg-darkPrimairy p-4 rounded-lg';
            el.innerHTML = `
                <h3 class="font-semibold text-xl">${section.title}</h3>
                <p class="text-2xl font-bold text-secondary">${topValue}</p>
                <p class="text-sm opacity-80">${percent}% of your choices</p>
            `;
            preferencesContainer.appendChild(el);
        }
    });
    
    // Display numeric and boolean preferences
    if (preferences.fake_boobs !== undefined) {
        const el = document.createElement('div');
        el.className = 'preference-item bg-darkPrimairy p-4 rounded-lg';
        el.innerHTML = `
            <h3 class="font-semibold text-xl">Fake Boobs</h3>
            <p class="text-2xl font-bold text-secondary">${preferences.fake_boobs}%</p>
            <p class="text-sm opacity-80">Preference for enhanced</p>
        `;
        preferencesContainer.appendChild(el);
    }
    
    if (preferences.height) {
        const el = document.createElement('div');
        el.className = 'preference-item bg-darkPrimairy p-4 rounded-lg';
        el.innerHTML = `
            <h3 class="font-semibold text-xl">Average Height</h3>
            <p class="text-2xl font-bold text-secondary">${preferences.height} cm</p>
            <p class="text-sm opacity-80">Of your choices</p>
        `;
        preferencesContainer.appendChild(el);
    }
}

// Fallback mode when WebSocket is not available
function showFallbackMode() {
    console.log("Switching to fallback mode using AJAX");
    
    // Show a notification to the user
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-secondary text-white p-4 rounded-lg shadow-lg z-50';
    notification.innerHTML = `
        <p>Using alternative connection method...</p>
        <button class="ml-2 font-bold" onclick="this.parentNode.remove()">×</button>
    `;
    document.body.appendChild(notification);
    
    // Set timeout to remove the notification
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
    
    // First test the API
    fetch('/api/test.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("API test successful:", data);
            // API is working, proceed with loading performers
            ajaxGetPerformers();
            
            // Replace WebSocket-specific event handlers with AJAX versions
            document.querySelectorAll('.smash-button').forEach(button => {
                button.removeEventListener('click', null);
                button.addEventListener('click', function() {
                    const option = this.getAttribute('data-option');
                    if (option === 'a') {
                        ajaxSaveChoice(currentPerformers.a.id, currentPerformers.b.id);
                    } else if (option === 'b') {
                        ajaxSaveChoice(currentPerformers.b.id, currentPerformers.a.id);
                    }
                });
            });
            
            const passBothBtn = document.getElementById('pass-both');
            if (passBothBtn) {
                passBothBtn.removeEventListener('click', null);
                passBothBtn.addEventListener('click', function() {
                    ajaxGetPerformers();
                });
            }
            
            // Add auto-apply filter logic for gender select
            const genderFilterSelect = document.getElementById('gender-filter');
            if (genderFilterSelect) {
                genderFilterSelect.removeEventListener('change', null);
                genderFilterSelect.addEventListener('change', function() {
                    const genderFilter = this.value;
                    ajaxGetPerformers(genderFilter);
                });
            }
            
            const applyFiltersBtn = document.getElementById('apply-filters');
            if (applyFiltersBtn) {
                applyFiltersBtn.removeEventListener('click', null);
                applyFiltersBtn.addEventListener('click', function() {
                    const genderFilter = document.getElementById('gender-filter').value;
                    ajaxGetPerformers(genderFilter);
                });
            }
            
            const viewPreferencesBtn = document.getElementById('view-preferences');
            if (viewPreferencesBtn) {
                viewPreferencesBtn.removeEventListener('click', null);
                viewPreferencesBtn.addEventListener('click', function() {
                    ajaxGetPreferences().then(() => {
                        document.getElementById('game-container').classList.add('hidden');
                        document.getElementById('results-container').classList.remove('hidden');
                    });
                });
            }
            
            const continuePlayingBtn = document.getElementById('continue-playing');
            if (continuePlayingBtn) {
                continuePlayingBtn.removeEventListener('click', null);
                continuePlayingBtn.addEventListener('click', function() {
                    document.getElementById('results-container').classList.add('hidden');
                    document.getElementById('game-container').classList.remove('hidden');
                });
            }
        })
        .catch(error => {
            console.error("API test failed:", error);
            // API is not working, show error message
            document.getElementById('game-container').innerHTML = `
                <div class="bg-primairy rounded-lg p-6 shadow-lg text-center">
                    <h2 class="text-2xl font-bold mb-4">Connection Error</h2>
                    <p class="mb-4">We're having trouble connecting to the server.</p>
                    <p class="mb-4">Error: ${error.message}</p>
                    <button id="refresh-page" class="bg-gradient-to-r from-secondary to-tertery text-white px-6 py-3 rounded-lg hover:opacity-90 transition">
                        Refresh Page
                    </button>
                </div>
            `;
            
            document.getElementById('refresh-page').addEventListener('click', function() {
                window.location.reload();
            });
        });
}

// AJAX fallback methods when WebSocket isn't available
function ajaxGetPerformers(gender = null) {
    // Construct the URL with proper query parameters
    let url = `/api/performers.php?action=random_performers`;
    if (gender) {
        url += `&gender=${encodeURIComponent(gender)}`;
    }
    
    console.log("Fetching performers from:", url);
    
    return fetch(url)
        .then(response => {
            console.log("Response status:", response.status);
            console.log("Response headers:", response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            
            // Check content type
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('text/html')) {
                // If we received HTML instead of JSON, try the fallback image API
                return {
                    type: 'performers',
                    performers: [
                        {
                            id: 'fallback-1',
                            name: 'Model A',
                            gender: 'Female',
                            ethnicity: 'Caucasian',
                            height: '170 cm',
                            images: ['/api/create_placeholder.php?id=1']
                        },
                        {
                            id: 'fallback-2',
                            name: 'Model B',
                            gender: 'Female',
                            ethnicity: 'Caucasian',
                            height: '175 cm',
                            images: ['/api/create_placeholder.php?id=2']
                        }
                    ],
                    fallback_mode: true
                };
            }
            
            return response.json();
        })
        .then(data => {
            console.log("Received performer data:", data);
            if (data.type === 'performers') {
                displayPerformers(data.performers);
                
                if (data.fallback_mode) {
                    console.log("Using fallback data mode");
                    showFallbackNotification();
                }
            }
            return data;
        })
        .catch(error => {
            console.error("Error fetching performers:", error);
            // Display error message to user
            document.getElementById('game-container').innerHTML = `
                <div class="bg-primairy rounded-lg p-6 shadow-lg text-center">
                    <h2 class="text-2xl font-bold mb-4">Failed to Load Performers</h2>
                    <p class="mb-4">We're having trouble connecting to the server.</p>
                    <p class="mb-4">Error: ${error.message}</p>
                    <button id="try-again" class="bg-gradient-to-r from-secondary to-tertery text-white px-6 py-3 rounded-lg hover:opacity-90 transition">
                        Try Again
                    </button>
                </div>
            `;
            
            document.getElementById('try-again').addEventListener('click', function() {
                ajaxGetPerformers(gender);
            });
        });
}

function showFallbackNotification() {
    const notification = document.createElement('div');
    notification.className = 'fixed bottom-4 left-4 bg-secondary text-white p-4 rounded-lg shadow-lg z-50';
    notification.innerHTML = `
        <p>Using demo mode with placeholder data.</p>
        <button class="ml-2 font-bold" onclick="this.parentNode.remove()">×</button>
    `;
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function ajaxSaveChoice(chosenId, rejectedId) {
    return fetch('/api/performers.php?action=save_choice', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            chosen_id: chosenId,
            rejected_id: rejectedId
        }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }
        return response.json();
    })
    .then(() => {
        // Update choice count and progress bar
        choiceCount++;
        document.getElementById('choice-count').textContent = choiceCount;
        
        // Update progress bar (max out at 100%)
        // Change multiplier from 2 to 1 to increment progress by 1% per choice
        const progressPercent = Math.min(choiceCount, 100);
        document.getElementById('progress-bar').style.width = progressPercent + '%';
        
        // Get next performers
        return ajaxGetPerformers();
    })
    .catch(error => {
        console.error("Error saving choice:", error);
    });
}

function ajaxGetPreferences() {
    return fetch('/api/performers.php?action=get_preferences')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.type === 'preferences') {
                displayPreferences(data.preferences);
            }
            return data;
        })
        .catch(error => {
            console.error("Error fetching preferences:", error);
        });
}