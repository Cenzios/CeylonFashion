<?php
// admin/edit-product.php
session_name('ADMIN_SESSION');
session_start();
include_once '../config.php';

// ---- Auth / admin check ----
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// Database connection (PDO for consistency)
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

// ---- Fetch product ID from GET ----
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}
$pid = (int)$_GET['id'];

// ---- Fetch product data ----
$stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$pid]);
$product = $stmt->fetch();
if (!$product) {
    header('Location: products.php');
    exit;
}

// ---- Fetch fabric details for this product ----
$fabricStmt = $pdo->prepare("SELECT fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = ?");
$fabricStmt->execute([$pid]);
$fabricResult = $fabricStmt->fetchAll();
$existingFabrics = [];
foreach ($fabricResult as $f) {
    $existingFabrics[$f['fabric_type']] = [
        'qty' => $f['fabric_qty'],
        'price' => $f['fabric_price']
    ];
}

// ---- Fetch existing color for this product (ONLY ONE) ----
$colorStmt = $pdo->prepare("SELECT color_name, color_code FROM product_colors WHERE product_id = ? LIMIT 1");
$colorStmt->execute([$pid]);
$existingColor = $colorStmt->fetch();

// ---- Fetch existing sizes ----
$sizeStmt = $pdo->prepare("SELECT size FROM product_sizes WHERE product_id = ?");
$sizeStmt->execute([$pid]);
$existingSizes = $sizeStmt->fetchAll(PDO::FETCH_COLUMN);

// ---- Fetch all available colors from database ----
$allColorsStmt = $pdo->query("SELECT id, color_name, color_code FROM colors ORDER BY color_name");
$availableColors = $allColorsStmt->fetchAll();

