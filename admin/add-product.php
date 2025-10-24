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

// ---- Add Product Logic ----
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $product_code = trim($_POST['product_code']);
  $product_name = trim($_POST['product_name']);
  $product_desc = trim($_POST['product_desc']);
  $qty = (int)$_POST['qty'];
  $price = (float)$_POST['price'];
  $color = $_POST['color'];
  $category = $_POST['category'];
  $img_name = '';

  if (!empty($_FILES['product_img_name']['name'])) {
    $target_dir = "../images/products/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $img_name = basename($_FILES['product_img_name']['name']);
    $target_file = $target_dir . $img_name;
    move_uploaded_file($_FILES['product_img_name']['tmp_name'], $target_file);
  }

  $stmt = $pdo->prepare("INSERT INTO products (product_code, product_name, product_desc, product_img_name, qty, price, color, category) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  if ($stmt->execute([$product_code, $product_name, $product_desc, $img_name, $qty, $price, $color, $category])) {
    $success = "✅ Product added successfully!";
  } else {
    $error = "❌ Failed to add product.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Product - Admin Panel</title>

  <!-- Bootstrap -->
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
      padding: 40px;
    }
    .form-container {
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      max-width: 700px;
      margin: auto;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="text-center text-light mb-4">Admin Panel</h4>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="products.php" class="active">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="users.php">👥 Users</a>
    <a href="settings.php">⚙️ Settings</a>
    <hr class="text-secondary">
    <a href="../logout.php" class="text-danger">🚪 Logout</a>
  </div>

<!-- Main Content -->
<div class="main-content">
  <div class="container-fluid">

    <h2 class="fw-bold mb-3">Add New Product</h2>
    <p class="text-muted mb-4">Fill out the form below to add a new product to your store.</p>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Form directly on background -->
    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Product Code</label>
        <input type="text" name="product_code" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Product Name</label>
        <input type="text" name="product_name" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="product_desc" rows="4" class="form-control" required></textarea>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Quantity</label>
          <input type="number" name="qty" min="1" class="form-control" required>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Price</label>
          <input type="number" name="price" step="0.01" min="0" class="form-control" required>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Category</label>
          <select name="category" class="form-select" required>
            <option value="" disabled selected>-- Select Category --</option>
            <option value="used">Used</option>
            <option value="bridalAttire">Bridal Attire</option>
            <option value="bridemaidAttire">Bridesmaid Attire</option>
            <option value="partyWear">Party Wear</option>
          </select>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Color</label>
          <select name="color" class="form-select" required>
            <option value="" disabled selected>-- Select Color --</option>
            <option value="Grey">Grey</option>
            <option value="Green">Green</option>
            <option value="Red">Red</option>
            <option value="Orange">Orange</option>
            <option value="Blue">Blue</option>
            <option value="White">White</option>
            <option value="Black">Black</option>
            <option value="Pink">Pink</option>
            <option value="Purple">Purple</option>
            <option value="Brown">Brown</option>
            <option value="Yellow">Yellow</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Product Image</label>
        <input type="file" name="product_img_name" class="form-control" accept="image/*" required>
      </div>

      <div class="text-center">
        <button type="submit" class="btn btn-primary px-4">Add Product</button>
        <a href="products.php" class="btn btn-outline-secondary ms-2">Cancel</a>
      </div>
    </form>

  </div>
</div>


  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
