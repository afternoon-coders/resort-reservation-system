<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
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
            <li><a href="index.php?page=home" class="<?php echo isset($page) && $page === 'home' ? 'active' : '' ?>">Home</a></li>
            <li><a href="index.php?page=rooms" class="<?php echo isset($page) && $page === 'rooms' ? 'active' : '' ?>">Rooms</a></li>
            <li><a href="index.php?page=amenities" class="<?php echo isset($page) && $page === 'amenities' ? 'active' : '' ?>">Amenities</a></li>
            <li><a href="index.php?page=gallery" class="<?php echo isset($page) && $page === 'gallery' ? 'active' : '' ?>">Gallery</a></li>
            <li><a href="index.php?page=contact" class="<?php echo isset($page) && $page === 'contact' ? 'active' : '' ?>">Contact</a></li>
            <li><a href="auth/login.php" class="login-btn">Login</a></li>
        </ul>

    </div>
</nav>

</body>
</html>