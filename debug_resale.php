<?php
include 'config.php';
$result = $mysqli->query("SELECT id, status, LENGTH(status) as len FROM reseller_products ORDER BY created_at DESC LIMIT 5");
if (!$result) {
    echo "Query failed: " . $mysqli->error;
    exit;
}
echo "ID | Status | Length\n";
while ($row = $result->fetch_assoc()) {
    echo $row['id'] . " | '" . $row['status'] . "' | " . $row['len'] . "\n";
}
?>
