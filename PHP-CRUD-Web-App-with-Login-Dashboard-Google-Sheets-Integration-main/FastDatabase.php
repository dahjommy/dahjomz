<?php
/**
 * Fast Database Class with Cache Integration
 * Uses local cache for read operations and syncs with Google Sheets
 */

require_once 'Database.php';
require_once 'CacheManager.php';

class FastDatabase extends Database {
    private $cache;
    private $useCache = true;
    
    public function __construct() {
        parent::__construct();
        $this->cache = new CacheManager();
        
        // Initial sync if cache is empty
        if ($this->cache->needsRefresh()) {
            $this->cache->syncFromSheets();
        }
    }
    
    /**
     * Override makeRequest to use cache for read operations
     */
    public function makeRequest($action, $params = []) {
        // For read operations, use cache
        if ($this->useCache && $this->isReadOperation($action)) {
            return $this->handleCachedRequest($action, $params);
        }
        
        // For write operations, update both sheets and cache
        if ($this->isWriteOperation($action)) {
            return $this->handleWriteRequest($action, $params);
        }
        
        // Default to parent implementation
        return parent::makeRequest($action, $params);
    }
    
    /**
     * Check if operation is a read operation
     */
    private function isReadOperation($action) {
        $readOps = [
            'getAllUsers', 'getUser', 'getAllEmployees', 'getEmployee'
        ];
        return in_array($action, $readOps);
    }
    
    /**
     * Check if operation is a write operation
     */
    private function isWriteOperation($action) {
        $writeOps = [
            'createUser', 'updateUser', 'deleteUser',
            'createEmployee', 'updateEmployee', 'deleteEmployee',
            'clockIn', 'clockOut', 'setup', 'register', 'login'
        ];
        return in_array($action, $writeOps);
    }
    
    /**
     * Handle cached read requests
     */
    private function handleCachedRequest($action, $params) {
        try {
            switch ($action) {
                case 'getAllUsers':
                    return [
                        'success' => true,
                        'message' => 'Users retrieved from cache',
                        'data' => $this->cache->getUsers()
                    ];
                    
                case 'getUser':
                    $userId = $params['userId'] ?? null;
                    $user = $this->cache->getUser($userId);
                    return [
                        'success' => $user !== null,
                        'message' => $user ? 'User found' : 'User not found',
                        'data' => $user
                    ];
                    
                case 'getAllEmployees':
                    return [
                        'success' => true,
                        'message' => 'Employees retrieved from cache',
                        'data' => $this->cache->getEmployees()
                    ];
                    
                case 'getEmployee':
                    $employeeId = $params['employeeId'] ?? null;
                    $employee = $this->cache->getEmployee($employeeId);
                    return [
                        'success' => $employee !== null,
                        'message' => $employee ? 'Employee found' : 'Employee not found',
                        'data' => $employee
                    ];
                    
                    
                default:
                    // Fallback to parent implementation
                    return parent::makeRequest($action, $params);
            }
        } catch (Exception $e) {
            // If cache fails, fallback to direct API call
            return parent::makeRequest($action, $params);
        }
    }
    
    /**
     * Handle write requests - update both sheets and cache
     */
    private function handleWriteRequest($action, $params) {
        // First, make the request to Google Sheets
        $result = parent::makeRequest($action, $params);
        
        // If successful, update cache accordingly
        if ($result['success']) {
            $this->updateCacheAfterWrite($action, $params, $result);
        }
        
        return $result;
    }
    
    /**
     * Update cache after successful write operation
     */
    private function updateCacheAfterWrite($action, $params, $result) {
        try {
            switch ($action) {
                case 'createUser':
                case 'register':
                    if (isset($result['data'])) {
                        $this->cache->addToCache('user', $result['data']);
                    }
                    break;
                    
                case 'updateUser':
                    if (isset($params['userId']) && isset($params['updates'])) {
                        $this->cache->updateInCache('user', $params['userId'], $params['updates']);
                    }
                    break;
                    
                case 'deleteUser':
                    if (isset($params['userId'])) {
                        $this->cache->deleteFromCache('user', $params['userId']);
                    }
                    break;
                    
                case 'createEmployee':
                    if (isset($result['data'])) {
                        $this->cache->addToCache('employee', $result['data']);
                    }
                    break;
                    
                case 'updateEmployee':
                    if (isset($params['employeeId']) && isset($params['updates'])) {
                        $this->cache->updateInCache('employee', $params['employeeId'], $params['updates']);
                    }
                    break;
                    
                case 'deleteEmployee':
                    if (isset($params['employeeId'])) {
                        $this->cache->deleteFromCache('employee', $params['employeeId']);
                    }
                    break;
                    
            }
        } catch (Exception $e) {
            // Log error but don't fail the operation
            error_log('Cache update failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Login with cache support
     */
    public function login($username, $password) {
        // First try to authenticate from cache
        $user = $this->cache->getUserByUsername($username);
        
        if ($user && isset($user['password'])) {
            // Verify password (assuming it's already hashed)
            $hashedPassword = hash('sha256', $password);
            if ($user['password'] === $hashedPassword) {
                // Update last login in background
                if (function_exists('exec')) {
                    exec("php -r \"require_once 'Database.php'; \$db = new Database(); \$db->makeRequest('updateUser', ['userId' => '{$user['id']}', 'updates' => ['lastLogin' => '" . date('Y-m-d H:i:s') . "']]);\" > /dev/null 2>&1 &");
                }
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'fullName' => $user['fullName'] ?? '',
                        'role' => $user['role']
                    ]
                ];
            }
        }
        
        // If cache authentication fails, try direct API
        return parent::makeRequest('login', [
            'username' => $username,
            'password' => $password
        ]);
    }
    
    /**
     * Force cache sync
     */
    public function syncCache() {
        return $this->cache->forceSync();
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats() {
        return $this->cache->getCacheStats();
    }
    
    /**
     * Disable cache temporarily
     */
    public function disableCache() {
        $this->useCache = false;
    }
    
    /**
     * Enable cache
     */
    public function enableCache() {
        $this->useCache = true;
    }
}