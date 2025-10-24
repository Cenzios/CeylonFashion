<?php
// db.php
$dsn = 'mysql:host=localhost;dbname=sahan;charset=utf8mb4';
$user = 'root';
$pass = '';

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Throwable $e) {
  http_response_code(500);
  exit('Database connection error.');
}
