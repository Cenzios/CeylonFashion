<?php
// admin/orders.php
if (session_id() == '' || !isset($_SESSION)) { session_name('ADMIN_SESSION'); session_start(); }
include_once '../config.php';

// ---- Admin check ----
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// Handle status update via POST (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    // Validate status
    $validStatuses = ['pending', 'paid', 'failed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        echo "invalid_status";
        exit;
    }
    
    $stmt = $mysqli->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
    exit;
}

// Handle delivery status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['delivery_status'])) {
    $orderId = (int)$_POST['order_id'];
    $deliveryStatus = $_POST['delivery_status'];
    
    // Validate delivery status
    $validDeliveryStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($deliveryStatus, $validDeliveryStatuses)) {
        echo "invalid_status";
        exit;
    }
    
    $stmt = $mysqli->prepare("UPDATE orders SET delivery_status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $deliveryStatus, $orderId);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
    exit;
}

// ---- Fetch orders with pagination ----
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$countResult = $mysqli->query("SELECT COUNT(*) as total FROM orders WHERE payment_status = 'paid' AND is_deleted = 0");
$totalOrders = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalOrders / $perPage);

// Fetch orders
$sql = "SELECT 
    id, order_id, username, product_name, product_code, fabric_type, size, quantity,
    unit_price, total_amount, payment_status, payment_id, delivery_status,
    customer_name, customer_email, customer_phone, delivery_address, city,
    created_at, updated_at
    FROM orders 
    WHERE payment_status = 'paid' AND is_deleted = 0
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("DB error: " . $mysqli->error);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin | Orders</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { 
    background:#f8f9fa; 
    font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; 
}
.sidebar { 
    width: 240px; 
    position: fixed; 
    left:0; 
    top:0; 
    bottom:0; 
    background:#430160ff; 
    color:#fff; 
    padding-top:20px; 
    overflow-y: auto;
}
.sidebar a { 
    display:block; 
    padding:12px 18px; 
    color:#cfd8dc; 
    text-decoration:none; 
    transition: all 0.2s;
}
.sidebar a:hover {
    background: rgba(255,255,255,0.1);
}
.sidebar a.active { 
    background:#007bff; 
    color:#fff; 
    display:block;
}
.main { 
    margin-left:240px; 
    padding:28px; 
    min-height:100vh; 
}
.table th, .table td { 
    vertical-align: middle; 
    font-size: 14px;
}
.status-select { 
    width: 130px; 
    font-size: 13px;
    padding: 4px 8px;
}
.badge {
    font-size: 11px;
    padding: 4px 8px;
}
.order-detail-btn {
    font-size: 12px;
    padding: 4px 10px;
}
.filter-section {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.stats-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}
.order-id-col {
    font-family: monospace;
    font-size: 12px;
    color: #666;
}
</style>
</head>
<body>

<div class="sidebar">
  <h4 class="text-center mb-3">Ceylon Fashion</h4>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="products.php">📦 Products</a>
  <a href="orders.php" class="active">🧾 Orders</a>
  <a href="users.php">👥 Users</a>
  <a href="reports.php">📊 Reports</a>
  <hr style="border-color: rgba(255,255,255,.06)">
  <a href="logout.php" class="text-danger" onclick="return confirm('Are you sure you want to logout?');">🚪 Logout</a>
</div>