// ---- Handle Add New Color via AJAX ----
if (isset($_POST['ajax_add_color'])) {
  header('Content-Type: application/json');
  
  $newColorName = trim($_POST['new_color_name']);
  $newColorCode = trim($_POST['new_color_code']);
  
  if (empty($newColorName) || empty($newColorCode)) {
    echo json_encode(['success' => false, 'message' => 'Color name and code are required']);
    exit;
  }
  
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

// ---- Handle form submission ----
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_add_color'])) {
    $product_code = trim($_POST['product_code']);
    $product_name = trim($_POST['product_name']);
    $product_desc = trim($_POST['product_desc']);
    $category = trim($_POST['category']);
    $selected_color_id = $_POST['product_color'] ?? '';

    // Handle image uploads (up to 4 images)
    $img1 = $product['product_img1'];
    $img2 = $product['product_img2'];
    $img3 = $product['product_img3'];
    $img4 = $product['product_img4'];

    $target_dir = '../images/products/';

    if (!empty($_FILES['product_img1']['name'])) {
        $img1 = basename($_FILES['product_img1']['name']);
        move_uploaded_file($_FILES['product_img1']['tmp_name'], $target_dir . $img1);
    }
    if (!empty($_FILES['product_img2']['name'])) {
        $img2 = basename($_FILES['product_img2']['name']);
        move_uploaded_file($_FILES['product_img2']['tmp_name'], $target_dir . $img2);
    }
    if (!empty($_FILES['product_img3']['name'])) {
        $img3 = basename($_FILES['product_img3']['name']);
        move_uploaded_file($_FILES['product_img3']['tmp_name'], $target_dir . $img3);
    }
    if (!empty($_FILES['product_img4']['name'])) {
        $img4 = basename($_FILES['product_img4']['name']);
        move_uploaded_file($_FILES['product_img4']['tmp_name'], $target_dir . $img4);
    }

    try {
        $pdo->beginTransaction();

        // Update main product info
        $update_stmt = $pdo->prepare("UPDATE products SET product_code=?, product_name=?, product_desc=?, product_img1=?, product_img2=?, product_img3=?, product_img4=?, category=? WHERE id=?");
        $update_stmt->execute([$product_code, $product_name, $product_desc, $img1, $img2, $img3, $img4, $category, $pid]);

        // Update fabric details (UPSERT to preserve fabric IDs for cart references)
        $submittedFabrics = [];
        if (isset($_POST['fabric_type'])) {
            foreach ($_POST['fabric_type'] as $i => $type) {
                $fabric_qty = isset($_POST['fabric_qty'][$i]) ? (int)$_POST['fabric_qty'][$i] : 0;
                $fabric_price = isset($_POST['fabric_price'][$i]) ? (float)$_POST['fabric_price'][$i] : 0;

                if (!empty($type) && $fabric_qty > 0) {
                    $submittedFabrics[] = $type;

                    // Check if this fabric type already exists for this product
                    $checkFabric = $pdo->prepare("SELECT id FROM product_fabrics WHERE product_id = ? AND fabric_type = ?");
                    $checkFabric->execute([$pid, $type]);
                    $existingRow = $checkFabric->fetch();

                    if ($existingRow) {
                        // UPDATE existing row (preserves the id)
                        $updateFabric = $pdo->prepare("UPDATE product_fabrics SET fabric_qty = ?, fabric_price = ? WHERE id = ?");
                        $updateFabric->execute([$fabric_qty, $fabric_price, $existingRow['id']]);
                    } else {
                        // INSERT new fabric type
                        $insertFabric = $pdo->prepare("INSERT INTO product_fabrics (product_id, fabric_type, fabric_qty, fabric_price) VALUES (?, ?, ?, ?)");
                        $insertFabric->execute([$pid, $type, $fabric_qty, $fabric_price]);
                    }
                }
            }
        }

        // Delete only fabric types that were removed by admin (not in submitted list)
        if (!empty($submittedFabrics)) {
            $placeholders = implode(',', array_fill(0, count($submittedFabrics), '?'));
            $deleteOld = $pdo->prepare("DELETE FROM product_fabrics WHERE product_id = ? AND fabric_type NOT IN ($placeholders)");
            $deleteOld->execute(array_merge([$pid], $submittedFabrics));
        } else {
            // If no fabrics submitted, delete all
            $deleteAll = $pdo->prepare("DELETE FROM product_fabrics WHERE product_id = ?");
            $deleteAll->execute([$pid]);
        }

        // Delete and re-insert color (ONLY ONE)
        $deleteColors = $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?");
        $deleteColors->execute([$pid]);

        if (!empty($selected_color_id)) {
            $colorInfo = $pdo->prepare("SELECT color_name, color_code FROM colors WHERE id = ?");
            $colorInfo->execute([$selected_color_id]);
            $color = $colorInfo->fetch();
            
            if ($color) {
                $stmtColor = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
                $stmtColor->execute([$pid, $color['color_name'], $color['color_code']]);
            }
        }

        // Delete and re-insert sizes
        $deleteSizes = $pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
        $deleteSizes->execute([$pid]);

        if (isset($_POST['product_sizes']) && is_array($_POST['product_sizes'])) {
            $stmtSize = $pdo->prepare("INSERT INTO product_sizes (product_id, size) VALUES (?, ?)");
            foreach ($_POST['product_sizes'] as $size) {
                $stmtSize->execute([$pid, $size]);
            }
        }

        $pdo->commit();
        $success = "✅ Product updated successfully with images, fabrics, and color!";
        
        // Refresh data
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
        $stmt->execute([$pid]);
        $product = $stmt->fetch();

        $fabricStmt = $pdo->prepare("SELECT fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = ?");
        $fabricStmt->execute([$pid]);
        $fabricResult = $fabricStmt->fetchAll();
        $existingFabrics = [];
        foreach ($fabricResult as $f) {
            $existingFabrics[$f['fabric_type']] = [
                'qty' => $f['fabric_qty'],
                'price' => $f['fabric_price']
            ];
        }

        // Refresh color
        $colorStmt = $pdo->prepare("SELECT color_name, color_code FROM product_colors WHERE product_id = ? LIMIT 1");
        $colorStmt->execute([$pid]);
        $existingColor = $colorStmt->fetch();

        // Refresh sizes
        $sizeStmt = $pdo->prepare("SELECT size FROM product_sizes WHERE product_id = ?");
        $sizeStmt->execute([$pid]);
        $existingSizes = $sizeStmt->fetchAll(PDO::FETCH_COLUMN);

    } catch (Throwable $e) {
        $pdo->rollBack();
        $error = "❌ Error: " . $e->getMessage();
    }
}

