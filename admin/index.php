<?php
// Determine which page to load
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 
                  'add_room', 
                  'edit_room', 
                  'manage_rooms', 
                  'reservations',
                  'rooms',
                  'customers',
                  'reports',
                  ];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}
?>

<div class="wrapper">

    <?php include '../inc/header.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . "/views/{$page}.php"; ?>
    </div>

</div>
