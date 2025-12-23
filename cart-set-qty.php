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

$key = isset($_GET['key']) ? $_GET['key'] : '';
$qty = isset($_GET['qty']) && ctype_digit($_GET['qty']) ? (int)$_GET['qty'] : 1;

if (empty($key)) {
    // Legacy support or fallback if needed? 
    // If only product_id is passed, we might assume key = product_id (legacy numeric)
    // But now keys are strings.
    if (isset($_GET['product_id'])) {
         $key = $_GET['product_id'];
    }
}

if (empty($key)) {
    header('Location: cart.php');
    exit;
}

if ($qty <= 0) {
    removeFromGuestCart($key);
} else {
    updateGuestCartQty($key, $qty);
}

header('Location: cart.php');
exit;
