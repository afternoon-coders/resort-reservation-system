<?php
// Ensure auth functions are available
if (file_exists(__DIR__ . '/../auth/auth_functions.php')) {
    require_once __DIR__ . '/../auth/auth_functions.php';
}

// Check login status
$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/static/css/style.css">
    <link rel="stylesheet" href="/static/css/adminpanel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <!-- Normal Users & Logged Out -->
    <?php if (!isAdmin()): ?>
        <nav class="navbar">
            <div class="nav-container">

                <!-- Logo + Text -->
                <div class="logo">
                    <img src="\static\img\lepaseo_logo.jpg" alt="Logo" class="logo-img">
                    <div class="logo-text">
                        <h2 class="main-name">Barr Mont Le Paseo Isla Andis Resort</h2>
                        <p class="sub-name">Island Resort</p>
                    </div>
                </div>

                <!-- Flexible spacer -->
                <div style="flex:1;"></div>

                <!-- Navigation Links -->

                <ul class="nav-links">
                    <li><a href="/index.php?page=home">Home</a></li>
                    <li><a href="/index.php?page=rooms">Rooms</a></li>
                    <li><a href="/index.php?page=amenities">Amenities</a></li>
                    <li><a href="/index.php?page=gallery">Gallery</a></li>
                    <li><a href="/index.php?page=booknow">Book Now</a></li>
                    <li><a href="/index.php?page=contact">Contact</a></li>

                    <!-- Login / Logout Section -->
                    <!-- I put 2 of this kind of condition. I have no other idea HAHAHHA -->
                    <?php if ($isLoggedIn): ?>
                        <li class="user-welcome">
                            <span>Welcome,<br> <?php echo htmlspecialchars($user['username'] ?? 'User'); ?></span>
                        </li>
                        <li><a href="/auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/auth/login.php">Login</a></li>
                    <?php endif; ?>
                    </ul>
            </div>
        </nav>
    <?php endif; ?>


    <!-- Admin Only -->
    <?php if (isAdmin()): ?>
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo-icon">≋</div>
                <div>
                <h2>Barr Mont Le Paseo</h2>
                <span>Admin Panel</span>
                </div>
            </div>

            <div class="sidebar-section">
                <p class="admin-section-title">MANAGEMENT</p>
                <ul class="">
                    <li>
                        <span>
                            <img src="/admin/static/img/dashboard.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=dashboard">Dashboard</a>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/reservation.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=reservations">Reservations</a>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/bed.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=rooms">Rooms</a>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/people.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=users">Customers</a>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/analytics.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=reports">Reports</a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <p class="admin-section-title">CONTENT</p>
                <ul>
                    <li>
                        <span>
                            <img src="/admin/static/img/edit_doc.svg" alt="">
                        </span>
                        Page Content
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/image.svg" alt="">
                        </span>
                        Media Library
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/settings.svg" alt="">
                        </span>
                        Site Settings
                    </li>
                </ul>
            </div>

            <div class="sidebar-footer">
                <!-- Login / Logout Section -->
                <?php if ($isLoggedIn): ?>
                    <li class="user-welcome">
                        <span>Welcome,<br> <?php echo htmlspecialchars($user['username'] ?? 'User'); ?></span>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/logout.svg" alt="">
                        </span>
                        <a href="/auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="/auth/login.php">Login</a>
                    </li>
                <?php endif; ?>
            
            </div>
    <?php endif; ?>
        </div>

</body>
</html>