<?php
require_once 'config.php';
require_once 'Database.php';

session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    setAlert('Please login to access the dashboard', 'warning');
    redirect('login.php');
}

$user = getCurrentUser();
$isAdmin = $user['role'] === 'admin';

// Only load stats for admin users
$statsData = [
    'totalEmployees' => 0,
    'activeEmployees' => 0,
    'departments' => 0,
    'newThisMonth' => 0
];

if ($isAdmin) {
    $db = new Database();
    
    // Get employees count with single request
    $empResult = $db->makeRequest('getAllEmployees');
    if ($empResult['success']) {
        $allEmployees = $empResult['data'];
        $statsData['totalEmployees'] = count($allEmployees);
        $statsData['activeEmployees'] = array_reduce($allEmployees, function($count, $e) {
            return $count + ($e['status'] === 'active' ? 1 : 0);
        }, 0);
        
        // Count unique departments
        $departments = array_unique(array_column($allEmployees, 'department'));
        $statsData['departments'] = count(array_filter($departments));
        
        // Count new employees this month
        $currentMonth = date('Y-m');
        $statsData['newThisMonth'] = array_reduce($allEmployees, function($count, $e) use ($currentMonth) {
            $joinDate = isset($e['dateOfJoining']) ? substr($e['dateOfJoining'], 0, 7) : '';
            return $count + ($joinDate === $currentMonth ? 1 : 0);
        }, 0);
    }
}

$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
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
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1), transparent);
            border-radius: 50%;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-title {
            color: white;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .welcome-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }
        
        /* Stats Grid */
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
            border-radius: 20px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), transparent);
            border-radius: 50%;
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
            margin-bottom: 1rem;
        }
        
        .stat-value {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .stat-change {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.25rem 0.75rem;
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .stat-change.negative {
            background: rgba(255, 71, 87, 0.2);
            color: #ff4757;
        }
        
        /* Quick Actions */
        .quick-actions {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .quick-actions-header {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .action-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1rem;
            text-align: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: translateY(-3px);
            color: white;
        }
        
        .action-btn i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .action-btn span {
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 1.5rem;
        }
        
        .chart-header {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .chart-placeholder {
            height: 250px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.3);
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-grid {
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
                    <a href="dashboard.php" class="menu-link active">
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
                    <input type="text" placeholder="Search...">
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
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="welcome-content">
                        <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($user['fullName'] ?: $user['username']); ?>! 🎯</h1>
                        <p class="welcome-subtitle">Here's what's happening with your organization today</p>
                    </div>
                </div>
                
                <?php if ($isAdmin): ?>
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-value"><?php echo $statsData['totalEmployees']; ?></div>
                        <div class="stat-label">Total Employees</div>
                        <span class="stat-change">+12%</span>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #2ed573 0%, #26de81 100%);">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div class="stat-value"><?php echo $statsData['activeEmployees']; ?></div>
                        <div class="stat-label">Active Employees</div>
                        <span class="stat-change">+5%</span>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fc5c65 0%, #fd79a8 100%);">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="stat-value"><?php echo $statsData['departments']; ?></div>
                        <div class="stat-label">Departments</div>
                        <span class="stat-change negative">-2%</span>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fed330 0%, #f8b500 100%);">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div class="stat-value"><?php echo $statsData['newThisMonth']; ?></div>
                        <div class="stat-label">New This Month</div>
                        <span class="stat-change">+8%</span>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3 class="quick-actions-header">Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="employees.php?action=add" class="action-btn">
                            <i class="bi bi-person-plus"></i>
                            <span>Add Employee</span>
                        </a>
                        <a href="employees.php" class="action-btn">
                            <i class="bi bi-people"></i>
                            <span>View All</span>
                        </a>
                        <a href="profile.php" class="action-btn">
                            <i class="bi bi-person-gear"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="setup.php" class="action-btn">
                            <i class="bi bi-gear"></i>
                            <span>Settings</span>
                        </a>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h3 class="chart-header">Employee Growth</h3>
                        <div class="chart-placeholder">
                            <i class="bi bi-graph-up" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <h3 class="chart-header">Department Distribution</h3>
                        <div class="chart-placeholder">
                            <i class="bi bi-pie-chart" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Regular User View -->
                <div class="quick-actions">
                    <h3 class="quick-actions-header">Available Actions</h3>
                    <div class="actions-grid">
                        <a href="profile.php" class="action-btn">
                            <i class="bi bi-person"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="profile.php#settings" class="action-btn">
                            <i class="bi bi-gear"></i>
                            <span>Settings</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
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
    </script>
</body>
</html>