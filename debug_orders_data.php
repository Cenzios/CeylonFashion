<?php
require_once 'config.php';
$stmt = $mysqli->prepare("SELECT id, username, customer_email, customer_name FROM orders ORDER BY id DESC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Users Username: " . $row['username'] . ", Customer Email: " . $row['customer_email'] . "\n";
}
?>
