<?php
/**
 * CSRF Token Protection Helper
 * 
 * Include this file and call csrf_field() inside <form> tags.
 * Call csrf_verify() at the top of POST handlers.
 */

/**
 * Generate or retrieve the current CSRF token.
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden input field with the CSRF token.
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify the submitted CSRF token matches the session token.
 * Call this at the beginning of any POST request handler.
 * Returns true if valid, false otherwise.
 */
function csrf_verify(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    
    if (empty($submitted) || empty($stored)) {
        return false;
    }
    
    return hash_equals($stored, $submitted);
}

/**
 * Verify CSRF and die with 403 if invalid.
 * Convenience wrapper for POST handlers.
 */
function csrf_verify_or_die(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
        http_response_code(403);
        exit('Invalid or missing CSRF token. Please go back and try again.');
    }
}
