<?php
if (session_id() == '' || !isset($_SESSION)) { session_name('ADMIN_SESSION'); session_start(); }
include_once '../config.php';

// Admin check
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = (int)$_GET['id'];

// Delete the order
$stmt = $mysqli->prepare("DELETE FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Order deleted successfully';
} else {
    $_SESSION['error_message'] = 'Failed to delete order';
}

$stmt->close();
header('Location: orders.php');
exit;
?>