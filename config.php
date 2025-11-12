<?php
// -----------------------------
// 💾 Database Configuration
// -----------------------------
$currency = 'Rs';

// Get database credentials from environment variables (Docker)
// Falls back to local values if not set
$db_host = getenv('MYSQL_HOST') ?: 'localhost';
$db_username = getenv('MYSQL_USER') ?: 'root';
$db_password = getenv('MYSQL_PASSWORD') ?: '';
$db_name = getenv('MYSQL_DATABASE') ?: 'sahan';

// Create database connection
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Set charset to UTF-8 (recommended)
$mysqli->set_charset("utf8mb4");

// -----------------------------
// 💳 PayHere Configuration
// -----------------------------
define('PAYHERE_MERCHANT_ID', '1232735');

// ⚠️ IMPORTANT: Replace this with the **actual merchant secret** from your PayHere dashboard
define('PAYHERE_MERCHANT_SECRET', 'MTk1NTk5MzE5MzQwMjY0Mjg4NjgyNjUzNjMyNzM2MTMwNTc5OTIy');

// 🧪 Use Sandbox Mode = true for testing | false for production
define('PAYHERE_SANDBOX', true);

// -----------------------------
// 🌍 URLs (Update for your live domain)
// -----------------------------
define('PAYHERE_RETURN_URL', 'https://ceylonfashion.cenzios.com/payment-success.php');
define('PAYHERE_CANCEL_URL', 'https://ceylonfashion.cenzios.com/payment-cancel.php');
define('PAYHERE_NOTIFY_URL', 'https://ceylonfashion.cenzios.com/payment-notify.php');
?>