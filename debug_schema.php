<?php
include 'config.php';
$result = $mysqli->query("DESCRIBE products");
while ($row = $result->fetch_assoc()) {
    print_r($row);
    echo "<br>";
}
?>
