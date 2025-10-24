<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
include 'config.php';
include 'lib/user.php';

if (!isset($_SESSION['username']) || (isset($_SESSION['type']) && $_SESSION['type'] === 'admin')) {
  header("Location: login.php"); exit();
}

$userId = current_user_id($mysqli);
if ($userId <= 0) { header("Location: login.php"); exit(); }

$productId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$qty       = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

if ($productId <= 0) { header("Location: cart.php"); exit(); }

if ($qty <= 0) {
  $stmt = $mysqli->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
  $stmt->bind_param("ii", $userId, $productId);
  $stmt->execute();
  $stmt->close();
} else {
  $stmt = $mysqli->prepare("UPDATE cart_items SET qty = ? WHERE user_id = ? AND product_id = ?");
  $stmt->bind_param("iii", $qty, $userId, $productId);
  $stmt->execute();
  $stmt->close();
}

header("Location: cart.php");
