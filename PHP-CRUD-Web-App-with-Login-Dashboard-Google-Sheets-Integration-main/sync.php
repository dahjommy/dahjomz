<?php
/**
 * Background Sync Script
 * Syncs cache with Google Sheets every 30 seconds
 * Can be run as a cron job or background process
 */

require_once 'config.php';
require_once 'Database.php';
require_once 'CacheManager.php';

// Set execution time limit
set_time_limit(0);
ignore_user_abort(true);

// Log file for sync operations
$logFile = 'sync.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Check if sync is already running
$pidFile = 'sync.pid';
if (file_exists($pidFile)) {
    $pid = file_get_contents($pidFile);
    // Check if process is still running
    if (file_exists("/proc/$pid")) {
        logMessage("Sync already running with PID $pid");
        exit;
    }
}

// Write current PID
file_put_contents($pidFile, getmypid());

logMessage("Sync process started");

try {
    $cacheManager = new CacheManager();
    
    // Continuous sync loop (for daemon mode)
    if (isset($argv[1]) && $argv[1] === '--daemon') {
        logMessage("Running in daemon mode");
        
        while (true) {
            try {
                logMessage("Starting sync cycle");
                
                // Sync from Google Sheets
                if ($cacheManager->syncFromSheets()) {
                    logMessage("Sync completed successfully");
                    
                    // Get cache stats
                    $stats = $cacheManager->getCacheStats();
                    logMessage("Cache stats - Users: {$stats['users_count']}, Employees: {$stats['employees_count']}");
                } else {
                    logMessage("Sync failed");
                }
                
                // Wait 30 seconds before next sync
                sleep(30);
                
            } catch (Exception $e) {
                logMessage("Sync error: " . $e->getMessage());
                sleep(30); // Wait before retry
            }
        }
    } else {
        // Single sync execution (for cron)
        logMessage("Running single sync");
        
        if ($cacheManager->syncFromSheets()) {
            logMessage("Sync completed successfully");
            
            // Get cache stats
            $stats = $cacheManager->getCacheStats();
            logMessage("Cache stats - Users: {$stats['users_count']}, Employees: {$stats['employees_count']}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Sync completed',
                'stats' => $stats
            ]);
        } else {
            logMessage("Sync failed");
            echo json_encode([
                'success' => false,
                'message' => 'Sync failed'
            ]);
        }
    }
    
} catch (Exception $e) {
    logMessage("Fatal error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Clean up PID file
    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
}
?>