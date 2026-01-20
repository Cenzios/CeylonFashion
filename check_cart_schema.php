<?php
require_once 'config.php';
echo "CART TABLE:\n";
$res = $mysqli->query("DESCRIBE cart");
while($row = $res->fetch_assoc()) { echo $row['Field'] . "\n"; }

echo "\nORDERS TABLE:\n";
$res = $mysqli->query("DESCRIBE orders");
while($row = $res->fetch_assoc()) { echo $row['Field'] . "\n"; }
?>
