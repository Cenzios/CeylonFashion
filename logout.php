<?php
session_start();

// Check if user is admin before destroying session
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';

session_unset();
session_destroy();

// Redirect admin to admin login page, regular users to main page
if ($isAdmin) {
    header('Location: admin/login.php');
} else {
    header('Location: index.php');
}
exit();
?>