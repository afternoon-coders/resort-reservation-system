<?php
// Session utilities for authentication

if (session_status() === PHP_SESSION_NONE) {
    // Harden session cookies for production
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    // Uncomment when SSL is configured:
    // ini_set('session.cookie_secure', 1);
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

function isGuest() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'guest';
}

function getCurrentUser() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'phone_number' => $_SESSION['phone_number'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Redirect to web-accessible login path instead of filesystem path
        // Use a site-root relative URL so the built-in PHP server doesn't expose filesystem paths
        header('Location: /auth/login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied: Admin only. Redirecting...';
        header('Refresh: 1.5; url=/index.php?page=home');
        exit;
    }
}

function requireStaff() {
    if (!isStaff() && !isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied: Staff only. Redirecting...';
        header('Refresh: 1.5; url=/index.php?page=home');
        exit;
    }
}
