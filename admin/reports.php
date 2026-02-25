<?php
// admin/download-all-reports.php
if (session_id() == '' || !isset($_SESSION)) { session_name('ADMIN_SESSION'); session_start(); }
include_once '../config/config.php';

$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// ---- Fetch Product History (With Filters) ----
$prodWhere = [];
$prodParams = [];
$prodTypes = "";

// Filters
$p_search = $_GET['p_search'] ?? '';
$p_cat = $_GET['p_cat'] ?? '';
$p_color = $_GET['p_color'] ?? '';
$p_stock = $_GET['p_stock'] ?? '';
$p_date_from = $_GET['p_date_from'] ?? '';
$p_date_to = $_GET['p_date_to'] ?? '';

if (!empty($p_search)) {
    $prodWhere[] = "(p.product_name LIKE ? OR p.product_code LIKE ?)";
    $prodParams[] = "%$p_search%";
    $prodParams[] = "%$p_search%";
    $prodTypes .= "ss";
}
if (!empty($p_cat)) {
    $prodWhere[] = "p.category = ?";
    $prodParams[] = $p_cat;
    $prodTypes .= "s";
}
if (!empty($p_color)) {
    // Join logic handles this, we filter by color_name
    $prodWhere[] = "pc.color_name LIKE ?";
    $prodParams[] = "%$p_color%";
    $prodTypes .= "s";
}
if (!empty($p_date_from)) {
    $prodWhere[] = "DATE(p.created_at) >= ?";
    $prodParams[] = $p_date_from;
    $prodTypes .= "s";
}
if (!empty($p_date_to)) {
    $prodWhere[] = "DATE(p.created_at) <= ?";
    $prodParams[] = $p_date_to;
    $prodTypes .= "s";
}

$whereSQL = "";
if (!empty($prodWhere)) {
    $whereSQL = " WHERE " . implode(" AND ", $prodWhere);
}

// Using HAVING for stock filter because it relies on aggregation
$havingSQL = "";
if (!empty($p_stock)) {
    if ($p_stock == 'in') {
        $havingSQL = " HAVING total_stock > 0";
    } elseif ($p_stock == 'out') {
        $havingSQL = " HAVING total_stock <= 0";
    }
}

// Fetch Categories and Colors for Dropdowns
$cats = $mysqli->query("SELECT DISTINCT category FROM products ORDER BY category");
$cols = $mysqli->query("SELECT DISTINCT color_name FROM product_colors ORDER BY color_name");

// Query
$prodHistorySQL = "SELECT 
    p.id, p.product_code, p.product_name, p.category, 
    p.created_at, p.updated_at, p.is_deleted,
    pc.color_name,
    GROUP_CONCAT(DISTINCT ps.size ORDER BY ps.id SEPARATOR ', ') as size_list,
    COALESCE(SUM(pf.fabric_qty), 0) as total_stock
FROM products p
LEFT JOIN product_colors pc ON p.id = pc.product_id
LEFT JOIN product_sizes ps ON p.id = ps.product_id
LEFT JOIN product_fabrics pf ON p.id = pf.product_id
$whereSQL
GROUP BY p.id
$havingSQL
ORDER BY p.created_at DESC";

$prodStmt = $mysqli->prepare($prodHistorySQL);
if (!empty($prodParams)) {
    $prodStmt->bind_param($prodTypes, ...$prodParams);
}
$prodStmt->execute();
$prodResult = $prodStmt->get_result();

// ---- Fetch Order History ----
$orderHistorySQL = "SELECT 
    o.id, o.order_id, o.username, o.product_name, o.product_code, o.fabric_type, o.size, o.quantity,
    o.unit_price, o.total_amount, o.payment_status, o.delivery_status, o.is_deleted,
    o.customer_name, o.customer_email, o.customer_phone, o.city, o.created_at as purchase_date
FROM orders o ORDER BY o.created_at DESC";
$orderHistoryResult = $mysqli->query($orderHistorySQL);

$totalRevenue = 0;
$deliveredCount = 0;
$pendingCount = 0;
$totalOrders = 0;

if ($orderHistoryResult && $orderHistoryResult->num_rows > 0) {
    $totalOrders = $orderHistoryResult->num_rows;
    $orderHistoryResult->data_seek(0);
    while($row = $orderHistoryResult->fetch_assoc()) {
        $totalRevenue += $row['total_amount'];
        if ($row['delivery_status'] === 'delivered') $deliveredCount++;
        if ($row['delivery_status'] === 'pending') $pendingCount++;
    }
    $orderHistoryResult->data_seek(0);
}

