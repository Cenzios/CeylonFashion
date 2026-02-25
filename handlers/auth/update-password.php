<?php
session_start();
include __DIR__ . '/../../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $email = $_POST['email'];
    $pass  = $_POST['password'];
    $conf  = $_POST['confirm_password'];

    if ($pass !== $conf) {
        die("Passwords do not match.");
    }

    // Verify Token again
    $stmt = $mysqli->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // PLAIN TEXT UPDATE
        $update = $mysqli->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $pass, $email);
        
        if ($update->execute()) {
            // Delete tokens
            $mysqli->query("DELETE FROM password_resets WHERE email='$email'");
            
            // Redirect to login with success
            // Since we don't have a dedicated login page, redirect to index with a flag to open sidebar
            header("Location: ../../index.php?login_msg=Password updated successfully. Please login.");
        } else {
            echo "Error updating password.";
        }
    } else {
        echo "Invalid token.";
    }
}
?>
