<?php
session_start();
if (!isset($_SESSION['is_superadmin'])) {
    header("Location: ../superadmin-login.php");
    exit();
}
require_once('../includes/config.php');

// Create backups directory
$backup_dir = '../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Settings file
$settings_file = '../config/backup.json';
$settings_dir = '../config';
if (!file_exists($settings_dir)) mkdir($settings_dir, 0755, true);

$default_settings = [
    'auto_backup_enabled' => false,
    'backup_frequency' => 'daily',
    'backup_retention' => 7
];

$settings = $default_settings;
if (file_exists($settings_file)) {
    $saved = json_decode(file_get_contents($settings_file), true);
    if (is_array($saved)) $settings = array_merge($default_settings, $saved);
}

// Handle actions
$success_message = $error_message = '';
$backups = [];

// Scan backups directory
if (file_exists($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'sql' || pathinfo($file, PATHINFO_EXTENSION) == 'gz') {
            $filepath = $backup_dir . $file;
            $backups[] = [
                'filename' => $file,
                'filesize' => filesize($filepath),
                'created_at' => date('Y-m-d H:i:s', filemtime($filepath))
            ];
        }
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
}

// Create backup
if (isset($_POST['create_backup'])) {
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    $content = "-- Hostel Management System Backup\n";
    $content .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
    $content .= "-- User: " . $_SESSION['username'] . "\n\n";
    
    if (file_put_contents($filepath, $content)) {
        $success_message = "✅ Backup created: $filename";
        header("Refresh:0");
        exit();
    }
}

// Delete backup
if (isset($_POST['delete_backup']) && isset($_POST['filename'])) {
    $filepath = $backup_dir . $_POST['filename'];
    if (file_exists($filepath) && unlink($filepath)) {
        $success_message = "✅ Backup deleted";
        header("Refresh:0");
        exit();
    }
}

// Save settings
if (isset($_POST['save_settings'])) {
    $new_settings = [
        'auto_backup_enabled' => isset($_POST['auto_backup_enabled']),
        'backup_frequency' => $_POST['backup_frequency'],
        'backup_retention' => intval($_POST['backup_retention'])
    ];
    
    if (file_put_contents($settings_file, json_encode($new_settings, JSON_PRETTY_PRINT))) {
        $success_message = "✅ Backup settings saved!";
        $settings = $new_settings;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #1a1e2c; font-family: 'Inter', sans-serif; padding: 30px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .card-header { background: linear-gradient(135deg, #1a1e2c, #2d3a4a); color: white; border-radius: 20px 20px 0 0 !important; padding: 25px; }
        .btn-create { background: #06d6a0; color: white; border: none; padding: 12px 30px; border-radius: 40px; }
        .btn-save { background: #4361ee; color: white; border: none; padding: 12px 30px; border-radius: 40px; }
        .btn-back { background: white; color: #1e293b; border: 1px solid #e2e8f0; padding: 12px 30px; border-radius: 40px; text-decoration: none; }
        .backup-item { background: #f8fafc; border-left: 4px solid #06d6a0; border-radius: 12px; padding: 15px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="bi bi-database-check me-2"></i> Backup & Restore</h3>
            </div>
            <div class="card-body">
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <!-- Manual Backup -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5><i class="bi bi-cloud-arrow-up me-2"></i> Manual Backup</h5>
                    <form method="POST">
                        <button type="submit" name="create_backup" class="btn-create">
                            <i class="bi bi-cloud-plus"></i> Create Backup
                        </button>
                    </form>
                </div>
                
                <!-- Auto Backup Settings -->
                <div class="card mb-4">
                    <div class="card-body bg-light">
                        <h5 class="mb-3"><i class="bi bi-gear-wide me-2"></i> Automatic Backup</h5>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               id="auto_backup_enabled" name="auto_backup_enabled" value="1"
                                               <?php echo $settings['auto_backup_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="auto_backup_enabled">Enable Auto Backup</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="backup_frequency">
                                        <option value="daily" <?php echo $settings['backup_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo $settings['backup_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo $settings['backup_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="backup_retention" 
                                           value="<?php echo $settings['backup_retention']; ?>" min="1" max="90">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="save_settings" class="btn-save w-100">
                                        <i class="bi bi-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Recent Backups -->
                <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i> Recent Backups</h5>
                
                <?php if (empty($backups)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cloud-slash fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No backups found</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($backups, 0, 10) as $backup): ?>
                        <div class="backup-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><i class="bi bi-file-earmark-sql me-2"></i> <?php echo $backup['filename']; ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php echo date('M d, Y H:i', strtotime($backup['created_at'])); ?> • 
                                    <?php echo round($backup['filesize'] / 1024, 2); ?> KB
                                </small>
                            </div>
                            <div>
                                <a href="<?php echo $backup_dir . $backup['filename']; ?>" download class="btn btn-sm btn-outline-success me-2">
                                    <i class="bi bi-download"></i>
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this backup?');">
                                    <input type="hidden" name="filename" value="<?php echo $backup['filename']; ?>">
                                    <button type="submit" name="delete_backup" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="../superadmin-dashboard.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>