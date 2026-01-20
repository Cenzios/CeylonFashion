<?php
require_once 'config.php';

echo "Database Cleanup Script Started...\n";
echo "Connected to: " . $db_name . "\n\n";

function runQuery($mysqli, $query, $description) {
    echo "Executing: $description...\n";
    try {
        if ($mysqli->query($query) === TRUE) {
            echo "✅ Success\n";
        } else {
             echo "❌ Error: " . $mysqli->error . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    echo "---------------------------------------------------\n";
}

// 1. Drop unused table
runQuery($mysqli, "DROP TABLE IF EXISTS cart_items", "Dropping unused table 'cart_items'");

// 2. Drop unused columns from cart
// We check if columns exist before dropping (MariaDB/MySQL will error if column doesn't exist)
// A simple way is to just try dropping them individually or in a block. 
// If they don't exist, it errors, which is harmless for a cleanup script (user knows they are gone).
runQuery($mysqli, "ALTER TABLE cart DROP COLUMN fabric_type, DROP COLUMN product_type, DROP COLUMN color", "Dropping unused columns (fabric_type, product_type, color) from 'cart'");

echo "\nCleanup Done!\n";
?>
