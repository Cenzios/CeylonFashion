<?php
include 'config.php';
$result = $mysqli->query("SHOW COLUMNS FROM reseller_products LIKE 'status'");
$row = $result->fetch_assoc();
echo "Type: " . $row['Type'];
?>
