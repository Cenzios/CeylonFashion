<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Ceylon Fashion</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 450px;
            width: 100%;
        }
        .btn-dark-blue {
            background-color: #0a1e42;
            color: white;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-dark-blue:hover {
            background-color: #071633;
            color: white;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-dark">Forgot Password?</h3>
        <p class="text-muted">Enter your email address to reset your password.</p>
    </div>

    <?php if (isset($_SESSION['reset_msg'])): ?>
        <div class="alert alert-info">
            <?= $_SESSION['reset_msg']; ?>
        </div>
        <?php unset($_SESSION['reset_msg']); ?>
    <?php endif; ?>

    <form action="send-reset-link.php" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control form-control-lg" id="email" name="email" required placeholder="name@example.com">
        </div>
        <button type="submit" class="btn btn-dark-blue w-100 mb-3">SEND RESET LINK</button>
        <div class="text-center">
            <a href="index.php" class="text-decoration-none text-muted">&larr; Back to Home</a>
        </div>
    </form>
</div>

</body>
</html>
