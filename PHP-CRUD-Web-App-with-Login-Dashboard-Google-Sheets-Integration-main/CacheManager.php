<?php
/**
 * Cache Manager for Super Fast Google Sheets Integration
 * Handles local cache operations and sync with Google Sheets
 */

class CacheManager {
    private $cacheFile = 'cache.json';
    private $lockFile = 'cache.lock';
    private $maxCacheAge = 30; // seconds
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->initializeCache();
    }
    
    /**
     * Initialize cache file if it doesn't exist
     */
    private function initializeCache() {
        if (!file_exists($this->cacheFile)) {
            $initialData = [
                'users' => [],
                'employees' => [],
                'last_sync' => null,
                'sync_status' => 'pending'
            ];
            $this->writeCache($initialData);
        }
    }
    
    /**
     * Read cache with file locking to prevent conflicts
     */
    public function readCache() {
        $attempts = 0;
        while ($attempts < 10) {
            if (!file_exists($this->lockFile) || (time() - filemtime($this->lockFile) > 5)) {
                // Create lock
                touch($this->lockFile);
                
                $data = json_decode(file_get_contents($this->cacheFile), true);
                
                // Remove lock
                if (file_exists($this->lockFile)) {
                    unlink($this->lockFile);
                }
                
                return $data;
            }
            $attempts++;
            usleep(100000); // Wait 100ms
        }
        
        // Fallback to direct read if lock timeout
        return json_decode(file_get_contents($this->cacheFile), true);
    }
    
    /**
     * Write to cache with file locking
     */
    private function writeCache($data) {
        $attempts = 0;
        while ($attempts < 10) {
            if (!file_exists($this->lockFile) || (time() - filemtime($this->lockFile) > 5)) {
                // Create lock
                touch($this->lockFile);
                
                file_put_contents($this->cacheFile, json_encode($data, JSON_PRETTY_PRINT));
                
                // Remove lock
                if (file_exists($this->lockFile)) {
                    unlink($this->lockFile);
                }
                
                return true;
            }
            $attempts++;
            usleep(100000); // Wait 100ms
        }
        
        return false;
    }
    
    /**
     * Check if cache needs refresh
     */
    public function needsRefresh() {
        $cache = $this->readCache();
        
        if (!$cache || !isset($cache['last_sync'])) {
            return true;
        }
        
        $lastSync = strtotime($cache['last_sync']);
        $now = time();
        
        return ($now - $lastSync) > $this->maxCacheAge;
    }
    
    /**
     * Sync all data from Google Sheets to cache
     */
    public function syncFromSheets() {
        try {
            $cache = $this->readCache();
            $cache['sync_status'] = 'syncing';
            $this->writeCache($cache);
            
            // Fetch all data from Google Sheets
            $users = $this->db->makeRequest('getAllUsers');
            $employees = $this->db->makeRequest('getAllEmployees');
            
            // Update cache with fresh data
            $cache['users'] = $users['success'] ? $users['data'] : [];
            $cache['employees'] = $employees['success'] ? $employees['data'] : [];
            $cache['last_sync'] = date('Y-m-d H:i:s');
            $cache['sync_status'] = 'completed';
            
            $this->writeCache($cache);
            
            return true;
        } catch (Exception $e) {
            $cache = $this->readCache();
            $cache['sync_status'] = 'error';
            $cache['last_error'] = $e->getMessage();
            $this->writeCache($cache);
            
            return false;
        }
    }
    
    /**
     * Get users from cache
     */
    public function getUsers() {
        $this->checkAndRefresh();
        $cache = $this->readCache();
        return $cache['users'] ?? [];
    }
    
    /**
     * Get specific user from cache
     */
    public function getUser($userId) {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['id'] == $userId) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * Get user by username from cache
     */
    public function getUserByUsername($username) {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }
    
    /**
     * Get employees from cache
     */
    public function getEmployees() {
        $this->checkAndRefresh();
        $cache = $this->readCache();
        return $cache['employees'] ?? [];
    }
    
    /**
     * Get specific employee from cache
     */
    public function getEmployee($employeeId) {
        $employees = $this->getEmployees();
        foreach ($employees as $employee) {
            if ($employee['id'] == $employeeId) {
                return $employee;
            }
        }
        return null;
    }
    
    
    /**
     * Add new data to cache (will be synced to sheets)
     */
    public function addToCache($type, $data) {
        $cache = $this->readCache();
        
        switch($type) {
            case 'user':
                $cache['users'][] = $data;
                break;
            case 'employee':
                $cache['employees'][] = $data;
                break;
        }
        
        $this->writeCache($cache);
    }
    
    /**
     * Update data in cache
     */
    public function updateInCache($type, $id, $updates) {
        $cache = $this->readCache();
        
        switch($type) {
            case 'user':
                foreach ($cache['users'] as &$user) {
                    if ($user['id'] == $id) {
                        $user = array_merge($user, $updates);
                        break;
                    }
                }
                break;
            case 'employee':
                foreach ($cache['employees'] as &$employee) {
                    if ($employee['id'] == $id) {
                        $employee = array_merge($employee, $updates);
                        break;
                    }
                }
                break;
        }
        
        $this->writeCache($cache);
    }
    
    /**
     * Delete from cache
     */
    public function deleteFromCache($type, $id) {
        $cache = $this->readCache();
        
        switch($type) {
            case 'user':
                $cache['users'] = array_filter($cache['users'], function($user) use ($id) {
                    return $user['id'] != $id;
                });
                break;
            case 'employee':
                $cache['employees'] = array_filter($cache['employees'], function($employee) use ($id) {
                    return $employee['id'] != $id;
                });
                break;
        }
        
        $this->writeCache($cache);
    }
    
    /**
     * Check and refresh cache if needed
     */
    private function checkAndRefresh() {
        if ($this->needsRefresh()) {
            // Run sync in background if possible
            if (function_exists('exec')) {
                exec('php sync.php > /dev/null 2>&1 &');
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats() {
        $cache = $this->readCache();
        
        return [
            'users_count' => count($cache['users'] ?? []),
            'employees_count' => count($cache['employees'] ?? []),
            'last_sync' => $cache['last_sync'],
            'sync_status' => $cache['sync_status'],
            'cache_age' => $cache['last_sync'] ? (time() - strtotime($cache['last_sync'])) : null
        ];
    }
    
    /**
     * Force immediate sync
     */
    public function forceSync() {
        return $this->syncFromSheets();
    }
    
    /**
     * Clear entire cache
     */
    public function clearCache() {
        $this->initializeCache();
        return $this->syncFromSheets();
    }
}