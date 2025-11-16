<?php
/**
 * AJAX Sync Handler
 * Handles sync requests from frontend
 */

require_once 'config.php';
require_once 'Database.php';
require_once 'CacheManager.php';

session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$cacheManager = new CacheManager();

switch ($action) {
    case 'sync':
        // Force immediate sync
        if ($cacheManager->forceSync()) {
            echo json_encode([
                'success' => true,
                'message' => 'Sync completed successfully',
                'stats' => $cacheManager->getCacheStats()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Sync failed'
            ]);
        }
        break;
        
    case 'status':
        // Get current cache status
        echo json_encode([
            'success' => true,
            'stats' => $cacheManager->getCacheStats()
        ]);
        break;
        
    case 'clear':
        // Clear and rebuild cache (admin only)
        if ($user['role'] === 'admin') {
            if ($cacheManager->clearCache()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cache cleared and rebuilt successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to clear cache'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Admin access required'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}
?>