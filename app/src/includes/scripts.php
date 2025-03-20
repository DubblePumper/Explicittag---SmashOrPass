<?php if (!defined('BASE_PATH')) exit; ?>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- AOS Animation Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Initialize AOS animation
    AOS.init({
        once: true,
        duration: 800
    });
    
    // Preloader
    window.addEventListener('load', function() {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 500);
        }
    });

    // Additional preloader hide logic (backup)
    document.addEventListener('DOMContentLoaded', function() {
        const preloader = document.getElementById('preloader');
        if (preloader && preloader.style.display !== 'none') {
            preloader.classList.add('fade-out');
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 500);
        }
    });
</script>

<!-- Common JavaScript -->
<script src="/assets/js/utils/cache.js"></script>

<!-- WebSocket Manager (load this first for pages that need it) -->
<?php 
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if ($currentPage === 'index') {
    echo '<script src="/assets/js/utils/websocket.js"></script>';
}
?>

<!-- Page-specific scripts -->
<?php 
if (file_exists(dirname($_SERVER['DOCUMENT_ROOT']) . "/public/assets/js/{$currentPage}Page/script.js")) {
    echo "<script src=\"/assets/js/{$currentPage}Page/script.js\"></script>";
}
?>

<!-- Initialize any components -->
<script>
    // Initialize any global components or features
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded successfully!');
    });
</script>