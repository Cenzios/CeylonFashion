<?php
$currency = 'Rs';
$db_username = 'root';
$db_password = '';
$db_name = 'sahan';
$db_host = 'localhost';
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

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