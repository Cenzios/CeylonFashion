<?php
require_once __DIR__ . '/../../config/config.php';

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
        
        // --- Reduce stock (fabric_qty) for purchased items when payment is successful ---
        if ($status_code == 2) {
            $itemStmt = $mysqli->prepare("SELECT fabric_id, quantity FROM orders WHERE order_id = ?");
            $itemStmt->bind_param("s", $order_id);
            $itemStmt->execute();
            $itemsResult = $itemStmt->get_result();
            
            $stockStmt = $mysqli->prepare("UPDATE product_fabrics SET fabric_qty = GREATEST(fabric_qty - ?, 0) WHERE id = ?");
            while ($orderItem = $itemsResult->fetch_assoc()) {
                $fabricId = (int)$orderItem['fabric_id'];
                $purchasedQty = (int)$orderItem['quantity'];
                if ($fabricId > 0 && $purchasedQty > 0) {
                    $stockStmt->bind_param("ii", $purchasedQty, $fabricId);
                    $stockStmt->execute();
                }
            }
            $stockStmt->close();
            $itemStmt->close();
            file_put_contents($log_file, "Stock reduced for order: $order_id\n", FILE_APPEND);
        }
        
        // --- Clear Cart Logic ---
        // 1. Get username from order
        $uStmt = $mysqli->prepare("SELECT username FROM orders WHERE order_id = ? LIMIT 1");
        $uStmt->bind_param("s", $order_id);
        $uStmt->execute();
        $uRes = $uStmt->get_result();
        
        if ($uRow = $uRes->fetch_assoc()) {
            $username = $uRow['username'];
            
            // 2. Get user_id from username
            $idStmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
            $idStmt->bind_param("s", $username);
            $idStmt->execute();
            $idRes = $idStmt->get_result();
            
            if ($userRow = $idRes->fetch_assoc()) {
                $user_id = $userRow['id'];
                
                // 3. Clear cart for this user
                // Only if payment was successful (status_code == 2)
                if ($status_code == 2) {
                    $delStmt = $mysqli->prepare("DELETE FROM cart WHERE user_id = ?");
                    $delStmt->bind_param("i", $user_id);
                    if ($delStmt->execute()) {
                         file_put_contents($log_file, "Cart cleared for user $user_id\n", FILE_APPEND);
                    }
                    $delStmt->close();
                }
            }
            $idStmt->close();
        }
        $uStmt->close();
        // ------------------------

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