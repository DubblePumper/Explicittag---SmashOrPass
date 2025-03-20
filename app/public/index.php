<?php
// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include necessary files from the new structure
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/src/includes/include-all.php';

// Get gradient class using the function from globals.php
$gradient = getRandomGradientClass(true);
$gradientClass = "bg-gradient-to-r {$gradient['from']} {$gradient['to']} bg-clip-text text-transparent";

// Set page title and description for head.php
$pageTitle = "Smash or Pass - Find Your Perfect Match";
$pageDescription = "Play Smash or Pass to find your ideal performer based on your preferences";
?>
<body class="text-TextWhite bg-darkPrimairy min-h-screen">
    <!-- Preloader with proper structure -->
    <div id="preloader">
        <div class="spinner"></div>
    </div>

    <!-- Main content -->
    <header class="p-4 text-center">
        <div class="mt-10 flex flex-col items-center justify-center space-y-2" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="text-4xl font-bold text-white text-center" data-aos="fade-down" data-aos-duration="1000">Welcome to <?php echo $siteName ?? 'ExplicitTags'; ?></h1>
            <h2 class="text-white text-xl text-center" data-aos="fade-down" data-aos-duration="1000">Are you looking for the porn star that suits you best</h2>
            <h3 class="text-white text-lg text-center" data-aos="fade-down" data-aos-duration="1000">Play smash or pass here to find out</h3>
        </div>
    </header>
    
    <main class="container mx-auto px-4 py-8">
        <!-- Filters Section -->
        <div class="mb-8">
            <div class="bg-primairy rounded-lg p-4 shadow-lg">
                <div class="flex flex-wrap justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">Gender Preference</h3>
                    
                    <!-- Blur Toggle Button -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-300">Blur Images</span>
                        <button id="blur-toggle" class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 focus:ring-offset-darkPrimairy bg-BgDark" role="switch" aria-checked="true">
                            <span id="blur-toggle-handle" class="inline-block w-4 h-4 transform transition-transform bg-tertery rounded-full translate-x-6"></span>
                        </button>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-4">
                    <div class="form-group w-full">
                        <select id="gender-filter" class="bg-darkPrimairy text-TextWhite rounded px-3 py-2 w-full">
                            <option value="">All Genders</option>
                            <option value="female">Female</option>
                            <option value="male">Male</option>
                            <option value="transgender">Transgender</option>
                        </select>
                        <p class="text-sm text-gray-400 mt-2">Filter will apply automatically when you select a gender</p>
                    </div>
                </div>
                <!-- Keep the button but visually hide it for accessibility -->
                <div class="mt-4 hidden">
                    <button id="apply-filters" class="bg-gradient-to-r from-secondary to-tertery text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Smash or Pass Game Area -->
        <div id="game-container" class="mb-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left Performer (Option A) -->
                <div id="performer-a" class="performer-card bg-primairy rounded-lg overflow-hidden shadow-lg transform hover:scale-105 transition duration-300" data-performer-id="">
                    <div class="relative flex flex-col items-center bg-darkPrimairy">
                        <div class="w-full h-96 flex items-center justify-center">
                            <img id="performer-a-image" src="/assets/images/placeholder-profile.jpg" alt="Performer A" class="max-h-full max-w-full object-contain transition-all duration-300 blur-image group-hover:blur-none">
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-primairy to-transparent p-4">
                            <h3 id="performer-a-name" class="text-2xl font-bold text-TextWhite">Loading...</h3>
                            <p id="performer-a-details" class="text-TextWhite opacity-90">Loading performer details...</p>
                        </div>
                    </div>
                    <div class="p-4 flex justify-center">
                        <button class="smash-button bg-gradient-to-r from-tertery to-secondary text-white px-8 py-3 rounded-full font-bold text-lg hover:opacity-90 transition" data-option="a">
                            SMASH
                        </button>
                    </div>
                </div>
                
                <!-- Right Performer (Option B) -->
                <div id="performer-b" class="performer-card bg-primairy rounded-lg overflow-hidden shadow-lg transform hover:scale-105 transition duration-300" data-performer-id="">
                    <div class="relative flex flex-col items-center bg-darkPrimairy">
                        <div class="w-full h-96 flex items-center justify-center">
                            <img id="performer-b-image" src="/assets/images/placeholder-profile.jpg" alt="Performer B" class="max-h-full max-w-full object-contain transition-all duration-300 blur-image group-hover:blur-none">
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-primairy to-transparent p-4">
                            <h3 id="performer-b-name" class="text-2xl font-bold text-TextWhite">Loading...</h3>
                            <p id="performer-b-details" class="text-TextWhite opacity-90">Loading performer details...</p>
                        </div>
                    </div>
                    <div class="p-4 flex justify-center">
                        <button class="smash-button bg-gradient-to-r from-tertery to-secondary text-white px-8 py-3 rounded-full font-bold text-lg hover:opacity-90 transition" data-option="b">
                            SMASH
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <button id="pass-both" class="bg-BgDark text-TextWhite px-6 py-3 rounded-lg hover:bg-opacity-80 transition">
                    PASS BOTH
                </button>
            </div>
        </div>
        
        <!-- Results Section (Hidden initially) -->
        <div id="results-container" class="hidden">
            <div class="bg-primairy rounded-lg p-6 shadow-lg">
                <h2 class="text-2xl font-bold mb-4 <?php echo $gradientClass; ?>">Your Preferences</h2>
                <div id="preferences-data" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Preferences will be loaded here -->
                </div>
                <div class="mt-6 text-center">
                    <button id="continue-playing" class="bg-gradient-to-r from-secondary to-tertery text-white px-6 py-3 rounded-lg hover:opacity-90 transition">
                        Continue Playing
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Progress -->
        <div class="mt-8">
            <div class="flex justify-between items-center mb-2">
                <span>Your choices: <span id="choice-count">0</span></span>
                <button id="view-preferences" class="text-secondary hover:underline">View Your Preferences</button>
            </div>
            <div class="h-2 bg-BgDark rounded-full overflow-hidden">
                <div id="progress-bar" class="h-full bg-gradient-to-r from-secondary to-tertery" style="width: 0%"></div>
            </div>
        </div>
        
        <!-- Hidden container for preloading images -->
        <div id="preload-container" aria-hidden="true"></div>
    </main>
    
    <?php include_once BASE_PATH . '/src/includes/scripts.php'; ?>
    
    <script>
    // Add this debugging helper to check if images are loading
    function checkImageLoading() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            console.log(`Image ${img.id || 'unnamed'} src: ${img.src}, complete: ${img.complete}`);
            if (!img.complete) {
                img.addEventListener('load', () => console.log(`Image ${img.id || 'unnamed'} loaded successfully`));
                img.addEventListener('error', () => console.log(`Image ${img.id || 'unnamed'} failed to load`));
            }
        });
    }
    
    // Run this after DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Check image loading after a short delay
        setTimeout(checkImageLoading, 2000);
    });
    </script>
</body>
</html>