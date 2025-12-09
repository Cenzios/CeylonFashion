<?php
// cart-set-qty.php - Set quantity for guest cart items
if (session_id() == '' || !isset($_SESSION)) {
    session_start();
}

require_once 'lib/guest-cart.php';

// Only allow guest users (logged in users use cart-update.php)
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: cart.php');
    exit;
}

$product_id = isset($_GET['product_id']) && ctype_digit($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$qty = isset($_GET['qty']) && ctype_digit($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($product_id <= 0) {
    header('Location: cart.php');
    exit;
}

if ($qty <= 0) {
    removeFromGuestCart($product_id);
} else {
    updateGuestCartQty($product_id, $qty);
}

header('Location: cart.php');
exit;
