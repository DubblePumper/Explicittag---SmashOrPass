/**
 * SmashOrPass Game Logic
 * Handles the UI and game logic for the Smash or Pass feature
 */
document.addEventListener('DOMContentLoaded', async function() {
    // Elements
    const gameContainer = document.getElementById('game-container');
    const resultsContainer = document.getElementById('results-container');
    const currentPerformerCard = document.getElementById('current-performer');
    const nextPerformerCard = document.getElementById('next-performer');
    const performerImage = document.getElementById('performer-image');
    const nextPerformerImage = document.getElementById('next-performer-image');
    const performerName = document.getElementById('performer-name');
    const nextPerformerName = document.getElementById('next-performer-name');
    const performerDetails = document.getElementById('performer-details');
    const nextPerformerDetails = document.getElementById('next-performer-details');
    const viewPreferencesButton = document.getElementById('view-preferences');
    const continuePlayingButton = document.getElementById('continue-playing');
    const preferencesData = document.getElementById('preferences-data');
    const choiceCount = document.getElementById('choice-count');
    const progressBar = document.getElementById('progress-bar');
    const genderFilter = document.getElementById('gender-filter');
    const blurToggle = document.getElementById('blur-toggle');
    const blurToggleHandle = document.getElementById('blur-toggle-handle');
    const swipeContainer = document.getElementById('performer-swipe-container');
    const swipeLeftIndicator = document.getElementById('swipe-left-indicator');
    const swipeRightIndicator = document.getElementById('swipe-right-indicator');
    const passButton = document.getElementById('pass-button');
    const smashButton = document.getElementById('smash-button');
    
    // Game state
    let currentPerformer = null;
    let nextPerformer = null;
    let userChoices = 0;
    let gameActive = true;
    let isProcessingChoice = false;
    let blurEnabled = localStorage.getItem('blurEnabled') !== 'false';
    
    // Swipe state
    let touchStartX = 0;
    let touchEndX = 0;
    let isDragging = false;
    let currentTranslateX = 0;
    
    // Preloading optimization
    let preloadedPerformers = [];
    let preloadedImages = new Map(); // Using Map for better performance
    let performerHistory = new Set(); // Using Set for faster lookups
    let preloadBatchSize = 5; // Number of performers to preload at once
    let minPreloadThreshold = 3; // When to trigger next preload
    
    // Initialize blur state and set up event listeners
    updateBlurState();
    await loadWebSocketManager();
    await initGame();
    setupSwipeListeners();
    setupButtonListeners();

    /**
     * Load WebSocket Manager script
     */
    async function loadWebSocketManager() {
        if (window.wsManager) {
            console.log('WebSocket manager already loaded');
            return;
        }
        
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/assets/js/utils/websocket.js';
            script.async = true;
            
            script.onload = () => {
                console.log('WebSocket manager script loaded successfully');
                resolve();
            };
            
            script.onerror = (error) => {
                console.error('Failed to load WebSocket manager script:', error);
                reject(error);
            };
            
            document.head.appendChild(script);
        });
    }
    
    /**
     * Initialize the game
     */
    async function initGame() {
        try {
            if (window.wsManager) {
                await wsManager.init();
                console.log('Attempting to connect to WebSocket on', wsManager.config?.url);
                
                wsManager.on('performers', handlePerformersData);
                wsManager.on('preferences', handlePreferencesData);
                wsManager.on('error', handleWebSocketError);
                wsManager.on('connect', () => {
                    console.log('WebSocket connected successfully');
                    requestNextBatch();
                });
            }
            
            await fetchPerformers();
        } catch (error) {
            console.error('Error initializing game:', error);
            setErrorState('Failed to initialize the game. Please refresh the page and try again.');
        }
    }

    /**
     * Set up swipe listeners for touch and mouse events
     */
    function setupSwipeListeners() {
        swipeContainer.addEventListener('touchstart', handleTouchStart, { passive: true });
        swipeContainer.addEventListener('touchmove', handleTouchMove, { passive: false });
        swipeContainer.addEventListener('touchend', handleTouchEnd);

        swipeContainer.addEventListener('mousedown', handleMouseDown);
        swipeContainer.addEventListener('mousemove', handleMouseMove);
        swipeContainer.addEventListener('mouseup', handleMouseUp);
        swipeContainer.addEventListener('mouseleave', handleMouseUp);
    }

    /**
     * Set up button listeners
     */
    function setupButtonListeners() {
        passButton.addEventListener('click', () => makeChoice(false));
        smashButton.addEventListener('click', () => makeChoice(true));
        blurToggle.addEventListener('click', () => {
            blurEnabled = !blurEnabled;
            localStorage.setItem('blurEnabled', blurEnabled);
            updateBlurState();
        });
        genderFilter.addEventListener('change', () => {
            const gender = genderFilter.value;
            applyFilters({ gender });
        });
        viewPreferencesButton.addEventListener('click', fetchPreferences);
        continuePlayingButton.addEventListener('click', () => {
            gameContainer.style.display = 'block';
            resultsContainer.style.display = 'none';
            gameActive = true;
            if (!currentPerformer) {
                fetchPerformers();
            }
        });
    }

    // Touch and mouse event handlers
    function handleTouchStart(e) {
        touchStartX = e.touches[0].clientX;
        startDrag();
    }

    function handleTouchMove(e) {
        if (!isDragging) return;
        e.preventDefault();
        touchEndX = e.touches[0].clientX;
        updateSwipe();
    }

    function handleTouchEnd() {
        if (!isDragging) return;
        endDrag();
    }

    function handleMouseDown(e) {
        touchStartX = e.clientX;
        startDrag();
    }

    function handleMouseMove(e) {
        if (!isDragging) return;
        touchEndX = e.clientX;
        updateSwipe();
    }

    function handleMouseUp() {
        if (!isDragging) return;
        endDrag();
    }

    function startDrag() {
        if (isProcessingChoice) return;
        isDragging = true;
        currentPerformerCard.style.transition = 'none';
    }

    function updateSwipe() {
        const deltaX = touchEndX - touchStartX;
        currentTranslateX = deltaX;
        
        currentPerformerCard.style.transform = `translateX(${deltaX}px)`;
        
        const swipeThreshold = window.innerWidth * 0.3;
        if (deltaX > swipeThreshold) {
            swipeRightIndicator.style.opacity = '1';
            swipeLeftIndicator.style.opacity = '0';
        } else if (deltaX < -swipeThreshold) {
            swipeLeftIndicator.style.opacity = '1';
            swipeRightIndicator.style.opacity = '0';
        } else {
            swipeLeftIndicator.style.opacity = '0';
            swipeRightIndicator.style.opacity = '0';
        }
    }

    function endDrag() {
        isDragging = false;
        currentPerformerCard.style.transition = 'transform 0.3s ease-out';
        
        const swipeThreshold = window.innerWidth * 0.3;
        if (currentTranslateX > swipeThreshold) {
            makeChoice(true);
        } else if (currentTranslateX < -swipeThreshold) {
            makeChoice(false);
        } else {
            currentPerformerCard.style.transform = 'translateX(0)';
        }
        
        swipeLeftIndicator.style.opacity = '0';
        swipeRightIndicator.style.opacity = '0';
        currentTranslateX = 0;
    }

    /**
     * Make a choice for the current performer
     * @param {boolean} isSmash True for smash, false for pass
     */
    async function makeChoice(isSmash) {
        if (isProcessingChoice || !currentPerformer) return;
        
        try {
            isProcessingChoice = true;
            
            performerHistory.add(currentPerformer.id);
            
            const choiceData = {
                action: 'choice',
                chosen_id: isSmash ? currentPerformer.id : null,
                rejected_id: isSmash ? null : currentPerformer.id,
                filter: { gender: genderFilter.value }
            };
            
            const direction = isSmash ? 1 : -1;
            currentPerformerCard.style.transform = `translateX(${direction * window.innerWidth}px)`;
            
            let response;
            if (window.wsManager && wsManager.isWebSocketAvailable()) {
                response = await wsManager.send(choiceData);
            } else {
                response = await fetch('/api/performers.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(choiceData)
                }).then(r => r.json());
            }
            
            userChoices++;
            choiceCount.textContent = userChoices;
            updateProgressBar();
            
            await showNextPerformer();
            
            if (preloadedPerformers.length < minPreloadThreshold) {
                requestNextBatch();
            }
            
        } catch (error) {
            console.error('Error making choice:', error);
            setErrorState('Failed to save your choice. Please try again.');
        } finally {
            isProcessingChoice = false;
        }
    }

    /**
     * Show the next performer with animation
     */
    async function showNextPerformer() {
        currentPerformerCard.style.transition = 'none';
        currentPerformerCard.style.transform = 'translateX(-100%)';
        
        if (preloadedPerformers.length > 0) {
            currentPerformer = preloadedPerformers.shift();
        } else {
            await fetchPerformers();
            return;
        }
        
        updatePerformerCard(currentPerformerCard, performerImage, performerName, performerDetails, currentPerformer);
        
        requestAnimationFrame(() => {
            currentPerformerCard.style.transition = 'transform 0.3s ease-out';
            currentPerformerCard.style.transform = 'translateX(0)';
        });
        
        if (preloadedPerformers.length > 0) {
            nextPerformer = preloadedPerformers[0];
            updatePerformerCard(nextPerformerCard, nextPerformerImage, nextPerformerName, nextPerformerDetails, nextPerformer);
        }
    }

    /**
     * Update a performer card with new data
     */
    function updatePerformerCard(card, image, nameEl, detailsEl, performer) {
        if (!performer) return;
        
        card.setAttribute('data-performer-id', performer.id);
        nameEl.textContent = performer.name || 'Unknown Performer';
        
        if (performer.images && performer.images.length > 0) {
            const imageUrl = performer.images[0];
            image.src = imageUrl;
            image.alt = performer.name || 'Performer';
            
            if (preloadedImages.has(imageUrl)) {
                const preloadedImg = preloadedImages.get(imageUrl);
                updateImageDimensions(image, preloadedImg);
            } else {
                image.onload = () => updateImageDimensions(image, image);
            }
        } else {
            image.src = '/assets/images/placeholder-profile.jpg';
        }
        
        const details = [];
        if (performer.gender) details.push(performer.gender);
        if (performer.ethnicity) details.push(performer.ethnicity);
        if (performer.measurements) details.push(performer.measurements);
        detailsEl.textContent = details.join(' • ') || 'No details available';
    }

    /**
     * Update image dimensions based on aspect ratio
     */
    function updateImageDimensions(imgEl, loadedImg) {
        if (loadedImg.height > loadedImg.width) {
            imgEl.classList.add('max-h-[450px]');
            imgEl.classList.remove('max-w-full');
        } else {
            imgEl.classList.add('max-w-full');
            imgEl.classList.remove('max-h-[450px]');
        }
    }

    function updateBlurState() {
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
    
    function handlePerformersData(data) {
        if (!data.performers || data.performers.length === 0) {
            console.error('No performers in the response');
            return;
        }
        
        preloadedPerformers.push(...data.performers);
        
        if (!currentPerformer) {
            showNextPerformer();
        }
        
        data.performers.forEach(performer => {
            if (performer.images) {
                performer.images.forEach(imageUrl => {
                    if (!preloadedImages.has(imageUrl)) {
                        const img = new Image();
                        img.src = imageUrl;
                        img.onload = () => preloadedImages.set(imageUrl, img);
                        img.onerror = () => {
                            console.error(`Failed to preload image: ${imageUrl}`);
                            performer.images = ['/assets/images/placeholder-profile.jpg'];
                        };
                    }
                });
            }
        });
    }
    
    async function fetchPerformers(filters = null) {
        try {
            setLoadingState(true);
            
            if (preloadedPerformers.length > 0 && !filters) {
                handlePerformersData({ performers: preloadedPerformers });
                setLoadingState(false);
                return;
            }
            
            if (!filters && genderFilter) {
                filters = { gender: genderFilter.value };
            }
            
            let response;
            if (window.wsManager && wsManager.isWebSocketAvailable()) {
                response = await wsManager.send({
                    action: 'random_performers',
                    filter: filters
                });
            } else {
                let url = '/api/performers.php?action=random_performers';
                if (filters?.gender) {
                    url += `&gender=${encodeURIComponent(filters.gender)}`;
                }
                const fetchResponse = await fetch(url);
                response = await fetchResponse.json();
            }
            
            if (response && response.performers) {
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
    
    function handlePreferencesData(data) {
        if (!data.preferences) {
            console.error('No preferences data found');
            return;
        }
        
        const preferences = data.preferences;
        
        preferencesData.innerHTML = '';
        
        if (userChoices > 5) {
            const summaryDiv = document.createElement('div');
            summaryDiv.className = 'bg-primairy/30 rounded-lg p-4 mb-6 w-full col-span-full text-center';
            
            const topGender = getTopPreference(preferences.gender);
            const topEthnicity = getTopPreference(preferences.ethnicity);
            
            let summaryText = `Your ideal performer: `;
            
            if (topGender) {
                summaryText += topGender;
            }
            
            if (topEthnicity) {
                summaryText += topGender ? `, ${topEthnicity}` : topEthnicity;
            }
            
            if (preferences.height) {
                summaryText += `, ${preferences.height}cm tall`;
            }
            
            summaryDiv.innerHTML = `<p class="text-xl font-semibold">${summaryText}</p>`;
            preferencesData.appendChild(summaryDiv);
        }
        
        const createPreferenceCard = (title, content) => {
            const card = document.createElement('div');
            card.className = 'bg-darkPrimairy rounded-lg p-4 shadow hover:shadow-lg transition-shadow duration-300';
            
            const titleEl = document.createElement('h3');
            titleEl.className = 'text-lg font-semibold mb-3 text-secondary';
            titleEl.textContent = title;
            
            const contentEl = document.createElement('div');
            contentEl.className = 'text-TextWhite';
            contentEl.innerHTML = content;
            
            card.appendChild(titleEl);
            card.appendChild(contentEl);
            
            return card;
        };
        
        ['gender', 'ethnicity', 'hair_color', 'eye_color', 'cup_size'].forEach(attr => {
            if (preferences[attr] && Object.keys(preferences[attr]).length > 0) {
                let content = '<ul class="list-disc list-inside">';
                
                const total = Object.values(preferences[attr]).reduce((sum, count) => sum + count, 0);
                
                const sorted = Object.entries(preferences[attr])
                    .sort((a, b) => b[1] - a[1])
                    .slice(0, 3);
                
                sorted.forEach(([value, count]) => {
                    const percentage = Math.round((count / total) * 100);
                    const progressBar = `
                        <div class="w-full bg-BgDark rounded-full h-2 mt-1 mb-2">
                            <div class="bg-gradient-to-r from-secondary to-tertery h-2 rounded-full" style="width: ${percentage}%"></div>
                        </div>
                    `;
                    content += `<li class="mb-2">
                        <span class="font-medium">${value}</span> <span class="text-gray-400">(${percentage}%)</span>
                        ${progressBar}
                    </li>`;
                });
                
                content += '</ul>';
                
                const title = attr.charAt(0).toUpperCase() + attr.slice(1).replace('_', ' ');
                
                preferencesData.appendChild(createPreferenceCard(title, content));
            }
        });
        
        ['height', 'weight'].forEach(attr => {
            if (preferences[attr]) {
                const unit = attr === 'height' ? ' cm' : ' kg';
                
                let content = `
                    <div class="flex flex-col items-center">
                        <div class="text-3xl font-bold mb-2">${preferences[attr]}${unit}</div>
                        <div class="text-sm text-gray-400">Based on your choices</div>
                        ${attr === 'height' ? createHeightVisualization(preferences[attr]) : ''}
                    </div>
                `;
                
                const title = attr.charAt(0).toUpperCase() + attr.slice(1);
                preferencesData.appendChild(createPreferenceCard(title, content));
            }
        });
        
        if ('fake_boobs' in preferences) {
            const percentage = preferences.fake_boobs;
            let content = `
                <div class="flex flex-col items-center">
                    <div class="w-36 h-36 relative mb-4">
                        <div class="absolute inset-0 rounded-full bg-BgDark"></div>
                        <svg class="absolute inset-0" viewBox="0 0 36 36">
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none" stroke-width="3" stroke-dasharray="${percentage}, 100"
                                stroke="url(#gradient)" stroke-linecap="round" />
                            <defs>
                                <linearGradient id="gradient">
                                    <stop offset="0%" stop-color="#40a6ea" />
                                    <stop offset="100%" stop-color="#9d65ea" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-3xl font-bold">${percentage}%</span>
                        </div>
                    </div>
                    <div class="text-center">
                        ${percentage > 50 ? 'You prefer enhanced' : 'You prefer natural'}
                    </div>
                </div>`;
            preferencesData.appendChild(createPreferenceCard('Fake Boobs Preference', content));
        }
    }
    
    function getTopPreference(prefObj) {
        if (!prefObj || Object.keys(prefObj).length === 0) return null;
        return Object.entries(prefObj).sort((a, b) => b[1] - a[1])[0][0];
    }
    
    function createHeightVisualization(height) {
        const heightInCm = parseInt(height);
        if (isNaN(heightInCm)) return '';
        
        const scale = 0.5;
        const visualHeight = heightInCm * scale;
        
        return `
            <div class="relative mt-4 mb-2 flex items-end h-[100px]">
                <div class="w-6 bg-gradient-to-t from-secondary to-tertery rounded-t" style="height: ${visualHeight}px"></div>
                <div class="ml-4 text-sm absolute bottom-0 transform -translate-y-4">
                    Average person (170cm)
                    <div class="absolute bottom-[85px] w-16 border-t border-dashed border-gray-400"></div>
                </div>
            </div>
        `;
    }
    
    function updateProgressBar() {
        const maxChoices = 100;
        const progress = Math.min(100, (userChoices / maxChoices) * 100);
        progressBar.style.width = `${progress}%`;
    }
    
    function setLoadingState(isLoading) {
        if (isLoading) {
            gameContainer.classList.add('opacity-60');
            document.querySelectorAll('button').forEach(btn => {
                btn.disabled = true;
            });
        } else {
            gameContainer.classList.remove('opacity-60');
            document.querySelectorAll('button').forEach(btn => {
                btn.disabled = false;
            });
        }
    }
    
    function setErrorState(message) {
        console.error('Error:', message);
        if (message) {
            alert(message);
        }
    }
    
    function handleWebSocketError(error) {
        console.error('WebSocket error:', error);
    }
});