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

// ---- Fetch color for this product (ONLY ONE COLOR) ----
$colorStmt = $mysqli->prepare("SELECT color_name, color_code FROM product_colors WHERE product_id = ? LIMIT 1");
$colorStmt->bind_param("i", $pid);
$colorStmt->execute();
$colorResult = $colorStmt->get_result();
$productColor = $colorResult->fetch_assoc();
$colorStmt->close();

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
.sidebar a:hover { background: #5a1b88; color: #fff; }
.main { margin-left:240px; padding:28px; min-height:100vh; }
.product-img { width: 100%; height: 250px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; }
.label { font-weight: 600; color: #555; }

/* Enhanced Color Display */
.color-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 25px;
    border: 2px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}
.color-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}
.color-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15), inset 0 1px 2px rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}
.color-circle.white-border {
    border-color: #adb5bd;
}
.color-text {
    font-size: 15px;
    font-weight: 600;
    color: #495057;
    letter-spacing: 0.3px;
}
.info-row {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}
.info-row:last-child {
    border-bottom: none;
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">View Product</h3>
            <p class="text-muted mb-0">Detailed information about the product</p>
        </div>
        <div>
            <a href="edit-product.php?id=<?php echo $pid; ?>" class="btn btn-primary me-2">
                <i class="bi bi-pencil"></i> Edit Product
            </a>
            <a href="products.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Product Details -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h4 class="mb-0 text-primary fw-bold"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                </div>
                <div class="card-body pt-0">
                    
                    <div class="info-row">
                        <div class="row">
                            <div class="col-md-4 label">Product Code</div>
                            <div class="col-md-8">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['product_code']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="row">
                            <div class="col-md-4 label">Category</div>
                            <div class="col-md-8">
                                <?php 
                                $categories = [
                                    'used' => '🔄 Used',
                                    'bridalAttire' => '👰 Bridal Attire',
                                    'bridemaidAttire' => '💐 Bridesmaid Attire',
                                    'partyWear' => '🎉 Party Wear'
                                ];
                                echo isset($categories[$product['category']]) ? $categories[$product['category']] : ucfirst($product['category']); 
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Product Color -->
                    <?php if ($productColor): ?>
                    <div class="info-row">
                        <div class="row align-items-center">
                            <div class="col-md-4 label">Product Color</div>
                            <div class="col-md-8">
                                <div class="color-badge">
                                    <div class="color-circle <?php echo strtolower($productColor['color_name']) === 'white' ? 'white-border' : ''; ?>" 
                                         style="background-color: <?php echo htmlspecialchars($productColor['color_code']); ?>;">
                                    </div>
                                    <span class="color-text"><?php echo htmlspecialchars($productColor['color_name']); ?></span>
                                    <small class="text-muted"><?php echo htmlspecialchars($productColor['color_code']); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="info-row">
                        <div class="row">
                            <div class="col-md-4 label">Product Color</div>
                            <div class="col-md-8">
                                <span class="text-muted fst-italic">No color assigned</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="info-row">
                        <div class="row">
                            <div class="col-md-4 label">Description</div>
                            <div class="col-md-8 text-muted">
                                <?php echo nl2br(htmlspecialchars($product['product_desc'])); ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Fabric Details -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-layers text-primary"></i> Fabric Availability
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <?php if (!empty($fabrics)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
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
                                            <td>
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                <?php echo htmlspecialchars($f['fabric_type']); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info text-dark px-3 py-2">
                                                    <?php echo (int)$f['fabric_qty']; ?> units
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold text-primary">
                                                Rs. <?php echo number_format((float)$f['fabric_price'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-0">No specific fabric details available for this product.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Images -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-images text-primary"></i> Product Images
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Main Image -->
                    <div class="mb-3">
                        <img src="<?php echo $images[0]; ?>" alt="Main Image" class="img-fluid rounded shadow-sm" style="width: 100%; height: 300px; object-fit: cover;">
                        <p class="text-center text-muted small mt-2 mb-0">Main Image</p>
                    </div>

                    <!-- Additional Images -->
                    <?php if (count($images) > 1): ?>
                        <div class="row g-2">
                            <?php for ($i = 1; $i < count($images); $i++): ?>
                                <div class="col-4">
                                    <img src="<?php echo $images[$i]; ?>" class="img-fluid rounded" style="height: 80px; width: 100%; object-fit: cover; cursor: pointer;" alt="Image <?php echo $i+1; ?>" onclick="changeMainImage(this.src)">
                                </div>
                            <?php endfor; ?>
                        </div>
                        <p class="text-center text-muted small mt-2 mb-0">Click to view</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Function to change main image when clicking thumbnails
function changeMainImage(src) {
    const mainImg = document.querySelector('.card-body img[alt="Main Image"]');
    if (mainImg) {
        mainImg.src = src;
    }
}
</script>
</body>
</html>