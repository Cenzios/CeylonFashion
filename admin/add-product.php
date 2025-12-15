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

// ---- Define Available Colors ----
$availableColors = [
  'red' => ['name' => 'Red', 'code' => '#FF0000'],
  'blue' => ['name' => 'Blue', 'code' => '#0000FF'],
  'green' => ['name' => 'Green', 'code' => '#00FF00'],
  'yellow' => ['name' => 'Yellow', 'code' => '#FFFF00'],
  'orange' => ['name' => 'Orange', 'code' => '#FFA500'],
  'purple' => ['name' => 'Purple', 'code' => '#800080'],
  'pink' => ['name' => 'Pink', 'code' => '#FFC0CB'],
  'black' => ['name' => 'Black', 'code' => '#000000'],
  'white' => ['name' => 'White', 'code' => '#FFFFFF'],
  'brown' => ['name' => 'Brown', 'code' => '#8B4513']
];

// ---- Add Product Logic ----
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $product_code = trim($_POST['product_code']);
  $product_name = trim($_POST['product_name']);
  $product_desc = trim($_POST['product_desc']);
  $category = $_POST['category'] ?? '';
  $selected_colors = $_POST['colors'] ?? [];

  // Validation
  if (empty($product_code) || empty($product_name) || empty($product_desc) || empty($category)) {
      $error = "❌ All fields (Code, Name, Description, Category) are required.";
  } else {
      // Check for duplicate product code
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
      $stmt->execute([$product_code]);
      if ($stmt->fetchColumn() > 0) {
          $error = "❌ Product Code '$product_code' already exists. Please use a unique code.";
      }
  }

  if (!$error) {
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
      // Start transaction for data integrity
      $pdo->beginTransaction();

      // Insert product with new image columns
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

            if (!empty($type) && ($fabric_qty > 0 || $fabric_price > 0)) {
              $stmtFabric = $pdo->prepare("INSERT INTO product_fabrics (product_id, fabric_type, fabric_qty, fabric_price)
                                           VALUES (?, ?, ?, ?)");
              $stmtFabric->execute([$product_id, $type, $fabric_qty, $fabric_price]);
            }
          }
        }

        // Insert selected colors
        if (!empty($selected_colors)) {
          $stmtColor = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
          
          foreach ($selected_colors as $colorKey) {
            if (isset($availableColors[$colorKey])) {
              $stmtColor->execute([
                $product_id, 
                $availableColors[$colorKey]['name'], 
                $availableColors[$colorKey]['code']
              ]);
            }
          }
        }

        // Commit transaction
        $pdo->commit();
        $success = "✅ Product added successfully with images, fabrics, and colors!";
        
      } else {
        $pdo->rollBack();
        $error = "❌ Failed to add product.";
      }
    } catch (Throwable $e) {
      $pdo->rollBack();
      $error = "❌ Database error: " . $e->getMessage();
    }
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
    body { background:#f8f9fa; font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
    .sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#430160ff; color:#fff; padding-top:20px; }
    .sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
    .sidebar a.active { background:#007bff; color:#fff; }
    .sidebar a:hover { background: #5a1b88; color: #fff; }
    .main { margin-left:240px; padding:28px; min-height:100vh; }
    .fabric-table input { width: 100%; }
    
    /* Color Selection Styles */
    .color-selection {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 10px;
      border: 2px solid #e9ecef;
    }
    .color-option {
      position: relative;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: transform 0.2s ease;
    }
    .color-option:hover {
      transform: translateY(-2px);
    }
    .color-option input[type="checkbox"] {
      position: absolute;
      opacity: 0;
      cursor: pointer;
      width: 0;
      height: 0;
    }
    .color-circle {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: 3px solid #dee2e6;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      position: relative;
    }
    .color-option input[type="checkbox"]:checked + .color-circle {
      border-color: #430160ff;
      border-width: 4px;
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(67, 1, 96, 0.4);
    }
    .color-option input[type="checkbox"]:checked + .color-circle::after {
      content: "✓";
      color: white;
      font-weight: bold;
      font-size: 24px;
      text-shadow: 0 0 4px rgba(0,0,0,0.6);
      position: absolute;
    }
    .color-circle.white {
      border-color: #adb5bd;
    }
    .color-circle.white::after {
      color: #000 !important;
      text-shadow: 0 0 2px rgba(255,255,255,0.8);
    }
    .color-label {
      text-align: center;
      font-size: 12px;
      margin-top: 6px;
      color: #6c757d;
      font-weight: 500;
    }
    .color-option input[type="checkbox"]:checked ~ .color-label {
      color: #430160ff;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="text-center mb-3">Ceylon Fashion</h4>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="products.php" class="active">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="users.php">👥 Users</a>
    <a href="reports.php">📊 Reports</a>
    <hr style="border-color: rgba(255,255,255,.06)">
    <a href="logout.php" class="text-danger" onclick="return confirm('Are you sure you want to logout?');">🚪 Logout</a>
  </div>

<!-- Main Content -->
<main class="main">
  <div class="container-fluid">

    <h2 class="fw-bold mb-3">Add New Product</h2>
    <p class="text-muted mb-4">Fill out the form below to add a new product to your store.</p>

    <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label fw-semibold">Product Code <span class="text-danger">*</span></label>
        <input type="text" name="product_code" class="form-control" required placeholder="Enter unique product code">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
        <input type="text" name="product_name" class="form-control" required placeholder="Enter product name">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
        <textarea name="product_desc" rows="4" class="form-control" required placeholder="Enter product description"></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
        <select name="category" class="form-select" required>
          <option value="" disabled selected>-- Select Category --</option>
          <option value="used">Used</option>
          <option value="bridalAttire">Bridal Attire</option>
          <option value="bridemaidAttire">Bridesmaid Attire</option>
          <option value="partyWear">Party Wear</option>
        </select>
      </div>

      <!-- 🎨 Color Selection Section -->
      <div class="mb-4">
        <h5 class="fw-bold text-primary mb-2">Available Colors</h5>
        <p class="text-muted small mb-3">Select all colors available for this product (click on circles)</p>
        <div class="color-selection">
          <?php foreach ($availableColors as $key => $color): ?>
            <div class="color-option">
              <label>
                <input type="checkbox" name="colors[]" value="<?= $key ?>">
                <div class="color-circle <?= $key === 'white' ? 'white' : '' ?>" style="background-color: <?= $color['code'] ?>;"></div>
                <div class="color-label"><?= $color['name'] ?></div>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 🌸 Four Product Images -->
      <div class="mb-4">
        <h5 class="fw-bold text-primary mb-3">Upload Product Images</h5>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Image 1 <span class="text-danger">*</span></label>
            <input type="file" name="product_img1" class="form-control" accept="image/*" required>
            <small class="text-muted">Main image</small>
          </div>
          <div class="col-md-3">
            <label class="form-label">Image 2</label>
            <input type="file" name="product_img2" class="form-control" accept="image/*">
            <small class="text-muted">Optional</small>
          </div>
          <div class="col-md-3">
            <label class="form-label">Image 3</label>
            <input type="file" name="product_img3" class="form-control" accept="image/*">
            <small class="text-muted">Optional</small>
          </div>
          <div class="col-md-3">
            <label class="form-label">Image 4</label>
            <input type="file" name="product_img4" class="form-control" accept="image/*">
            <small class="text-muted">Optional</small>
          </div>
        </div>
      </div>

      <!-- 🌸 Fabric Type Section -->
      <div class="mb-4">
        <h5 class="fw-bold mb-3 text-primary">Fabric Types & Details</h5>
        <p class="text-muted small mb-3">Enter quantity and price for available fabrics (leave empty if not available)</p>
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
                  <td><input type="number" name="fabric_qty[]" min="0" class="form-control" placeholder="0"></td>
                  <td><input type="number" name="fabric_price[]" step="0.01" min="0" class="form-control" placeholder="0.00"></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary btn-lg px-5">
          <i class="bi bi-plus-circle"></i> Add Product
        </button>
        <a href="products.php" class="btn btn-outline-secondary btn-lg px-4 ms-2">
          Cancel
        </a>
      </div>
    </form>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>