<?php
require_once 'config.php';
$result = $mysqli->query("SHOW COLUMNS FROM orders");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
