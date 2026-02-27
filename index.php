<?php
// Include header with navbar
include 'inc/header.php';

// Determine which page to load
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowed_pages = ['home', 'rooms', 'amenities', 'gallery', 'booknow', 'contact'];

if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Include the page content
include "views/{$page}.php";

// Include footer
include 'inc/footer.php';
?>
