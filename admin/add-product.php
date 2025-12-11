<?php
session_name('ADMIN_SESSION');
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
  $category = $_POST['category'];

  // Handle image uploads (4 images)
  $image_fields = ['product_img1', 'product_img2', 'product_img3', 'product_img4'];
  $uploaded_images = [];

  $target_dir = "../images/products/";
  if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

  foreach ($image_fields as $field) {
    if (!empty($_FILES[$field]['name'])) {
      $img_name = basename($_FILES[$field]['name']);
      $target_file = $target_dir . $img_name;
      if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_file)) {
        $uploaded_images[$field] = $img_name;
      } else {
        $uploaded_images[$field] = null;
      }
    } else {
      $uploaded_images[$field] = null;
    }
  }

  try {
    // Insert product (with 4 images)
    $stmt = $pdo->prepare("
      INSERT INTO products (product_code, product_name, product_desc, product_img1, product_img2, product_img3, product_img4, category)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if ($stmt->execute([
      $product_code,
      $product_name,
      $product_desc,
      $uploaded_images['product_img1'],
      $uploaded_images['product_img2'],
      $uploaded_images['product_img3'],
      $uploaded_images['product_img4'],
      $category
    ])) {
      $product_id = $pdo->lastInsertId();

      // Insert fabric details
      if (isset($_POST['fabric_type'])) {
        foreach ($_POST['fabric_type'] as $i => $type) {
          $fabric_qty = $_POST['fabric_qty'][$i] ?? 0;
          $fabric_price = $_POST['fabric_price'][$i] ?? 0;

          if (!empty($type)) {
            $stmtFabric = $pdo->prepare("INSERT INTO product_fabrics (product_id, fabric_type, fabric_qty, fabric_price)
                                         VALUES (?, ?, ?, ?)");
            $stmtFabric->execute([$product_id, $type, $fabric_qty, $fabric_price]);
          }
        }
      }

      $success = "✅ Product added successfully with all images and fabric details!";
    } else {
      $error = "❌ Failed to add product.";
    }
  } catch (Throwable $e) {
    $error = "❌ Database error: " . $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Product - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f5f6fa; font-family: 'Poppins', sans-serif; }
    .sidebar { width: 240px; height: 100vh; position: fixed; top: 0; left: 0; background: #343a40; color: white; padding-top: 20px; }
    .sidebar a { display: block; padding: 12px 20px; color: #ccc; text-decoration: none; transition: 0.3s; }
    .sidebar a:hover { background: #495057; color: #fff; }
    .sidebar .active { background: #007bff; color: white; }
    .main-content { margin-left: 240px; padding: 40px; }
    .form-container { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); max-width: 900px; margin: auto; }
    .fabric-table input { width: 100%; }
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
    <hr class="text-secondary">
    <a href="logout.php" class="text-danger">🚪 Logout</a>
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

      <div class="mb-3">
        <label class="form-label">Category</label>
        <select name="category" class="form-select" required>
          <option value="" disabled selected>-- Select Category --</option>
          <option value="used">Used</option>
          <option value="bridalAttire">Bridal Attire</option>
          <option value="bridemaidAttire">Bridesmaid Attire</option>
          <option value="partyWear">Party Wear</option>
        </select>
      </div>

      <!-- 🌸 Four Product Images -->
      <div class="mb-4">
        <h5 class="fw-bold text-primary mb-3">Upload Product Images</h5>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Image 1</label>
            <input type="file" name="product_img1" class="form-control" accept="image/*" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Image 2</label>
            <input type="file" name="product_img2" class="form-control" accept="image/*">
          </div>
          <div class="col-md-3">
            <label class="form-label">Image 3</label>
            <input type="file" name="product_img3" class="form-control" accept="image/*">
          </div>
          <div class="col-md-3">
            <label class="form-label">Image 4</label>
            <input type="file" name="product_img4" class="form-control" accept="image/*">
          </div>
        </div>
        <small class="text-muted">You can upload up to 4 images (Image 1 is required).</small>
      </div>

      <!-- 🌸 Fabric Type Section -->
      <div class="mb-4">
        <h5 class="fw-bold mb-3 text-primary">Fabric Types & Details</h5>
        <div class="table-responsive">
          <table class="table table-bordered align-middle fabric-table">
            <thead class="table-light">
              <tr class="text-center">
                <th>Fabric Type</th>
                <th>Available Quantity</th>
                <th>Unit Price (Rs)</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $fabrics = ["Silk", "Lace and Net", "Satin", "Georgette", "Velvet"];
              foreach ($fabrics as $f): ?>
                <tr>
                  <td>
                    <input type="hidden" name="fabric_type[]" value="<?= $f ?>">
                    <span class="fw-semibold"><?= $f ?></span>
                  </td>
                  <td><input type="number" name="fabric_qty[]" min="0" class="form-control" placeholder="Qty"></td>
                  <td><input type="number" name="fabric_price[]" step="0.01" min="0" class="form-control" placeholder="Price"></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="text-center">
        <button type="submit" class="btn btn-primary px-4">Add Product</button>
        <a href="products.php" class="btn btn-outline-secondary ms-2">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
