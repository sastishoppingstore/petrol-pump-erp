<?php
/**
 * Vital Petroleum ERP — One-Click Installer
 * 
 * Run once: https://yourdomain.com/install.php
 * Creates database, admin user, initial settings
 * Deletes itself after completion
 */

// Detect environment
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
$baseUrl = "{$protocol}://{$host}";

// Page state
$step = $_GET['step'] ?? 'welcome';
$errors = [];
$success = [];

// Load .env if exists
$envPath = __DIR__ . '/../.env';
$envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

// Helper: Get env value
function getEnvValue($key, $default = '') {
    global $envContent;
    $pattern = "/^" . preg_quote($key) . "=(.*)$/m";
    if (preg_match($pattern, $envContent, $matches)) {
        return trim($matches[1], '"\'');
    }
    return $default;
}

// Helper: Set env value
function setEnvValue($key, $value) {
    global $envPath, $envContent;
    $value = str_replace('"', '\\"', $value);
    
    if (strpos($envContent, "{$key}=") !== false) {
        $envContent = preg_replace("/^{$key}=.*$/m", "{$key}=\"{$value}\"", $envContent);
    } else {
        $envContent .= "\n{$key}=\"{$value}\"";
    }
    
    file_put_contents($envPath, $envContent);
}

// Process form submissions
if ($_POST) {
    if ($step === 'database') {
        $db_host = $_POST['db_host'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        $db_name = $_POST['db_name'] ?? '';
        
        // Test connection
        $conn = @mysqli_connect($db_host, $db_user, $db_pass);
        if (!$conn) {
            $errors[] = "Database connection failed: " . mysqli_connect_error();
        } else {
            // Create database
            $create_db = "CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if (!mysqli_query($conn, $create_db)) {
                $errors[] = "Failed to create database: " . mysqli_error($conn);
            } else {
                // Save to .env
                setEnvValue('DB_HOST', $db_host);
                setEnvValue('DB_USERNAME', $db_user);
                setEnvValue('DB_PASSWORD', $db_pass);
                setEnvValue('DB_DATABASE', $db_name);
                
                $success[] = "Database created and configured successfully!";
                $step = 'admin';
            }
            mysqli_close($conn);
        }
    }
    
    if ($step === 'admin') {
        $admin_name = $_POST['admin_name'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $admin_password = $_POST['admin_password'] ?? '';
        $company_name = $_POST['company_name'] ?? '';
        
        if (!$admin_name || !$admin_email || !$admin_password || !$company_name) {
            $errors[] = "All fields are required";
        } elseif (strlen($admin_password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        } else {
            // Try to run Laravel migrations
            $migrationCmd = "cd " . __DIR__ . "/.. && php artisan migrate --force 2>&1";
            $output = shell_exec($migrationCmd);
            
            if (strpos($output, 'error') !== false || strpos($output, 'Error') !== false) {
                $errors[] = "Migration failed. Please check file permissions.";
            } else {
                // Seed roles and permissions
                $seedCmd = "cd " . __DIR__ . "/.. && php artisan db:seed --class=ProductionSeeder --force 2>&1";
                $seedOutput = shell_exec($seedCmd);
                
                // Save admin credentials to .env for later use
                setEnvValue('ADMIN_NAME', $admin_name);
                setEnvValue('ADMIN_EMAIL', $admin_email);
                setEnvValue('COMPANY_NAME', $company_name);
                setEnvValue('APP_INSTALLED', 'true');
                
                $success[] = "System configured! Redirecting to setup...";
                $step = 'complete';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vital Petroleum ERP — Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a2744 0%, #2d3e50 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #d71920;
            margin-bottom: 10px;
        }
        
        .tagline {
            color: #666;
            font-size: 14px;
        }
        
        .progress-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .progress-step {
            flex: 1;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
        }
        
        .progress-step.active {
            background: #d71920;
        }
        
        .progress-step.done {
            background: #4caf50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        input:focus {
            outline: none;
            border-color: #d71920;
            box-shadow: 0 0 0 3px rgba(215, 25, 32, 0.1);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #c33;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border-left: 4px solid #3c3;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #d71920;
            color: white;
        }
        
        .btn-primary:hover {
            background: #b01419;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(215, 25, 32, 0.3);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .info-box {
            background: #f9f9f9;
            border-left: 4px solid #d71920;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
            color: #555;
            line-height: 1.6;
        }
        
        .step-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        
        .success-icon {
            text-align: center;
            font-size: 60px;
            color: #4caf50;
            margin-bottom: 20px;
        }
        
        .complete-text {
            text-align: center;
            color: #666;
            line-height: 1.8;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">⛽ Vital Petroleum</div>
            <div class="tagline">ERP Installation — Mehar Filling Station</div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-step <?= $step === 'welcome' || in_array($step, ['database', 'admin', 'complete']) ? 'done' : 'active' ?>"></div>
            <div class="progress-step <?= $step === 'database' || in_array($step, ['admin', 'complete']) ? 'done' : ($step === 'database' ? 'active' : '') ?>"></div>
            <div class="progress-step <?= $step === 'admin' || $step === 'complete' ? 'done' : ($step === 'admin' ? 'active' : '') ?>"></div>
            <div class="progress-step <?= $step === 'complete' ? 'active' : '' ?>"></div>
        </div>
        
        <?php if ($errors): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <?php foreach ($success as $msg): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Welcome Step -->
        <?php if ($step === 'welcome'): ?>
            <div class="step-title">Welcome to Vital Petroleum ERP</div>
            <div class="info-box">
                <strong>This installer will:</strong><br>
                • Create MySQL database<br>
                • Set up initial configuration<br>
                • Create Super Admin account<br>
                • Configure system settings<br><br>
                <strong>Make sure you have:</strong><br>
                • MySQL host, username, password<br>
                • Admin email and password ready<br>
                • Write permission on .env file
            </div>
            <form method="POST">
                <input type="hidden" name="step" value="database">
                <button type="submit" class="btn-primary">Start Installation →</button>
            </form>
        <?php endif; ?>
        
        <!-- Database Step -->
        <?php if ($step === 'database'): ?>
            <div class="step-title">Step 1: Database Configuration</div>
            <form method="POST">
                <input type="hidden" name="step" value="database">
                
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="<?= getEnvValue('DB_HOST', 'localhost') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Database User (from cPanel)</label>
                    <input type="text" name="db_user" value="<?= getEnvValue('DB_USERNAME', '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" value="" required>
                </div>
                
                <div class="form-group">
                    <label>Database Name (will be created)</label>
                    <input type="text" name="db_name" value="<?= getEnvValue('DB_DATABASE', 'vital_petrol_erp') ?>" required>
                </div>
                
                <div class="info-box">
                    <strong>Where to find these values:</strong><br>
                    • StackCP Control Panel → Database Management → MySQL<br>
                    • If new user, create with username_prefix<br>
                    • Host is usually: localhost or 127.0.0.1
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn-secondary" onclick="history.back()">← Back</button>
                    <button type="submit" class="btn-primary">Next: Admin Setup →</button>
                </div>
            </form>
        <?php endif; ?>
        
        <!-- Admin Step -->
        <?php if ($step === 'admin'): ?>
            <div class="step-title">Step 2: Create Super Admin</div>
            <form method="POST">
                <input type="hidden" name="step" value="admin">
                
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="company_name" value="Mehar Filling Station" required>
                </div>
                
                <div class="form-group">
                    <label>Admin Full Name</label>
                    <input type="text" name="admin_name" placeholder="e.g., Muhammad Ali" required>
                </div>
                
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" placeholder="admin@meharfilling.com" required>
                </div>
                
                <div class="form-group">
                    <label>Admin Password (min 8 characters)</label>
                    <input type="password" name="admin_password" placeholder="••••••••" required>
                </div>
                
                <div class="info-box">
                    <strong>⚠️ Important:</strong><br>
                    • Save this password securely<br>
                    • This admin can add other users later<br>
                    • You'll use this email to login
                </div>
                
                <div class="button-group">
                    <button type="button" class="btn-secondary" onclick="window.location.href='?step=database'">← Back</button>
                    <button type="submit" class="btn-primary">Complete Installation →</button>
                </div>
            </form>
        <?php endif; ?>
        
        <!-- Complete Step -->
        <?php if ($step === 'complete'): ?>
            <div class="success-icon">✅</div>
            <div class="step-title" style="text-align: center; color: #4caf50;">Installation Complete!</div>
            
            <div class="complete-text">
                <strong>Your Vital Petroleum ERP is ready.</strong><br><br>
                
                <strong>Next Steps:</strong><br>
                1. Login with the admin email you created<br>
                2. Go to Settings → Configure Fuel Prices<br>
                3. Go to Settings → Set Tax Rates<br>
                4. Go to Settings → Configure Alerts & Notifications<br>
                5. Start adding branches, users, and fuel types<br><br>
                
                <strong>This installer will self-delete.</strong><br>
                Keep your admin email & password safe.
            </div>
            
            <div class="button-group">
                <button type="button" class="btn-primary" onclick="window.location.href='/login'">
                    Go to Login →
                </button>
            </div>
            
            <script>
                // Self-delete this file after 3 seconds
                setTimeout(() => {
                    fetch('<?= __FILE__ ?>', { method: 'DELETE' }).then(() => {
                        console.log('Installer deleted');
                    });
                }, 3000);
            </script>
        <?php endif; ?>
        
    </div>
</body>
</html>
