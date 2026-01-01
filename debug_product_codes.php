<?php
// debug_product_codes.php
$dsn = 'mysql:host=localhost;dbname=ceylon_fashion;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '');
$stmt = $pdo->query("SELECT product_code FROM products ORDER BY LENGTH(product_code) DESC, product_code DESC LIMIT 10");
$codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<h3>Existing Product Codes (Top 10 Descending):</h3><pre>";
print_r($codes);
echo "</pre>";
?>
