<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $mysqli->real_escape_string($_POST['email']);

    // Check if email exists
    $result = $mysqli->query("SELECT id FROM users WHERE email='$email' LIMIT 1");

    if ($result->num_rows > 0) {
        // Generate Token
        $token = bin2hex(random_bytes(32)); // 64 char token
        
        // Remove existing reset tokens for this email
        $mysqli->query("DELETE FROM password_resets WHERE email='$email'");

        // Insert new token
        $sql = "INSERT INTO password_resets (email, token, created_at) VALUES ('$email', '$token', NOW())";
        
        if ($mysqli->query($sql)) {
            // Simulation: Display the link directly since we can't send email
            $resetLink = "http://localhost/CeylonFashion/reset-password.php?token=" . $token . "&email=" . urlencode($email);
            
            $_SESSION['reset_msg'] = "Password reset link sent! <br><a href='$resetLink'>Click here to reset (Simulation)</a>";
        } else {
            $_SESSION['reset_msg'] = "Error generating token.";
        }
    } else {
        $_SESSION['reset_msg'] = "We couldn't find an account with that email.";
    }
}

header("Location: forgot-password.php");
exit;
?>
