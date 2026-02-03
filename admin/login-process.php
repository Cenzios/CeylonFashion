<?php
session_name('ADMIN_SESSION');
session_start();
// Include config from parent directory
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $result = $mysqli->query("SELECT * FROM users WHERE email='$email'");

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check password
        if ($password === $user['password']) {
            
            // Check if user is admin
            if ($user['type'] === 'admin') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['email'];
                $_SESSION['type'] = $user['type'];
                $_SESSION['first_name'] = $user['fname'];
                $_SESSION['last_name'] = $user['lname'];

                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['login_error'] = 'Access denied. Admins only.';
                header("Location: login.php");
                exit;
            }

        } else {
            $_SESSION['login_error'] = 'Invalid password.';
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['login_error'] = 'User not found.';
        header("Location: login.php");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>
