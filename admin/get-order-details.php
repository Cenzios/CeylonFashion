<?php
if (session_id() == '' || !isset($_SESSION)) { session_name('ADMIN_SESSION'); session_start(); }
include_once '../config/config.php';

header('Content-Type: application/json');

// Admin check
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['order_id']) && !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

// Support both for legacy safety, but prioritize order_id string
if (isset($_GET['order_id'])) {
    $orderIdStr = $_GET['order_id'];
    $stmt = $mysqli->prepare("
        SELECT 
            id, order_id, username, product_id, product_name, product_code,
            fabric_id, fabric_type, size, quantity,
            unit_price, total_amount, payment_status, payment_id, delivery_status,
            customer_name, customer_email, customer_phone, 
            delivery_address, city, postal_code,
            created_at, updated_at
        FROM orders 
        WHERE order_id = ?
    ");
    $stmt->bind_param("s", $orderIdStr);
} else {
    // Fallback ID int
    $id = (int)$_GET['id'];
    $stmt = $mysqli->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// Meta data from first item
$orderMeta = $items[0];

echo json_encode(['success' => true, 'order' => $orderMeta, 'items' => $items]);
?>