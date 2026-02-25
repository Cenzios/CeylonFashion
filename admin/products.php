<?php
// admin/products.php
if (session_id() == '' || !isset($_SESSION)) { session_name('ADMIN_SESSION'); session_start(); }
include_once '../config/config.php'; // ensure this defines $mysqli

// ---- Auth / admin check ----
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
  header('Location: ../index.php');
  exit;
}

// ---- Fetch products with fabric details ----
$sql = "SELECT p.id, p.product_name, p.product_code, p.product_desc, p.product_img1
        FROM products p
        WHERE p.is_deleted = 0
        ORDER BY p.id DESC";
$result = $mysqli->query($sql);
if ($result === false) {
  die('DB error: ' . $mysqli->error);
}

// fallback image path
$fallback = '../assets/no-image.png';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin | Products</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body { background:#f8f9fa; font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
    .sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#430160ff; color:#fff; padding-top:20px; }
    .sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
    .sidebar a.active { background:#007bff; color:#fff; }
    .main { margin-left:240px; padding:28px; min-height:100vh; }
    .card-img-top { height:200px; object-fit:cover; border-top-left-radius:.375rem; border-top-right-radius:.375rem; }
    .card { border: 0; border-radius:.5rem; transition: transform .12s ease, box-shadow .12s ease; }
    .card:hover { transform: translateY(-4px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
    .card-icons { display:flex; gap:.5rem; }
    .card-icons a { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; background: rgba(255,255,255,.95); color:#333; text-decoration:none; border:1px solid rgba(0,0,0,.06); }
    .card-icons a:hover { background:#007bff; color:#fff; transform:translateY(-1px); }
    .fabric-box { background:#f1f3f5; border-radius:8px; padding:10px; font-size:0.9rem; }
    .fabric-box table { width:100%; font-size:0.85rem; margin:0; }
    .fabric-box th, .fabric-box td { padding:4px 6px; }
    .fabric-box th { color:#555; }
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

  <!-- Main Section -->
  <main class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="mb-0">Manage Products</h3>
      <div>
        <a href="add-product.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
      </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
      <?php if ($result->num_rows === 0): ?>
        <div class="col">
          <div class="card p-4 text-center">
            <div class="card-body">
              <p class="mb-0 text-muted">No products found.</p>
            </div>
          </div>
        </div>
      <?php else: ?>
        <?php while ($p = $result->fetch_assoc()): ?>
          <?php
            $pid   = (int)$p['id'];
            $pname = htmlentities($p['product_name'], ENT_QUOTES, 'UTF-8');
            $pcode = htmlentities($p['product_code'], ENT_QUOTES, 'UTF-8');
            $pdesc = htmlentities($p['product_desc'], ENT_QUOTES, 'UTF-8');
            $pimg  = htmlentities($p['product_img1'], ENT_QUOTES, 'UTF-8');
            $imgPath = '../assets/images/products/' . $pimg;
            if (empty($pimg) || !file_exists($imgPath)) {
              $imgPath = $fallback;
            }

            // fetch fabric details for this product
            $fabricSql = "SELECT fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = $pid";
            $fabricRes = $mysqli->query($fabricSql);
            $fabrics = [];
            if ($fabricRes && $fabricRes->num_rows > 0) {
              while ($f = $fabricRes->fetch_assoc()) {
                $fabrics[] = $f;
              }
            }
          ?>
          <div class="col">
            <div class="card h-100 shadow-sm">
              <img src="<?php echo $imgPath; ?>" alt="<?php echo $pname; ?>" class="card-img-top">
              <div class="card-body d-flex flex-column">
                <h5 class="card-title mb-1"><?php echo $pname; ?></h5>
                <p class="text-muted small mb-1">Code: <?php echo $pcode; ?></p>
                <p class="text-muted small mb-2"><?php echo (strlen($pdesc) > 100) ? substr($pdesc,0,100).'...' : $pdesc; ?></p>

                <?php if (!empty($fabrics)): ?>
                  <div class="fabric-box mb-3">
                    <strong>Fabric Details</strong>
                    <table class="table table-sm mt-2 mb-0">
                      <thead>
                        <tr>
                          <th>Type</th>
                          <th>Qty</th>
                          <th>Price (Rs)</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($fabrics as $f): ?>
                          <tr>
                            <td><?php echo htmlentities($f['fabric_type']); ?></td>
                            <td><?php echo (int)$f['fabric_qty']; ?></td>
                            <td><?php echo number_format((float)$f['fabric_price'], 2); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <p class="text-muted small mb-3"><em>No fabric details</em></p>
                <?php endif; ?>

                  <div class="mt-auto d-flex justify-content-end align-items-center">
                    <div class="card-icons">
                    <a href="view-product.php?id=<?php echo $pid; ?>" title="View"><i class="bi bi-eye"></i></a>
                    <a href="edit-product.php?id=<?php echo $pid; ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                    <a href="#" class="text-danger" title="Delete" onclick="showDeleteModal('<?php echo $pid; ?>','<?php echo addslashes($pname); ?>'); return false;">
                      <i class="bi bi-trash"></i>
                    </a>
                  </div>
                </div>

              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
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
          Are you sure you want to delete <strong id="deleteProductName"></strong>? This action cannot be undone.
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
    function showDeleteModal(productId, productName) {
      document.getElementById('deleteProductName').textContent = productName;
      document.getElementById('confirmDeleteBtn').href = 'delete-product.php?id=' + productId;
      var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
      deleteModal.show();
    }
  </script>
</body>
</html>