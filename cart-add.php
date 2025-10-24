<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    die("Invalid product or quantity.");
}

// 1️⃣ Check if the user already has an active cart (status = 'pending')
$result = $mysqli->query("SELECT * FROM carts WHERE user_id = $user_id AND status = 'pending' LIMIT 1");

if ($result && $result->num_rows > 0) {
    $cart = $result->fetch_assoc();
    $cart_id = (int)$cart['id'];
} else {
    // Create new cart
    $stmt = $mysqli->prepare("INSERT INTO carts (user_id, status, created_at, updated_at) VALUES (?, 'pending', NOW(), NOW())");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_id = $mysqli->insert_id;
    $stmt->close();
}

// 2️⃣ Check if product is already in cart
$stmt = $mysqli->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1");
$stmt->bind_param("ii", $cart_id, $product_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    // Update quantity
    $item = $res->fetch_assoc();
    $new_qty = $item['qty'] + $quantity;
    $stmtUpdate = $mysqli->prepare("UPDATE cart_items SET qty = ?, updated_at = NOW() WHERE id = ?");
    $stmtUpdate->bind_param("ii", $new_qty, $item['id']);
    $stmtUpdate->execute();
    $stmtUpdate->close();
} else {
    // Insert new item
    $stmtInsert = $mysqli->prepare("INSERT INTO cart_items (cart_id, product_id, qty, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmtInsert->bind_param("iii", $cart_id, $product_id, $quantity);
    $stmtInsert->execute();
    $stmtInsert->close();
}

$stmt->close();

// Redirect back to products page or cart
header("Location: cart.php?added=1");
exit;
?>
