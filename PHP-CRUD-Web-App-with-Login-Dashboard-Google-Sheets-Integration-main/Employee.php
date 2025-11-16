<?php
// Employee class for Google Sheets integration

class Employee {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Create new employee
    public function create($data) {
        try {
            return $this->db->makeRequest('createEmployee', $data);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Get employee by ID
    public function get($employeeId) {
        try {
            return $this->db->makeRequest('getEmployee', [
                'employeeId' => $employeeId
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Update employee
    public function update($employeeId, $updates) {
        try {
            return $this->db->makeRequest('updateEmployee', [
                'employeeId' => $employeeId,
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
    
    // Delete employee
    public function delete($employeeId) {
        try {
            return $this->db->makeRequest('deleteEmployee', [
                'employeeId' => $employeeId
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    // Get all employees
    public function getAll() {
        try {
            return $this->db->makeRequest('getAllEmployees');
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    // Make request to Google Apps Script (inherited from Database)
    private function makeRequest($action, $params = []) {
        $scriptUrl = GOOGLE_SCRIPT_URL;
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
        $response = @file_get_contents($scriptUrl, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to connect to Google Apps Script');
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid response from Google Apps Script');
        }
        
        return $result;
    }
    
    // Validate employee data
    public static function validate($data) {
        $errors = [];
        
        // Required fields
        if (empty($data['firstName'])) {
            $errors[] = 'First name is required';
        }
        
        if (empty($data['lastName'])) {
            $errors[] = 'Last name is required';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s()]+$/', $data['phone'])) {
            $errors[] = 'Invalid phone number format';
        }
        
        if (!empty($data['salary']) && !is_numeric($data['salary'])) {
            $errors[] = 'Salary must be a number';
        }
        
        return $errors;
    }
    
    // Format employee data for display
    public static function format($employee) {
        return [
            'id' => $employee['id'] ?? '',
            'employeeCode' => $employee['employeeCode'] ?? '',
            'fullName' => trim(($employee['firstName'] ?? '') . ' ' . ($employee['lastName'] ?? '')),
            'firstName' => $employee['firstName'] ?? '',
            'lastName' => $employee['lastName'] ?? '',
            'email' => $employee['email'] ?? '',
            'phone' => $employee['phone'] ?? '',
            'department' => $employee['department'] ?? '',
            'position' => $employee['position'] ?? '',
            'dateOfBirth' => $employee['dateOfBirth'] ?? '',
            'dateOfJoining' => $employee['dateOfJoining'] ?? '',
            'salary' => $employee['salary'] ?? '',
            'address' => $employee['address'] ?? '',
            'city' => $employee['city'] ?? '',
            'country' => $employee['country'] ?? '',
            'status' => $employee['status'] ?? 'active'
        ];
    }
}