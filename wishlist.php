<?php
// =======================================
// FILE: wishlist.php
// Display user wishlist items
// =======================================

session_start();
require_once 'config.php'; // must define $mysqli

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Handle optional remove by GET (fallback)
if (isset($_GET['remove']) && ctype_digit($_GET['remove'])) {
    $remove_id = (int) $_GET['remove'];
    $stmt = $mysqli->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $remove_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header('Location: wishlist.php');
    exit;
}

// Fetch wishlist items with product details
$query = "
    SELECT 
        w.id AS wishlist_id,
        w.product_id,
        p.product_name,
        p.product_code,
        p.product_desc,
        p.product_img_name,
        p.category
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
";
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    die("SQL Error: " . $mysqli->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlist_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// For each wishlist item, fetch fabric details
foreach ($wishlist_items as &$item) {
    $product_id = (int)$item['product_id'];
    
    // Fetch fabric details
    $fabricStmt = $mysqli->prepare("SELECT fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = ?");
    $fabricStmt->bind_param("i", $product_id);
    $fabricStmt->execute();
    $fabricResult = $fabricStmt->get_result();
    
    $fabrics = [];
    $minPrice = null;
    $maxPrice = null;
    $totalQty = 0;
    
    while ($f = $fabricResult->fetch_assoc()) {
        $fabrics[] = $f;
        $price = (float)$f['fabric_price'];
        $qty = (int)$f['fabric_qty'];
        
        if ($minPrice === null || $price < $minPrice) $minPrice = $price;
        if ($maxPrice === null || $price > $maxPrice) $maxPrice = $price;
        $totalQty += $qty;
    }
    
    $item['fabrics'] = $fabrics;
    $item['min_price'] = $minPrice;
    $item['max_price'] = $maxPrice;
    $item['total_qty'] = $totalQty;
    
    $fabricStmt->close();
}
unset($item);
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container wishlist-container">
    <h3 class="mb-4 text-center">❤️ My Wishlist</h3>

    <?php if (!empty($wishlist_items)): ?>
      <?php foreach ($wishlist_items as $item): ?>
        <?php
          $imgPath = 'images/products/' . ($item['product_img_name'] ?: 'no-image.png');
          if (!file_exists($imgPath)) {
              $imgPath = 'assets/no-image.png';
          }
        ?>
        <div class="wishlist-item">
          <div class="d-flex align-items-center flex-grow-1">
            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
            <div class="meta">
              <h5 class="mb-1">
                <a href="product-view.php?id=<?php echo (int)$item['product_id']; ?>" class="text-decoration-none text-dark">
                  <?php echo htmlspecialchars($item['product_name']); ?>
                </a>
              </h5>
              <div class="small text-muted mb-1">Code: <?php echo htmlspecialchars($item['product_code']); ?></div>
              
              <!-- Price Range -->
              <?php if ($item['min_price'] !== null): ?>
                <div class="small fw-bold text-success mb-2">
                  <?php if ($item['min_price'] === $item['max_price']): ?>
                    Price: Rs. <?php echo number_format($item['min_price'], 2); ?>
                  <?php else: ?>
                    Price: Rs. <?php echo number_format($item['min_price'], 2); ?> - Rs. <?php echo number_format($item['max_price'], 2); ?>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <!-- Fabric Options -->
              <?php if (!empty($item['fabrics'])): ?>
                <div class="fabric-options-small">
                  <small class="text-muted d-block mb-1"><strong>Available Fabrics:</strong></small>
                  <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($item['fabrics'] as $fabric): ?>
                      <?php if ((int)$fabric['fabric_qty'] > 0): ?>
                        <span class="badge bg-secondary" style="font-size:0.7rem;">
                          <?php echo htmlspecialchars($fabric['fabric_type']); ?> 
                          (<?php echo (int)$fabric['fabric_qty']; ?>)
                        </span>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <!-- Availability -->
              <div class="mt-2">
                <?php if ($item['total_qty'] > 0): ?>
                  <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i> In Stock (<?php echo $item['total_qty']; ?> units)
                  </span>
                <?php else: ?>
                  <span class="badge bg-danger">
                    <i class="bi bi-x-circle"></i> Out of Stock
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="text-end d-flex flex-column align-items-end gap-2">
            <!-- View Details button -->
            <a href="product-view.php?id=<?php echo (int)$item['product_id']; ?>" class="btn btn-primary btn-sm">
              <i class="bi bi-eye"></i> View Details
            </a>

            <?php if ($item['total_qty'] > 0): ?>
              <!-- Add to Cart button -->
              <a href="cart-add.php?id=<?php echo (int)$item['product_id']; ?>&qty=1" class="btn btn-success btn-sm">
                <i class="bi bi-cart-plus"></i> Add to Cart
              </a>
            <?php endif; ?>

            <!-- Remove form -->
            <form action="wishlist-remove.php" method="post" onsubmit="return confirm('Remove this item from wishlist?');" class="mb-0">
              <input type="hidden" name="wishlist_id" value="<?php echo (int)$item['wishlist_id']; ?>">
              <button type="submit" class="btn-remove" title="Remove">
                <i class="bi bi-trash"></i> Remove
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="text-center mt-4">
        <a href="index.php" class="btn btn-outline-primary">Continue Shopping</a>
      </div>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="bi bi-heart" style="font-size: 4rem; color: #e9ecef;"></i>
        <p class="lead mt-3">Your wishlist is empty.</p>
        <a href="index.php" class="btn btn-primary">Continue Shopping</a>
      </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<style>
  body { background:#f8f9fa; }
  .wishlist-container {
    max-width: 1100px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
  }
  .wishlist-item {
    display:flex;
    align-items:center;
    justify-content:space-between;
    border-bottom:1px solid #eee;
    padding:20px 0;
    gap:20px;
  }
  .wishlist-item:last-child {
    border-bottom: none;
  }
  .wishlist-item img {
    width:120px;
    height:120px;
    object-fit:cover;
    border-radius:10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  .wishlist-item .meta {
    flex:1;
    margin-left:16px;
  }
  .btn-remove {
    background:none;
    border:1px solid #dc3545;
    color:#dc3545;
    font-size:14px;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
  }
  .btn-remove:hover {
    background:#dc3545;
    color:#fff;
  }
  .fabric-options-small {
    margin-top: 8px;
  }
  
  @media (max-width: 768px) {
    .wishlist-item {
      flex-direction: column;
      align-items: flex-start;
    }
    .wishlist-item img {
      width: 100%;
      max-width: 200px;
      height: auto;
    }
    .wishlist-item .meta {
      margin-left: 0;
      margin-top: 12px;
    }
    .text-end {
      width: 100%;
      text-align: left !important;
    }
    .text-end .d-flex {
      flex-direction: row !important;
      justify-content: flex-start !important;
    }
  }
</style>

<?php include 'includes/scripts.php'; ?>

</body>
</html