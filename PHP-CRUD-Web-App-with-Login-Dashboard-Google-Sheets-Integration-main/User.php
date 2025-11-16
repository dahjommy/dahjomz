<?php
// User class for Google Sheets integration

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Create new user
    public function create($data) {
        try {
            return $this->db->makeRequest('createUser', $data);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Get user by ID
    public function get($userId) {
        try {
            return $this->db->makeRequest('getUser', [
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
    public function update($userId, $updates) {
        try {
            return $this->db->makeRequest('updateUser', [
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
    
    // Delete user
    public function delete($userId) {
        try {
            return $this->db->makeRequest('deleteUser', [
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
    
    // Get all users
    public function getAll() {
        try {
            return $this->db->makeRequest('getAllUsers');
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    // Validate user data
    public static function validate($data) {
        $errors = [];
        
        // Required fields
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (!Database::validateUsername($data['username'])) {
            $errors[] = 'Username must be 3-20 characters long and contain only letters, numbers, and underscores';
        }
        
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!Database::validateEmail($data['email'])) {
            $errors[] = 'Please enter a valid email address';
        }
        
        if (empty($data['fullName'])) {
            $errors[] = 'Full name is required';
        }
        
        // Password validation (only for new users)
        if (isset($data['password']) && !empty($data['password'])) {
            if (!Database::validatePassword($data['password'])) {
                $errors[] = 'Password must be at least 6 characters long';
            }
        }
        
        // Role validation
        if (!in_array($data['role'] ?? '', ['admin', 'user'])) {
            $errors[] = 'Invalid role selected';
        }
        
        // Status validation
        if (!in_array($data['status'] ?? '', ['active', 'inactive'])) {
            $errors[] = 'Invalid status selected';
        }
        
        return $errors;
    }
    
    // Hash password
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}