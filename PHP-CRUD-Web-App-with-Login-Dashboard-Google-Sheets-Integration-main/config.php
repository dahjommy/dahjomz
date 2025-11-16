<?php
// Configuration file for Google Sheets Web App

// Google Apps Script Web App URL
define('GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbzjpkUCdIECec8mI4mvKDyx_M3EvIyIkrNACgfgX5T9Zroj9-40poqAC2YrBdb9yfmVSw/exec');

// Session configuration
define('SESSION_NAME', 'google_sheets_app');
define('SESSION_LIFETIME', 3600); // 1 hour

// Application settings
define('APP_NAME', 'MRI SCRIPTS');
define('APP_VERSION', '2.0.0');
define('APP_DEBUG', true); // Set to false in production

// Cache configuration (Disabled - using CSV mode instead)
define('USE_CACHE', false); // Disabled - using CSV mode
define('CACHE_SYNC_INTERVAL', 30); // Sync interval in seconds

// CSV Mode Configuration - SUPER FAST!
define('USE_CSV_MODE', true); // Enable CSV storage for ultra-fast performance
define('CSV_SYNC_INTERVAL', 30); // Sync to Google Sheets every 30 seconds

// Timezone
date_default_timezone_set('UTC');

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Start session with custom configuration
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Function to check if user is logged in
function isLoggedIn() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

// Function to get current user
function getCurrentUser() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return $_SESSION['user'] ?? null;
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to display alerts
function setAlert($message, $type = 'info') {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Function to get and clear alert
function getAlert() {
    if (!isset($_SESSION)) {
        session_start();
    }
    $alert = $_SESSION['alert'] ?? null;
    unset($_SESSION['alert']);
    return $alert;
}