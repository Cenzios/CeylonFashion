<?php
// cart-remove.php - receives POST cart_id or product_id and deletes the item
session_start();
require_once 'config.php'; // must define $mysqli
require_once 'lib/guest-cart.php';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

if ($isLoggedIn) {
    // Logged in user - remove from database
    if (!isset($_POST['cart_id']) || !ctype_digit(strval($_POST['cart_id']))) {
        header('Location: cart.php');
        exit;
    }

    $cart_id = (int) $_POST['cart_id'];
    $user_id = (int) $_SESSION['user_id'];

    $stmt = $mysqli->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    if ($stmt === false) {
        // log or show error
        die("DB prepare error: " . $mysqli->error);
    }
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Guest user - remove from session
    if (!isset($_POST['product_id']) || !ctype_digit(strval($_POST['product_id']))) {
        header('Location: cart.php');
        exit;
    }

    $product_id = (int) $_POST['product_id'];
    removeFromGuestCart($product_id);
}

header('Location: cart.php');
exit;
