<?php
// Session utilities for authentication

if (session_status() === PHP_SESSION_NONE) {
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
        'email' => $_SESSION['email'] ?? null,
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
        exit('Access denied: Admin only');
        header('Refresh: 1.5; url=/index.php?page=home');
    }
}

function requireStaff() {
    if (!isStaff() && !isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied: Staff only');
        header('Refresh: 1.5; url=/index.php?page=home');
    }
}
