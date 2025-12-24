<?php
session_name('ADMIN_SESSION');
session_start();

// ---- Redirect if not logged in or not admin ----
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
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

// ---- Fetch all available colors from database ----
$colorStmt = $pdo->query("SELECT id, color_name, color_code FROM colors ORDER BY color_name");
$availableColors = $colorStmt->fetchAll();

// ---- Handle Add New Color via AJAX ----
if (isset($_POST['ajax_add_color'])) {
  header('Content-Type: application/json');
  
  $newColorName = trim($_POST['new_color_name']);
  $newColorCode = trim($_POST['new_color_code']);
  
  if (empty($newColorName) || empty($newColorCode)) {
    echo json_encode(['success' => false, 'message' => 'Color name and code are required']);
    exit;
  }
  
  // Validate hex color code
  if (!preg_match('/^#[0-9A-F]{6}$/i', $newColorCode)) {
    echo json_encode(['success' => false, 'message' => 'Invalid color code format. Use #RRGGBB']);
    exit;
  }
  
  try {
    $insertColor = $pdo->prepare("INSERT INTO colors (color_name, color_code) VALUES (?, ?)");
    $insertColor->execute([$newColorName, $newColorCode]);
    
    echo json_encode([
      'success' => true, 
      'message' => 'Color added successfully',
      'color' => [
        'id' => $pdo->lastInsertId(),
        'name' => $newColorName,
        'code' => $newColorCode
      ]
    ]);
  } catch (PDOException $e) {
    if ($e->getCode() == 23000) {
      echo json_encode(['success' => false, 'message' => 'Color name already exists']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Database error']);
    }
  }
  exit;
}

// ---- Add Product Logic ----
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_add_color'])) {
  $product_code = trim($_POST['product_code']);
  $product_name = trim($_POST['product_name']);
  $product_desc = trim($_POST['product_desc']);
  $category = $_POST['category'] ?? '';
  $selected_color_id = $_POST['product_color'] ?? '';

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

        // Insert selected color (ONLY ONE)
        if (!empty($selected_color_id)) {
          $colorInfo = $pdo->prepare("SELECT color_name, color_code FROM colors WHERE id = ?");
          $colorInfo->execute([$selected_color_id]);
          $color = $colorInfo->fetch();
          
          if ($color) {
            $stmtColor = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
            $stmtColor->execute([$product_id, $color['color_name'], $color['color_code']]);
          }
        }

        // Insert selected sizes
        if (isset($_POST['product_sizes']) && is_array($_POST['product_sizes'])) {
          $stmtSize = $pdo->prepare("INSERT INTO product_sizes (product_id, size) VALUES (?, ?)");
          foreach ($_POST['product_sizes'] as $size) {
            $stmtSize->execute([$product_id, $size]);
          }
        }

        // Commit transaction
        $pdo->commit();
        $success = "✅ Product added successfully with images, fabrics, and color!";
        
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
    
    .color-dropdown-container {
      display: flex;
      gap: 10px;
      align-items: start;
    }
    .color-dropdown-container select {
      flex: 1;
    }
    .color-preview {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      border: 2px solid #dee2e6;
      display: inline-block;
      vertical-align: middle;
      margin-left: 10px;
    }
    .color-select-option {
      display: flex;
      align-items: center;
      gap: 8px;
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

    <?php
    // Generate unique product code
    $generated_code = '';
    do {
        $rand_num = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $generated_code = 'P' . $rand_num;
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
        $checkStmt->execute([$generated_code]);
    } while ($checkStmt->fetchColumn() > 0);
    ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label fw-semibold">Product Code <span class="text-danger">*</span></label>
        <input type="text" name="product_code" class="form-control" value="<?= $generated_code ?>" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
        <small class="text-muted">Auto-generated unique product code.</small>
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

          <option value="bridalAttire">Bridal Attire</option>
          <option value="bridemaidAttire">Bridesmaid Attire</option>
          <option value="partyWear">Party Wear</option>
        </select>
      </div>

      <!-- 🎨 Color Selection Dropdown -->
      <div class="mb-4">
        <h5 class="fw-bold text-primary mb-2">Product Color</h5>
        <p class="text-muted small mb-3">Select one color for this product</p>
        <div class="color-dropdown-container">
          <div style="flex: 1;">
            <select name="product_color" id="productColor" class="form-select" required>
              <option value="">-- Select Color --</option>
              <?php foreach ($availableColors as $color): ?>
                <option value="<?= $color['id'] ?>" data-color="<?= $color['color_code'] ?>">
                  <?= htmlspecialchars($color['color_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="colorPreview" class="color-preview" style="background-color: #fff;"></div>
          <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addColorModal">
            + Add New Color
          </button>
        </div>
      </div>

      <!-- 📏 Size Selection -->
      <div class="mb-4">
        <h5 class="fw-bold text-primary mb-2">Available Sizes</h5>
        <p class="text-muted small mb-3">Select available sizes (Hold Ctrl/Cmd to select multiple)</p>
        <select name="product_sizes[]" class="form-select" multiple size="5">
          <option value="XS">XS</option>
          <option value="S">S</option>
          <option value="M">M</option>
          <option value="L">L</option>
          <option value="XL">XL</option>
          <option value="XXL">XXL</option>
        </select>
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
            <tbody id="fabricTableBody">
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
                  <td></td> <!-- Empty for default delete check or strict layout -->
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <button type="button" class="btn btn-sm btn-outline-success mt-2" id="addFabricBtn">
            <i class="bi bi-plus-lg"></i> Add New Fabric
          </button>
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

<!-- Add Color Modal -->
<div class="modal fade" id="addColorModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Color</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="colorModalAlert"></div>
        <div class="mb-3">
          <label class="form-label">Color Name <span class="text-danger">*</span></label>
          <input type="text" id="newColorName" class="form-control" placeholder="e.g., Navy Blue">
        </div>
        <div class="mb-3">
          <label class="form-label">Color Code (Hex) <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="text" id="newColorCode" class="form-control" placeholder="#000000" maxlength="7">
            <input type="color" id="newColorPicker" class="form-control form-control-color" value="#000000" title="Pick a color">
          </div>
          <small class="text-muted">Format: #RRGGBB</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Preview</label>
          <div id="newColorPreview" style="width: 100%; height: 50px; border-radius: 8px; border: 2px solid #dee2e6; background-color: #000000;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveColorBtn">Save Color</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Dynamic Fabric Rows
document.getElementById('addFabricBtn').addEventListener('click', function() {
    const tbody = document.getElementById('fabricTableBody');
    const row = document.createElement('tr');
    
    row.innerHTML = `
        <td>
            <input type="text" name="fabric_type[]" class="form-control" placeholder="Enter Fabric Name" required>
        </td>
        <td>
            <input type="number" name="fabric_qty[]" min="0" class="form-control" placeholder="0">
        </td>
        <td>
            <input type="number" name="fabric_price[]" step="0.01" min="0" class="form-control" placeholder="0.00">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-fabric-btn" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
});

// Event delegation for remove buttons
document.getElementById('fabricTableBody').addEventListener('click', function(e) {
    if (e.target.closest('.remove-fabric-btn')) {
        e.target.closest('tr').remove();
    }
});
// Color preview on selection
document.getElementById('productColor').addEventListener('change', function() {
  const selectedOption = this.options[this.selectedIndex];
  const colorCode = selectedOption.getAttribute('data-color');
  document.getElementById('colorPreview').style.backgroundColor = colorCode || '#fff';
});

// Color picker sync
const colorPicker = document.getElementById('newColorPicker');
const colorCode = document.getElementById('newColorCode');
const colorPreview = document.getElementById('newColorPreview');

colorPicker.addEventListener('input', function() {
  colorCode.value = this.value.toUpperCase();
  colorPreview.style.backgroundColor = this.value;
});

colorCode.addEventListener('input', function() {
  const val = this.value;
  if (/^#[0-9A-F]{6}$/i.test(val)) {
    colorPicker.value = val;
    colorPreview.style.backgroundColor = val;
  }
});

// Save new color
document.getElementById('saveColorBtn').addEventListener('click', function() {
  const name = document.getElementById('newColorName').value.trim();
  const code = document.getElementById('newColorCode').value.trim();
  const alertDiv = document.getElementById('colorModalAlert');
  
  if (!name || !code) {
    alertDiv.innerHTML = '<div class="alert alert-danger">Please fill in all fields</div>';
    return;
  }
  
  if (!/^#[0-9A-F]{6}$/i.test(code)) {
    alertDiv.innerHTML = '<div class="alert alert-danger">Invalid color code format. Use #RRGGBB</div>';
    return;
  }
  
  // Send AJAX request
  const formData = new FormData();
  formData.append('ajax_add_color', '1');
  formData.append('new_color_name', name);
  formData.append('new_color_code', code);
  
  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Add new option to select
      const select = document.getElementById('productColor');
      const option = document.createElement('option');
      option.value = data.color.id;
      option.setAttribute('data-color', data.color.code);
      option.textContent = data.color.name;
      select.appendChild(option);
      select.value = data.color.id;
      
      // Update preview
      document.getElementById('colorPreview').style.backgroundColor = data.color.code;
      
      // Close modal and reset
      bootstrap.Modal.getInstance(document.getElementById('addColorModal')).hide();
      document.getElementById('newColorName').value = '';
      document.getElementById('newColorCode').value = '#000000';
      colorPicker.value = '#000000';
      colorPreview.style.backgroundColor = '#000000';
      alertDiv.innerHTML = '';
      
      // Show success message
      const successAlert = document.createElement('div');
      successAlert.className = 'alert alert-success alert-dismissible fade show';
      successAlert.innerHTML = `${data.message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
      document.querySelector('.container-fluid').insertBefore(successAlert, document.querySelector('h2'));
    } else {
      alertDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
    }
  })
  .catch(error => {
    alertDiv.innerHTML = '<div class="alert alert-danger">Error adding color</div>';
  });
});
</script>
</body>
</html>