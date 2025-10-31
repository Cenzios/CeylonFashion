<?php
// =======================================
// FILE: wishlist-remove.php
// Remove item from wishlist
// =======================================

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_POST['wishlist_id']) || !ctype_digit(strval($_POST['wishlist_id']))) {
    header('Location: wishlist.php');
    exit;
}

$wishlist_id = (int) $_POST['wishlist_id'];
$user_id = (int) $_SESSION['user_id'];

$stmt = $mysqli->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $wishlist_id, $user_id);
$stmt->execute();
$stmt->close();

header('Location: wishlist.php');
exit;
