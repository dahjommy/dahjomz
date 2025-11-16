<?php
require_once 'config.php';
require_once 'Database.php';
require_once 'Employee.php';

session_start();

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    setAlert('Please login to access this page', 'warning');
    redirect('login.php');
}

$user = getCurrentUser();
$isAdmin = $user['role'] === 'admin';
if (!$isAdmin) {
    setAlert('Access denied. Admin privileges required.', 'danger');
    redirect('dashboard.php');
}

$employee = new Employee();
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new employee
    if (isset($_POST['add_employee'])) {
        $employeeData = [
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
        
        $validationErrors = Employee::validate($employeeData);
        if (empty($validationErrors)) {
            $result = $employee->create($employeeData);
            if ($result['success']) {
                setAlert('Employee added successfully!', 'success');
                redirect('employees.php');
            } else {
                $errors[] = 'Failed to add employee: ' . $result['message'];
            }
        } else {
            $errors = $validationErrors;
        }
    }
    
    // Delete employee
    if (isset($_POST['delete_employee'])) {
        $employeeId = $_POST['employee_id'];
        $result = $employee->delete($employeeId);
        if ($result['success']) {
            setAlert('Employee deleted successfully!', 'success');
        } else {
            setAlert('Failed to delete employee: ' . $result['message'], 'danger');
        }
        redirect('employees.php');
    }
}

