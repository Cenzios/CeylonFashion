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

// ---- Fetch product ID from GET ----
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}
$pid = (int)$_GET['id'];

// ---- Fetch product data ----
$stmt = $mysqli->prepare("SELECT * FROM products WHERE id=?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
if (!$product) {
    header('Location: products.php');
    exit;
}

// ---- Fetch fabric details for this product ----
$fabricStmt = $mysqli->prepare("SELECT fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = ?");
$fabricStmt->bind_param("i", $pid);
$fabricStmt->execute();
$fabricResult = $fabricStmt->get_result();
$existingFabrics = [];
while ($f = $fabricResult->fetch_assoc()) {
    $existingFabrics[$f['fabric_type']] = [
        'qty' => $f['fabric_qty'],
        'price' => $f['fabric_price']
    ];
}
$fabricStmt->close();

// ---- Fetch existing colors for this product ----
$colorStmt = $mysqli->prepare("SELECT color_name, color_code FROM product_colors WHERE product_id = ?");
$colorStmt->bind_param("i", $pid);
$colorStmt->execute();
$colorResult = $colorStmt->get_result();
$existingColors = [];
while ($c = $colorResult->fetch_assoc()) {
    $existingColors[] = strtolower($c['color_name']);
}
$colorStmt->close();

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

// ---- Handle form submission ----
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_code = $mysqli->real_escape_string($_POST['product_code']);
    $product_name = $mysqli->real_escape_string($_POST['product_name']);
    $product_desc = $mysqli->real_escape_string($_POST['product_desc']);
    $category = $mysqli->real_escape_string($_POST['category']);
    $selected_colors = $_POST['colors'] ?? [];

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

    // Update main product info
    $update_stmt = $mysqli->prepare("UPDATE products SET product_code=?, product_name=?, product_desc=?, product_img1=?, product_img2=?, product_img3=?, product_img4=?, category=? WHERE id=?");
    $update_stmt->bind_param("ssssssssi", $product_code, $product_name, $product_desc, $img1, $img2, $img3, $img4, $category, $pid);

    if ($update_stmt->execute()) {
        // Delete and re-insert fabric details
        $deleteFabric = $mysqli->prepare("DELETE FROM product_fabrics WHERE product_id = ?");
        $deleteFabric->bind_param("i", $pid);
        $deleteFabric->execute();
        $deleteFabric->close();

        if (isset($_POST['fabric_type'])) {
            foreach ($_POST['fabric_type'] as $i => $type) {
                $fabric_qty = isset($_POST['fabric_qty'][$i]) ? (int)$_POST['fabric_qty'][$i] : 0;
                $fabric_price = isset($_POST['fabric_price'][$i]) ? (float)$_POST['fabric_price'][$i] : 0;

                if (!empty($type) && $fabric_qty > 0) {
                    $stmtFabric = $mysqli->prepare("INSERT INTO product_fabrics (product_id, fabric_type, fabric_qty, fabric_price) VALUES (?, ?, ?, ?)");
                    $stmtFabric->bind_param("isid", $pid, $type, $fabric_qty, $fabric_price);
                    $stmtFabric->execute();
                    $stmtFabric->close();
                }
            }
        }

        // Delete and re-insert colors
        $deleteColors = $mysqli->prepare("DELETE FROM product_colors WHERE product_id = ?");
        $deleteColors->bind_param("i", $pid);
        $deleteColors->execute();
        $deleteColors->close();

        if (!empty($selected_colors)) {
            $stmtColor = $mysqli->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
            foreach ($selected_colors as $colorKey) {
                if (isset($availableColors[$colorKey])) {
                    $colorName = $availableColors[$colorKey]['name'];
                    $colorCode = $availableColors[$colorKey]['code'];
                    $stmtColor->bind_param("iss", $pid, $colorName, $colorCode);
                    $stmtColor->execute();
                }
            }
            $stmtColor->close();
        }

        $success = "✅ Product updated successfully with images, fabrics, and colors!";
        
        // Refresh data
        $stmt = $mysqli->prepare("SELECT * FROM products WHERE id=?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        $fabricStmt = $mysqli->prepare("SELECT fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = ?");
        $fabricStmt->bind_param("i", $pid);
        $fabricStmt->execute();
        $fabricResult = $fabricStmt->get_result();
        $existingFabrics = [];
        while ($f = $fabricResult->fetch_assoc()) {
            $existingFabrics[$f['fabric_type']] = [
                'qty' => $f['fabric_qty'],
                'price' => $f['fabric_price']
            ];
        }
        $fabricStmt->close();

        // Refresh colors
        $colorStmt = $mysqli->prepare("SELECT color_name FROM product_colors WHERE product_id = ?");
        $colorStmt->bind_param("i", $pid);
        $colorStmt->execute();
        $colorResult = $colorStmt->get_result();
        $existingColors = [];
        while ($c = $colorResult->fetch_assoc()) {
            $existingColors[] = strtolower($c['color_name']);
        }
        $colorStmt->close();
    } else {
        $error = "❌ Error: " . $mysqli->error;
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
            <input type="text" name="product_code" class="form-control" value="<?= htmlspecialchars($product['product_code']) ?>" required>
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

        <!-- 🎨 Color Selection Section -->
        <div class="mb-4">
            <h5 class="fw-bold text-primary mb-2">Available Colors</h5>
            <p class="text-muted small mb-3">Select all colors available for this product (click on circles)</p>
            <div class="color-selection">
                <?php foreach ($availableColors as $key => $color): 
                    $isChecked = in_array(strtolower($color['name']), $existingColors) ? 'checked' : '';
                ?>
                    <div class="color-option">
                        <label>
                            <input type="checkbox" name="colors[]" value="<?= $key ?>" <?= $isChecked ?>>
                            <div class="color-circle <?= $key === 'white' ? 'white' : '' ?>" style="background-color: <?= $color['code'] ?>;"></div>
                            <div class="color-label"><?= $color['name'] ?></div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
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

        <!-- Fabric Type Section -->
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
                        foreach ($fabrics as $f):
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>