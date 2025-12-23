<?php
session_start();
require_once 'config.php';
require_once 'lib/guest-cart.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$cart_id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($cart_id <= 0 || !in_array($action, ['increase', 'decrease'])) {
    header('Location: cart.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Fetch cart item to verify ownership and get current details
$stmt = $mysqli->prepare("SELECT product_id, fabric_id, quantity, price FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'Item not found']);
        exit;
    }
    header('Location: cart.php');
    exit;
}

$product_id = (int)$item['product_id'];
$fabric_id = (int)$item['fabric_id'];
$current_qty = (int)$item['quantity'];
$price = (float)$item['price'];
$new_qty = $current_qty;
$error_msg = "";

if ($action === 'increase') {
    // Check stock availability (Specific Fabric or Total)
    $available_qty = 0;
    if ($fabric_id > 0) {
        $fStmt = $mysqli->prepare("SELECT fabric_qty FROM product_fabrics WHERE id = ?");
        $fStmt->bind_param("i", $fabric_id);
        $fStmt->execute();
        $res = $fStmt->get_result()->fetch_assoc();
        $available_qty = $res ? (int)$res['fabric_qty'] : 0;
        $fStmt->close();
    } else {
        // Fallback
        $fStmt = $mysqli->prepare("SELECT SUM(fabric_qty) as total_qty FROM product_fabrics WHERE product_id = ?");
        $fStmt->bind_param("i", $product_id);
        $fStmt->execute();
        $res = $fStmt->get_result()->fetch_assoc();
        $available_qty = $res ? (int)$res['total_qty'] : 0;
        $fStmt->close();
    }
    
    if ($current_qty < $available_qty) {
        $new_qty++;
    } else {
        $error_msg = "Cannot add more. Only $available_qty items available.";
        if (!$is_ajax) $_SESSION['cart_error'] = $error_msg;
    }
    
} elseif ($action === 'decrease') {
    if ($current_qty > 1) {
        $new_qty--;
    }
}

// Update if quantity changed
if ($new_qty !== $current_qty) {
    $stmt = $mysqli->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $new_qty, $cart_id);
    if ($stmt->execute()) {
        // Success
    } else {
         $error_msg = "Failed to update cart.";
         if (!$is_ajax) $_SESSION['cart_error'] = $error_msg;
    }
    $stmt->close();
}

if ($is_ajax) {
    if (!empty($error_msg)) {
        echo json_encode(['success' => false, 'error' => $error_msg]);
    } else {
        // Calculate new totals
        $new_subtotal = $new_qty * $price;
        
        // Get Grand Total
        $gStmt = $mysqli->prepare("SELECT SUM(quantity * price) as grand_total FROM cart WHERE user_id = ?");
        $gStmt->bind_param("i", $user_id);
        $gStmt->execute();
        $gRes = $gStmt->get_result()->fetch_assoc();
        $grand_total = $gRes ? (float)$gRes['grand_total'] : 0;
        $gStmt->close();
        
        echo json_encode([
            'success' => true, 
            'new_qty' => $new_qty, 
            'new_subtotal' => number_format($new_subtotal, 2),
            'grand_total' => number_format($grand_total, 2),
            'cart_count' => getUserCartCount($mysqli, $user_id)
        ]);
    }
    require_once 'lib/guest-cart.php'; // ensure getUserCartCount available if not already included via config->... wait, guest-cart isn't usually included in config. Let's include it.
    exit;
}

header('Location: cart.php');
exit;
