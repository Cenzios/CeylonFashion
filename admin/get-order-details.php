<?php
if (session_id() == '' || !isset($_SESSION)) { session_name('ADMIN_SESSION'); session_start(); }
include_once '../config.php';

header('Content-Type: application/json');

// Admin check
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

$orderId = (int)$_GET['id'];

$stmt = $mysqli->prepare("
    SELECT 
        id, order_id, username, product_id, product_name, 
        fabric_id, fabric_type, size, quantity,
        unit_price, total_amount, payment_status, payment_id, delivery_status,
        customer_name, customer_email, customer_phone, 
        delivery_address, city, postal_code,
        created_at, updated_at
    FROM orders 
    WHERE id = ?
");

$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

$order = $result->fetch_assoc();
$stmt->close();

echo json_encode(['success' => true, 'order' => $order]);
?>