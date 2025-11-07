<?php
require_once 'config.php';

// Log all incoming data
$log_file = 'payment_logs.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Notification received\n", FILE_APPEND);
file_put_contents($log_file, print_r($_POST, true) . "\n", FILE_APPEND);

// Get POST parameters
$merchant_id = $_POST['merchant_id'] ?? '';
$order_id = $_POST['order_id'] ?? '';
$payhere_amount = $_POST['payhere_amount'] ?? '';
$payhere_currency = $_POST['payhere_currency'] ?? '';
$status_code = $_POST['status_code'] ?? '';
$md5sig = $_POST['md5sig'] ?? '';
$payment_id = $_POST['payment_id'] ?? '';
$method = $_POST['method'] ?? '';
$status_message = $_POST['status_message'] ?? '';

// Generate local md5sig for verification
$merchant_secret = PAYHERE_MERCHANT_SECRET;
$local_md5sig = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        $payhere_amount . 
        $payhere_currency . 
        $status_code . 
        strtoupper(md5($merchant_secret)) 
    ) 
);

// Verify the signature
if ($local_md5sig === $md5sig) {
    file_put_contents($log_file, "Signature verified successfully\n", FILE_APPEND);
    
    // Determine payment status
    $payment_status = 'pending';
    if ($status_code == 2) {
        $payment_status = 'paid';
        file_put_contents($log_file, "Payment SUCCESS for order: $order_id\n", FILE_APPEND);
    } else if ($status_code == -1) {
        $payment_status = 'cancelled';
    } else if ($status_code == -2 || $status_code == -3) {
        $payment_status = 'failed';
    }
    
    // Update orders table
    $stmt = $mysqli->prepare("
        UPDATE orders 
        SET payment_status = ?, payment_id = ?, updated_at = NOW()
        WHERE order_id = ?
    ");
    $stmt->bind_param("sss", $payment_status, $payment_id, $order_id);
    
    if ($stmt->execute()) {
        file_put_contents($log_file, "Order updated successfully\n", FILE_APPEND);
    } else {
        file_put_contents($log_file, "Order update error: " . $stmt->error . "\n", FILE_APPEND);
    }
    $stmt->close();
    
    // Also save to payments table (for reference)
    $stmt = $mysqli->prepare("
        INSERT INTO payments (
            order_id, payment_id, merchant_id, amount, currency, 
            status, payment_method, status_message, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            payment_id = VALUES(payment_id),
            status = VALUES(status),
            payment_method = VALUES(payment_method),
            status_message = VALUES(status_message)
    ");
    
    $status_str = ($status_code == 2) ? 'success' : 'failed';
    $stmt->bind_param(
        "sssdssss",
        $order_id, $payment_id, $merchant_id, $payhere_amount, $payhere_currency,
        $status_str, $method, $status_message
    );
    
    $stmt->execute();
    $stmt->close();
    
} else {
    file_put_contents($log_file, "Signature verification FAILED!\n", FILE_APPEND);
}

http_response_code(200);
?>