<main class="main">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Manage Orders</h3>
    <span class="badge bg-primary fs-6"><?php echo $totalOrders; ?> Total Orders</span>
  </div>

  <!-- Stats Cards -->
  <div class="row mb-4">
    <?php
    // Get statistics
    $statsQuery = "SELECT 
        COUNT(*) as total,
        COUNT(*) as paid,
        0 as pending,
        SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(total_amount) as total_revenue
        FROM orders WHERE payment_status = 'paid' AND is_deleted = 0";
    $statsResult = $mysqli->query($statsQuery);
    $stats = $statsResult->fetch_assoc();
    ?>
    <div class="col-md-3">
      <div class="stats-card">
        <h6 class="text-muted mb-2">Total Orders</h6>
        <h3 class="mb-0"><?php echo (int)$stats['total']; ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stats-card">
        <h6 class="text-muted mb-2">Paid Orders</h6>
        <h3 class="mb-0 text-success"><?php echo (int)$stats['paid']; ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stats-card">
        <h6 class="text-muted mb-2">Pending Orders</h6>
        <h3 class="mb-0 text-warning"><?php echo (int)$stats['pending']; ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stats-card">
        <h6 class="text-muted mb-2">Total Revenue</h6>
        <h3 class="mb-0 text-primary">Rs. <?php echo number_format((float)$stats['total_revenue'], 2); ?></h3>
      </div>
    </div>
  </div>

  <?php if ($result->num_rows === 0): ?>
      <div class="alert alert-warning">No orders found.</div>
  <?php else: ?>
    <div class="table-responsive bg-white rounded shadow-sm">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
          <tr>
            <th>Order ID</th>
            <th>Product Code</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Details</th>
            <th>Amount</th>
            <th>Payment</th>
            <th>Delivery</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          // Group orders logic
          $groupedAdminOrders = [];
          while($row = $result->fetch_assoc()) {
              $oid = $row['order_id'];
              if (!isset($groupedAdminOrders[$oid])) {
                  $groupedAdminOrders[$oid] = [
                      'meta' => $row,
                      'items' => []
                  ];
              }
              $groupedAdminOrders[$oid]['items'][] = $row;
          }
          
          if (empty($groupedAdminOrders)): 
          ?>
            <tr><td colspan="10" class="text-center text-muted">No orders found.</td></tr>
          <?php else: ?>
            <?php foreach($groupedAdminOrders as $oid => $orderData):
                $meta = $orderData['meta'];
                $items = $orderData['items'];
                
                // Recalculate total for display
                $totalAmount = 0;
                foreach($items as $i) $totalAmount += $i['total_amount'];
            ?>
            <tr id="orderRow<?php echo (int)$meta['id']; ?>">
              <td class="order-id-col">
                <strong><?php echo htmlspecialchars($meta['order_id']); ?></strong>
                <?php if ($meta['payment_id']): ?>
                <br><small class="text-muted">Pay: <?php echo htmlspecialchars($meta['payment_id']); ?></small>
                <?php endif; ?>
              </td>
              <td>
                <?php foreach($items as $item): ?>
                    <span class="badge bg-light text-dark border mb-1 d-block"><?php echo htmlspecialchars($item['product_code'] ?? 'N/A'); ?></span>
                <?php endforeach; ?>
              </td>
              <td>
                <strong><?php echo htmlspecialchars($meta['customer_name']); ?></strong>
                <br><small class="text-muted"><?php echo htmlspecialchars($meta['username']); ?></small>
                <br><small class="text-muted"><?php echo htmlspecialchars($meta['customer_phone']); ?></small>
              </td>
              <td>
                <?php foreach($items as $item): ?>
                    <div class="mb-1"><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></div>
                <?php endforeach; ?>
              </td>
              <td>
                <?php foreach($items as $item): ?>
                <div class="mb-2 border-bottom pb-1">
                  <small>
                    <strong>Size:</strong> <?php echo htmlspecialchars($item['size']); ?> | 
                    <strong>Qty:</strong> <?php echo (int)$item['quantity']; ?>
                  </small>
                </div>
                <?php endforeach; ?>
              </td>
              <td>
                <strong>Rs. <?php echo number_format($totalAmount, 2); ?></strong>
              </td>
              <td>
                  <?php
                  $paymentStatuses = ['pending' => 'warning', 'paid' => 'success', 'failed' => 'danger', 'cancelled' => 'secondary'];
                  $s = $meta['payment_status'];
                  $color = isset($paymentStatuses[$s]) ? $paymentStatuses[$s] : 'secondary';
                  ?>
                  <span class="badge bg-<?php echo $color; ?> fs-6">
                      <?php echo ucfirst($s); ?>
                  </span>
              </td>
              <td>
                <select class="form-select form-select-sm status-select" 
                        onchange="updateDeliveryStatus(<?php echo (int)$meta['id']; ?>, this)">
                  <?php
                  $deliveryStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                  foreach($deliveryStatuses as $s) {
                      $selected = ($meta['delivery_status'] === $s) ? 'selected' : '';
                      echo "<option value='$s' $selected>" . ucfirst($s) . "</option>";
                  }
                  ?>
                </select>
              </td>
              <td>
                <small><?php echo date('Y-m-d', strtotime($meta['created_at'])); ?></small>
                <br><small class="text-muted"><?php echo date('H:i', strtotime($meta['created_at'])); ?></small>
              </td>
              <td>
                <button class="btn btn-sm btn-info order-detail-btn" 
                        onclick="showOrderDetails('<?php echo htmlspecialchars($meta['order_id']); ?>')" 
                        title="View Details">
                  <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-danger" 
                        onclick="showDeleteModal(<?php echo (int)$meta['id']; ?>)" 
                        title="Delete">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
          <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
        </li>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <?php if ($i == $page): ?>
            <li class="page-item active"><span class="page-link"><?php echo $i; ?></span></li>
          <?php else: ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
          <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <li class="page-item">
          <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
    <?php endif; ?>
  <?php endif; ?>
