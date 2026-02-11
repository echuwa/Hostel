<?php
session_start();

// ============================================
// CHECK SUPER ADMIN AUTHENTICATION
// ============================================
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: ../superadmin-login.php");
    exit();
}

require_once('../includes/config.php');

// ============================================
// SIMPLE SETTINGS - DIRECT FILE WRITE
// ============================================
$settings_file = '../config/settings.json';
$settings_dir = '../config';

// Create config directory if not exists
if (!file_exists($settings_dir)) {
    mkdir($settings_dir, 0755, true);
}

// Default settings
$default_settings = [
    'site_name' => 'Hostel Management System',
    'site_description' => 'Modern Hostel Management Platform',
    'admin_email' => 'admin@hostel.com',
    'timezone' => 'Africa/Dar_es_Salaam',
    'items_per_page' => 20,
    'maintenance_mode' => false,
    'registration_enabled' => true
];

// Load existing settings
$settings = $default_settings;
if (file_exists($settings_file)) {
    $saved = json_decode(file_get_contents($settings_file), true);
    if (is_array($saved)) {
        $settings = array_merge($default_settings, $saved);
    }
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    
    $new_settings = [];
    foreach ($default_settings as $key => $default) {
        if (isset($_POST[$key])) {
            if (is_bool($default)) {
                $new_settings[$key] = isset($_POST[$key]) ? true : false;
            } elseif (is_int($default)) {
                $new_settings[$key] = intval($_POST[$key]);
            } else {
                $new_settings[$key] = trim($_POST[$key]);
            }
        }
    }
    
    // Save to JSON file
    if (file_put_contents($settings_file, json_encode($new_settings, JSON_PRETTY_PRINT))) {
        $success_message = "✅ Settings saved successfully!";
        $settings = array_merge($settings, $new_settings);
    } else {
        $error_message = "❌ Failed to save settings. Check file permissions.";
    }
}

// Timezone list
$timezones = [
    'Africa/Dar_es_Salaam' => 'Dar es Salaam',
    'Africa/Nairobi' => 'Nairobi',
    'Africa/Johannesburg' => 'Johannesburg',
    'Africa/Lagos' => 'Lagos',
    'Africa/Cairo' => 'Cairo',
    'Europe/London' => 'London',
    'America/New_York' => 'New York',
    'Asia/Dubai' => 'Dubai',
    'Asia/Singapore' => 'Singapore',
    'Australia/Sydney' => 'Sydney'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; padding: 30px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #4361ee, #3a56d4); color: white; border-radius: 20px 20px 0 0 !important; padding: 25px; }
        .card-body { padding: 30px; }
        .form-label { font-weight: 600; color: #1e293b; }
        .btn-save { background: #4361ee; color: white; border: none; padding: 12px 30px; border-radius: 40px; font-weight: 600; }
        .btn-save:hover { background: #3a56d4; color: white; }
        .btn-back { background: white; color: #1e293b; border: 1px solid #e2e8f0; padding: 12px 30px; border-radius: 40px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="bi bi-gear-wide-connected me-2"></i> General Settings</h3>
            </div>
            <div class="card-body">
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" class="form-control" name="site_name" 
                               value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Site Description</label>
                        <textarea class="form-control" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Admin Email</label>
                            <input type="email" class="form-control" name="admin_email" 
                                   value="<?php echo htmlspecialchars($settings['admin_email']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Timezone</label>
                            <select class="form-select" name="timezone">
                                <?php foreach ($timezones as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $settings['timezone'] == $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Items Per Page</label>
                            <input type="number" class="form-control" name="items_per_page" 
                                   value="<?php echo $settings['items_per_page']; ?>" min="5" max="100">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   id="maintenance_mode" name="maintenance_mode" value="1"
                                   <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                        </div>
                        
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   id="registration_enabled" name="registration_enabled" value="1"
                                   <?php echo $settings['registration_enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="registration_enabled">Allow Registration</label>
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