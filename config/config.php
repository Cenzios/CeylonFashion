<?php
// -----------------------------
// 💾 Database Configuration
// -----------------------------
$currency = 'Rs';
$db_username = 'root';
$db_password = '';  // same password used in Dockploy database setup
$db_name = 'ceylon_fashion';             // same as Database Name in Dockploy
$db_host = 'localhost';                // service name defined in Dockploy (not localhost)

$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

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
define('PAYHERE_RETURN_URL', 'https://ceylonfashion.cenzios.com/handlers/payment/payment-success.php');
define('PAYHERE_CANCEL_URL', 'https://ceylonfashion.cenzios.com/handlers/payment/payment-cancel.php');
define('PAYHERE_NOTIFY_URL', 'https://ceylonfashion.cenzios.com/handlers/payment/payment-notify.php');