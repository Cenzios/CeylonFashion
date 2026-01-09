<?php
// admin/users.php
if (session_id() == '' || !isset($_SESSION)) { session_name('ADMIN_SESSION'); session_start(); }
include_once '../config.php';

// ---- Admin check ----
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// ---- Fetch users ----
// ---- Fetch users with Filters ----
$where = [];
$params = [];
$types = "";

// 1. Filter by Type
if (!empty($_GET['type'])) {
    $where[] = "type = ?";
    $params[] = $_GET['type'];
    $types .= "s";
}

// 2. Filter by Date Range (using 'created' column)
if (!empty($_GET['start_date'])) {
    $where[] = "DATE(created) >= ?";
    $params[] = $_GET['start_date'];
    $types .= "s";
}
if (!empty($_GET['end_date'])) {
    $where[] = "DATE(created) <= ?";
    $params[] = $_GET['end_date'];
    $types .= "s";
}

// 3. Search (Name, Email)
if (!empty($_GET['search'])) {
    $term = '%' . $_GET['search'] . '%';
    $where[] = "(fname LIKE ? OR lname LIKE ? OR email LIKE ?)";
    $params[] = $term; // fname
    $params[] = $term; // lname

    
    $params[] = $term; // email
    $types .= "sss";
}

$sql = "SELECT id, fname, lname, email, created, type FROM users WHERE is_deleted = 0";
if (!empty($where)) {
    $sql .= " AND " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$stmt = $mysqli->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
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
<title>Admin | Users</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background:#f8f9fa; font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
.sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#430160ff; color:#fff; padding-top:20px; }
.sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
.sidebar a.active { background:#007bff; color:#fff; }
.main { margin-left:240px; padding:28px; min-height:100vh; }
.table th, .table td { vertical-align: middle; }
.toggle-btn { cursor:pointer; }
</style>
</head>
<body>

<div class="sidebar">
  <h4 class="text-center mb-3">Ceylon Fashion</h4>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="products.php">📦 Products</a>
  <a href="orders.php">🧾 Orders</a>
  <a href="users.php" class="active">👥 Users</a>
  <a href="reports.php">📊 Reports</a>
  <hr style="border-color: rgba(255,255,255,.06)">
  <a href="logout.php" class="text-danger" onclick="return confirm('Are you sure you want to logout?');">🚪 Logout</a>
</div>

<main class="main">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Manage Users</h3>
    <div>
      <a href="add-user.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add User</a>
    </div>
  </div>

  <!-- Filter Section -->
  <div class="card mb-4">
    <div class="card-body bg-light">
      <form method="GET" action="" class="row g-3">
        <!-- User Type -->
        <div class="col-md-2">
          <label class="form-label">User Type</label>
          <select name="type" class="form-select">
            <option value="">All Types</option>
            <option value="admin" <?php if(isset($_GET['type']) && $_GET['type'] == 'admin') echo 'selected'; ?>>Admin</option>
            <option value="user" <?php if(isset($_GET['type']) && $_GET['type'] == 'user') echo 'selected'; ?>>User</option>
          </select>
        </div>
        
        <!-- Date Range -->
        <div class="col-md-2">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? htmlentities($_GET['start_date']) : ''; ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? htmlentities($_GET['end_date']) : ''; ?>">
        </div>

        <!-- Search -->
        <div class="col-md-4">
          <label class="form-label">Search</label>
          <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Name or Email" value="<?php echo isset($_GET['search']) ? htmlentities($_GET['search']) : ''; ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
          </div>
        </div>
        
        <!-- Actions -->
        <div class="col-md-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary w-100">Filter</button>
          <a href="users.php" class="btn btn-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
      </form>
    </div>
  </div>

  <?php if ($result->num_rows === 0): ?>
      <div class="alert alert-warning">No users found matching your criteria.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Joined Date</th>
            <th>Type</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($user = $result->fetch_assoc()): ?>
            <tr id="userRow<?php echo (int)$user['id']; ?>">
              <td><?php echo (int)$user['id']; ?></td>
              <td><?php echo htmlentities($user['fname']); ?></td>
              <td><?php echo htmlentities($user['lname']); ?></td>
              <td><?php echo htmlentities($user['email']); ?></td>
              <td><?php echo date('M d, Y', strtotime($user['created'])); ?></td>
              <td>
                <span class="badge bg-<?php echo $user['type'] === 'admin' ? 'primary' : 'secondary'; ?> toggle-btn"
                      onclick="toggleUserType(<?php echo (int)$user['id']; ?>, this)">
                  <?php echo htmlentities($user['type']); ?>
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-danger" title="Delete"
                        onclick="showDeleteModal('<?php echo (int)$user['id']; ?>','<?php echo addslashes($user['fname'].' '.$user['lname']); ?>');">
                  <i class="bi bi-trash"></i>
                </button>
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
        Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="confirmDeleteBtn" type="button" class="btn btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Delete modal
let deleteUserId = null;

function showDeleteModal(userId, userName) {
    deleteUserId = userId;
    document.getElementById('deleteUserName').textContent = userName;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if(deleteUserId) {
        // Redirect to delete-user.php with ID
        window.location.href = 'delete-user.php?id=' + deleteUserId;
    }
});

// Toggle user type
function toggleUserType(userId, element) {
    const currentType = element.textContent.trim();
    const newType = currentType === 'admin' ? 'user' : 'admin';
    
    fetch('toggle-user-type.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + userId + '&type=' + newType
    })
    .then(response => response.text())
    .then(data => {
        if(data === 'success') {
            element.textContent = newType;
            element.classList.toggle('bg-primary');
            element.classList.toggle('bg-secondary');
        } else {
            alert('Failed to toggle user type');
        }
    })
    .catch(err => alert('Error: ' + err));
}
</script>

</body>
</html>
