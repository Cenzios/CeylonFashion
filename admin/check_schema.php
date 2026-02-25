<?php
include '../config.php';
$result = $mysqli->query("SHOW COLUMNS FROM products LIKE 'product_color'");
if ($result->num_rows > 0) {
    echo "Column 'product_color' EXISTS in 'products' table.";
} else {
    echo "Column 'product_color' DOES NOT EXIST in 'products' table.";
}
$result = $mysqli->query("SHOW COLUMNS FROM products");
while($row = $result->fetch_assoc()){
    echo "\n" . $row['Field'];
}
?>
