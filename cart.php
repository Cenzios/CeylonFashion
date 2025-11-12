<?php
// cart.php - show user's cart
session_start();
require_once 'config.php'; // must define $mysqli (mysqli object)

// Redirect to login if needed
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Handle optional 'remove' via GET for quick testing
if (isset($_GET['remove']) && ctype_digit($_GET['remove'])) {
    $remove_id = (int) $_GET['remove'];
    $del = $mysqli->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $remove_id, $user_id);
    $del->execute();
    $del->close();
    header('Location: cart.php');
    exit;
}

// Fetch cart items for the user with price from cart table
$sql = "
    SELECT 
        c.id AS cart_id,
        c.product_id,
        c.quantity,
        c.price,
        p.product_name,
        p.product_code,
        p.product_img1,
        p.product_img2,
        p.product_img3,
        p.product_img4,
        p.category
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
";
$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    die("DB prepare error: " . $mysqli->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// For each cart item, fetch current fabric availability
foreach ($items as &$item) {
    $product_id = (int)$item['product_id'];
    
    // Get total available quantity from fabrics
    $fabricStmt = $mysqli->prepare("SELECT SUM(fabric_qty) as total_qty FROM product_fabrics WHERE product_id = ?");
    $fabricStmt->bind_param("i", $product_id);
    $fabricStmt->execute();
    $fabricResult = $fabricStmt->get_result();
    $fabricData = $fabricResult->fetch_assoc();
    $fabricStmt->close();
    
    $item['available_qty'] = $fabricData ? (int)$fabricData['total_qty'] : 0;
    
    // Get first available image
    $item['display_image'] = '';
    for ($i = 1; $i <= 4; $i++) {
        $imgField = 'product_img' . $i;
        if (!empty($item[$imgField])) {
            $item['display_image'] = $item[$imgField];
            break;
        }
    }
}
unset($item);
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container cart-container">
    <h3 class="mb-4 text-center">🛒 My Shopping Cart</h3>

    <?php if (isset($_SESSION['cart_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['cart_success']); unset($_SESSION['cart_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['cart_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($_SESSION['cart_error']); unset($_SESSION['cart_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
      <?php $grand = 0; ?>
      <?php foreach ($items as $row): ?>
        <?php
          $subtotal = $row['price'] * $row['quantity'];
          $grand += $subtotal;
          
          // Use first available image or fallback
          $imgPath = !empty($row['display_image']) ? 'images/products/' . $row['display_image'] : 'assets/no-image.png';
          if (!file_exists($imgPath)) {
              $imgPath = 'assets/no-image.png';
          }
          
          // Check if item quantity exceeds available stock
          $stock_warning = ($row['quantity'] > $row['available_qty']);
        ?>
        <div class="cart-item <?php echo $stock_warning ? 'stock-warning' : ''; ?>">
          <div class="d-flex align-items-center flex-grow-1">
            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
            <div class="meta">
              <h5 class="mb-1">
                <a href="product-view.php?id=<?php echo (int)$row['product_id']; ?>" class="text-decoration-none text-dark">
                  <?php echo htmlspecialchars($row['product_name']); ?>
                </a>
              </h5>
              <div class="small text-muted mb-1">Code: <?php echo htmlspecialchars($row['product_code']); ?></div>
              <div class="small text-muted">
                Price: Rs. <?php echo number_format($row['price'],2); ?> &nbsp; • &nbsp; 
                Qty: <strong><?php echo (int)$row['quantity']; ?></strong>
              </div>
              
              <?php if ($stock_warning): ?>
                <div class="mt-2">
                  <span class="badge bg-warning text-dark">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Only <?php echo $row['available_qty']; ?> available
                  </span>
                </div>
              <?php else: ?>
                <div class="mt-2">
                  <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i> In Stock
                  </span>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="text-end d-flex flex-column align-items-end gap-2">
            <div class="fw-bold text-primary fs-5">Rs. <?php echo number_format($subtotal,2); ?></div>

            <!-- Quantity Update -->
            <div class="qty-controls d-flex align-items-center gap-2">
              <a href="cart-update.php?id=<?php echo $row['cart_id']; ?>&action=decrease" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-dash"></i>
              </a>
              <span class="qty-display"><?php echo (int)$row['quantity']; ?></span>
              <a href="cart-update.php?id=<?php echo $row['cart_id']; ?>&action=increase" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-plus"></i>
              </a>
            </div>

            <!-- Remove form (POST) -->
            <form action="cart-remove.php" method="post" onsubmit="return confirm('Remove this item from cart?');" class="mb-0">
              <input type="hidden" name="cart_id" value="<?php echo (int)$row['cart_id']; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                <i class="bi bi-trash"></i> Remove
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="cart-summary">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Subtotal:</h4>
          <h4 class="mb-0 text-primary">Rs. <?php echo number_format($grand,2); ?></h4>
        </div>
        <div class="text-muted small mb-3">Shipping and taxes calculated at checkout</div>
        <div class="d-flex gap-2">
          <a href="index.php" class="btn btn-outline-secondary">Continue Shopping</a>
          <a href="checkout.php" class="btn btn-success flex-grow-1">Proceed to Checkout</a>
        </div>
      </div>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="bi bi-cart-x" style="font-size: 4rem; color: #e9ecef;"></i>
        <p class="lead mt-3">Your cart is empty.</p>
        <a href="index.php" class="btn btn-primary">Continue Shopping</a>
      </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<style>
  body { background:#f8f9fa; }
  .cart-container {
    max-width: 1100px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
  }
  .cart-item {
    border-bottom: 1px solid #eee;
    padding: 20px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    transition: background 0.3s ease;
  }
  .cart-item:hover {
    background: #f8f9fa;
    padding-left: 10px;
    padding-right: 10px;
    border-radius: 8px;
  }
  .cart-item:last-child {
    border-bottom: none;
  }
  .cart-item.stock-warning {
    background: #fff3cd;
    padding: 20px 10px;
    border-radius: 8px;
    border-left: 4px solid #ffc107;
  }
  .cart-item img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  .cart-item .meta {
    flex: 1;
    margin-left: 16px;
  }
  .cart-summary {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #dee2e6;
  }
  .qty-controls {
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 8px;
  }
  .qty-display {
    min-width: 30px;
    text-align: center;
    font-weight: 600;
  }
  
  @media (max-width: 768px) {
    .cart-item {
      flex-direction: column;
      align-items: flex-start;
    }
    .cart-item img {
      width: 100%;
      max-width: 200px;
      height: auto;
    }
    .cart-item .meta {
      margin-left: 0;
      margin-top: 12px;
    }
    .text-end {
      width: 100%;
      text-align: left !important;
    }
  }
</style>

<?php include 'includes/scripts.php'; ?>

</body>
</html>