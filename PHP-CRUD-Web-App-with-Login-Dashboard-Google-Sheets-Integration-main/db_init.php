<?php
/**
 * Database Initialization Helper
 * Automatically selects FastDatabase or regular Database based on configuration
 */

require_once 'config.php';

// Function to get database instance
function getDatabase() {
    if (USE_CSV_MODE && file_exists('FastCSVDatabase.php')) {
        require_once 'FastCSVDatabase.php';
        return new FastCSVDatabase();
    } elseif (USE_CACHE && file_exists('FastDatabase.php')) {
        require_once 'FastDatabase.php';
        return new FastDatabase();
    } else {
        require_once 'Database.php';
        return new Database();
    }
}

// Global database instance
$db = getDatabase();
?>