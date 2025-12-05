<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Ceylon Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            background: white;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h3 {
            color: #430160;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #430160;
            border-color: #430160;
        }
        .btn-primary:hover {
            background-color: #2e0042;
            border-color: #2e0042;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <h3>Admin Panel</h3>
        <p class="text-muted">Please login to continue</p>
    </div>

    <?php
    session_start();
    if (isset($_SESSION['login_error'])) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
        unset($_SESSION['login_error']);
    }
    ?>

    <form action="login-process.php" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
    </form>
    
    <div class="text-center mt-3">
        <a href="../index.php" class="text-decoration-none text-muted small">&larr; Back to Website</a>
    </div>
</div>

</body>
</html>
