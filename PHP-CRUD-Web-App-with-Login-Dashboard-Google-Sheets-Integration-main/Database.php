<?php
// Database class for Google Sheets integration

class Database {
    private $scriptUrl;
    
    public function __construct() {
        $this->scriptUrl = GOOGLE_SCRIPT_URL;
        
        if ($this->scriptUrl === 'YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE') {
            throw new Exception('Google Apps Script URL not configured. Please run setup.php first.');
        }
    }
    
    // Make a request to Google Apps Script
    public function makeRequest($action, $params = []) {
        $data = array_merge(['action' => $action], $params);
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($this->scriptUrl, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to connect to Google Apps Script');
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid response from Google Apps Script');
        }
        
        return $result;
    }
    
    // Setup the Google Sheet with headers
    public function setupSheet() {
        try {
            return $this->makeRequest('setup');
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // User login
    public function login($username, $password) {
        try {
            return $this->makeRequest('login', [
                'username' => $username,
                'password' => $password
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // User registration
    public function register($username, $password, $email, $fullName = '', $role = 'user') {
        try {
            return $this->makeRequest('register', [
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'fullName' => $fullName,
                'role' => $role
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Get user by ID
    public function getUser($userId) {
        try {
            return $this->makeRequest('getUser', [
                'userId' => $userId
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Update user
    public function updateUser($userId, $updates) {
        try {
            return $this->makeRequest('updateUser', [
                'userId' => $userId,
                'updates' => $updates
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Get all users
    public function getAllUsers() {
        try {
            return $this->makeRequest('getAllUsers');
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Delete user
    public function deleteUser($userId) {
        try {
            return $this->makeRequest('deleteUser', [
                'userId' => $userId
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Validate username
    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
    }
    
    // Validate password strength
    public static function validatePassword($password) {
        return strlen($password) >= 6;
    }
    
    // Sanitize input
    public static function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}