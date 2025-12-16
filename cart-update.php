<?php
session_start();
require_once 'config.php';

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

// Fetch cart item to verify ownership and get current details
$stmt = $mysqli->prepare("SELECT product_id, quantity FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    header('Location: cart.php');
    exit;
}

$product_id = (int)$item['product_id'];
$current_qty = (int)$item['quantity'];
$new_qty = $current_qty;

if ($action === 'increase') {
    // Check stock availability
    $fabricStmt = $mysqli->prepare("SELECT SUM(fabric_qty) as total_qty FROM product_fabrics WHERE product_id = ?");
    $fabricStmt->bind_param("i", $product_id);
    $fabricStmt->execute();
    $fabricRes = $fabricStmt->get_result();
    $fabricData = $fabricRes->fetch_assoc();
    $fabricStmt->close();
    
    $available_qty = $fabricData ? (int)$fabricData['total_qty'] : 0;
    
    if ($current_qty < $available_qty) {
        $new_qty++;
    } else {
        $_SESSION['cart_error'] = "Cannot add more. Only $available_qty items available.";
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
        // basic success, no message needed usually for simple inc/dec
    } else {
        $_SESSION['cart_error'] = "Failed to update cart.";
    }
    $stmt->close();
}

header('Location: cart.php');
exit;
