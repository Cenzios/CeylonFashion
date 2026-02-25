<?php
// admin/view-product.php
session_name('ADMIN_SESSION');
session_start();
include_once '../config/config.php';

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

// ---- Fetch available sizes ----
$sizeStmt = $mysqli->prepare("SELECT size FROM product_sizes WHERE product_id = ?");
$sizeStmt->bind_param("i", $pid);
$sizeStmt->execute();
$sizeResult = $sizeStmt->get_result();
$sizes = [];
while ($row = $sizeResult->fetch_assoc()) {
    $sizes[] = $row['size'];
}
$sizeStmt->close();
// Sort sizes logic
$sizeOrder = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
usort($sizes, function($a, $b) use ($sizeOrder) {
    return ($sizeOrder[$a] ?? 99) <=> ($sizeOrder[$b] ?? 99);
});

// Prepare images
$fallback = '../assets/no-image.png';
$images = [];
for ($i = 1; $i <= 4; $i++) {
    $imgField = 'product_img' . $i;
    $imgName = $product[$imgField];
    $imgPath = '../assets/images/products/' . $imgName;
    if (!empty($imgName) && file_exists($imgPath)) {
        $images[] = $imgPath;
    }
}
if (empty($images)) {
    $images[] = $fallback;
}

// Category Label Map
$categoryMap = [
    'used' => 'Previously Owned',
    'bridalAttire' => 'Bridal Attire',
    'bridemaidAttire' => 'Bridesmaid Attire',
    'partyWear' => 'Party Wear'
];
$displayCategory = $categoryMap[$product['category']] ?? ucfirst($product['category']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>View Product | Admin Panel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary-color: #430160;
    --primary-light: #5a1b88;
    --bg-light: #f8f9fa;
    --text-dark: #1e293b;
    --text-muted: #64748b;
}
body { 
    background: var(--bg-light); 
    font-family: "Plus Jakarta Sans", system-ui, -apple-system, sans-serif; 
    color: var(--text-dark);
}

/* Sidebar */
.sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#430160ff; color:#fff; padding-top:20px; z-index: 1000; }
.sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
.sidebar a.active { background:#007bff; color:#fff; }
.sidebar a:hover { background: rgba(255,255,255,0.1); }
.main { margin-left:240px; padding:28px; min-height:100vh; }

/* Product Page Styles */
.page-header { margin-bottom: 32px; }
.page-title { font-size: 24px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px; }
.page-subtitle { color: var(--text-muted); font-size: 14px; }

/* Visual Column */
.product-gallery { position: sticky; top: 40px; }
.main-image-container {
    background: #fff;
    padding: 12px;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
    margin-bottom: 16px;
}
.main-image {
    width: 100%;
    aspect-ratio: 3/4;
    object-fit: cover;
    border-radius: 12px;
}
.thumbs-row {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 4px;
}
.thumb-btn {
    border: 2px solid transparent;
    border-radius: 10px;
    padding: 2px;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}
.thumb-btn.active { border-color: var(--primary-color); }
.thumb-img {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 8px;
}

/* Color Card */
.color-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    margin-top: 16px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    gap: 16px;
}
.color-swatch-lg {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 1px #e2e8f0;
}
.color-swatch-lg.white-border { border-color: #e2e8f0; }

/* Details Column */
.details-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    padding: 32px;
    margin-bottom: 24px;
}
.product-name { font-size: 32px; font-weight: 800; line-height: 1.2; margin-bottom: 16px; color: var(--text-dark); }

