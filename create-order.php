<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
require_once 'config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    // Get product and order details
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $fabric_id = isset($_POST['fabric_id']) ? (int)$_POST['fabric_id'] : 0;
    $size = isset($_POST['size']) ? trim($_POST['size']) : '';
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $order_id = isset($_POST['order_id']) ? trim($_POST['order_id']) : '';
    
    // Get customer details from POST
    $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
    $customer_email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
    $customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
    $delivery_address = isset($_POST['delivery_address']) ? trim($_POST['delivery_address']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    
    // Validation
    if (!$product_id || !$fabric_id || !$size || !$order_id) {
        throw new Exception('Missing required product fields');
    }
    
    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($delivery_address) || empty($city)) {
        throw new Exception('Missing required customer fields');
    }
    
    // Validate email
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // Validate phone
    if (!preg_match('/^[0-9]{10,15}$/', $customer_phone)) {
        throw new Exception('Invalid phone number');
    }
    
    // Get product details
    $stmt = $mysqli->prepare("SELECT product_name FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $mysqli->error);
    }
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Get fabric details
    $stmt = $mysqli->prepare("SELECT fabric_type, fabric_price FROM product_fabrics WHERE id = ? AND product_id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $mysqli->error);
    }
    $stmt->bind_param("ii", $fabric_id, $product_id);
    $stmt->execute();
    $fabric = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$fabric) {
        throw new Exception('Fabric not found');
    }
    
    $unit_price = (float)$fabric['fabric_price'];
    $total_amount = $unit_price * $quantity;
    
    // Get username from session
    $username = $_SESSION['username'];
    
    // Insert order with customer-provided details
    $stmt = $mysqli->prepare("
        INSERT INTO orders (
            order_id, username, product_id, product_name, 
            fabric_id, fabric_type, size, quantity, 
            unit_price, total_amount, payment_status,
            customer_name, customer_email, customer_phone, 
            delivery_address, city
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $mysqli->error);
    }
    
    $stmt->bind_param(
        "ssisssiddssssss",
        $order_id, 
        $username, 
        $product_id, 
        $product['product_name'],
        $fabric_id, 
        $fabric['fabric_type'], 
        $size, 
        $quantity,
        $unit_price, 
        $total_amount,
        $customer_name, 
        $customer_email, 
        $customer_phone,
        $delivery_address, 
        $city
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create order: ' . $stmt->error);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'amount' => number_format($total_amount, 2, '.', '')
    ]);
    
} catch (Exception $e) {
    // Log error to file for debugging
    error_log(date('Y-m-d H:i:s') . ' - Order creation error: ' . $e->getMessage() . "\n", 3, 'order_errors.txt');
    
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>