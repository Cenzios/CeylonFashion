<?php
session_start();

// ---- Redirect if not logged in or not admin ----
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit;
}

// Database connection
$dsn = 'mysql:host=localhost;dbname=sahan;charset=utf8mb4';
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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-color: #f5f6fa;
      font-family: 'Poppins', sans-serif;
    }
    .sidebar {
      width: 240px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background: #343a40;
      color: white;
      padding-top: 20px;
    }
    .sidebar a {
      display: block;
      padding: 12px 20px;
      color: #ccc;
      text-decoration: none;
      transition: 0.3s;
    }
    .sidebar a:hover {
      background: #495057;
      color: #fff;
    }
    .sidebar .active {
      background: #007bff;
      color: white;
    }
    .main-content {
      margin-left: 240px;
      padding: 30px;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="text-center text-light mb-4">Admin Panel</h4>
    <a href="dashboard.php" class="active">🏠 Dashboard</a>
    <a href="products.php">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="users.php">👥 Users</a>
    <a href="settings.php">⚙️ Settings</a>
    <hr class="text-secondary">
    <a href="../logout.php" class="text-danger">🚪 Logout</a>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container-fluid">
      <h2 class="fw-bold">Welcome, <?php echo htmlspecialchars($userData['fname']); ?> 👋</h2>
      <p class="text-muted">You are logged in as <strong>Admin</strong>.</p>

      <div class="row mt-4">
        <div class="col-md-4">
          <div class="card shadow-sm border-0">
            <div class="card-body text-center">
              <h5 class="card-title">Users</h5>
              <p class="display-6 fw-bold text-primary">
                <?php
                  $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                  echo $count;
                ?>
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card shadow-sm border-0">
            <div class="card-body text-center">
              <h5 class="card-title">Orders</h5>
              <p class="display-6 fw-bold text-success">
                <?php
                  $count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                  echo $count;
                ?>
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card shadow-sm border-0">
            <div class="card-body text-center">
              <h5 class="card-title">Products</h5>
              <p class="display-6 fw-bold text-warning">
                <?php
                  $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                  echo $count;
                ?>
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
