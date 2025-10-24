<?php
// admin/delete-user.php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
include_once '../config.php';

// ---- Admin check ----
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// ---- Check for user ID ----
if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$userId = (int)$_GET['id'];

// ---- Prevent admin from deleting themselves ----
if (isset($_SESSION['id']) && $_SESSION['id'] === $userId) {
    $_SESSION['error'] = "You cannot delete your own account.";
    header('Location: users.php');
    exit;
}

// ---- Delete user ----
$stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);

if ($stmt->execute()) {
    $_SESSION['success'] = "User deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete user: " . $mysqli->error;
}

$stmt->close();
$mysqli->close();

// ---- Redirect back to users page ----
header('Location: users.php');
exit;
