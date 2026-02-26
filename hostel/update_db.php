<?php
include('includes/config.php');

$queries = [
    "ALTER TABLE userregistration ADD COLUMN fees_paid DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE userregistration ADD COLUMN accommodation_paid DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE userregistration ADD COLUMN registration_paid DECIMAL(15,2) DEFAULT 0",
    "ALTER TABLE userregistration ADD COLUMN payment_status VARCHAR(50) DEFAULT 'Pending'"
];

foreach ($queries as $query) {
    if ($mysqli->query($query)) {
        echo "Successfully executed: $query\n";
    } else {
        echo "Error executing $query: " . $mysqli->error . "\n";
    }
}
?>
