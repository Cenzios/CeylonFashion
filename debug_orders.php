<?php
require_once 'config.php';
$stmt = $mysqli->query("SELECT id, order_id, size, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
echo "Last 5 Orders:\n";
while ($row = $stmt->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | OrderID: " . $row['order_id'] . " | Size: '" . $row['size'] . "' | Created: " . $row['created_at'] . "\n";
}
?>
