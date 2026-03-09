<?php
// Ensure auth functions are available
if (file_exists(__DIR__ . '/../auth/auth_functions.php')) {
    require_once __DIR__ . '/../auth/auth_functions.php';
}

// Note: Reservation status updates are now handled by a cron job
// See scripts/cron_update_statuses.php

// Handle profile update POST
$profileMsg = '';
$profileMsgType = '';
if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (file_exists(__DIR__ . '/../helpers/UserModel.php')) require_once __DIR__ . '/../helpers/UserModel.php';
    if (file_exists(__DIR__ . '/../helpers/GuestModel.php')) require_once __DIR__ . '/../helpers/GuestModel.php';

    $userModel = new UserModel();
    $guestModel = new GuestModel();
    $currentUser = getCurrentUser();
    $userdata = $userModel->getById($currentUser['user_id']);

    $firstName   = trim($_POST['first_name'] ?? '');
    $lastName    = trim($_POST['last_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');

    if (!$firstName || !$lastName || !$email) {
        $profileMsg = 'First name, last name, and email are required.';
        $profileMsgType = 'error';
    } else {
        try {
            if (!empty($userdata['guest_id'])) {
                $guestModel->update((int)$userdata['guest_id'], [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'email'      => $email,
                    'phone'      => $phoneNumber,
                ]);
            }
            $userModel->update($currentUser['user_id'], [
                'email' => $email,
            ]);
            $profileMsg = 'Profile updated successfully!';
            $profileMsgType = 'success';
        } catch (\Throwable $e) {
            $profileMsg = 'Error: ' . $e->getMessage();
            $profileMsgType = 'error';
        }
    }
}

// Check login status
$isLoggedIn = isLoggedIn();
$user = $isLoggedIn ? getCurrentUser() : null;

