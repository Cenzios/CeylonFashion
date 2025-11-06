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

// ---- Add User Logic ----
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fname = trim($_POST['fname']);
  $lname = trim($_POST['lname']);
  $address = trim($_POST['address']);
  $city = trim($_POST['city']);
  $pin = trim($_POST['pin']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $type = $_POST['type'];

  if ($fname && $lname && $email && $password) {
    try {
      $stmt = $pdo->prepare("INSERT INTO users (fname, lname, address, city, pin, email, password, type)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      if ($stmt->execute([$fname, $lname, $address, $city, $pin, $email, $password, $type])) {
        $success = "✅ User added successfully!";
      } else {
        $error = "❌ Failed to add user.";
      }
    } catch (Throwable $e) {
      $error = "❌ Database error: " . $e->getMessage();
    }
  } else {
    $error = "❌ Please fill in all required fields.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add User - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f5f6fa; font-family: 'Poppins', sans-serif; }
    .sidebar { width: 240px; height: 100vh; position: fixed; top: 0; left: 0; background: #343a40; color: white; padding-top: 20px; }
    .sidebar a { display: block; padding: 12px 20px; color: #ccc; text-decoration: none; transition: 0.3s; }
    .sidebar a:hover { background: #495057; color: #fff; }
    .sidebar .active { background: #007bff; color: white; }
    .main-content { margin-left: 240px; padding: 40px; }
    .form-container { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); max-width: 700px; margin: auto; }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="text-center text-light mb-4">Admin Panel</h4>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="products.php">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="users.php" class="active">👥 Users</a>
    <hr class="text-secondary">
    <a href="../logout.php" class="text-danger">🚪 Logout</a>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container-fluid">
      <h2 class="fw-bold mb-3">Add New User</h2>
      <p class="text-muted mb-4">Fill out the form below to add a new user to your system.</p>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" class="form-container">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">First Name *</label>
            <input type="text" name="fname" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Last Name *</label>
            <input type="text" name="lname" class="form-control" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Postal Code</label>
            <input type="text" name="pin" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">User Type</label>
            <select name="type" class="form-select">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>

        <div class="text-center mt-4">
          <button type="submit" class="btn btn-primary px-4">Add User</button>
          <a href="users.php" class="btn btn-outline-secondary ms-2">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
