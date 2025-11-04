<?php
// admin/edit-product.php
session_start();
include_once '../config.php'; // adjust path to your config.php

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

// ---- Handle form submission ----
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_code = $mysqli->real_escape_string($_POST['product_code']);
    $product_name = $mysqli->real_escape_string($_POST['product_name']);
    $product_desc = $mysqli->real_escape_string($_POST['product_desc']);
    $category = $mysqli->real_escape_string($_POST['category']);

    // Handle image upload
    $img_name = $product['product_img_name']; // keep existing if no new file
    if (!empty($_FILES['product_img_name']['name'])) {
        $target_dir = '../images/products/';
        $img_name = basename($_FILES['product_img_name']['name']);
        $target_file = $target_dir . $img_name;
        move_uploaded_file($_FILES['product_img_name']['tmp_name'], $target_file);
    }

    // Update main product info (without qty, price, color)
    $update_stmt = $mysqli->prepare("UPDATE products SET product_code=?, product_name=?, product_desc=?, product_img_name=?, category=? WHERE id=?");
    $update_stmt->bind_param("sssssi", $product_code, $product_name, $product_desc, $img_name, $category, $pid);

    if ($update_stmt->execute()) {
        // Delete existing fabric entries
        $deleteFabric = $mysqli->prepare("DELETE FROM product_fabrics WHERE product_id = ?");
        $deleteFabric->bind_param("i", $pid);
        $deleteFabric->execute();
        $deleteFabric->close();

        // Insert updated fabric details
        if (isset($_POST['fabric_type'])) {
            foreach ($_POST['fabric_type'] as $i => $type) {
                $fabric_qty = isset($_POST['fabric_qty'][$i]) ? (int)$_POST['fabric_qty'][$i] : 0;
                $fabric_price = isset($_POST['fabric_price'][$i]) ? (float)$_POST['fabric_price'][$i] : 0;

                if (!empty($type)) {
                    $stmtFabric = $mysqli->prepare("INSERT INTO product_fabrics (product_id, fabric_type, fabric_qty, fabric_price) VALUES (?, ?, ?, ?)");
                    $stmtFabric->bind_param("isid", $pid, $type, $fabric_qty, $fabric_price);
                    $stmtFabric->execute();
                    $stmtFabric->close();
                }
            }
        }

        $success = "✅ Product updated successfully with fabric details!";
        // refresh product info
        $product['product_code'] = $product_code;
        $product['product_name'] = $product_name;
        $product['product_desc'] = $product_desc;
        $product['category'] = $category;
        $product['product_img_name'] = $img_name;

        // Refresh fabric data
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
    } else {
        $error = "❌ Error: " . $mysqli->error;
    }
}

// fallback image
$fallback = '../assets/no-image.png';
$imgPath = '../images/products/' . $product['product_img_name'];
if (empty($product['product_img_name']) || !file_exists($imgPath)) {
    $imgPath = $fallback;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Product || Admin Panel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background:#f8f9fa; font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
.sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#430160ff; color:#fff; padding-top:20px; }
.sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
.sidebar a.active { background:#007bff; color:#fff; }
.main { margin-left:240px; padding:28px; min-height:100vh; }
.fabric-table input { width: 100%; }
</style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center mb-3">Ceylon Fashion</h4>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="products.php" class="active">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="users.php">👥 Users</a>
    <hr style="border-color: rgba(255,255,255,.06)">
    <a href="../logout.php" class="text-danger">🚪 Logout</a>
</div>

<main class="main">
    <h3 class="mb-3">Edit Product</h3>
    <p class="text-muted mb-4">Update the details of the product below.</p>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Product Code</label>
            <input type="text" name="product_code" class="form-control" value="<?= htmlspecialchars($product['product_code']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="product_desc" rows="4" class="form-control" required><?= htmlspecialchars($product['product_desc']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
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

        <div class="mb-3">
            <label class="form-label">Product Image</label><br>
            <img src="<?= $imgPath ?>" alt="Product Image" style="width:150px;height:150px;object-fit:cover;margin-bottom:10px;border-radius:8px;"><br>
            <input type="file" name="product_img_name" class="form-control" accept="image/*">
            <small class="text-muted">Leave empty to keep existing image</small>
        </div>

        <!-- Fabric Type Section -->
        <div class="mb-4">
            <h5 class="fw-bold mb-3 text-primary">Fabric Types & Details</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle fabric-table">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th>Fabric Type</th>
                            <th>Available Quantity</th>
                            <th>Additional Price (Rs)</th>
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
                                <td><input type="number" name="fabric_qty[]" min="0" class="form-control" placeholder="Qty" value="<?= $qty ?>"></td>
                                <td><input type="number" name="fabric_price[]" step="0.01" min="0" class="form-control" placeholder="Price" value="<?= $price ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <small class="text-muted">Specify quantity and additional price for each fabric type.</small>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary px-4">Update Product</button>
            <a href="products.php" class="btn btn-outline-secondary ms-2">Cancel</a>
        </div>
    </form>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>