// fallback image
$fallback = '../assets/no-image.png';
$imgPath1 = !empty($product['product_img1']) ? '../images/products/' . $product['product_img1'] : $fallback;
$imgPath2 = !empty($product['product_img2']) ? '../images/products/' . $product['product_img2'] : '';
$imgPath3 = !empty($product['product_img3']) ? '../images/products/' . $product['product_img3'] : '';
$imgPath4 = !empty($product['product_img4']) ? '../images/products/' . $product['product_img4'] : '';

if (!file_exists($imgPath1)) {
    $imgPath1 = $fallback;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Product | Admin Panel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background:#f8f9fa; font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
.sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#430160ff; color:#fff; padding-top:20px; }
.sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
.sidebar a.active { background:#007bff; color:#fff; }
.sidebar a:hover { background: #5a1b88; color: #fff; }
.main { margin-left:240px; padding:28px; min-height:100vh; }
.fabric-table input { width: 100%; }
.image-preview { width:150px; height:150px; object-fit:cover; margin-bottom:10px; border-radius:8px; border: 2px solid #ddd; }

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
</style>
</head>
<body>

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

<main class="main">
    <h3 class="mb-3">Edit Product</h3>
    <p class="text-muted mb-4">Update the details of the product below.</p>

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
            <input type="text" name="product_code" class="form-control" value="<?= htmlspecialchars($product['product_code']) ?>" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
            <small class="text-muted">Product code cannot be changed.</small>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
            <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
            <textarea name="product_desc" rows="4" class="form-control" required><?= htmlspecialchars($product['product_desc']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
            <select name="category" class="form-select" required>
                <option value="" disabled>-- Select Category --</option>
                <?php
                $categories = ['used'=>'Used','bridalAttire'=>'Bridal Attire','bridemaidAttire'=>'Bridesmaid Attire','partyWear'=>'Party Wear'];
                foreach ($categories as $key=>$label) {
                    $selected = ($product['category']==$key)?'selected':'';
                    echo "<option value='$key' $selected>$label</option>";
                }
                ?>
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
                        <?php 
                        // Find the color ID that matches existing color
                        $selectedColorId = null;
                        if ($existingColor) {
                            foreach ($availableColors as $color) {
                                if (strtolower($color['color_name']) === strtolower($existingColor['color_name'])) {
                                    $selectedColorId = $color['id'];
                                    break;
                                }
                            }
                        }
                        
                        foreach ($availableColors as $color): 
                            $selected = ($color['id'] == $selectedColorId) ? 'selected' : '';
                        ?>
                            <option value="<?= $color['id'] ?>" data-color="<?= $color['color_code'] ?>" <?= $selected ?>>
                                <?= htmlspecialchars($color['color_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="colorPreview" class="color-preview" style="background-color: <?= $existingColor ? htmlspecialchars($existingColor['color_code']) : '#fff' ?>;"></div>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addColorModal">
                    + Add New Color
                </button>
            </div>
            </div>
        </div>

        <!-- 📏 Size Selection -->
        <div class="mb-4">
            <h5 class="fw-bold text-primary mb-2">Available Sizes</h5>
            <p class="text-muted small mb-3">Select available sizes (Hold Ctrl/Cmd to select multiple)</p>
            <select name="product_sizes[]" class="form-select" multiple size="5">
                <?php 
                $allSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                foreach ($allSizes as $s): 
                    $selected = in_array($s, $existingSizes) ? 'selected' : '';
                ?>
                    <option value="<?= $s ?>" <?= $selected ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Product Images Section -->
        <div class="mb-4">
            <h5 class="fw-bold mb-3 text-primary">Product Images</h5>
            <div class="row g-3">
                <!-- Image 1 (Main) -->
                <div class="col-md-3">
                    <label class="form-label">Image 1 (Main)</label><br>
                    <?php if ($imgPath1 && file_exists($imgPath1)): ?>
                        <img src="<?= $imgPath1 ?>" alt="Image 1" class="image-preview"><br>
                    <?php endif; ?>
                    <input type="file" name="product_img1" class="form-control" accept="image/*">
                    <small class="text-muted">Leave empty to keep current</small>
                </div>

                <!-- Image 2 -->
                <div class="col-md-3">
                    <label class="form-label">Image 2</label><br>
                    <?php if ($imgPath2 && file_exists($imgPath2)): ?>
                        <img src="<?= $imgPath2 ?>" alt="Image 2" class="image-preview"><br>
                    <?php endif; ?>
                    <input type="file" name="product_img2" class="form-control" accept="image/*">
                    <small class="text-muted">Optional</small>
                </div>

                <!-- Image 3 -->
                <div class="col-md-3">
                    <label class="form-label">Image 3</label><br>
                    <?php if ($imgPath3 && file_exists($imgPath3)): ?>
                        <img src="<?= $imgPath3 ?>" alt="Image 3" class="image-preview"><br>
                    <?php endif; ?>
                    <input type="file" name="product_img3" class="form-control" accept="image/*">
                    <small class="text-muted">Optional</small>
                </div>

                <!-- Image 4 -->
                <div class="col-md-3">
                    <label class="form-label">Image 4</label><br>
                    <?php if ($imgPath4 && file_exists($imgPath4)): ?>
                        <img src="<?= $imgPath4 ?>" alt="Image 4" class="image-preview"><br>
                    <?php endif; ?>
                    <input type="file" name="product_img4" class="form-control" accept="image/*">
                    <small class="text-muted">Optional</small>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h5 class="fw-bold mb-3 text-primary">Fabric Types & Details</h5>
            <p class="text-muted small mb-3">Enter quantity and price for available fabrics.</p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle fabric-table">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th>Fabric Type</th>
                            <th>Available Quantity</th>
                            <th>Unit Price (Rs)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="fabricTableBody">
                        <?php
                        $defaultFabrics = ["Silk", "Lace and Net", "Satin", "Georgette", "Velvet"];
                        
                        // Merge defaults with existing from DB to capture everything
                        // But we want to distinguish them for display purposes potentially
                        // Best way: Loop through defaults first, then loop through any extras in $existingFabrics that aren't defaults.
                        
                        // 1. Render Defaults
                        foreach ($defaultFabrics as $f) {
                            $qty = isset($existingFabrics[$f]) ? $existingFabrics[$f]['qty'] : 0;
                            $price = isset($existingFabrics[$f]) ? $existingFabrics[$f]['price'] : 0;
                            ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="fabric_type[]" value="<?= $f ?>">
                                    <span class="fw-semibold"><?= $f ?></span>
                                </td>
                                <td><input type="number" name="fabric_qty[]" min="0" class="form-control" placeholder="0" value="<?= $qty ?>"></td>
                                <td><input type="number" name="fabric_price[]" step="0.01" min="0" class="form-control" placeholder="0.00" value="<?= number_format($price, 2, '.', '') ?>"></td>
                                <td></td> <!-- Defaults cannot be removed via UI in this design -->
                            </tr>
                            <?php
                            // Remove from processed list so we know what's left as custom
                            unset($existingFabrics[$f]);
                        }
                        
                        // 2. Render Remaining (Custom) Fabrics
                        foreach ($existingFabrics as $type => $data) {
                            ?>
                            <tr>
                                <td>
                                    <input type="text" name="fabric_type[]" class="form-control" value="<?= htmlspecialchars($type) ?>" required>
                                </td>
                                <td><input type="number" name="fabric_qty[]" min="0" class="form-control" placeholder="0" value="<?= $data['qty'] ?>"></td>
                                <td><input type="number" name="fabric_price[]" step="0.01" min="0" class="form-control" placeholder="0.00" value="<?= number_format($data['price'], 2, '.', '') ?>"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-fabric-btn" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-success mt-2" id="addFabricBtn">
                    <i class="bi bi-plus-lg"></i> Add New Fabric
                </button>
            </div>
        </div>

        <script>
        // Dynamic Fabric Rows Logic (Embedded for immediate execution availability)
        document.addEventListener('DOMContentLoaded', function() {
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
                    if (confirm('Are you sure you want to remove this fabric row?')) {
                        e.target.closest('tr').remove();
                    }
                }
            });
        });
        </script>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-check-circle"></i> Update Product
            </button>
            <a href="products.php" class="btn btn-outline-secondary btn-lg px-4 ms-2">
                Cancel
            </a>
        </div>
    </form>
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
      document.querySelector('main').insertBefore(successAlert, document.querySelector('h3'));
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