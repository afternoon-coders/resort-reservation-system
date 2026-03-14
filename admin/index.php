<?php
require_once __DIR__ . '/../auth/auth_functions.php';

requireLogin();
requireAdmin();

// Determine which page to load
$page = isset($_GET['page']) ? (string)$_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 
                  'add_room', 
                  'edit_room', 
                  'manage_rooms', 
                  'reservations',
                  'rooms',
                  'customers',
                  'reports',
                  'payments',
                  ];

if (!in_array($page, $allowed_pages, true)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    
    <link rel="icon" type="image/png" href="/static/img/lepaseo_logo.png">
    
</head>
<body>
        
    <div class="wrapper">

        <?php include '../inc/header.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . "/views/{$page}.php"; ?>
        </div>

    </div>

</body>
</html>