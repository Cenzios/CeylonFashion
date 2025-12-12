<?php
// admin/view-product.php
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
$stmt->close();

// ---- Fetch fabric details for this product ----
$fabricStmt = $mysqli->prepare("SELECT fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = ?");
$fabricStmt->bind_param("i", $pid);
$fabricStmt->execute();
$fabricResult = $fabricStmt->get_result();
$fabrics = [];
while ($f = $fabricResult->fetch_assoc()) {
    $fabrics[] = $f;
}
$fabricStmt->close();

// Prepare images
$fallback = '../assets/no-image.png';
$images = [];
for ($i = 1; $i <= 4; $i++) {
    $imgField = 'product_img' . $i;
    $imgName = $product[$imgField];
    $imgPath = '../images/products/' . $imgName;
    if (!empty($imgName) && file_exists($imgPath)) {
        $images[] = $imgPath;
    }
}
if (empty($images)) {
    $images[] = $fallback;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>View Product | Admin Panel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background:#f8f9fa; font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
.sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#430160ff; color:#fff; padding-top:20px; }
.sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
.sidebar a.active { background:#007bff; color:#fff; }
.main { margin-left:240px; padding:28px; min-height:100vh; }
.product-img { width: 100%; height: 250px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; }
.label { font-weight: 600; color: #555; }
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>View Product</h3>
        <a href="products.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Products</a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row">
                <!-- Product Details -->
                <div class="col-md-8">
                    <h4 class="mb-3 text-primary"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 label">Product Code:</div>
                        <div class="col-md-9"><?php echo htmlspecialchars($product['product_code']); ?></div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3 label">Category:</div>
                        <div class="col-md-9">
                            <?php 
                            $categories = ['used'=>'Used','bridalAttire'=>'Bridal Attire','bridemaidAttire'=>'Bridesmaid Attire','partyWear'=>'Party Wear'];
                            echo isset($categories[$product['category']]) ? $categories[$product['category']] : ucfirst($product['category']); 
                            ?>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3 label">Description:</div>
                        <div class="col-md-9 text-muted">
                            <?php echo nl2br(htmlspecialchars($product['product_desc'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Main Image Preview -->
                <div class="col-md-4">
                    <?php if (isset($images[0])): ?>
                        <img src="<?php echo $images[0]; ?>" alt="Main Image" class="img-fluid rounded shadow-sm">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Images -->
    <?php if (count($images) > 1): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Product Images</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($images as $img): ?>
                    <div class="col-6 col-md-3">
                        <img src="<?php echo $img; ?>" class="product-img" alt="Product Image">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Fabric Details -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h5 class="mb-0">Fabric Availability</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($fabrics)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fabric Type</th>
                                <th class="text-center">Available Qty</th>
                                <th class="text-end">Additional Price (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fabrics as $f): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['fabric_type']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info text-dark"><?php echo (int)$f['fabric_qty']; ?></span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?php echo number_format((float)$f['fabric_price'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No specific fabric details available for this product.</p>
            <?php endif; ?>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
