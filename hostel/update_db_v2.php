<?php
include('includes/config.php');

$queries = [
    "ALTER TABLE userregistration ADD COLUMN fee_control_no VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE userregistration ADD COLUMN acc_control_no VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE userregistration ADD COLUMN reg_control_no VARCHAR(20) DEFAULT NULL",
    "CREATE TABLE IF NOT EXISTS payment_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        regNo VARCHAR(50),
        control_no VARCHAR(20),
        amount DECIMAL(15,2),
        payment_type VARCHAR(50),
        transaction_id VARCHAR(100),
        status VARCHAR(20) DEFAULT 'Success',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $query) {
    if ($mysqli->query($query)) {
        echo "Successfully executed: $query\n";
    } else {
        echo "Error executing $query: " . $mysqli->error . "\n";
    }
}
?>
