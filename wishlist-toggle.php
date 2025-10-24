<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }

include 'config.php';
include 'lib/user.php';

// Require login and block admins from using wishlist
if (empty($_SESSION['user_id']) || (!empty($_SESSION['type']) && $_SESSION['type'] === 'admin')) {
  header("Location: login.php");
  exit();
}

$userId = current_user_id($mysqli);
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0 || $productId <= 0) {
  header("Location: products.php");
  exit();
}

// Toggle wishlist
$stmt = $mysqli->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1");
$stmt->bind_param("ii", $userId, $productId);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();

if ($exists) {
  $stmt = $mysqli->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
  $stmt->bind_param("ii", $userId, $productId);
  $stmt->execute();
  $stmt->close();
} else {
  $stmt = $mysqli->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
  $stmt->bind_param("ii", $userId, $productId);
  $stmt->execute();
  $stmt->close();
}

header("Location: products.php");