// Get all employees
$allEmployees = [];
$result = $employee->getAll();
if ($result['success']) {
    $allEmployees = $result['data'];
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #0f0c29;
            background: linear-gradient(to right, #24243e, #302b63, #0f0c29);
            min-height: 100vh;
        }
        
        /* Dashboard Layout */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar.collapsed .sidebar-header {
            padding: 2rem 1rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #f093fb 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .logo-text {
            font-size: 1.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #f093fb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: opacity 0.3s;
        }
        
        .sidebar.collapsed .logo-text {
            opacity: 0;
            display: none;
        }
        
        .sidebar-menu {
            padding: 1.5rem 0;
        }
        
        .menu-item {
            margin: 0.5rem 1rem;
        }
        
        .menu-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .menu-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .menu-link.active {
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .menu-link i {
            font-size: 1.2rem;
            width: 24px;
        }
        
        .menu-text {
            font-size: 0.9rem;
            font-weight: 500;
            transition: opacity 0.3s;
        }
        
        .sidebar.collapsed .menu-text {
            opacity: 0;
            display: none;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
            width: calc(100% - 280px);
        }
        
        .main-content.expanded {
            margin-left: 80px;
            width: calc(100% - 80px);
        }
        
        /* Top Bar */
        .topbar {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }
        
        .search-box {
            flex: 1;
            max-width: 400px;
            margin: 0 2rem;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            outline: none;
        }
        
        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .notification-btn {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #f093fb 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .user-name {
            color: white;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Content Area */
        .content {
            padding: 2rem;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1), transparent);
            border-radius: 50%;
        }
        
        .page-header-content {
            position: relative;
            z-index: 1;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }
        
        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            margin-top: 0.5rem;
        }
        
        /* Table Container */
        .table-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 1.5rem;
            overflow: hidden;
        }
        
        .table {
            color: white;
        }
        
        .table thead th {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            padding: 1rem;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .table tbody td {
            padding: 1rem;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        
        /* Badges */
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge.bg-success {
            background: linear-gradient(135deg, #2ed573 0%, #26de81 100%);
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, #fed330 0%, #f8b500 100%);
        }
        
        .badge.bg-secondary {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Buttons */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            border: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #11cdef 0%, #1890ff 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f5365c 0%, #f56036 100%);
            color: white;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        /* Modal Styles */
        .modal-content {
            background: linear-gradient(135deg, #1a1a3e 0%, #2d2d5f 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            color: white;
        }
        
        .modal-header {
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
        }
        
        .modal-title {
            color: white;
            font-weight: 600;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            background: rgba(255, 255, 255, 0.05);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
        }
        
        /* Form Controls in Modal */
        .modal .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .modal .form-control,
        .modal .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        
        .modal .form-control:focus,
        .modal .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            color: white;
        }
        
        .modal .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .modal .form-select option {
            background: #2d2d5f;
            color: white;
        }
        
        /* Info Group for View Modal */
        .info-group {
            margin-bottom: 1.5rem;
        }
        
        .info-group label {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-group .value {
            color: white;
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* DataTables Custom Styling */
        .dataTables_wrapper {
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label {
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white !important;
            border-radius: 8px;
            padding: 0.5rem;
        }
        
        .dataTables_wrapper .dataTables_length select option {
            background: #2d2d5f;
            color: white;
        }
        
        .table, .table td, .table th {
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_info {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: white !important;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            margin: 0 2px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-color: #667eea !important;
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-color: #667eea !important;
            color: white !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5;
        }
        
        /* Alert Styles */
        .alert {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
        }
        
        .alert-success {
            background: rgba(45, 206, 137, 0.1);
            border-color: rgba(45, 206, 137, 0.3);
        }
        
        .alert-danger {
            background: rgba(245, 54, 92, 0.1);
            border-color: rgba(245, 54, 92, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .search-box {
                display: none;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="logo">
                    <div class="logo-icon">
                        <i class="bi bi-layers"></i>
                    </div>
                    <span class="logo-text">MRI SCRIPTS</span>
                </a>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-item">
                    <a href="dashboard.php" class="menu-link">
                        <i class="bi bi-grid-1x2"></i>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </div>
                
                <div class="menu-item">
                    <a href="employees.php" class="menu-link active">
                        <i class="bi bi-people"></i>
                        <span class="menu-text">Employees</span>
                    </a>
                </div>
                
                <?php if ($isAdmin): ?>
                <div class="menu-item">
                    <a href="users.php" class="menu-link">
                        <i class="bi bi-person-badge"></i>
                        <span class="menu-text">Users</span>
                    </a>
                </div>
                
                <div class="menu-item">
                    <a href="csv_sync.php" class="menu-link">
                        <i class="bi bi-arrow-repeat"></i>
                        <span class="menu-text">CSV Sync</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="menu-item">
                    <a href="profile.php" class="menu-link">
                        <i class="bi bi-person"></i>
                        <span class="menu-text">Profile</span>
                    </a>
                </div>
                
                <div class="menu-item" style="margin-top: auto;">
                    <a href="logout.php" class="menu-link" style="color: #ff6b6b;">
                        <i class="bi bi-box-arrow-left"></i>
                        <span class="menu-text">Logout</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Top Bar -->
            <div class="topbar">
                <button class="toggle-btn" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                
                <div class="search-box">
                    <input type="text" placeholder="Search employees...">
                </div>
                
                <div class="user-menu">
                    <div class="notification-btn">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    
                    <div class="user-profile dropdown">
                        <div class="user-avatar">
                            <i class="bi bi-person"></i>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="content">
                <?php if ($alert): ?>
                    <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($alert['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter: brightness(0) invert(1);"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-header-content">
                        <h1 class="page-title"><i class="bi bi-people"></i> Employee Management</h1>
                        <p class="page-subtitle">Manage your workforce efficiently</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal" style="position: relative; z-index: 1;">
                        <i class="bi bi-person-plus"></i> Add New Employee
                    </button>
                </div>
                
                <!-- Employees Table -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table id="employeesTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allEmployees as $emp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['id']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($emp['employeeCode']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($emp['firstName'] . ' ' . $emp['lastName']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['department']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['position']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $emp['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($emp['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-info text-white view-employee" 
                                                    data-id="<?php echo $emp['id']; ?>" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewEmployeeModal"
                                                    title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning edit-employee" 
                                                    data-id="<?php echo $emp['id']; ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editEmployeeModal"
                                                    title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                                <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                                <button type="submit" name="delete_employee" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="firstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="lastName" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department">
                                    <option value="">Select Department</option>
                                    <option value="IT">IT</option>
                                    <option value="HR">Human Resources</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Operations">Operations</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position</label>
                                <input type="text" class="form-control" name="position">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="dateOfBirth">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Joining</label>
                                <input type="date" class="form-control" name="dateOfJoining" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary</label>
                                <input type="number" class="form-control" name="salary" step="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_employee" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Add Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Employee Modal -->
    <div class="modal fade" id="viewEmployeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person"></i> Employee Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewEmployeeContent">
                    <div class="text-center p-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editEmployeeForm">
                    <div class="modal-body" id="editEmployeeContent">
                        <div class="text-center p-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#employeesTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]],
                "language": {
                    "search": "Search employees:",
                    "lengthMenu": "Show _MENU_ employees"
                }
            });
            
            // View employee
            $('.view-employee').click(function() {
                const employeeId = $(this).data('id');
                $('#viewEmployeeContent').html('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>');
                
                $.get('ajax_handler.php', { action: 'getEmployee', id: employeeId }, function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        const emp = data.data;
                        const html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-group">
                                        <label>Employee Code</label>
                                        <div class="value">${emp.employeeCode || 'N/A'}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>Full Name</label>
                                        <div class="value">${emp.firstName} ${emp.lastName}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>Email</label>
                                        <div class="value">${emp.email || 'N/A'}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>Phone</label>
                                        <div class="value">${emp.phone || 'N/A'}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>Department</label>
                                        <div class="value">${emp.department || 'N/A'}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>Position</label>
                                        <div class="value">${emp.position || 'N/A'}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-group">
                                        <label>Date of Birth</label>
                                        <div class="value">${emp.dateOfBirth || 'N/A'}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>Date of Joining</label>
                                        <div class="value">${emp.dateOfJoining || 'N/A'}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>Salary</label>
                                        <div class="value">${emp.salary ? '$' + emp.salary : 'N/A'}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>Address</label>
                                        <div class="value">${emp.address || 'N/A'}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>City</label>
                                        <div class="value">${emp.city || 'N/A'}</div>
                                    </div>
                                    <div class="info-group">
                                        <label>Status</label>
                                        <div class="value">
                                            <span class="badge bg-${emp.status === 'active' ? 'success' : 'warning'}">
                                                ${emp.status ? emp.status.charAt(0).toUpperCase() + emp.status.slice(1) : 'N/A'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#viewEmployeeContent').html(html);
                    } else {
                        $('#viewEmployeeContent').html('<div class="alert alert-danger">Failed to load employee details</div>');
                    }
                });
            });
            
            // Edit employee
            $('.edit-employee').click(function() {
                const employeeId = $(this).data('id');
                $('#editEmployeeContent').html('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>');
                
                $.get('ajax_handler.php', { action: 'getEmployee', id: employeeId }, function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        const emp = data.data;
                        const html = `
                            <input type="hidden" name="employee_id" value="${emp.id}">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="firstName" value="${emp.firstName}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="lastName" value="${emp.lastName}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="${emp.email || ''}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" value="${emp.phone || ''}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department">
                                        <option value="">Select Department</option>
                                        <option value="IT" ${emp.department === 'IT' ? 'selected' : ''}>IT</option>
                                        <option value="HR" ${emp.department === 'HR' ? 'selected' : ''}>Human Resources</option>
                                        <option value="Finance" ${emp.department === 'Finance' ? 'selected' : ''}>Finance</option>
                                        <option value="Marketing" ${emp.department === 'Marketing' ? 'selected' : ''}>Marketing</option>
                                        <option value="Sales" ${emp.department === 'Sales' ? 'selected' : ''}>Sales</option>
                                        <option value="Operations" ${emp.department === 'Operations' ? 'selected' : ''}>Operations</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" name="position" value="${emp.position || ''}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="dateOfBirth" value="${emp.dateOfBirth || ''}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Joining</label>
                                    <input type="date" class="form-control" name="dateOfJoining" value="${emp.dateOfJoining || ''}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Salary</label>
                                    <input type="number" class="form-control" name="salary" step="0.01" value="${emp.salary || ''}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active" ${emp.status === 'active' ? 'selected' : ''}>Active</option>
                                        <option value="inactive" ${emp.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2">${emp.address || ''}</textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city" value="${emp.city || ''}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Country</label>
                                    <input type="text" class="form-control" name="country" value="${emp.country || ''}">
                                </div>
                            </div>
                        `;
                        $('#editEmployeeContent').html(html);
                    } else {
                        $('#editEmployeeContent').html('<div class="alert alert-danger">Failed to load employee details</div>');
                    }
                });
            });
            
            // Handle edit form submission
            $('#editEmployeeForm').submit(function(e) {
                e.preventDefault();
                const formData = $(this).serialize() + '&action=updateEmployee';
                
                $.post('ajax_handler.php', formData, function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to update employee: ' + data.message);
                    }
                });
            });
        });
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Mobile toggle
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active');
            }
        }
        
        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const toggleBtn = document.querySelector('.toggle-btn');
                
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>