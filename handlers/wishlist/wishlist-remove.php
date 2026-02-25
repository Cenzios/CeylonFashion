<?php
// =======================================
// FILE: wishlist-remove.php
// Remove item from wishlist (supports guest and logged-in users)
// =======================================

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/guest-cart.php';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

if ($isLoggedIn) {
    // Logged in user - remove from database
    if (!isset($_POST['wishlist_id']) || !ctype_digit(strval($_POST['wishlist_id']))) {
        header('Location: ../../wishlist.php');
        exit;
    }

    $wishlist_id = (int) $_POST['wishlist_id'];
    $user_id = (int) $_SESSION['user_id'];

    $stmt = $mysqli->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $wishlist_id, $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Guest user - remove from session
    if (!isset($_POST['product_id']) || !ctype_digit(strval($_POST['product_id']))) {
        header('Location: ../../wishlist.php');
        exit;
    }

    $product_id = (int) $_POST['product_id'];
    toggleGuestWishlist($product_id); // Toggle will remove if exists
}

header('Location: ../../wishlist.php');
exit;
