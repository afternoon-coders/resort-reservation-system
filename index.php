<?php
ob_start();
// Determine which page to load
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// List of allowed pages
$allowed_pages = [
    'home',
    'rooms',
    'amenities',
    'gallery',
    'booknow',
    'contact',
    'receipt'
];

// Check if the requested page is allowed
if (!in_array($page, $allowed_pages)) {
    $page = 'home'; // Default page if invalid
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Resort Website</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/static/img/lepaseo_logo.png">
</head>

<body>

    <!-- Include header / navbar -->
    <?php include __DIR__ . '/inc/header.php'; ?>

    <!-- Main page content -->
    <?php include __DIR__ . "/views/{$page}.php"; ?>

    <!-- Include footer -->
    <?php include __DIR__ . '/inc/footer.php'; ?>

</body>
</html>