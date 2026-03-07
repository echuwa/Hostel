<?php
// DB Migration Script: Wallet System Integration
// This script adds the wallet_balance column and creates the wallet_transactions table.

session_start();
include('../includes/config.php');

// Restricted to Super Admin or Manual Execution
if(!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    echo "Access Denied. Only Super Admins can execute database updates.";
    exit();
}

echo "<h3>Wallet System Database Migration</h3>";

// 1. Add wallet_balance to userregistration table
$sql1 = "ALTER TABLE userregistration ADD COLUMN IF NOT EXISTS wallet_balance DECIMAL(15,2) DEFAULT 0.00 AFTER email";
if($mysqli->query($sql1)) {
    echo "<div style='color:green;'>✔️ SUCCESS: Column 'wallet_balance' processed.</div>";
} else {
    echo "<div style='color:red;'>❌ ERROR adding column: " . $mysqli->error . "</div>";
}

// 2. Create wallet_transactions table
$sql2 = "CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_type ENUM('Deposit', 'Payment', 'Withdrawal') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    prev_balance DECIMAL(15,2) NOT NULL,
    new_balance DECIMAL(15,2) NOT NULL,
    description TEXT,
    reference_no VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('Pending', 'Completed', 'Cancelled') DEFAULT 'Completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if($mysqli->query($sql2)) {
    echo "<div style='color:green;'>✔️ SUCCESS: Table 'wallet_transactions' processed.</div>";
} else {
    echo "<div style='color:red;'>❌ ERROR creating table: " . $mysqli->error . "</div>";
}

echo "<br><a href='dashboard.php'>Return to Dashboard</a>";
?>
