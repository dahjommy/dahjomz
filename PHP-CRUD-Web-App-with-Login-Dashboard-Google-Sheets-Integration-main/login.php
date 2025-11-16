<?php
require_once 'config.php';
require_once 'Database.php';

session_start();

// Redirect if already logged in  
if (isLoggedIn()) {
    // Regular users go to profile page
    if ($_SESSION['user']['role'] === 'user') {
        redirect('profile.php');
    } else {
        redirect('dashboard.php');
    }
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = Database::sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $db = new Database();
            $result = $db->login($username, $password);
            
            if ($result['success']) {
                // Store user in session
                $_SESSION['user'] = $result['data'];
                $_SESSION['login_time'] = time();
                
                // Redirect based on role
                if ($result['data']['role'] === 'user') {
                    redirect('profile.php');
                } else {
                    redirect('dashboard.php');
                }
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            $error = 'Connection error. Please check your configuration.';
        }
    }
}

// Get any alerts from session
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
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
            min-height: 100vh;
            background: #0f0c29;
            background: linear-gradient(to right, #24243e, #302b63, #0f0c29);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3), transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 99, 164, 0.3), transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(102, 126, 234, 0.3), transparent 50%);
            animation: float 20s ease-in-out infinite;
            z-index: -1;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(-20px, -20px) rotate(1deg); }
            66% { transform: translate(20px, -10px) rotate(-1deg); }
        }
        
        /* Floating orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.6;
            animation: orb-float 20s infinite;
        }
        
        .orb1 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            top: -150px;
            left: -150px;
            animation-duration: 25s;
        }
        
        .orb2 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            bottom: -100px;
            right: -100px;
            animation-duration: 20s;
            animation-delay: -5s;
        }
        
        .orb3 {
            width: 250px;
            height: 250px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            top: 50%;
            left: 50%;
            animation-duration: 30s;
            animation-delay: -10s;
        }
        
        @keyframes orb-float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(100px, -100px) scale(1.1);
            }
            66% {
                transform: translate(-100px, 100px) scale(0.9);
            }
        }
        
        .login-container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            gap: 3rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        /* Left Side - Branding */
        .login-left {
            flex: 1;
            color: white;
            padding: 2rem;
            animation: slideInLeft 0.8s ease;
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 3rem;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #f093fb 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .brand-name {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #f093fb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 3rem;
            line-height: 1.6;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(10px);
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        /* Right Side - Login Form */
        .login-right {
            flex: 0 0 450px;
            animation: slideInRight 0.8s ease;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h2 {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: white;
            padding: 0.875rem 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            color: white;
            outline: none;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .password-field {
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
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        .form-check {
            margin-bottom: 1.5rem;
        }
        
        .form-check-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .form-check-label {
            color: rgba(255, 255, 255, 0.8);
            margin-left: 0.5rem;
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .divider span {
            background: rgba(255, 255, 255, 0.05);
            padding: 0 1rem;
            position: relative;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }
        
        .form-footer {
            text-align: center;
        }
        
        .form-footer a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .form-footer a:hover {
            color: #f093fb;
            text-decoration: underline;
        }
        
        .form-footer .text-muted {
            color: rgba(255, 255, 255, 0.5) !important;
        }
        
        /* Alert Styles */
        .alert {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
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
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left {
                text-align: center;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .login-right {
                width: 100%;
                max-width: 450px;
            }
        }
        
        @media (max-width: 576px) {
            .login-card {
                padding: 2rem;
            }
            
            .hero-title {
                font-size: 1.8rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating orbs -->
    <div class="orb orb1"></div>
    <div class="orb orb2"></div>
    <div class="orb orb3"></div>
    
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <div class="brand-logo">
                <div class="logo-icon">
                    <i class="bi bi-layers"></i>
                </div>
                <span class="brand-name">MRI SCRIPTS</span>
            </div>
            
            <h1 class="hero-title">Welcome Back!</h1>
            <p class="hero-subtitle">
                Access your powerful employee management system. Manage workforce and analyze data in real-time.
            </p>
            
            <ul class="features-list">
                <li class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <strong>Employee Management</strong>
                        <p class="mb-0" style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">Complete workforce control</p>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div>
                        <strong>Real-time Analytics</strong>
                        <p class="mb-0" style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">Instant insights & reports</p>
                    </div>
                </li>
            </ul>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-card">
                <div class="form-header">
                    <h2>Sign In</h2>
                    <p>Enter your credentials to continue</p>
                </div>
                
                <?php if ($alert): ?>
                    <div class="alert alert-<?php echo $alert['type']; ?>" role="alert">
                        <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($alert['message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Enter your username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required 
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-field">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
                                   required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                </form>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <div class="form-footer">
                    <p class="mb-2">
                        <a href="register.php">Create New Account</a>
                    </p>
                    <p class="mb-3">
                        <a href="setup.php">Setup & Configuration</a>
                    </p>
                    <p class="text-muted small">
                        Default credentials: admin / admin123
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>