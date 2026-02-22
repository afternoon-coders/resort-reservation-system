<?php
// Include header with navbar
include 'inc/header.php';

// Determine which page to load
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'add_room', 'edit_room', 'manage_rooms'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// Include the page content
include "{$page}.php";

// Include footer
include 'inc/footer.php';
?>