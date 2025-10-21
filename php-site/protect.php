<?php
// This file provides code protection and security for the SOL Incinerator

// Generate and verify CSRF token
function generate_csrf_token() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    
    return true;
}

// Rate limiting function
function check_rate_limit($key, $limit = 10, $period = 60) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $current_time = time();
    
    // Initialize rate limiting data
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    // Clean up old entries
    foreach ($_SESSION['rate_limits'] as $k => $data) {
        if ($current_time - $data['timestamp'] > $period) {
            unset($_SESSION['rate_limits'][$k]);
        }
    }
    
    // Check if key exists and create if not
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [
            'count' => 1,
            'timestamp' => $current_time
        ];
        return true;
    }
    
    // Check if limit exceeded
    if ($_SESSION['rate_limits'][$key]['count'] >= $limit) {
        return false;
    }
    
    // Increment counter
    $_SESSION['rate_limits'][$key]['count']++;
    return true;
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Function to obfuscate JavaScript code
function obfuscate_js($code) {
    // Remove comments
    $code = preg_replace('!/\*.*?\*/!s', '', $code);
    $code = preg_replace('/\/\/.*$/m', '', $code);
    
    // Remove whitespace
    $code = preg_replace('/\s+/', ' ', $code);
    
    // Basic variable name obfuscation
    $replacements = [];
    for ($i = 0; $i < 100; $i++) {
        $replacements["_var$i"] = "_" . substr(md5(random_bytes(4)), 0, 5);
    }
    
    return $code;
}

// Function to protect the source code and add security headers
function protect_source() {
    // Start session if not already started
    if (!isset($_SESSION) && !headers_sent()) {
        @session_start();
    }
    
    // Security headers - only if headers not already sent
    if (!headers_sent()) {
        // Basic CSP that allows needed resources
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        
        // Prevent caching of sensitive content
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    }
    
    // Prevent direct access to this file
    if (basename($_SERVER['SCRIPT_FILENAME']) == 'protect.php') {
        http_response_code(404);
        exit('Not Found');
    }
    
    // Basic security checks without complex patterns
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Simple checks for very obvious attacks
    if (strpos($request_uri, '../') !== false || 
        strpos($request_uri, '..\\') !== false) {
        http_response_code(403);
        exit('Access Denied');
    }
}

// Call protection function
protect_source();
