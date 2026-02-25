<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// Verify user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get parameters
$merchant_id = PAYHERE_MERCHANT_ID;
$order_id = isset($_POST['order_id']) ? trim($_POST['order_id']) : '';
$amount = isset($_POST['amount']) ? trim($_POST['amount']) : '';
$currency = isset($_POST['currency']) ? trim($_POST['currency']) : 'LKR';

// Validate inputs
if (empty($order_id) || empty($amount)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Format amount to 2 decimal places
$amount_formatted = number_format((float)$amount, 2, '.', '');

// Generate hash according to PayHere documentation
$merchant_secret = PAYHERE_MERCHANT_SECRET;
$hash = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        $amount_formatted . 
        $currency .  
        strtoupper(md5($merchant_secret)) 
    ) 
);

echo json_encode([
    'success' => true,
    'hash' => $hash,
    'merchant_id' => $merchant_id
]);
?>