.badge-soft {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.3px;
}
.badge-purple { background: #f3e8ff; color: #6b21a8; }
.badge-gray { background: #f1f5f9; color: #475569; }

.section-label {
    text-transform: uppercase;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 0.8px;
    margin-bottom: 12px;
    display: block;
}

.description-text {
    font-size: 15px;
    line-height: 1.7;
    color: #475569;
}

/* Size Grid */
.size-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.size-badge {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
}
.size-badge.available {
    background: var(--bg-light);
    border-color: #cbd5e1;
    color: var(--text-dark);
}

/* Fabric Table */
.fabric-table-wrapper {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}
.table-custom th {
    background: #f8fafc;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    color: var(--text-muted);
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
}
.table-custom td {
    padding: 12px 16px;
    vertical-align: middle;
    color: var(--text-dark);
    font-size: 14px;
    border-bottom: 1px solid #e2e8f0;
}
.table-custom tr:last-child td { border-bottom: none; }

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
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-end page-header">
        <div>
            <h1 class="page-title">Product Details</h1>
            <p class="page-subtitle">View and manage sizes, stock, and variations.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="products.php" class="btn btn-light border bg-white text-muted shadow-sm">
                 Close
            </a>
            <a href="edit-product.php?id=<?php echo $pid; ?>" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" style="background: var(--primary-color); border:none;">
                <i class="bi bi-pencil-square"></i> Edit Product
            </a>
        </div>
    </div>

    <div class="row g-4">
        
        <!-- Left Column: Visuals -->
        <div class="col-lg-4">
            <div class="product-gallery">
                <div class="main-image-container">
                    <img src="<?php echo $images[0]; ?>" alt="Product Content" class="main-image" id="mainImage">
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="thumbs-row">
                    <?php foreach ($images as $idx => $img): ?>
                    <div class="thumb-btn <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo $img; ?>', this)">
                        <img src="<?php echo $img; ?>" class="thumb-img" alt="Thumb">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Color Card -->
                <?php if ($productColor): ?>
                <div class="color-card">
                    <div class="color-swatch-lg <?php echo strtolower($productColor['color_name'])==='white'?'white-border':''; ?>" 
                         style="background-color: <?php echo htmlspecialchars($productColor['color_code']); ?>"></div>
                    <div>
                        <div class="section-label mb-1" style="margin-bottom:0px;">Color Variation</div>
                        <div class="fw-bold" style="font-size:16px;"><?php echo htmlspecialchars($productColor['color_name']); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($productColor['color_code']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Details -->
        <div class="col-lg-8">
            <div class="details-card">
                
                <!-- Identification -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge badge-purple"><?php echo htmlspecialchars($displayCategory); ?></span>
                    <span class="badge badge-gray">#<?php echo htmlspecialchars($product['product_code']); ?></span>
                </div>

                <h2 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h2>

                <!-- Description -->
                <div class="mb-5">
                    <span class="section-label">About Item</span>
                    <p class="description-text mb-0"><?php echo nl2br(htmlspecialchars($product['product_desc'])); ?></p>
                </div>

                <div class="row g-5">
                    <!-- Sizes -->
                    <div class="col-md-5">
                        <span class="section-label">Available Sizes</span>
                        <?php if (!empty($sizes)): ?>
                            <div class="size-grid">
                                <?php foreach ($sizes as $s): ?>
                                    <div class="size-badge available" title="Available"><?php echo htmlspecialchars($s); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small fst-italic">All standard sizes available (Legacy)</p>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-5" style="border-color:#e2e8f0;">

                <!-- Fabrics -->
                <div class="mb-0">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="section-label mb-0">Fabric Inventory</span>
                        <span class="badge bg-light text-dark border"><?php echo count($fabrics); ?> Variations</span>
                    </div>

                    <?php if (!empty($fabrics)): ?>
                        <div class="fabric-table-wrapper">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Fabric Type</th>
                                        <th class="text-center">Stock Level</th>
                                        <th class="text-end">Base Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fabrics as $f): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-circle-fill" style="font-size:6px; color:var(--primary-color);"></i>
                                                <?php echo htmlspecialchars($f['fabric_type']); ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($f['fabric_qty'] > 10): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">
                                                    <?php echo (int)$f['fabric_qty']; ?> In Stock
                                                </span>
                                            <?php elseif ($f['fabric_qty'] > 0): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">
                                                    <?php echo (int)$f['fabric_qty']; ?> Low Stock
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Out of Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold font-monospace">Rs. <?php echo number_format((float)$f['fabric_price'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center border rounded-3 bg-light">
                            <p class="text-muted mb-0">No fabric data recorded.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function changeImage(src, btn) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumb-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>

</body>
</html>