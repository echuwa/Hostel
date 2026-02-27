<?php
/**
 * Security Helper Functions - Hostel Management System
 */

/**
 * Generate a CSRF token and store it in the session
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Output a hidden CSRF token field for forms
 */
function csrf_field() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Security Headers
 */
function set_security_headers() {
    // Prevent Clickjacking
    header("X-Frame-Options: SAMEORIGIN");
    // Prevent MIME-sniffing
    header("X-Content-Type-Options: nosniff");
    // XSS Protection for older browsers
    header("X-XSS-Protection: 1; mode=block");
    // Secure Referral Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");
    // Content Security Policy (Basic)
    // header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com https://unpkg.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:;");
}

/**
 * Session Security Binding
 */
function secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (!isset($_SESSION['ss_ip'])) {
        $_SESSION['ss_ip'] = $ip;
        $_SESSION['ss_ua'] = $ua;
    } else {
        if ($_SESSION['ss_ip'] !== $ip || $_SESSION['ss_ua'] !== $ua) {
            // Session hijacking attempt?
            session_unset();
            session_destroy();
            return false;
        }
    }
    return true;
}

/**
 * Sanitize text output for XSS prevention (shorthand for htmlspecialchars)
 */
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
