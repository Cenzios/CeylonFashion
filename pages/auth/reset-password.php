<?php
session_start();
include __DIR__ . '/../../config/config.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$valid = false;

if ($token && $email) {
    // Validate Token
    $stmt = $mysqli->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $valid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .reset-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 450px; width: 100%; }
    </style>
</head>
<body>

<div class="reset-card">
    <?php if ($valid): ?>
        <h3 class="fw-bold mb-4 text-center">Reset Password</h3>
        <form action="../../handlers/auth/update-password.php" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email); ?>">
            
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="text" name="password" class="form-control" placeholder="Enter new password" required minlength="6">
                <!-- Using text input so user can see what they type, or use password type -->
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="text" name="confirm_password" class="form-control" placeholder="Confirm password" required minlength="6">
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Update Password</button>
        </form>
    <?php else: ?>
        <div class="alert alert-danger text-center">
            Invalid or expired token.
        </div>
        <div class="text-center">
            <a href="../../pages/auth/forgot-password.php">Request a new link</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
