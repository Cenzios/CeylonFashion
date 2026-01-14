<?php
include 'config.php';
$result = $mysqli->query("SHOW CREATE TABLE reseller_products");
$row = $result->fetch_assoc();
echo $row['Create Table'];
?>