</main>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="orderDetailsLabel">
          <i class="bi bi-receipt"></i> Order Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="orderDetailsContent">
        <div class="text-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <!-- Buttons removed as per request -->
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this order? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- All JavaScript in ONE script tag -->
<script>
// Global variables
let currentOrderId = null;
let deleteOrderId = null;

// Show order details in modal
function showOrderDetails(orderIdStr) {
    console.log('Opening order details for Order ID:', orderIdStr);
    currentOrderId = orderIdStr;
    
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const content = document.getElementById('orderDetailsContent');
    
    // Show loading
    content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // Fetch order details via order_id (string)
    fetch('get-order-details.php?order_id=' + encodeURIComponent(orderIdStr))
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                const meta = data.order; // Main order details
                const items = data.items; // Array of items
                
                // Construct items HTML
                let itemsHtml = '';
                items.forEach(item => {
                    itemsHtml += `
                        <tr class="align-middle">
                            <td>
                                <strong>${item.product_name}</strong>
                                <br><small class="text-muted">${item.product_code || 'N/A'}</small>
                            </td>
                            <td>
                                <small>
                                    Fabric: ${item.fabric_type}<br>
                                    Size: ${item.size}
                                </small>
                            </td>
                            <td class="text-center">${item.quantity}</td>
                            <td class="text-end">Rs. ${parseFloat(item.total_amount).toFixed(2)}</td>
                        </tr>
                    `;
                });

                // Calculate total from items sum if needed, or use meta totals?
                // Meta total might be just the first row's total if we didn't aggregate in backend.
                // Best to aggregate in backend. Assuming backend returns total of order in meta, OR we sum it.
                // Let's sum it here for safety.
                let grandTotal = items.reduce((sum, i) => sum + parseFloat(i.total_amount), 0);
                
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-info-circle"></i> Order Information
                            </h6>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="40%">Order ID:</th>
                                    <td><strong>${meta.order_id}</strong></td>
                                </tr>
                                <tr>
                                    <th>Payment ID:</th>
                                    <td>${meta.payment_id || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <th>Username:</th>
                                    <td>${meta.username}</td>
                                </tr>
                                <tr>
                                    <th>Date:</th>
                                    <td>${meta.created_at}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-person"></i> Customer Information
                            </h6>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="40%">Name:</th>
                                    <td>${meta.customer_name}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>${meta.customer_email}</td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>${meta.customer_phone}</td>
                                </tr>
                                <tr>
                                    <th>City:</th>
                                    <td>${meta.city}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-box-seam"></i> Purchased Items
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Spec</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itemsHtml}
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="3" class="text-end">Total Amount:</th>
                                            <th class="text-end text-primary fw-bold">Rs. ${grandTotal.toFixed(2)}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-geo-alt"></i> Delivery Details
                            </h6>
                             <div class="d-flex justify-content-between align-items-start border rounded p-3 bg-light">
                                <div>
                                    <strong>Address:</strong><br>
                                    ${meta.delivery_address}<br>
                                    ${meta.city}
                                    ${meta.postal_code ? '<br>Postal Code: ' + meta.postal_code : ''}
                                </div>
                                <div class="text-end">
                                     <div class="mb-2">
                                        Payment:
                                        <span class="badge bg-${meta.payment_status === 'paid' ? 'success' : 'warning'}">
                                            ${meta.payment_status.toUpperCase()}
                                        </span>
                                     </div>
                                     <div>
                                        Delivery:
                                        <span class="badge bg-info">
                                            ${meta.delivery_status.toUpperCase()}
                                        </span>
                                     </div>
                                </div>
                             </div>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Failed to load order details</div>';
            }
        })
        .catch(err => {
            console.error('Error loading order details:', err);
            content.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Error loading order details</div>';
        });
}


