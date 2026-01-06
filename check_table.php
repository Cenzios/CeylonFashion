<?php
include 'config.php';
$check = $mysqli->query("SHOW TABLES LIKE 'password_resets'");
if ($check->num_rows > 0) {
    echo "Exists";
} else {
    echo "Missing";
}
?>
