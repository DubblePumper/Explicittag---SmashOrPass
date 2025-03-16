<?php 
// No output before this PHP tag - not even whitespace
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}


// Define default values
$pageDescription = isset($pageDescription) ? $pageDescription : "Find the performer you like";
$pageKeywords = isset($pageKeywords) ? $pageKeywords : "adult, video analysis, AI identification, porn tags, performer recognition";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    
    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
    
    <!-- Favicon links -->
    <link rel="icon" href="/assets/images/icons/ExplicitTags-logo-SVG-NoQuote.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/images/icons/ExplicitTags-logo-SVG-NoQuote.svg" type="image/svg+xml">

    <!-- Open Graph tags for social sharing -->
    <meta property="og:title" content="ExplicitTags - AI Adult Content Analyzer">
    <meta property="og:description" content="Advanced AI-powered system for analyzing and recommending adult content">
    <meta property="og:image" content="/assets/images/icons/ExplicitTags-logo-SVG-NoQuote.svg">
    <meta property="og:url" content="https://explicittags.com">
    <meta property="og:type" content="website">

    <!-- Twitter Card data -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="ExplicitTags - AI Adult Content Analyzer">
    <meta name="twitter:description" content="Advanced AI-powered system for analyzing and recommending adult content">
    <meta name="twitter:image" content="/assets/images/icons/ExplicitTags-logo-SVG-NoQuote.svg">

    <!-- Canonical link to avoid duplicate content issues -->
    <link rel="canonical" href="https://explicittags.com/">

    <!-- AOS Animation CDN -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" type="text/css">


    <!-- tailwindCSS -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <style type="text/tailwindcss">

    </style>

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,container-queries"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        transparent: 'transparent',
                        current: 'currentColor',
                        'primairy': '#12143a',
                        'secondary': '#40a6ea',
                        'tertery': '#9d65ea',
                        'darkPrimairy': '#0d0d0d',
                        'secondaryTerteryMix': '#837de7',
                        'secondaryDarker': '#2b5891',
                        'white': '#e0e0e0',
                        'TextWhite': '#e0e0e0',
                        'BgDark': '#222222',
                    },
                    fontFamily: {
                        'sans': ['Helvetica', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Jquery -->
    <script
        src="https://code.jquery.com/jquery-3.7.1.slim.min.js"
        integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8="
        crossorigin="anonymous"></script>
    <!-- Stylesheets and fonts -->
    <link rel="stylesheet" type="text/css" href="/assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css?family=Helvetica|Roboto" rel="stylesheet" type="text/css">



    <!-- Additional meta tags -->
    <meta name="theme-color" content="#12143a">
    <meta name="msapplication-TileColor" content="#12143a">
    <meta name="msapplication-TileImage" content="/assets/images/icons/mstile-144x144.png">

    <!-- Preloader CSS -->
    <link rel="stylesheet" type="text/css" href="/assets/css/preloader.css?v=<?php echo time(); ?>">

    <!-- Load preloader script early -->
    <script src="/assets/js/preloader.js?v=<?php echo time(); ?>"></script>
</head>
