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

// Handle optional 'remove' via GET for quick testing (keeps form-based removal below as primary)
if (isset($_GET['remove']) && ctype_digit($_GET['remove'])) {
    $remove_id = (int) $_GET['remove'];
    $del = $mysqli->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $remove_id, $user_id);
    $del->execute();
    $del->close();
    header('Location: cart.php');
    exit;
}

// Fetch cart items for the user
$sql = "
    SELECT 
        c.id AS cart_id,
        c.product_id,
        c.quantity,
        p.product_name,
        p.price,
        p.product_img_name
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My Cart - CeylonFashion</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body{ background:#f8f9fa; }
    .cart-container{ max-width:980px; margin:40px auto; background:#fff; padding:24px; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,.06); }
    .cart-item{ border-bottom:1px solid #eee; padding:16px 0; display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .cart-item img{ width:90px; height:90px; object-fit:cover; border-radius:8px; }
    .cart-item .meta{ flex:1; margin-left:12px; }
    .cart-summary{ text-align:right; margin-top:18px; }
    .btn-remove{ background:none; border:0; color:#d9534f; font-size:18px; }
  </style>
</head>
<body>
  <div class="container cart-container">
    <h3 class="mb-4 text-center">🛒 My Shopping Cart</h3>

    <?php if (!empty($items)): ?>
      <?php $grand = 0; ?>
      <?php foreach ($items as $row): ?>
        <?php
          $subtotal = $row['price'] * $row['quantity'];
          $grand += $subtotal;
          $imgPath = 'uploads/' . ($row['product_img_name'] ?: 'no-image.png');
        ?>
        <div class="cart-item">
          <div class="d-flex align-items-center">
            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
            <div class="meta">
              <h5 class="mb-1"><?php echo htmlspecialchars($row['product_name']); ?></h5>
              <div class="small text-muted">Price: Rs. <?php echo number_format($row['price'],2); ?> &nbsp; • &nbsp; Qty: <?php echo (int)$row['quantity']; ?></div>
            </div>
          </div>

          <div class="text-end">
            <div class="fw-bold mb-2">Rs. <?php echo number_format($subtotal,2); ?></div>

            <!-- Remove form (POST) -->
            <form action="cart-remove.php" method="post" onsubmit="return confirm('Remove this item from cart?');">
              <input type="hidden" name="cart_id" value="<?php echo (int)$row['cart_id']; ?>">
              <button type="submit" class="btn-remove" title="Remove"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="cart-summary">
        <h4>Total: Rs. <?php echo number_format($grand,2); ?></h4>
        <a href="checkout.php" class="btn btn-success mt-3">Proceed to Checkout</a>
      </div>
    <?php else: ?>
      <div class="text-center py-5">
        <p class="lead">Your cart is empty.</p>
        <a href="index.php" class="btn btn-primary">Continue Shopping</a>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
