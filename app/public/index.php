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
$pageTitle = "ExplicitTags - AI Adult Content Analyzer";
$pageDescription = "Advanced AI system for analyzing and categorizing adult content";
?>
<body class="text-TextWhite">
    <!-- Preloader with proper structure -->
    <div id="preloader">
        <div class="spinner"></div>
    </div>

    <!-- Main content -->
    <header>
        <div class="mt-10 flex flex-col items-center justify-center space-y-2" data-aos="fade-down" data-aos-duration="1000">
            <h1 class="text-4xl font-bold <?php echo $gradientClass; ?> text-center" data-aos="fade-down" data-aos-duration="1000">Welcome to <?php echo $siteName ?? 'ExplicitTags'; ?></h1>
            <h2 class="<?php echo $gradientClass; ?> text-center" data-aos="fade-down" data-aos-duration="1000">Are you looking for the porn star that suits you best</h2>
            <h3 class="<?php echo $gradientClass; ?> text-center" data-aos="fade-down" data-aos-duration="1000">Play smash or pass here to find out</h3>
        </div>
    </header>
    <main>
        <div class="flex flex-col items-center mt-60 space-y-6">
            <a class="hover:transition hover:duration-[150ms] px-4 py-2 bg-primary text-white rounded border border-secondary hover:bg-secondary hover:text-gray-950" data-aos="fade-up" data-aos-duration="1500" href="experience">
                Click here to customize your search
            </a>
            <a class="hover:transition hover:duration-[150ms] px-4 py-2 bg-primary text-white rounded border border-secondary hover:bg-secondary hover:text-gray-950" data-aos="fade-up" data-aos-duration="1500" href="tag">
                Or tag an video yourself
            </a>
        </div>
    </main>
    <?php include_once BASE_PATH . '/src/includes/scripts.php'; ?>
</body>
</html>