// Get fresh user data for the form
$profileUser = null;
if ($isLoggedIn) {
    if (!class_exists('UserModel') && file_exists(__DIR__ . '/../helpers/UserModel.php')) {
        require_once __DIR__ . '/../helpers/UserModel.php';
    }
    if (class_exists('UserModel')) {
        $userModel = $userModel ?? new UserModel();
        $profileUser = $userModel->getById($user['user_id']);
    }
}
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

    <!-- Floating Alert -->
    <div id="js-alert"></div>

    <?php if ($profileMsg): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => showAlert('<?php echo $profileMsgType; ?>', '<?php echo addslashes(htmlspecialchars($profileMsg)); ?>'));
        </script>
    <?php endif; ?>

    <!-- Profile Modal -->
    <?php if ($isLoggedIn && $profileUser): ?>
    <div id="profile-modal" style="
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    ">
        <div style="
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin: 0 20px;
        ">
            <!-- Close Button -->
            <button onclick="closeProfileModal()" style="
                position: absolute;
                top: 15px; right: 15px;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 20px;
                color: #666;
                line-height: 1;
            ">&times;</button>

            <!-- Modal Header -->
            <div style="display:flex; align-items:center; gap:15px; margin-bottom:25px;">
                <div style="
                    width: 55px; height: 55px;
                    border-radius: 50%;
                    background: #086584;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 22px;
                    color: #fff;
                    font-weight: 600;
                    flex-shrink: 0;
                ">
                    <?php echo strtoupper(substr($profileUser['first_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div>
                    <h3 style="margin:0;">My Profile</h3>
                    <p style="margin:0; color:#64748b; font-size:13px;">Update your personal information</p>
                </div>
            </div>

            <hr style="margin-bottom:20px;">

            <!-- Profile Form -->
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div>
                        <label style="font-size:13px; font-weight:500; display:block; margin-bottom:5px;">First Name *</label>
                        <input type="text" name="first_name" class="booknow-input"
                            value="<?php echo htmlspecialchars($profileUser['first_name'] ?? ''); ?>"
                            placeholder="First name" required
                            style="background:transparent; border:1.5px solid #e2e8f0; width:100%;">
                    </div>
                    <div>
                        <label style="font-size:13px; font-weight:500; display:block; margin-bottom:5px;">Last Name *</label>
                        <input type="text" name="last_name" class="booknow-input"
                            value="<?php echo htmlspecialchars($profileUser['last_name'] ?? ''); ?>"
                            placeholder="Last name" required
                            style="background:transparent; border:1.5px solid #e2e8f0; width:100%;">
                    </div>
                </div>

                <div style="margin-top:15px;">
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:5px;">Email Address *</label>
                    <input type="email" name="email" class="booknow-input"
                        value="<?php echo htmlspecialchars($profileUser['guest_email'] ?? $profileUser['account_email'] ?? ''); ?>"
                        placeholder="Email address" required
                        style="background:transparent; border:1.5px solid #e2e8f0; width:100%;">
                </div>

                <div style="margin-top:15px;">
                    <label style="font-size:13px; font-weight:500; display:block; margin-bottom:5px;">Phone Number</label>
                    <input type="tel" name="phone_number" class="booknow-input"
                        value="<?php echo htmlspecialchars($profileUser['phone_number'] ?? ''); ?>"
                        placeholder="Phone number"
                        style="background:transparent; border:1.5px solid #e2e8f0; width:100%;">
                </div>

                <div class="row" style="gap: 5px;">
                    <button type="submit" class="btn btn-primary save-profile-btn">
                        Save Changes
                    </button>
                    <button type="button" class="cancel-profile-btn btn" onclick="closeProfileModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Normal Users & Logged Out -->
    <?php if (!isAdmin()): ?>
        <nav class="navbar">
            <div class="nav-container">

                <!-- Logo + Text -->
                <div class="logo">
                    <img src="/static/img/lepaseo_logo.jpg" alt="Logo" class="logo-img">
                    <div class="logo-text">
                        <h2 class="main-name">Barr Mont Le Paseo Isla Andis Resort</h2>
                        <p class="sub-name">Island Resort</p>
                    </div>
                </div>

                <div style="flex:1;"></div>

                <ul class="nav-links">
                    <li><a href="/index.php?page=home">Home</a></li>
                    <li><a href="/index.php?page=rooms">Rooms</a></li>
                    <li><a href="/index.php?page=amenities">Amenities</a></li>
                    <li><a href="/index.php?page=gallery">Gallery</a></li>
                    <li><a href="/index.php?page=booknow">Book Now</a></li>
                    <li><a href="/index.php?page=contact">Contact</a></li>

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
                                    <!-- Changed from href to onclick to open modal -->
                                    <a href="#" style="color: #555;" onclick="openProfileModal(); return false;">My Profile</a>
                                    <hr>
                                    <a href="/auth/logout.php" class="logout">Logout</a>
                                </div>
                            </div>
                        </li>
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
                        <span><img src="/admin/static/img/adminpanel_icons/dashboard.svg" alt=""></span>
                        <a href="/admin/index.php?page=dashboard">Dashboard</a>
                    </li>
                    <li>
                        <span><img src="/admin/static/img/adminpanel_icons/reservation.svg" alt=""></span>
                        <a href="/admin/index.php?page=reservations">Reservations</a>
                    </li>
                    <li>
                        <span><img src="/admin/static/img/adminpanel_icons/bed.svg" alt=""></span>
                        <a href="/admin/index.php?page=manage_rooms">Cottages</a>
                    </li>
                    <li>
                        <span><img src="/admin/static/img/adminpanel_icons/people.svg" alt=""></span>
                        <a href="/admin/index.php?page=customers">Customers</a>
                    </li>
                    <li>
                        <span><img src="/admin/static/img/adminpanel_icons/analytics.svg" alt=""></span>
                        <a href="/admin/index.php?page=reports">Reports</a>
                    </li>
                    <li>
                        <span><img src="/admin/static/img/adminpanel_icons/dollar.svg" alt=""></span>
                        <a href="/admin/index.php?page=payments">Payments</a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <p class="admin-section-title">CONTENT</p>
                <ul>
                    <li>
                        <span><img src="/admin/static/img/adminpanel_icons/edit_doc.svg" alt=""></span>
                        Page Content
                    </li>
                    <li>
                        <span><img src="/admin/static/img/adminpanel_icons/image.svg" alt=""></span>
                        Media Library
                    </li>
                    <li>
                        <span><img src="/admin/static/img/adminpanel_icons/settings.svg" alt=""></span>
                        Site Settings
                    </li>
                </ul>
            </div>

            <div class="sidebar-footer">
                <?php if ($isLoggedIn): ?>
                    <li class="user-welcome">
                        <span><?php echo htmlspecialchars($user['first_name'] ?: 'User'); ?></span>
                    </li>
                    <li>
                        <span><img src="/admin/static/img/adminpanel_icons/logout.svg" alt=""></span>
                        <a href="/auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li><a href="/auth/login.php">Login</a></li>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function toggleDropdown() {
            document.getElementById("dropdownMenu").classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.closest('.profile-dropdown')) {
                const menu = document.getElementById("dropdownMenu");
                if (menu) menu.classList.remove("show");
            }
        }

        function openProfileModal() {
            const modal = document.getElementById('profile-modal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeProfileModal() {
            const modal = document.getElementById('profile-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('profile-modal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeProfileModal();
                });
            }
        });

        // Navbar active state
        function setActiveNav() {
            const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';
            const navLinks = document.querySelectorAll('.nav-links a');
            navLinks.forEach(link => {
                const linkPage = new URLSearchParams(link.search).get('page');
                if (linkPage === currentPage) link.classList.add('active');
            });
        }

        document.addEventListener('DOMContentLoaded', setActiveNav);

        // Admin sidebar active state
        function setActiveSidebar() {
            const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
            const sidebarLinks = document.querySelectorAll('.sidebar a');
            sidebarLinks.forEach(link => {
                const linkPage = new URLSearchParams(link.search).get('page');
                if (linkPage === currentPage) link.parentElement.classList.add('active');
            });
        }

        document.addEventListener('DOMContentLoaded', setActiveSidebar);

        function showAlert(type, message) {
            const alert = document.getElementById('js-alert');
            if (!alert) return;
            if (type === 'success') {
                alert.style.backgroundColor = '#d4edda';
                alert.style.color = '#155724';
                alert.style.border = '1px solid #c3e6cb';
            } else {
                alert.style.backgroundColor = '#f8d7da';
                alert.style.color = '#721c24';
                alert.style.border = '1px solid #f5c6cb';
            }
            alert.textContent = message;
            alert.style.display = 'block';
            alert.style.opacity = '1';
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 400);
            }, 3000);
        }
    </script>
</body>
</html>