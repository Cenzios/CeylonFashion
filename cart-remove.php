<?php
// cart-remove.php - receives POST cart_id and deletes the item
session_start();
require_once 'config.php'; // must define $mysqli

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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

header('Location: cart.php');
exit;
