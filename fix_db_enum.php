<?php
include 'config.php';

// 1. Alter Table
$sql = "ALTER TABLE reseller_products MODIFY COLUMN status ENUM('pending', 'approved', 'sold', 'rejected', 'available') DEFAULT 'pending'";
if ($mysqli->query($sql)) {
    echo "Table Start Successfully Modified.\n";
} else {
    echo "Error modifying table: " . $mysqli->error . "\n";
}

// 2. Fix broken data (empty strings)
$sql = "UPDATE reseller_products SET status = 'available' WHERE status = ''";
if ($mysqli->query($sql)) {
    echo "Fixed " . $mysqli->affected_rows . " rows with empty status.\n";
} else {
    echo "Error fixing data: " . $mysqli->error . "\n";
}

// 3. Verify Schema
$result = $mysqli->query("SHOW COLUMNS FROM reseller_products LIKE 'status'");
$row = $result->fetch_assoc();
echo "New Type: " . $row['Type'] . "\n";
?>
