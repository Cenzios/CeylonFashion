<?php
session_name('ADMIN_SESSION');
session_start();

// ---- Redirect if not logged in or not admin ----
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Database connection
$dsn = 'mysql:host=localhost;dbname=ceylon_fashion;charset=utf8mb4';
$user = 'root';
$pass = '';
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];
try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Throwable $e) {
  exit('Database connection failed.');
}

// ---- Check if admin ----
$stmt = $pdo->prepare("SELECT type, fname FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();

if (!$userData || $userData['type'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin | Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
  background:#f8f9fa;
  font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
}
.sidebar {
  width: 240px;
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  background: #430160ff;
  color: #fff;
  padding-top: 20px;
}
.sidebar a {
  display: block;
  padding: 12px 18px;
  color: #cfd8dc;
  text-decoration: none;
}
.sidebar a.active {
  background: #007bff;
  color: #fff;
}
.sidebar a:hover {
  background: #5a1b88;
  color: #fff;
}
.main {
  margin-left: 240px;
  padding: 28px;
  min-height: 100vh;
}
.card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.card-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
}
.display-6 {
  font-size: 40px;
  margin: 0;
}
</style>
</head>
<body>

<div class="sidebar">
  <h4 class="text-center mb-3">Ceylon Fashion</h4>
  <a href="dashboard.php" class="active">🏠 Dashboard</a>
  <a href="products.php">📦 Products</a>
  <a href="orders.php">🧾 Orders</a>
  <a href="users.php">👥 Users</a>
  <a href="reports.php">📊 Reports</a>
  <hr style="border-color: rgba(255,255,255,.06)">
  <a href="logout.php" class="text-danger" onclick="return confirm('Are you sure you want to logout?');">🚪 Logout</a>
</div>

<main class="main">
  <h3 class="mb-4">Welcome, <?php echo htmlspecialchars($userData['fname']); ?> 👋</h3>
  <p class="text-muted mb-5">You are logged in as <strong>Admin</strong>.</p>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="card text-center">
        <div class="card-body">
          <i class="bi bi-people display-5 text-primary mb-2"></i>
          <h5 class="card-title">Users</h5>
          <p class="display-6 fw-bold text-primary">
            <?php echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?>
          </p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card text-center">
        <div class="card-body">
          <i class="bi bi-bag-check display-5 text-success mb-2"></i>
          <h5 class="card-title">Orders</h5>
          <p class="display-6 fw-bold text-success">
            <?php echo $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); ?>
          </p>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card text-center">
        <div class="card-body">
          <i class="bi bi-box-seam display-5 text-warning mb-2"></i>
          <h5 class="card-title">Products</h5>
          <p class="display-6 fw-bold text-warning">
            <?php echo $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); ?>
          </p>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
