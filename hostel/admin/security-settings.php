<?php
session_start();
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: ../superadmin-login.php");
    exit();
}
require_once('../includes/config.php');

// Settings file
$settings_file = '../config/security.json';
$settings_dir = '../config';

if (!file_exists($settings_dir)) mkdir($settings_dir, 0755, true);

$default_settings = [
    'session_timeout' => 1800,
    'password_min_length' => 8,
    'max_login_attempts' => 5,
    'lockout_time' => 900,
    'two_factor_auth' => false,
    'force_https' => true
];

$settings = $default_settings;
if (file_exists($settings_file)) {
    $saved = json_decode(file_get_contents($settings_file), true);
    if (is_array($saved)) $settings = array_merge($default_settings, $saved);
}

$success_message = $error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $new_settings = [];
    foreach ($default_settings as $key => $default) {
        if (isset($_POST[$key])) {
            if (is_bool($default)) {
                $new_settings[$key] = isset($_POST[$key]) ? true : false;
            } else {
                $new_settings[$key] = intval($_POST[$key]);
            }
        }
    }
    
    if (file_put_contents($settings_file, json_encode($new_settings, JSON_PRETTY_PRINT))) {
        $success_message = "✅ Security settings saved!";
        $settings = array_merge($settings, $new_settings);
    } else {
        $error_message = "❌ Failed to save settings.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0b1a2e; font-family: 'Inter', sans-serif; padding: 30px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card-header { background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border-radius: 20px 20px 0 0 !important; padding: 25px; }
        .btn-save { background: #4361ee; color: white; border: none; padding: 12px 30px; border-radius: 40px; }
        .btn-back { background: white; color: #1e293b; border: 1px solid #e2e8f0; padding: 12px 30px; border-radius: 40px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="bi bi-shield-lock me-2"></i> Security Settings</h3>
            </div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Session Timeout (seconds)</label>
                            <input type="number" class="form-control" name="session_timeout" 
                                   value="<?php echo $settings['session_timeout']; ?>" min="120" max="7200" step="300">
                            <small class="text-muted"><?php echo $settings['session_timeout']/60; ?> minutes</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password Min Length</label>
                            <input type="number" class="form-control" name="password_min_length" 
                                   value="<?php echo $settings['password_min_length']; ?>" min="6" max="20">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max Login Attempts</label>
                            <input type="number" class="form-control" name="max_login_attempts" 
                                   value="<?php echo $settings['max_login_attempts']; ?>" min="1" max="10">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lockout Time (seconds)</label>
                            <input type="number" class="form-control" name="lockout_time" 
                                   value="<?php echo $settings['lockout_time']; ?>" min="60" max="3600" step="60">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   id="two_factor_auth" name="two_factor_auth" value="1"
                                   <?php echo $settings['two_factor_auth'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="two_factor_auth">Enable Two-Factor Authentication</label>
                        </div>
                        
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   id="force_https" name="force_https" value="1"
                                   <?php echo $settings['force_https'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="force_https">Force HTTPS</label>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="../superadmin-dashboard.php" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <button type="submit" name="save_settings" class="btn-save">
                            <i class="bi bi-check-circle"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>