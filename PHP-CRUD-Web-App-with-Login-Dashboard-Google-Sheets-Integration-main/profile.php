<?php
require_once 'config.php';
require_once 'Database.php';

session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    setAlert('Please login to access your profile', 'warning');
    redirect('login.php');
}

$user = getCurrentUser();
$isAdmin = $user['role'] === 'admin';
$db = new Database();
$errors = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $email = Database::sanitize($_POST['email'] ?? '');
        $fullName = Database::sanitize($_POST['full_name'] ?? '');
        
        // Validation
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!Database::validateEmail($email)) {
            $errors[] = 'Invalid email format';
        }
        
        if (empty($errors)) {
            $updates = [
                'email' => $email,
                'fullName' => $fullName
            ];
            
            $result = $db->updateUser($user['id'], $updates);
            
            if ($result['success']) {
                // Update session
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['fullName'] = $fullName;
                $user = getCurrentUser();
                setAlert('Profile updated successfully!', 'success');
                redirect('profile.php');
            } else {
                $errors[] = 'Failed to update profile: ' . $result['message'];
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (!Database::validatePassword($newPassword)) {
            $errors[] = 'New password must be at least 6 characters long';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }
        
        if (empty($errors)) {
            // Verify current password
            $loginResult = $db->login($user['username'], $currentPassword);
            
            if ($loginResult['success']) {
                // Update password
                $result = $db->updateUser($user['id'], ['password' => $newPassword]);
                
                if ($result['success']) {
                    setAlert('Password changed successfully!', 'success');
                    redirect('profile.php');
                } else {
                    $errors[] = 'Failed to change password';
                }
            } else {
                $errors[] = 'Current password is incorrect';
            }
        }
    }
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
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
        
        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1), transparent);
            border-radius: 50%;
        }
        
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-avatar-large i {
            font-size: 4rem;
            color: white;
        }
        
        .profile-name {
            color: white;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-username {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .profile-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            color: white;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        /* Cards */
        .profile-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .card-title i {
            color: #667eea;
        }
        
        /* Form Controls */
        .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            color: white;
        }
        
        .form-control:disabled {
            background: rgba(255, 255, 255, 0.03);
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            z-index: 10;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        .text-muted {
            color: rgba(255, 255, 255, 0.5) !important;
        }
        
        /* Buttons */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
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
        
        .btn-warning {
            background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%);
            color: white;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
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
                
                
                <?php if ($isAdmin): ?>
                <div class="menu-item">
                    <a href="employees.php" class="menu-link">
                        <i class="bi bi-people"></i>
                        <span class="menu-text">Employees</span>
                    </a>
                </div>
                
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
                    <a href="profile.php" class="menu-link active">
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
                
                <div class="user-menu">
                    <div class="notification-btn">
                        <i class="bi bi-bell"></i>
                    </div>
                    
                    <div class="user-profile">
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
                        <i class="bi bi-exclamation-triangle"></i>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar-large">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['fullName'] ?: $user['username']); ?></h2>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <span class="profile-badge"><?php echo ucfirst($user['role']); ?> Account</span>
                </div>
                
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="stat-label">Account Created</div>
                        <div class="stat-value">Recently</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #2ed573 0%, #26de81 100%);">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stat-label">Last Login</div>
                        <div class="stat-value">Just Now</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fc5c65 0%, #fd79a8 100%);">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="stat-label">Account Status</div>
                        <div class="stat-value">Active</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fed330 0%, #f8b500 100%);">
                            <i class="bi bi-award"></i>
                        </div>
                        <div class="stat-label">Access Level</div>
                        <div class="stat-value"><?php echo ucfirst($user['role']); ?></div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-lg-6">
                        <div class="profile-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="bi bi-person-lines-fill"></i> Profile Information
                                </h5>
                            </div>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="full_name" 
                                           name="full_name" 
                                           value="<?php echo htmlspecialchars($user['fullName'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Account Role</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="col-lg-6">
                        <div class="profile-card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="bi bi-key"></i> Change Password
                                </h5>
                            </div>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="current_password" 
                                               name="current_password" 
                                               required>
                                        <button class="password-toggle" type="button" onclick="togglePassword('current_password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="new_password" 
                                               name="new_password" 
                                               required>
                                        <button class="password-toggle" type="button" onclick="togglePassword('new_password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               required>
                                        <button class="password-toggle" type="button" onclick="togglePassword('confirm_password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="bi bi-shield-lock"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>