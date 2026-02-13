<nav class="navbar">
    <div class="nav-container">

        <!-- Logo + Text -->
        <div class="logo">
            <img src="static/images/lapaseo_logo.jpg" alt="Logo" class="logo-img">
            <div class="logo-text">
                <h2 class="main-name">Barr Mont Le Paseo</h2>
                <p class="sub-name">Island Resort</p>
            </div>
        </div>

        <!-- Flexible spacer -->
        <div style="flex:1;"></div>

        <!-- Navigation Links -->
        <ul class="nav-links">
            <li><a href="dashboard.php?page=home" class="<?php echo $page === 'home' ? 'active' : '' ?>">Home</a></li>
            <li><a href="index.php?page=rooms"> class="<?php echo $page === 'rooms' ? 'active' : '' ?>">Rooms</a></li>
            <li><a href="dashboard.php?page=amenities" class="<?php echo $page === 'amenities' ? 'active' : '' ?>">Amenities</a></li>
            <li><a href="dashboard.php?page=gallery" class="<?php echo $page === 'gallery' ? 'active' : '' ?>">Gallery</a></li>
            <li><a href="dashboard.php?page=contact" class="<?php echo $page === 'contact' ? 'active' : '' ?>">Contact</a></li>
            <li><a href="auth/login.php" class="login-btn">Login</a></li>
        </ul>

    </div>
</nav>
