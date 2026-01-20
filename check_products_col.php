<?php
require_once 'config.php';
$res = $mysqli->query("DESCRIBE products");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
