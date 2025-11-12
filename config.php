<?php
// -----------------------------
// 💾 Database Configuration
// -----------------------------
$currency = 'Rs';
$db_username = 'root';
$db_password = '12345';  // same password used in Dockploy database setup
$db_name = 'sahan';             // same as Database Name in Dockploy
$db_host = 'ceylon-fashion-db-zu6efo';                // service name defined in Dockploy (not localhost)

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
define('PAYHERE_RETURN_URL', 'https://ceylonfashion.cenzios.com/payment-success.php');
define('PAYHERE_CANCEL_URL', 'https://ceylonfashion.cenzios.com/payment-cancel.php');
define('PAYHERE_NOTIFY_URL', 'https://ceylonfashion.cenzios.com/payment-notify.php'); 