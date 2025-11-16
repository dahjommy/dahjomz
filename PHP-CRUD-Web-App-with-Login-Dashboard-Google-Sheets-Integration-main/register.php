<?php
require_once 'config.php';
require_once 'Database.php';

session_start();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = Database::sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = Database::sanitize($_POST['email'] ?? '');
    $fullName = Database::sanitize($_POST['full_name'] ?? '');
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (!Database::validateUsername($username)) {
        $errors[] = 'Username must be 3-20 characters and contain only letters, numbers, and underscores';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (!Database::validatePassword($password)) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!Database::validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $db = new Database();
            $result = $db->register($username, $password, $email, $fullName);
            
            if ($result['success']) {
                setAlert('Registration successful! Please login with your credentials.', 'success');
                redirect('login.php');
            } else {
                $errors[] = $result['message'];
            }
        } catch (Exception $e) {
            $errors[] = 'Registration failed. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
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
        
        .register-container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            gap: 3rem;
            align-items: center;
        }
        
        /* Left Side - Branding */
        .register-left {
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
        
        .feature-text {
            flex: 1;
        }
        
        .feature-text strong {
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .feature-text span {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Right Side - Form */
        .register-right {
            flex: 0 0 500px;
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
        
        .register-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h3 {
            color: white;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
        }
        
        .form-label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            padding: 0.75rem 1rem;
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
            color: rgba(255, 255, 255, 0.4);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group-text {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            z-index: 10;
        }
        
        .input-group .form-control {
            padding-left: 2.5rem;
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
        
        .form-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        
        .form-check {
            margin-bottom: 1.5rem;
        }
        
        .form-check-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 20px;
            height: 20px;
        }
        
        .form-check-input:checked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .form-check-label {
            color: rgba(255, 255, 255, 0.9);
            margin-left: 0.5rem;
        }
        
        .form-check-label a {
            color: #667eea;
            text-decoration: none;
        }
        
        .form-check-label a:hover {
            text-decoration: underline;
        }
        
        .btn-register {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.5s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-register:hover::before {
            left: 100%;
        }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
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
        
        .login-link {
            text-align: center;
        }
        
        .login-link a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            color: #667eea;
        }
        
        .alert {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: white;
        }
        
        .alert-danger {
            background: rgba(245, 54, 92, 0.1);
            border-color: rgba(245, 54, 92, 0.3);
        }
        
        .alert-success {
            background: rgba(45, 206, 137, 0.1);
            border-color: rgba(45, 206, 137, 0.3);
        }
        
        .alert ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .register-container {
                flex-direction: column;
                gap: 2rem;
            }
            
            .register-left {
                text-align: center;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .register-right {
                flex: 0 0 auto;
                width: 100%;
                max-width: 500px;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 1rem;
            }
            
            .register-card {
                padding: 1.5rem;
            }
            
            .hero-title {
                font-size: 1.75rem;
            }
            
            .features-list {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Left Side - Branding -->
        <div class="register-left">
            <div class="brand-logo">
                <div class="logo-icon">
                    <i class="bi bi-layers"></i>
                </div>
                <div class="brand-name"><?php echo APP_NAME; ?></div>
            </div>
            
            <h1 class="hero-title">Join Our Platform Today</h1>
            <p class="hero-subtitle">Create your account and start managing your workforce with the power of cloud-based technology</p>
            
            <ul class="features-list">
                <li class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="feature-text">
                        <strong>Secure & Private</strong>
                        <span>Your data is encrypted and protected</span>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <div class="feature-text">
                        <strong>Lightning Fast</strong>
                        <span>Real-time sync with Google Sheets</span>
                    </div>
                </li>
                <li class="feature-item">
                    <div class="feature-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="feature-text">
                        <strong>Team Collaboration</strong>
                        <span>Work together seamlessly</span>
                    </div>
                </li>
            </ul>
        </div>
        
        <!-- Right Side - Registration Form -->
        <div class="register-right">
            <div class="register-card">
                <div class="register-header">
                    <h3>Create Account</h3>
                    <p>Fill in your details to get started</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="register.php">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Choose username"
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       required>
                            </div>
                            <small class="form-text">3-20 characters only</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Your email"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                            <input type="text" 
                                   class="form-control" 
                                   id="full_name" 
                                   name="full_name" 
                                   placeholder="Enter your full name"
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Choose password"
                                       required>
                                <button class="password-toggle" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="form-text">Minimum 6 characters</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Confirm password"
                                       required>
                                <button class="password-toggle" type="button" id="toggleConfirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#">terms and conditions</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-register">
                        <i class="bi bi-person-plus"></i> Create Account
                    </button>
                    
                    <div class="divider">
                        <span>OR</span>
                    </div>
                    
                    <div class="login-link">
                        <a href="login.php">Already have an account? <strong>Sign In</strong></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>