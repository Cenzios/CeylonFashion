<?php
// admin/orders.php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
include_once '../config.php';

// ---- Admin check ----
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// ---- Fetch orders ----
$sql = "SELECT id, product_code, product_name, price, units, total, date, email, status FROM orders ORDER BY id DESC";
$result = $mysqli->query($sql);
if ($result === false) {
    die("DB error: " . $mysqli->error);
}

// Handle status update via POST (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $mysqli->real_escape_string($_POST['status']);
    $updateSql = "UPDATE orders SET status='$status' WHERE id=$orderId";
    if ($mysqli->query($updateSql)) {
        echo "success";
    } else {
        echo "error";
    }
    exit;
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
body { background:#f8f9fa; font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
.sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#343a40; color:#fff; padding-top:20px; }
.sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
.sidebar a.active { background:#007bff; color:#fff; }
.main { margin-left:240px; padding:28px; min-height:100vh; }
.table th, .table td { vertical-align: middle; }
.status-select { width: 140px; }
</style>
</head>
<body>

<div class="sidebar">
  <h4 class="text-center mb-3">Admin Panel</h4>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="products.php">📦 Products</a>
  <a href="orders.php" class="active">🧾 Orders</a>
  <a href="users.php">👥 Users</a>
  <hr style="border-color: rgba(255,255,255,.06)">
  <a href="../logout.php" class="text-danger">🚪 Logout</a>
</div>

<main class="main">
  <h3 class="mb-4">Manage Orders</h3>

  <?php if ($result->num_rows === 0): ?>
      <div class="alert alert-warning">No orders found.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Product Code</th>
            <th>Product Name</th>
            <th>Price</th>
            <th>Units</th>
            <th>Total</th>
            <th>Date</th>
            <th>Email</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($order = $result->fetch_assoc()): ?>
            <tr id="orderRow<?php echo (int)$order['id']; ?>">
              <td><?php echo (int)$order['id']; ?></td>
              <td><?php echo htmlentities($order['product_code']); ?></td>
              <td><?php echo htmlentities($order['product_name']); ?></td>
              <td>Rs. <?php echo number_format((float)$order['price'],2); ?></td>
              <td><?php echo (int)$order['units']; ?></td>
              <td>Rs. <?php echo number_format((float)$order['total'],2); ?></td>
              <td><?php echo htmlentities($order['date']); ?></td>
              <td><?php echo htmlentities($order['email']); ?></td>
              <td>
                <select class="form-select status-select" onchange="updateStatus(<?php echo (int)$order['id']; ?>, this)">
                  <?php
                  $statuses = ['pending','dispatch','delivered','returned'];
                  foreach($statuses as $s) {
                      $selected = ($order['status'] === $s) ? 'selected' : '';
                      echo "<option value='$s' $selected>$s</option>";
                  }
                  ?>
                </select>
              </td>
              <td>
                <a href="#" class="btn btn-sm btn-danger" title="Delete" onclick="showDeleteModal('<?php echo (int)$order['id']; ?>'); return false;">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>

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
        <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Delete modal
let deleteOrderId = null;
function showDeleteModal(orderId) {
    deleteOrderId = orderId;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if(deleteOrderId) {
        window.location.href = 'delete-order.php?id=' + deleteOrderId;
    }
});

// Update status via AJAX
function updateStatus(orderId, selectElement) {
    const status = selectElement.value;
    fetch('orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'order_id=' + orderId + '&status=' + status
    })
    .then(resp => resp.text())
    .then(data => {
        if(data !== 'success') {
            alert('Failed to update status');
        }
    })
    .catch(err => alert('Error: ' + err));
}
</script>

</body>
</html>
