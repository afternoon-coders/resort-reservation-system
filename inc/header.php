<?php
// Ensure auth functions are available
if (file_exists(__DIR__ . '/../auth/auth_functions.php')) {
    require_once __DIR__ . '/../auth/auth_functions.php';
}

// Auto-run reservation status logic on every page load
if (file_exists(__DIR__ . '/../helpers/ReservationModel.php')) {
    require_once __DIR__ . '/../helpers/ReservationModel.php';
    $reservationModel = new ReservationModel();
    $reservationModel->autoUpdateStatuses();
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
    <link rel="icon" type="image/png" href="/static/img/lepaseo_logo.png">
    
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
                        <li class="user-welcome logout-btn">
                            <div class="profile-dropdown">
                                <div class="profile-toggle" onclick="toggleDropdown()">
                                    <span class="profile-name">
                                        <?php echo htmlspecialchars($user['first_name'] ?: $user['username'] ?: 'User'); ?>
                                    </span>
                                    <span class="arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#000"><path d="m480-384 144-144H336l144 144Zm.28 288Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm-.28-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z"/></svg>
                                    </span>
                                </div>

                                <div class="dropdown-menu" id="dropdownMenu">
                                    <a href="/profile.php">My Profile</a>
                                    <a href="/settings.php">Settings</a>
                                    <hr>
                                    <a href="/auth/logout.php" class="logout">Logout</a>
                                </div>
                            </div>
                        </li>
                        <!-- <li>
                            <a  href="/auth/logout.php">Logout</a>
                        </li> -->
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

            <hr>

            <div class="sidebar-section">
                <p class="admin-section-title">MANAGEMENT</p>
                <ul class="">
                    <li>
                        <span>
                            <img src="/admin/static/img/adminpanel_icons/dashboard.svg" alt="" >
                        </span>
                        <a href="/admin/index.php?page=dashboard">Dashboard</a>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/adminpanel_icons/reservation.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=reservations">Reservations</a>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/adminpanel_icons/bed.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=manage_rooms">Cottages</a>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/adminpanel_icons/people.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=customers">Customers</a>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/adminpanel_icons/analytics.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=reports">Reports</a>
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/adminpanel_icons/dollar.svg" alt="">
                        </span>
                        <a href="/admin/index.php?page=payments">Payments</a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <p class="admin-section-title">CONTENT</p>
                <ul>
                    <li>
                        <span>
                            <img src="/admin/static/img/adminpanel_icons/edit_doc.svg" alt="">
                        </span>
                        Page Content
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/adminpanel_icons/image.svg" alt="">
                        </span>
                        Media Library
                    </li>
                    <li>
                        <span>
                            <img src="/admin/static/img/adminpanel_icons/settings.svg" alt="">
                        </span>
                        Site Settings
                    </li>
                </ul>
            </div>

            <div class="sidebar-footer">
                <!-- Login / Logout Section -->
                <?php if ($isLoggedIn): ?>
                    <li class="user-welcome">
                        <span><?php echo htmlspecialchars($user['first_name'] ?: 'User'); ?></span>
                    </li>
                    <li>
                        <span>  
                            <img src="/admin/static/img/adminpanel_icons/logout.svg" alt="">
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
    
    <script>
        function toggleDropdown() {
            document.getElementById("dropdownMenu").classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.closest('.profile-dropdown')) {
                document.getElementById("dropdownMenu").classList.remove("show");
            }
        }
        
        // Navbar in client side active state logic
        function setActiveNav() {
            const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';
            const navLinks = document.querySelectorAll('.nav-links a');

            navLinks.forEach(link => {
                const linkPage = new URLSearchParams(link.search).get('page');
                if (linkPage === currentPage) {
                    link.classList.add('active');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', setActiveNav);

        //  Admin sidebar active state logic
        function setActiveSidebar() {
            const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
            const sidebarLinks = document.querySelectorAll('.sidebar a');

            sidebarLinks.forEach(link => {
                const linkPage = new URLSearchParams(link.search).get('page');
                if (linkPage === currentPage) {
                    link.parentElement.classList.add('active');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', setActiveSidebar);
    </script>
</body>
</html>