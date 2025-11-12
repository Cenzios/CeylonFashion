<?php
// ======================================
// 💾 DATABASE CONFIGURATION
// ======================================

// Get environment variables (works for both local + Docker)
$db_host = getenv('MYSQL_HOST') ?: 'localhost';
$db_username = getenv('MYSQL_USER') ?: 'root';
$db_password = getenv('MYSQL_PASSWORD') ?: ''; // empty for XAMPP
$db_name = getenv('MYSQL_DATABASE') ?: 'sahan';

// Currency setting
$currency = 'Rs';

// Connect to MySQL
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// ======================================
// 💳 PAYHERE CONFIGURATION
// ======================================
define('PAYHERE_MERCHANT_ID', '1232735');
define('PAYHERE_MERCHANT_SECRET', 'MTk1NTk5MzE5MzQwMjY0Mjg4NjgyNjUzNjMyNzM2MTMwNTc5OTIy');
define('PAYHERE_SANDBOX', true);

// ======================================
// 🌍 URL CONFIGURATION (Change for live)
// ======================================
define('PAYHERE_RETURN_URL', 'https://ceylonfashion.cenzios.com/payment-success.php');
define('PAYHERE_CANCEL_URL', 'https://ceylonfashion.cenzios.com/payment-cancel.php');
define('PAYHERE_NOTIFY_URL', 'https://ceylonfashion.cenzios.com/payment-notify.php');

// ======================================
// 🖼️ IMAGE PATH CONFIG
// ======================================
define('UPLOAD_PATH', __DIR__ . '/uploads/');  // physical folder
define('UPLOAD_URL', '/uploads/');             // public URL

// Ensure upload folder exists
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
?>
