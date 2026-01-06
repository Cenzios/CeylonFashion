<?php
include 'config.php';
function checkCol($mysqli, $table, $col) {
    if ($mysqli->query("SHOW COLUMNS FROM $table LIKE '$col'")->num_rows > 0) {
        echo "$table.$col exists.\n";
    } else {
        echo "$table.$col missing.\n";
        // Attempt add
        if ($mysqli->query("ALTER TABLE $table ADD COLUMN $col VARCHAR(50) AFTER product_id")) {
             echo "$table.$col added.\n";
        } else {
             echo "Error adding $table.$col: " . $mysqli->error . "\n";
        }
    }
}
checkCol($mysqli, 'orders', 'product_code');
checkCol($mysqli, 'reseller_products', 'product_code');
?>
