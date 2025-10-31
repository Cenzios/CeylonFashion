<?php
if (session_id() == '' || !isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php?return=' . urlencode($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

require_once 'config.php';

$product_id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
$quantity = isset($_GET['qty']) && ctype_digit($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ✅ Match actual column names in your products table
$stmt = $mysqli->prepare("SELECT qty, price FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product || $product['qty'] < $quantity) {
    $_SESSION['cart_error'] = 'Product not available or insufficient stock.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Check if item already in cart
$stmt = $mysqli->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$stmt->close();

if ($existing) {
    $new_qty = $existing['quantity'] + $quantity;
    $stmt = $mysqli->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $new_qty, $existing['id']);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $mysqli->prepare("INSERT INTO cart (user_id, product_id, quantity, price, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $product['price']);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['cart_success'] = 'Item added to cart successfully!';
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
exit;
?>
