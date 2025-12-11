<?php
session_name('ADMIN_SESSION');
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to admin login
header('Location: login.php');
exit;
?>
