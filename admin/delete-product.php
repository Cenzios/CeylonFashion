<?php
session_name('ADMIN_SESSION');
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

// ---- Fetch product to delete image files ----
$stmt = $mysqli->prepare("SELECT product_img1, product_img2, product_img3, product_img4 FROM products WHERE id=?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if ($product) {
    // Delete all product image files if they exist
    $imageFields = ['product_img1', 'product_img2', 'product_img3', 'product_img4'];
    
    foreach ($imageFields as $field) {
        if (!empty($product[$field])) {
            $imgPath = '../images/products/' . $product[$field];
            if (file_exists($imgPath)) {
                unlink($imgPath);
            }
        }
    }

    // Delete associated fabrics first (foreign key constraint)
    $delFabricsStmt = $mysqli->prepare("DELETE FROM product_fabrics WHERE product_id=?");
    $delFabricsStmt->bind_param("i", $pid);
    $delFabricsStmt->execute();
    $delFabricsStmt->close();

    // Delete associated reviews
    $delReviewsStmt = $mysqli->prepare("DELETE FROM product_reviews WHERE product_id=?");
    $delReviewsStmt->bind_param("i", $pid);
    $delReviewsStmt->execute();
    $delReviewsStmt->close();

    // Delete associated questions
    $delQuestionsStmt = $mysqli->prepare("DELETE FROM product_questions WHERE product_id=?");
    $delQuestionsStmt->bind_param("i", $pid);
    $delQuestionsStmt->execute();
    $delQuestionsStmt->close();

    // Delete product from database
    $delStmt = $mysqli->prepare("DELETE FROM products WHERE id=?");
    $delStmt->bind_param("i", $pid);
    $delStmt->execute();
    $delStmt->close();
}

// Close connection
$mysqli->close();

// Redirect back to products page
header('Location: products.php');
exit;
?>