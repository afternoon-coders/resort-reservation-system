<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barr Mont Le Paseo - Island Resort</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>

<?php
// Include navbar
include 'navbar.php';

// Determine which page to load
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Allowed pages
$allowed_pages = ['home', 'rooms', 'amenities', 'gallery', 'contact'];

// Default to home if page is not allowed
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Include the page content
include "{$page}.php";
?>

</body>
</html>
