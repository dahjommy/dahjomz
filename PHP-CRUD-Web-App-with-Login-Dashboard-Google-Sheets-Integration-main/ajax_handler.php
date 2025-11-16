<?php
require_once 'config.php';
require_once 'Database.php';
require_once 'Employee.php';
require_once 'User.php';

session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
if ($user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$employee = new Employee();
$userModel = new User();

switch ($action) {
    case 'getEmployee':
        $employeeId = $_GET['id'] ?? '';
        if ($employeeId) {
            $result = $employee->get($employeeId);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        }
        break;
        
    case 'updateEmployee':
        $employeeId = $_POST['employee_id'] ?? '';
        $updates = [
            'firstName' => Database::sanitize($_POST['firstName'] ?? ''),
            'lastName' => Database::sanitize($_POST['lastName'] ?? ''),
            'email' => Database::sanitize($_POST['email'] ?? ''),
            'phone' => Database::sanitize($_POST['phone'] ?? ''),
            'department' => Database::sanitize($_POST['department'] ?? ''),
            'position' => Database::sanitize($_POST['position'] ?? ''),
            'dateOfBirth' => $_POST['dateOfBirth'] ?? '',
            'dateOfJoining' => $_POST['dateOfJoining'] ?? '',
            'salary' => $_POST['salary'] ?? '',
            'address' => Database::sanitize($_POST['address'] ?? ''),
            'city' => Database::sanitize($_POST['city'] ?? ''),
            'country' => Database::sanitize($_POST['country'] ?? ''),
            'status' => $_POST['status'] ?? 'active'
        ];
        
        $result = $employee->update($employeeId, $updates);
        echo json_encode($result);
        break;
        
    case 'deleteEmployee':
        $employeeId = $_POST['employee_id'] ?? '';
        if ($employeeId) {
            $result = $employee->delete($employeeId);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        }
        break;
        
    // User management actions
    case 'getUser':
        $userId = $_GET['id'] ?? '';
        if ($userId) {
            $result = $userModel->get($userId);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
        }
        break;
        
    case 'updateUser':
        $userId = $_POST['user_id'] ?? '';
        $updates = [
            'username' => Database::sanitize($_POST['username'] ?? ''),
            'email' => Database::sanitize($_POST['email'] ?? ''),
            'fullName' => Database::sanitize($_POST['fullName'] ?? ''),
            'role' => $_POST['role'] ?? 'user',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Only update password if provided
        if (!empty($_POST['password'])) {
            $updates['password'] = User::hashPassword($_POST['password']);
        }
        
        // Remove empty values for update
        $updates = array_filter($updates, function($value) {
            return $value !== '';
        });
        
        $result = $userModel->update($userId, $updates);
        echo json_encode($result);
        break;
        
    case 'deleteUser':
        $userId = $_POST['user_id'] ?? '';
        if ($userId) {
            $result = $userModel->delete($userId);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}