<?php
$currency = 'Rs';

// Docker environment variables with fallbacks
$db_host = getenv('MYSQL_HOST') ?: 'db';  // Use 'db' service name in Docker
$db_username = getenv('MYSQL_USER') ?: 'myuser';
$db_password = getenv('MYSQL_PASSWORD') ?: 'mypass';
$db_name = getenv('MYSQL_DATABASE') ?: 'sahan';

// Connection with retry logic (for Docker startup delays)
$retries = 5;
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

while ($mysqli->connect_errno && $retries--) {
    if ($retries > 0) {
        error_log("Database connection failed, retrying... ($retries attempts left)");
        sleep(3);
        $mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);
    } else {
        die("Database connection failed after multiple attempts: " . $mysqli->connect_error);
    }
}

// Set charset
$mysqli->set_charset("utf8mb4");

// PayHere Configuration
define('PAYHERE_MERCHANT_ID', '1232735');
// ⚠️ IMPORTANT: Get the actual merchant secret from PayHere dashboard (not base64)
// Go to: Side Menu > Integrations > Your approved domain > Copy the secret shown
define('PAYHERE_MERCHANT_SECRET', 'MTk1NTk5MzE5MzQwMjY0Mjg4NjgyNjUzNjMyNzM2MTMwNTc5OTIy'); // Replace with plain secret from dashboard
define('PAYHERE_SANDBOX', true);
define('PAYHERE_RETURN_URL', 'http://localhost/CeylonFashion/payment-success.php');
define('PAYHERE_CANCEL_URL', 'http://localhost/CeylonFashion/payment-cancel.php');
// ⚠️ For testing, use ngrok or similar to expose localhost
// For production, use your actual domain
define('PAYHERE_NOTIFY_URL', 'https://your-domain.com/payment-notify.php'); 
?>