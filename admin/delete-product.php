<?php
session_start();
include_once '../config.php'; // mysqli connection

// ---- Admin check ----
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// ---- Check ID ----
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$pid = (int)$_GET['id'];

// ---- Fetch product to delete image file ----
$stmt = $mysqli->prepare("SELECT product_img_name FROM products WHERE id=?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if ($product) {
    // Delete product image file if exists
    $imgPath = '../images/products/' . $product['product_img_name'];
    if (!empty($product['product_img_name']) && file_exists($imgPath)) {
        unlink($imgPath);
    }

    // Delete product from database
    $delStmt = $mysqli->prepare("DELETE FROM products WHERE id=?");
    $delStmt->bind_param("i", $pid);
    $delStmt->execute();
}

// Redirect back to products page
header('Location: products.php');
exit;
