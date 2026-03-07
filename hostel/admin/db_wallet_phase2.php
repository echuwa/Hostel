<?php
// DB Migration: Wallet Phase 2 — Real Deposit Flow + Refund System
session_start();
include('../includes/config.php');

if(!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    die("<div style='color:red;font-family:monospace;'>❌ Access Denied. Super Admin only.</div>");
}

echo "<style>body{font-family:monospace;padding:30px;background:#0f172a;color:#94a3b8;}h2{color:#4ade80;}
.ok{color:#4ade80;} .err{color:#f87171;} .info{color:#60a5fa;} .box{background:#1e293b;padding:15px;border-radius:8px;margin:10px 0;}</style>";
echo "<h2>🏦 Wallet System — Phase 2 Database Migration</h2>";
echo "<div class='box'>";

$errors = 0;

// ─── 1. deposit_requests table ───────────────────────────────────────
$sql1 = "CREATE TABLE IF NOT EXISTS deposit_requests (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    user_id         INT NOT NULL,
    amount          DECIMAL(15,2) NOT NULL,
    payment_method  VARCHAR(50) NOT NULL,
    reference_no    VARCHAR(150) NOT NULL,
    proof_file      VARCHAR(255) NOT NULL,
    status          ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    admin_id        INT DEFAULT NULL,
    admin_note      TEXT DEFAULT NULL,
    requested_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at     TIMESTAMP NULL DEFAULT NULL
)";
if($mysqli->query($sql1)) {
    echo "<div class='ok'>✔ Table <strong>deposit_requests</strong> — OK</div>";
} else {
    echo "<div class='err'>✘ deposit_requests: " . $mysqli->error . "</div>"; $errors++;
}

// ─── 2. refund_requests table ─────────────────────────────────────────
$sql2 = "CREATE TABLE IF NOT EXISTS refund_requests (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    user_id         INT NOT NULL,
    admin_id        INT NOT NULL,
    amount          DECIMAL(15,2) NOT NULL,
    reason          TEXT NOT NULL,
    payment_ref     VARCHAR(150) DEFAULT NULL,
    fee_type        VARCHAR(50) DEFAULT NULL,
    reverse_fee     TINYINT(1) DEFAULT 0,
    status          ENUM('Pending','Completed','Rejected') DEFAULT 'Pending',
    confirm_token   VARCHAR(128) DEFAULT NULL,
    token_expires   TIMESTAMP NULL DEFAULT NULL,
    confirmed_at    TIMESTAMP NULL DEFAULT NULL,
    completed_at    TIMESTAMP NULL DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if($mysqli->query($sql2)) {
    echo "<div class='ok'>✔ Table <strong>refund_requests</strong> — OK</div>";
} else {
    echo "<div class='err'>✘ refund_requests: " . $mysqli->error . "</div>"; $errors++;
}

// ─── 3. Ensure upload folder exists ──────────────────────────────────
$upload_dir = __DIR__ . '/../uploads/deposit_proofs/';
if(!is_dir($upload_dir)) {
    if(mkdir($upload_dir, 0755, true)) {
        echo "<div class='ok'>✔ Upload folder <strong>uploads/deposit_proofs/</strong> created</div>";
        // Write security .htaccess
        file_put_contents($upload_dir . '.htaccess', "php_flag engine off\nOptions -ExecCGI -Indexes\n");
        echo "<div class='ok'>✔ Security .htaccess written to upload folder</div>";
    } else {
        echo "<div class='err'>✘ Could not create upload folder — check permissions</div>"; $errors++;
    }
} else {
    echo "<div class='info'>ℹ Upload folder already exists — OK</div>";
    if(!file_exists($upload_dir . '.htaccess')) {
        file_put_contents($upload_dir . '.htaccess', "php_flag engine off\nOptions -ExecCGI -Indexes\n");
        echo "<div class='ok'>✔ .htaccess security file added to existing folder</div>";
    }
}

echo "</div>";

if($errors === 0) {
    echo "<div class='box'><span class='ok'>✅ Migration complete with 0 errors. All systems ready.</span></div>";
} else {
    echo "<div class='box'><span class='err'>⚠ Migration finished with {$errors} error(s). Check above.</span></div>";
}

echo "<br><a href='wallet-management.php' style='color:#4361ee;font-weight:700;'>← Return to Wallet Management</a>";
?>
