<?php
require_once 'config.php';
require_once 'Database.php';

session_start();

$message = '';
$messageType = 'info';
$testResult = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Test connection
    if (isset($_POST['test'])) {
        $scriptUrl = $_POST['script_url'] ?? GOOGLE_SCRIPT_URL;
        
        if ($scriptUrl && $scriptUrl !== 'YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE') {
            try {
                $db = new Database($scriptUrl);
                // Try a simple test request
                $testData = [
                    'action' => 'test'
                ];
                
                $ch = curl_init($scriptUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($response && $httpCode === 200) {
                    $testResult = json_decode($response, true);
                    if (isset($testResult['success']) && $testResult['success']) {
                        $message = 'Connection successful! Google Apps Script is responding.';
                        $messageType = 'success';
                        
                        // Update config file with the URL
                        $configContent = file_get_contents('config.php');
                        $configContent = preg_replace(
                            "/define\('GOOGLE_SCRIPT_URL', '.*?'\);/",
                            "define('GOOGLE_SCRIPT_URL', '$scriptUrl');",
                            $configContent
                        );
                        file_put_contents('config.php', $configContent);
                    } else {
                        $message = 'Connection established but unexpected response received.';
                        $messageType = 'warning';
                    }
                } else {
                    $message = 'Failed to connect. Please check your URL and ensure the script is deployed.';
                    $messageType = 'danger';
                }
            } catch (Exception $e) {
                $message = 'Connection error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        } else {
            $message = 'Please enter a valid Google Apps Script URL';
            $messageType = 'warning';
        }
    }
    
    // Run setup
    if (isset($_POST['setup'])) {
        try {
            $db = new Database();
            $result = $db->setupSheet();
            
            if ($result['success']) {
                $message = 'Setup completed successfully! Default admin user created.';
                $messageType = 'success';
                $_SESSION['setup_complete'] = true;
            } else {
                $message = 'Setup failed: ' . $result['message'];
                $messageType = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Setup error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Check current configuration
$isConfigured = GOOGLE_SCRIPT_URL !== 'YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - <?php echo APP_NAME; ?></title>
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
        
        .setup-container {
            width: 100%;
            max-width: 800px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.8s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .setup-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #f093fb 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .setup-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .setup-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
        }
        
        .step-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        
        .step-card:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }
        
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            font-weight: 600;
            margin-right: 1rem;
        }
        
        .step-title {
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .step-content {
            color: rgba(255, 255, 255, 0.8);
            margin-left: 3rem;
        }
        
        .step-content ol, .step-content ul {
            margin-top: 1rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .step-content li {
            margin-bottom: 0.5rem;
        }
        
        .code-block {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #4ade80;
            overflow-x: auto;
        }
        
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
            padding: 0.875rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            color: white;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .btn {
            padding: 0.875rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #2ed573 0%, #26de81 100%);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            color: white;
        }
        
        .alert-success {
            background: rgba(45, 206, 137, 0.1);
            border-color: rgba(45, 206, 137, 0.3);
        }
        
        .alert-warning {
            background: rgba(255, 193, 7, 0.1);
            border-color: rgba(255, 193, 7, 0.3);
        }
        
        .alert-danger {
            background: rgba(245, 54, 92, 0.1);
            border-color: rgba(245, 54, 92, 0.3);
        }
        
        .alert-info {
            background: rgba(102, 126, 234, 0.1);
            border-color: rgba(102, 126, 234, 0.3);
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 25px;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status-dot.success {
            background: #2ed573;
        }
        
        .status-dot.error {
            background: #f5365c;
        }
        
        .status-dot.pending {
            background: #ffd93d;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .footer-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 1rem;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: #f093fb;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Floating orbs -->
    <div class="orb orb1"></div>
    <div class="orb orb2"></div>
    
    <div class="setup-container">
        <div class="setup-card">
            <div class="setup-header">
                <div class="logo-icon">
                    <i class="bi bi-gear"></i>
                </div>
                <h1 class="setup-title">Setup & Configuration</h1>
                <p class="setup-subtitle">Configure your MRI SCRIPTS application</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Step 1: Instructions -->
            <div class="step-card">
                <h3 class="step-title">
                    <span class="step-number">1</span>
                    Deploy Google Apps Script
                </h3>
                <div class="step-content">
                    <ol>
                        <li>Create a new Google Sheet</li>
                        <li>Go to <strong>Extensions → Apps Script</strong></li>
                        <li>Delete any existing code and paste the contents of <strong>Code.gs</strong></li>
                        <li>Click <strong>"Deploy" → "New Deployment"</strong></li>
                        <li>Choose type: <strong>"Web app"</strong></li>
                        <li>Configure settings:
                            <ul>
                                <li>Execute as: <strong>Me</strong></li>
                                <li>Who has access: <strong>Anyone</strong></li>
                            </ul>
                        </li>
                        <li>Click <strong>"Deploy"</strong> and copy the Web App URL</li>
                    </ol>
                </div>
            </div>
            
            <!-- Step 2: Configure URL -->
            <div class="step-card">
                <h3 class="step-title">
                    <span class="step-number">2</span>
                    Configure Connection
                </h3>
                <div class="step-content">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="script_url" class="form-label">Google Apps Script Web App URL</label>
                            <input type="url" 
                                   class="form-control" 
                                   id="script_url" 
                                   name="script_url" 
                                   placeholder="https://script.google.com/macros/s/YOUR_DEPLOYMENT_ID/exec"
                                   value="<?php echo $isConfigured ? GOOGLE_SCRIPT_URL : ''; ?>"
                                   required>
                            <small style="color: rgba(255,255,255,0.5);">Paste your deployed Web App URL here</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="test" class="btn btn-primary">
                                <i class="bi bi-wifi"></i> Test Connection
                            </button>
                            
                            <?php if ($isConfigured): ?>
                                <div class="status-indicator">
                                    <span class="status-dot success"></span>
                                    <span style="color: rgba(255,255,255,0.8);">Configured</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <?php if ($testResult): ?>
                        <div class="code-block">
                            <?php echo json_encode($testResult, JSON_PRETTY_PRINT); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Step 3: Initialize -->
            <div class="step-card">
                <h3 class="step-title">
                    <span class="step-number">3</span>
                    Initialize Database
                </h3>
                <div class="step-content">
                    <p>This will create the necessary sheets and add a default admin user.</p>
                    
                    <form method="POST" class="mt-3">
                        <button type="submit" 
                                name="setup" 
                                class="btn btn-success"
                                <?php echo !$isConfigured ? 'disabled' : ''; ?>>
                            <i class="bi bi-play-circle"></i> Run Setup
                        </button>
                        
                        <?php if (!$isConfigured): ?>
                            <small style="color: rgba(255,255,255,0.5); display: block; margin-top: 0.5rem;">
                                Please configure and test the connection first
                            </small>
                        <?php endif; ?>
                    </form>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Default Admin Credentials:</strong><br>
                        <div style="margin-left: 1rem; margin-top: 0.5rem;">
                            Username: <code style="color: #4ade80;">admin</code><br>
                            Password: <code style="color: #4ade80;">admin123</code>
                        </div>
                        <small style="color: rgba(255,255,255,0.7);">⚠️ Please change these after first login!</small>
                    </div>
                </div>
            </div>
            
            <div class="footer-links">
                <a href="login.php"><i class="bi bi-box-arrow-in-right"></i> Go to Login</a>
                <a href="register.php"><i class="bi bi-person-plus"></i> Register</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>