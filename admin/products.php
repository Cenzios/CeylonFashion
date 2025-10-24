<?php
// admin/products.php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
include_once '../config.php'; // adjust path if needed (this should define $mysqli and DB creds)

// ---- Auth / admin check ----
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
  header('Location: ../index.php');
  exit;
}

// ---- Fetch products (mysqli from your config.php) ----
$sql = "SELECT id, product_name, product_code, product_desc, product_img_name, qty, price FROM products ORDER BY id DESC";
$result = $mysqli->query($sql);
if ($result === false) {
  die("DB error: " . $mysqli->error);
}

// helper fallback image path
$fallback = '../assets/no-image.png'; // ensure this exists
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
    .sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#343a40; color:#fff; padding-top:20px; }
    .sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
    .sidebar a.active { background:#007bff; color:#fff; }
    .main { margin-left:240px; padding:28px; min-height:100vh; }
    .card-img-top { height:200px; object-fit:cover; border-top-left-radius:.375rem; border-top-right-radius:.375rem; }
    .card { border: 0; border-radius:.5rem; transition: transform .12s ease, box-shadow .12s ease; }
    .card:hover { transform: translateY(-4px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
    .card .meta-row { display:flex; justify-content:space-between; align-items:center; gap:.5rem; }
    .card-icons { display:flex; gap:.5rem; }
    .card-icons a { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; background: rgba(255,255,255,.95); color:#333; text-decoration:none; border:1px solid rgba(0,0,0,.06); }
    .card-icons a:hover { background:#007bff; color:#fff; transform:translateY(-1px); }
    .price { font-size:1.1rem; font-weight:700; color:#0d6efd; }
    .desc { min-height:3.2rem; } /* keep cards aligned */
    @media (max-width: 575px) {
      .card-img-top { height:160px; }
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="text-center mb-3">Admin Panel</h4>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="products.php" class="active">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="users.php">👥 Users</a>
    <hr style="border-color: rgba(255,255,255,.06)">
    <a href="../logout.php" class="text-danger">🚪 Logout</a>
  </div>

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
        <?php while ($obj = $result->fetch_object()): ?>
          <?php
            $pid   = (int)$obj->id;
            $pname = htmlentities($obj->product_name, ENT_QUOTES, 'UTF-8');
            $pcode = htmlentities($obj->product_code, ENT_QUOTES, 'UTF-8');
            $pdesc = htmlentities($obj->product_desc, ENT_QUOTES, 'UTF-8');
            $pimg  = htmlentities($obj->product_img_name, ENT_QUOTES, 'UTF-8');
            $qty   = (int)$obj->qty;
            $price = number_format((float)$obj->price, 2);
            $imgPath = '../images/products/' . $pimg;
            if (empty($pimg) || !file_exists($imgPath)) {
              $imgPath = $fallback;
            }
          ?>
          <div class="col">
            <div class="card h-100 shadow-sm">
              <img src="<?php echo $imgPath; ?>" alt="<?php echo $pname; ?>" class="card-img-top">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="card-title mb-0" title="<?php echo $pname; ?>"><?php echo $pname; ?></h5>
                  <span class="price">Rs. <?php echo $price; ?></span>
                </div>

                <p class="text-muted small mb-2">Code: <?php echo $pcode; ?></p>

                <p class="card-text desc text-muted small mb-3"><?php echo (strlen($pdesc) > 120) ? htmlentities(substr($pdesc,0,120), ENT_QUOTES, 'UTF-8').'...' : $pdesc; ?></p>

                <div class="mt-auto d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center small text-muted">
                    <i class="bi bi-box-seam me-1"></i>
                    <span><?php echo ($qty > 0) ? $qty . ' in stock' : '<span style="color:#c00;">Out of stock</span>'; ?></span>
                  </div>

                  <div class="card-icons">
                    <a href="view-product.php?id=<?php echo $pid; ?>" class="text-decoration-none" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="edit-product.php?id=<?php echo $pid; ?>" class="text-decoration-none" title="Edit">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                    <!-- Delete Button -->
                    <a href="#" class="text-decoration-none text-danger" title="Delete" onclick="showDeleteModal('<?php echo $pid; ?>','<?php echo addslashes($pname); ?>'); return false;">
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

  <!-- Bootstrap JS -->
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
