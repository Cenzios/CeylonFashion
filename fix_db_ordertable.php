<?php
require_once 'config.php';

// Check if order_id is unique
$result = $mysqli->query("SHOW INDEX FROM orders WHERE Key_name = 'order_id'");
if ($result->num_rows > 0) {
    echo "Index 'order_id' found. Attempting to drop UNIQUE constraint...<br>";
    
    // We don't know if it's a PRIMARY KEY or just UNIQUE.
    // Error said "key 'order_id'", usually implies a named index or unique constraint.
    // Let's try to drop the index.
    
    // First, check if it is the PRIMARY KEY?
    $row = $result->fetch_assoc();
    if ($row['Key_name'] == 'PRIMARY') {
         echo "order_id is PRIMARY KEY. This is harder. We assumed 'id' is PK.<br>";
         // Check columns
         $cols = $mysqli->query("SHOW COLUMNS FROM orders");
         while($c = $cols->fetch_assoc()) {
             echo $c['Field'] . " - " . $c['Key'] . "<br>";
         }
    } else {
        // It's likely a UNIQUE index named 'order_id'
        $sql = "ALTER TABLE orders DROP INDEX order_id";
        if ($mysqli->query($sql)) {
            echo "Successfully dropped UNIQUE index 'order_id'.<br>";
            
            // Now add a regular index for performance
            $sqlAdd = "ALTER TABLE orders ADD INDEX idx_order_id (order_id)";
            if ($mysqli->query($sqlAdd)) {
                echo "Successfully added non-unique index 'idx_order_id'.<br>";
            } else {
                echo "Failed to add regular index: " . $mysqli->error . "<br>";
            }
        } else {
            echo "Failed to drop index: " . $mysqli->error . "<br>";
            // Maybe it's a constraint?
            // "Duplicate entry ... for key 'order_id'"
        }
    }
} else {
    echo "Index 'order_id' not found or already removed.<br>";
    
    // Just to be sure, check if there is ANY unique constraint on order_id
    // Sometimes it might be named differently (e.g. order_id_unique)
    $res = $mysqli->query("SHOW INDEX FROM orders");
    while ($r = $res->fetch_assoc()) {
        if ($r['Column_name'] == 'order_id' && $r['Non_unique'] == 0) {
             echo "Found other unique index: " . $r['Key_name'] . ". Dropping...<br>";
             $mysqli->query("ALTER TABLE orders DROP INDEX " . $r['Key_name']);
        }
    }
}

echo "Database schema update completed.";
?>