// ---- Fetch Resale History ----
$resaleHistorySQL = "SELECT 
    r.id, r.product_id, r.product_code, r.user_id, r.first_name, r.last_name, r.contact_number,
    r.email, r.address, r.notes, r.fabric_type, r.resale_price, r.original_price,
    r.status, r.created_at
FROM reseller_products r
ORDER BY r.created_at DESC";
$resaleHistoryResult = $mysqli->query($resaleHistorySQL);

$totalResaleItems = 0;
$soldCount = 0;
$totalResaleRevenue = 0;

if ($resaleHistoryResult && $resaleHistoryResult->num_rows > 0) {
    $totalResaleItems = $resaleHistoryResult->num_rows;
    $resaleHistoryResult->data_seek(0);
    while($row = $resaleHistoryResult->fetch_assoc()) {
        if ($row['status'] === 'sold') {
            $soldCount++;
            $totalResaleRevenue += $row['resale_price'];
        }
    }
    $resaleHistoryResult->data_seek(0);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ceylon Fashion - Complete Reports</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { 
    background:#f8f9fa; 
    font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; 
}

/* Sidebar Styles */
.sidebar { 
    width: 240px; 
    position: fixed; 
    left:0; 
    top:0; 
    bottom:0; 
    background:#430160ff; 
    color:#fff; 
    padding-top:20px; 
    z-index: 1000;
}
.sidebar a { 
    display:block; 
    padding:12px 18px; 
    color:#cfd8dc; 
    text-decoration:none; 
}
.sidebar a.active { 
    background:#007bff; 
    color:#fff; 
}
.sidebar a:hover {
    background: rgba(255,255,255,0.1);
}

/* Main Content */
.main { 
    margin-left:240px; 
    padding:28px; 
    min-height:100vh; 
}

/* Report Styles */
.report-container {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header { 
    text-align: center; 
    margin-bottom: 25px; 
    border-bottom: 3px solid #430160ff; 
    padding-bottom: 15px; 
}
.header h1 { 
    color: #430160ff; 
    margin: 0 0 5px 0; 
    font-size: 28px; 
}
.header .subtitle { 
    color: #666; 
    font-size: 13px; 
}

.stats { 
    display: flex; 
    justify-content: space-around; 
    margin: 20px 0; 
    flex-wrap: wrap; 
}
.stat-box { 
    background: #f8f9fa; 
    border-left: 4px solid #430160ff; 
    padding: 12px 18px; 
    margin: 8px; 
    min-width: 160px; 
    border-radius: 4px;
}
.stat-box h3 { 
    margin: 0; 
    font-size: 22px; 
    color: #430160ff; 
}
.stat-box p { 
    margin: 3px 0 0 0; 
    color: #666; 
    font-size: 13px; 
}

.section-title { 
    background: #430160ff; 
    color: white; 
    padding: 8px 12px; 
    font-size: 16px; 
    font-weight: bold; 
    margin: 30px 0 12px 0; 
    border-radius: 4px;
}

table { 
    width: 100%; 
    border-collapse: collapse; 
    font-size: 11px; 
    margin-bottom: 20px; 
}
th, td { 
    border: 1px solid #ddd; 
    padding: 8px; 
    text-align: left; 
}
th { 
    background-color: #f0f0f0; 
    font-weight: bold; 
}
tr:nth-child(even) { 
    background-color: #f9f9f9; 
}

.status { 
    padding: 3px 8px; 
    border-radius: 3px; 
    font-size: 10px; 
    font-weight: bold; 
    display: inline-block; 
}
.status-paid, .status-delivered, .status-sold { 
    background: #d4edda; 
    color: #155724; 
}
.status-pending { 
    background: #fff3cd; 
    color: #856404; 
}
.status-failed, .status-rejected { 
    background: #f8d7da; 
    color: #721c24; 
}
.status-shipped, .status-approved { 
    background: #d1ecf1; 
    color: #0c5460; 
}
.status-processing { 
    background: #cfe2ff; 
    color: #084298; 
}

.footer { 
    text-align: center; 
    color: #999; 
    font-size: 11px; 
    margin-top: 30px; 
    padding-top: 15px; 
    border-top: 1px solid #ddd; 
}

/* Print Styles */
@media print { 
    body { 
        margin: 0; 
        padding: 15px; 
        background: white;
    }
    .sidebar { 
        display: none !important; 
    }
    .main {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .no-print { 
        display: none !important; 
    }
    .report-container {
        box-shadow: none;
        padding: 0;
    }
    .section-title { 
        page-break-before: always; 
    }
    .section-title:first-of-type { 
        page-break-before: auto; 
    }
    table {
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar no-print">
  <h4 class="text-center mb-3">Ceylon Fashion</h4>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="products.php">📦 Products</a>
  <a href="orders.php">🧾 Orders</a>
  <a href="users.php">👥 Users</a>
  <a href="reports.php" class="active">📊 Reports</a>
  <hr style="border-color: rgba(255,255,255,.06)">
  <a href="logout.php" class="text-danger" onclick="return confirm('Are you sure you want to logout?');">🚪 Logout</a>
</div>

<!-- Main Content -->
<main class="main">
    <div class="no-print mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Complete Business Report</h3>
            <a href="download-all-reports-pdf.php" class="btn btn-primary">
    <i class="bi bi-download"></i> Download PDF
</a>
        </div>
        <p class="text-muted mt-2">
            <i class="bi bi-info-circle"></i> This will generate a complete report with both orders and resale data
        </p>
    </div>

    <div class="report-container">
        <div class="header">
            <h1>Ceylon Fashion - Complete Business Report</h1>
            <div class="subtitle">Orders & Resale History</div>

        </div>

        <!-- Overall Statistics -->
        <div class="stats">
            <div class="stat-box">
                <h3><?php echo $totalOrders; ?></h3>
                <p>Total Orders</p>
            </div>
            <div class="stat-box">
                <h3>Rs. <?php echo number_format($totalRevenue, 2); ?></h3>
                <p>Order Revenue</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $deliveredCount; ?> / <?php echo $pendingCount; ?></h3>
                <p>Delivered / Pending</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $totalResaleItems; ?></h3>
                <p>Resale Items</p>
            </div>
            <div class="stat-box">
                <h3>Rs. <?php echo number_format($totalResaleRevenue, 2); ?></h3>
                <p>Resale Revenue</p>
            </div>
            <div class="stat-box">
                <h3>Rs. <?php echo number_format($totalRevenue + $totalResaleRevenue, 2); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>

        <!-- Product History Section -->
        <div class="section-title">📂 Product History</div>
        
        <!-- Filters Form -->
        <div class="mb-4 p-3 bg-light rounded border">
            <form method="GET" action="reports.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Search</label>
                    <input type="text" name="p_search" class="form-control form-control-sm" placeholder="Name or Code" value="<?php echo htmlspecialchars($p_search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Category</label>
                    <select name="p_cat" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php 
                        if ($cats) {
                            $cats->data_seek(0);
                            while($c = $cats->fetch_assoc()) {
                                $sel = $p_cat == $c['category'] ? 'selected' : '';
                                echo "<option value='".htmlentities($c['category'])."' $sel>".htmlentities($c['category'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Color</label>
                    <select name="p_color" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php 
                        if ($cols) {
                            $cols->data_seek(0);
                            while($cl = $cols->fetch_assoc()) {
                                $sel = $p_color == $cl['color_name'] ? 'selected' : '';
                                echo "<option value='".htmlentities($cl['color_name'])."' $sel>".htmlentities($cl['color_name'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Stock</label>
                    <select name="p_stock" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="in" <?php echo $p_stock=='in'?'selected':''; ?>>In Stock</option>
                        <option value="out" <?php echo $p_stock=='out'?'selected':''; ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Created Date</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="p_date_from" class="form-control" value="<?php echo $p_date_from; ?>">
                        <span class="input-group-text">-</span>
                        <input type="date" name="p_date_to" class="form-control" value="<?php echo $p_date_to; ?>">
                    </div>
                </div>
                <div class="col-12 text-end">
                    <a href="reports.php" class="btn btn-secondary btn-sm">Reset</a>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Filter</button>
                </div>
            </form>
        </div>

        <?php if ($prodResult && $prodResult->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Color</th>
                            <th>Sizes</th>
                            <th>Stock Status</th>
                            <th>Admin Action</th>
                            <th>Created</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($prod = $prodResult->fetch_assoc()): 
                            $inStock = $prod['total_stock'] > 0;
                            // Stock Status logic (reverted to original)
                            $stockClass = $inStock ? 'status-paid' : 'status-failed'; 
                            $stockText = $inStock ? 'Available' : 'Not Available';
                            
                            // Admin Action logic
                            $adminActionRaw = $prod['is_deleted'] == 1 ? '<span class="status status-failed">Admin Deleted</span>' : '-';
                        ?>
                            <tr>
                                <td><strong><?php echo htmlentities($prod['product_code']); ?></strong></td>
                                <td><?php echo htmlentities($prod['product_name']); ?></td>
                                <td><?php echo htmlentities($prod['category']); ?></td>
                                <td><?php echo htmlentities($prod['color_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlentities($prod['size_list'] ?: 'N/A'); ?></td>
                                <td><span class="status <?php echo $stockClass; ?>"><?php echo $stockText; ?></span></td>
                                <td><?php echo $adminActionRaw; ?></td>
                                <td><?php echo $prod['created_at'] ? date('Y-m-d', strtotime($prod['created_at'])) : '-'; ?></td>
                                <td><?php echo $prod['updated_at'] ? date('Y-m-d', strtotime($prod['updated_at'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">No products found matching filters.</div>
        <?php endif; ?>

        <div class="section-title">📦 Order History</div>
        <?php if ($orderHistoryResult && $orderHistoryResult->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product Code</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Fabric</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Delivery</th>
                            <th>Admin Action</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $orderHistoryResult->fetch_assoc()): 
                            $paymentClass = $order['payment_status'] === 'paid' ? 'status-paid' : 
                                           ($order['payment_status'] === 'failed' ? 'status-failed' : 'status-pending');
                            $deliveryClass = $order['delivery_status'] === 'delivered' ? 'status-delivered' : 
                                            ($order['delivery_status'] === 'shipped' ? 'status-shipped' : 
                                            ($order['delivery_status'] === 'processing' ? 'status-processing' : 'status-pending'));
                                            
                            $adminActionRaw = $order['is_deleted'] == 1 ? '<span class="status status-failed">Deleted</span>' : '-';
                        ?>
                            <tr>
                                <td><strong><?php echo htmlentities($order['order_id']); ?></strong></td>
                                <td><span style="font-family:monospace;"><?php echo htmlentities($order['product_code'] ?? '-'); ?></span></td>
                                <td>
                                    <?php echo htmlentities($order['customer_name']); ?><br>
                                    <small style="font-size:9px;color:#666;"><?php echo htmlentities($order['customer_email']); ?></small><br>
                                    <small style="font-size:9px;color:#666;"><?php echo htmlentities($order['customer_phone']); ?></small>
                                </td>
                                <td><?php echo htmlentities($order['product_name']); ?></td>
                                <td><?php echo htmlentities($order['fabric_type'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlentities($order['size'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlentities($order['quantity']); ?></td>
                                <td>Rs. <?php echo number_format($order['unit_price'], 2); ?></td>
                                <td><strong>Rs. <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td><span class="status <?php echo $paymentClass; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                                <td><span class="status <?php echo $deliveryClass; ?>"><?php echo ucfirst($order['delivery_status']); ?></span></td>
                                <td><?php echo $adminActionRaw; ?></td>
                                <td><?php echo date('Y.m.d', strtotime($order['purchase_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">No orders found.</div>
        <?php endif; ?>

        <!-- Resale History Section -->
        <div class="section-title">🔄 Resale History</div>
        <?php if ($resaleHistoryResult && $resaleHistoryResult->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Code</th>
                            <th>Seller</th>
                            <th>Contact</th>
                            <th>Product</th>
                            <th>Fabric</th>
                            <th>Original Price</th>
                            <th>Resale Price</th>
                            <th>Profit</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($resale = $resaleHistoryResult->fetch_assoc()): 
                            $profit = $resale['original_price'] ? ($resale['resale_price'] - $resale['original_price']) : 0;
                            $statusClass = $resale['status'] === 'sold' ? 'status-sold' : 
                                          ($resale['status'] === 'approved' ? 'status-approved' : 
                                          ($resale['status'] === 'rejected' ? 'status-rejected' : 'status-pending'));
                        ?>
                            <tr>
                                <td><strong>#<?php echo htmlentities($resale['id']); ?></strong></td>
                                <td><span style="font-family:monospace;"><?php echo htmlentities($resale['product_code'] ?? '-'); ?></span></td>
                                <td><?php echo htmlentities($resale['first_name'] . ' ' . $resale['last_name']); ?></td>
                                <td style="font-size:9px;">
                                    <?php echo htmlentities($resale['contact_number']); ?><br>
                                    <?php echo htmlentities($resale['email']); ?>
                                </td>
                                <td><?php echo htmlentities('Product #' . $resale['product_id']); ?></td>
                                <td><?php echo htmlentities($resale['fabric_type'] ?: 'N/A'); ?></td>
                                <td>Rs. <?php echo number_format($resale['original_price'] ?: 0, 2); ?></td>
                                <td><strong>Rs. <?php echo number_format($resale['resale_price'], 2); ?></strong></td>
                                <td style="color: <?php echo $profit >= 0 ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                    Rs. <?php echo number_format($profit, 2); ?>
                                </td>
                                <td><span class="status <?php echo $statusClass; ?>"><?php echo ucfirst($resale['status']); ?></span></td>
                                <td><?php echo date('Y.m.d', strtotime($resale['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">No resale items found.</div>
        <?php endif; ?>

        <div class="footer">
            <p><strong>Ceylon Fashion Admin Panel</strong> | Complete Business Report</p>
            <p>© <?php echo date('Y'); ?> Ceylon Fashion. All rights reserved.</p>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>