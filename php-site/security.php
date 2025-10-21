<?php
// Security monitoring and protection for SOL Incinerator

// Basic security function - simplified to avoid errors
function basic_security_check() {
    // Get client IP for logging
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Only log if we have write permissions
    $log_dir = __DIR__ . '/logs';
    if (is_writable(__DIR__) && (!file_exists($log_dir) || is_writable($log_dir))) {
        // Try to create logs directory if it doesn't exist
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        // Only log if directory exists and is writable
        if (file_exists($log_dir) && is_writable($log_dir)) {
            $log_file = $log_dir . '/access.log';
            $log_data = date('Y-m-d H:i:s') . " | $ip | " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
            @file_put_contents($log_file, $log_data, FILE_APPEND);
        }
    }
    
    // Very basic input validation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Simple size check for POST data
        $post_size = 0;
        foreach ($_POST as $value) {
            if (is_string($value)) {
                $post_size += strlen($value);
            }
        }
        
        // If POST data is too large, return an error
        if ($post_size > 5000000) { // 5MB limit
            http_response_code(413);
            echo 'Request too large';
            exit;
        }
    }
}

// Call the basic security check
basic_security_check();
