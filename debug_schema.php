<?php
require_once 'config.php';
$stmt = $mysqli->query("DESCRIBE cart");
while ($row = $stmt->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
