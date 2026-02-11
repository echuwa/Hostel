<?php
session_start();
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: ../superadmin-login.php");
    exit();
}
require_once('../includes/config.php');

$settings_file = '../config/email.json';
$settings_dir = '../config';

if (!file_exists($settings_dir)) mkdir($settings_dir, 0755, true);

$default_settings = [
    'mail_driver' => 'smtp',
    'mail_host' => 'smtp.gmail.com',
    'mail_port' => 587,
    'mail_encryption' => 'tls',
    'mail_username' => '',
    'mail_password' => '',
    'mail_from_address' => 'noreply@hostel.com',
    'mail_from_name' => 'Hostel System',
    'email_notifications' => true
];

$settings = $default_settings;
if (file_exists($settings_file)) {
    $saved = json_decode(file_get_contents($settings_file), true);
    if (is_array($saved)) $settings = array_merge($default_settings, $saved);
}

$success_message = $error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
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
        
        if (file_put_contents($settings_file, json_encode($new_settings, JSON_PRETTY_PRINT))) {
            $success_message = "✅ Email settings saved!";
            $settings = array_merge($settings, $new_settings);
        }
    }
    
    if (isset($_POST['test_email'])) {
        $success_message = "✅ Test email sent! (Demo mode)";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0b3954; font-family: 'Inter', sans-serif; padding: 30px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card-header { background: linear-gradient(135deg, #0b3954, #087e8b); color: white; border-radius: 20px 20px 0 0 !important; padding: 25px; }
        .btn-save { background: #087e8b; color: white; border: none; padding: 12px 30px; border-radius: 40px; }
        .btn-test { background: white; color: #1e293b; border: 1px solid #e2e8f0; padding: 12px 30px; border-radius: 40px; }
        .btn-back { background: white; color: #1e293b; border: 1px solid #e2e8f0; padding: 12px 30px; border-radius: 40px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="bi bi-envelope-paper me-2"></i> Email Settings</h3>
            </div>
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Mail Driver</label>
                        <select class="form-select" name="mail_driver">
                            <option value="smtp" <?php echo $settings['mail_driver'] == 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                            <option value="sendmail" <?php echo $settings['mail_driver'] == 'sendmail' ? 'selected' : ''; ?>>Sendmail</option>
                            <option value="mail" <?php echo $settings['mail_driver'] == 'mail' ? 'selected' : ''; ?>>PHP Mail</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" name="mail_host" value="<?php echo htmlspecialchars($settings['mail_host']); ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Port</label>
                            <input type="number" class="form-control" name="mail_port" value="<?php echo $settings['mail_port']; ?>">
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Encryption</label>
                            <select class="form-select" name="mail_encryption">
                                <option value="tls" <?php echo $settings['mail_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $settings['mail_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="" <?php echo $settings['mail_encryption'] == '' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" name="mail_username" value="<?php echo htmlspecialchars($settings['mail_username']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" name="mail_password" value="<?php echo htmlspecialchars($settings['mail_password']); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Address</label>
                            <input type="email" class="form-control" name="mail_from_address" value="<?php echo htmlspecialchars($settings['mail_from_address']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From Name</label>
                            <input type="text" class="form-control" name="mail_from_name" value="<?php echo htmlspecialchars($settings['mail_from_name']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   id="email_notifications" name="email_notifications" value="1"
                                   <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_notifications">Enable Email Notifications</label>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="../superadmin-dashboard.php" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <div>
                            <button type="submit" name="test_email" class="btn-test me-2">
                                <i class="bi bi-send"></i> Test
                            </button>
                            <button type="submit" name="save_settings" class="btn-save">
                                <i class="bi bi-check-circle"></i> Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>