</script>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this order? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Delete modal
function showDeleteModal(orderId) {
    deleteOrderId = orderId;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Confirm delete
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if(deleteOrderId) {
        window.location.href = 'delete-order.php?id=' + deleteOrderId;
    }
});



// Update delivery status via AJAX
function updateDeliveryStatus(orderId, selectElement) {
    const status = selectElement.value;
    const originalValue = selectElement.getAttribute('data-original') || selectElement.value;
    
    fetch('orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order_id=' + orderId + '&delivery_status=' + status
    })
    .then(resp => resp.text())
    .then(data => {
        if(data === 'success') {
            selectElement.setAttribute('data-original', status);
            // Show success feedback
            const row = document.getElementById('orderRow' + orderId);
            if(row) {
                row.style.backgroundColor = '#d4edda';
                setTimeout(() => { row.style.backgroundColor = ''; }, 1000);
            }
        } else {
            alert('Failed to update delivery status');
            selectElement.value = originalValue;
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Error: ' + err);
        selectElement.value = originalValue;
    });
}

// Show order details in modal
function showOrderDetails(orderId) {
   window.currentOrderID = orderId;
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const content = document.getElementById('orderDetailsContent');
    
    // Show loading
    content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // Fetch order details
    fetch('get-order-details.php?id=' + orderId)
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Order Information</h6>
                            <table class="table table-sm">
                                <tr><th>Order ID:</th><td>${order.order_id}</td></tr>
                                <tr><th>Payment ID:</th><td>${order.payment_id || 'N/A'}</td></tr>
                                <tr><th>Username:</th><td>${order.username}</td></tr>
                                <tr><th>Date:</th><td>${order.created_at}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Customer Information</h6>
                            <table class="table table-sm">
                                <tr><th>Name:</th><td>${order.customer_name}</td></tr>
                                <tr><th>Email:</th><td>${order.customer_email}</td></tr>
                                <tr><th>Phone:</th><td>${order.customer_phone}</td></tr>
                                <tr><th>City:</th><td>${order.city}</td></tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Product Details</h6>
                            <table class="table table-sm">
                                <tr><th>Product:</th><td>${order.product_name}</td></tr>
                                <tr><th>Fabric:</th><td>${order.fabric_type}</td></tr>
                                <tr><th>Size:</th><td>${order.size}</td></tr>
                                <tr><th>Quantity:</th><td>${order.quantity}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Delivery Address</h6>
                            <p>${order.delivery_address}<br>${order.city}</p>
                            <h6 class="text-muted mt-3">Amount</h6>
                            <p>
                                Unit Price: Rs. ${parseFloat(order.unit_price).toFixed(2)}<br>
                                <strong>Total: Rs. ${parseFloat(order.total_amount).toFixed(2)}</strong>
                            </p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            <p>
                                Payment: <span class="badge bg-${order.payment_status === 'paid' ? 'success' : 'warning'}">${order.payment_status}</span><br>
                                Delivery: <span class="badge bg-info">${order.delivery_status}</span>
                            </p>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load order details</div>';
            }
        })
        .catch(err => {
            content.innerHTML = '<div class="alert alert-danger">Error loading order details</div>';
        });
}
</script>

</body>
</html>