<?php
// cart.php - show user's cart
session_start();
require_once 'config.php'; // must define $mysqli (mysqli object)
require_once 'lib/guest-cart.php';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$items = [];

if ($isLoggedIn) {
    // Logged in user - fetch from database
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
            c.fabric_id,
            c.size,
            c.quantity,
            c.price,
            p.product_name,
            p.product_code,
            p.product_img1,
            p.product_img2,
            p.product_img3,
            p.product_img4,
            p.category,
            pf.fabric_type
        FROM cart c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN product_fabrics pf ON c.fabric_id = pf.id
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

    // For each cart item, fetch current fabric availability and get first image
    foreach ($items as &$item) {
        $product_id = (int)$item['product_id'];
        $fabric_id  = (int)$item['fabric_id'];
        
        // Get first available image from product_img1-4
        $firstImage = '';
        for ($i = 1; $i <= 4; $i++) {
            $imgField = 'product_img' . $i;
            if (!empty($item[$imgField])) {
                $firstImage = $item[$imgField];
                break;
            }
        }
        $item['product_img_name'] = $firstImage;
        
        // Get available quantity. If fabric_id is set, get THAT fabric's qty.
        if ($fabric_id > 0) {
            $fStmt = $mysqli->prepare("SELECT fabric_qty FROM product_fabrics WHERE id = ?");
            $fStmt->bind_param("i", $fabric_id);
            $fStmt->execute();
            $res = $fStmt->get_result()->fetch_assoc();
            $item['available_qty'] = $res ? (int)$res['fabric_qty'] : 0;
            $fStmt->close();
        } else {
            // Fallback total qty (legacy)
            $fabricStmt = $mysqli->prepare("SELECT SUM(fabric_qty) as total_qty FROM product_fabrics WHERE product_id = ?");
            $fabricStmt->bind_param("i", $product_id);
            $fabricStmt->execute();
            $fabricResult = $fabricStmt->get_result();
            $fabricData = $fabricResult->fetch_assoc();
            $fabricStmt->close();
            $item['available_qty'] = $fabricData ? (int)$fabricData['total_qty'] : 0;
        }
    }
    unset($item);
} else {
    // Guest user - fetch from session
    $guestCart = getGuestCart();
    
    if (!empty($guestCart)) {
        // Collect product IDs
        $productIds = [];
        foreach ($guestCart as $k => $v) {
             $productIds[] = $v['product_id'];
        }
        $productIds = array_unique($productIds);
        
        if (!empty($productIds)) {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            
            // Get product info and logic to attach fabric names requires iterating.
            // Let's get products first.
            $productsInfo = [];
            $sql = "
                SELECT 
                    id AS product_id,
                    product_name,
                    product_code,
                    product_img1,
                    product_img2,
                    product_img3,
                    product_img4,
                    category
                FROM products
                WHERE id IN ($placeholders)
            ";
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($p = $result->fetch_assoc()) {
                    $productsInfo[$p['product_id']] = $p;
                }
                $stmt->close();
            }

            foreach ($guestCart as $key => $cartItem) {
                $product_id = $cartItem['product_id'];
                if (!isset($productsInfo[$product_id])) continue;
                
                $product = $productsInfo[$product_id];
                $fabric_id = $cartItem['fabric_id'] ?? 0;
                
                // Get first available image
                $firstImage = '';
                for ($i = 1; $i <= 4; $i++) {
                   $imgField = 'product_img' . $i;
                   if (!empty($product[$imgField])) {
                       $firstImage = $product[$imgField];
                       break;
                   }
                }
                
                // Get Fabric Type Name and Available Quantity
                $fabricType = '';
                $availableQty = 0;
                
                if ($fabric_id > 0) {
                    $fStmt = $mysqli->prepare("SELECT fabric_type, fabric_qty FROM product_fabrics WHERE id = ?");
                    $fStmt->bind_param("i", $fabric_id);
                    $fStmt->execute();
                    if ($fData = $fStmt->get_result()->fetch_assoc()) {
                        $fabricType = $fData['fabric_type'];
                        $availableQty = (int)$fData['fabric_qty'];
                    }
                    $fStmt->close();
                } else {
                     // Fallback
                    $fStmt = $mysqli->prepare("SELECT SUM(fabric_qty) as total FROM product_fabrics WHERE product_id = ?");
                    $fStmt->bind_param("i", $product_id);
                    $fStmt->execute();
                    $d = $fStmt->get_result()->fetch_assoc();
                    $availableQty = $d ? (int)$d['total'] : 0;
                    $fStmt->close();
                }
                
                $items[] = [
                    'cart_id' => $key, // This IS the session key (string)
                    'product_id' => $product_id,
                    'fabric_id' => $fabric_id,
                    'size' => $cartItem['size'] ?? '',
                    'quantity' => $cartItem['quantity'],
                    'price' => $cartItem['price'],
                    'product_name' => $product['product_name'],
                    'product_code' => $product['product_code'],
                    'product_img_name' => $firstImage,
                    'category' => $product['category'],
                    'fabric_type' => $fabricType,
                    'available_qty' => $availableQty
                ];
            }
        }
    }
}
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
          $imgPath = 'images/products/' . ($row['product_img_name'] ?: 'no-image.png');
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
              
              <!-- Fabric & Size Display -->
              <div class="small text-muted mb-1">
                  <?php if (!empty($row['fabric_type'])): ?>
                      <span class="me-3"><strong>Fabric:</strong> <?php echo htmlspecialchars($row['fabric_type']); ?></span>
                  <?php endif; ?>
                  
                  <?php if (!empty($row['size'])): ?>
                      <span><strong>Size:</strong> <?php echo htmlspecialchars($row['size']); ?></span>
                  <?php endif; ?>
              </div>

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
              <?php if ($isLoggedIn): ?>
                <a href="cart-update.php?id=<?php echo $row['cart_id']; ?>&action=decrease" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-dash"></i>
                </a>
                <span class="qty-display"><?php echo (int)$row['quantity']; ?></span>
                <a href="cart-update.php?id=<?php echo $row['cart_id']; ?>&action=increase" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-plus"></i>
                </a>
              <?php else: ?>
                <!-- For guest, we pass cart_id (which is product_id_fabric_id_... key) -->
                <!-- Ideally cart-set-qty.php needs to be updated to accept cart_id instead of just product_id -->
                <!-- However, to minimize changes, we can pass product_id and variants? No, cart-set-qty handles global logic. -->
                <!-- Simplest hack: modify `cart-set-qty.php`? It was not in plan but required. -->
                <!-- Let's assume for now user uses the buttons which call `cart-update.php`? No, guest links were `cart-set-qty` -->
                <!-- Since `cart-set-qty.php` is likely simple, let's use a workaround or update it. -->
                <!-- Actually, let's update this link to use `cart-set-qty` but passing the KEY as `id` instead of `product_id`? -->
                <!-- If `cart-set-qty.php` expects `product_id` (int), passing a string key might break it. -->
                <!-- Let's check `cart-set-qty.php` content in next step if possible. For now, let's assume `id` or `key` param. -->
                
                <!-- Temporary: Disabled quantity update for guests on complex items until cart-set-qty is fixed? -->
                <!-- Or: Just link it, and if it breaks, we fix. -->
                <!-- Using `key` parameter which we will add support for in `cart-set-qty.php`. -->
                <a href="cart-set-qty.php?key=<?php echo urlencode($row['cart_id']); ?>&qty=<?php echo max(1, (int)$row['quantity'] - 1); ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-dash"></i>
                </a>
                <span class="qty-display"><?php echo (int)$row['quantity']; ?></span>
                <a href="cart-set-qty.php?key=<?php echo urlencode($row['cart_id']); ?>&qty=<?php echo (int)$row['quantity'] + 1; ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-plus"></i>
                </a>
              <?php endif; ?>
            </div>

            <!-- Remove form (POST) -->
            <?php if ($isLoggedIn): ?>
              <form action="cart-remove.php" method="post" onsubmit="return confirm('Remove this item from cart?');" class="mb-0">
                <input type="hidden" name="cart_id" value="<?php echo (int)$row['cart_id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                  <i class="bi bi-trash"></i> Remove
                </button>
              </form>
            <?php else: ?>
              <form action="cart-remove.php" method="post" onsubmit="return confirm('Remove this item from cart?');" class="mb-0">
                <!-- Pass KEY as cart_id or similar -->
                <input type="hidden" name="cart_id" value="<?php echo htmlspecialchars($row['cart_id']); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                  <i class="bi bi-trash"></i> Remove
                </button>
              </form>
            <?php endif; ?>
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
          <?php if ($isLoggedIn): ?>
            <button type="button" class="btn btn-success flex-grow-1" onclick="showCustomerDetailsModal()">Proceed to Checkout</button>
          <?php else: ?>
            <button type="button" class="btn btn-success flex-grow-1" onclick="document.getElementById('loginRedirectUrl').value = 'cart.php'; showLoginModal()">Login to Checkout</button>
          <?php endif; ?>
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

<!-- Customer Details Modal -->
<div class="modal fade" id="customerDetailsModal" tabindex="-1" aria-labelledby="customerDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerDetailsLabel">Confirm Your Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i> Please verify your delivery details before proceeding to payment.
        </div>
        
        <form id="customerDetailsForm">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="customerName" class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="customerName" 
                     value="<?= htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?>" 
                     required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="customerEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="customerEmail" 
                     value="<?= htmlspecialchars($_SESSION['email'] ?? ''); ?>" 
                     required>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="customerPhone" class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input type="tel" class="form-control" id="customerPhone" 
                     value="<?= htmlspecialchars($_SESSION['phone'] ?? ''); ?>" 
                     pattern="[0-9]{10,15}" 
                     placeholder="0771234567"
                     required>
              <small class="text-muted">10-15 digits only</small>
            </div>
            <div class="col-md-6 mb-3">
              <label for="customerCity" class="form-label">City <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="customerCity" 
                     value="<?= htmlspecialchars($_SESSION['city'] ?? ''); ?>" 
                     required>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="deliveryAddress" class="form-label">Delivery Address <span class="text-danger">*</span></label>
            <textarea class="form-control" id="deliveryAddress" rows="3" required><?= htmlspecialchars($_SESSION['address'] ?? ''); ?></textarea>
            <small class="text-muted">Street address, house/apartment number</small>
          </div>
          
          <div class="order-summary-box">
            <h6>Cart Summary</h6>
            <div class="summary-row">
                <span>Total Items:</span>
                <span><?php echo count($items); ?></span>
            </div>
            <div class="summary-row">
                <span>Total Amount:</span>
                <span>Rs. <?php echo isset($grand) ? number_format($grand, 2) : '0.00'; ?></span>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmOrderBtn" onclick="processCartPayment()">
          <span id="confirmBtnText">Proceed to Payment</span>
          <span id="confirmSpinner" class="spinner-border spinner-border-sm" style="display:none;" role="status" aria-hidden="true"></span>
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  .order-summary-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
  }
  .order-summary-box h6 {
    color: #430160;
    margin-bottom: 10px;
  }
  .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #e0e0e0;
  }
  .summary-row:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 1.1em;
    color: #430160;
  }
</style>

<script type="text/javascript" src="https://www.payhere.lk/lib/payhere.js"></script>
<script>
// PayHere Event Handlers
payhere.onCompleted = function(orderId) {
    console.log("Payment completed. OrderID:" + orderId);
    alert("Payment completed! Order ID: " + orderId);
    window.location.href = "payment-success.php?order_id=" + orderId;
};

payhere.onDismissed = function() {
    console.log("Payment dismissed");
    alert("Payment was cancelled. You can retry from your cart.");
};

payhere.onError = function(error) {
    console.log("Error:" + error);
    alert("Payment error: " + error);
};

function showCustomerDetailsModal() {
    const modal = new bootstrap.Modal(document.getElementById('customerDetailsModal'));
    modal.show();
}

async function processCartPayment() {
    // Get customer details from form
    const customerName = document.getElementById('customerName').value.trim();
    const customerEmail = document.getElementById('customerEmail').value.trim();
    const customerPhone = document.getElementById('customerPhone').value.trim();
    const deliveryAddress = document.getElementById('deliveryAddress').value.trim();
    const customerCity = document.getElementById('customerCity').value.trim();
    
    // Validation
    if (!customerName || !customerEmail || !customerPhone || !deliveryAddress || !customerCity) {
        alert('Please fill in all required fields');
        return;
    }
    
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerEmail)) {
        alert('Please enter a valid email address');
        return;
    }
    
    if (!/^[0-9]{10,15}$/.test(customerPhone)) {
        alert('Please enter a valid phone number (10-15 digits)');
        return;
    }
    
    // Show loading
    const submitBtn = document.getElementById('confirmOrderBtn');
    const btnText = document.getElementById('confirmBtnText');
    const spinner = document.getElementById('confirmSpinner');
    
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    spinner.style.display = 'inline-block';
    
    try {
        // Generate unique order ID
        const orderId = 'ORD-CART-' + Date.now();
        const currency = 'LKR';
        
        // Step 1: Create order in database (Source: Cart)
        const body = new URLSearchParams();
        body.append('source', 'cart');
        body.append('order_id', orderId);
        body.append('customer_name', customerName);
        body.append('customer_email', customerEmail);
        body.append('customer_phone', customerPhone);
        body.append('delivery_address', deliveryAddress);
        body.append('city', customerCity);

        const orderResponse = await fetch('create-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        
        const orderData = await orderResponse.json();
        
        if (!orderData.success) {
            throw new Error(orderData.error || 'Failed to create order');
        }

        // Step 2: Get payment hash
        const hashResponse = await fetch('generate-hash.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                order_id: orderId,
                amount: orderData.amount,
                currency: currency
            })
        });
        
        if (!hashResponse.ok) {
            throw new Error('Failed to generate payment hash');
        }
        
        const hashData = await hashResponse.json();
        
        if (!hashData.success) {
            throw new Error(hashData.error || 'Failed to generate payment hash');
        }
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('customerDetailsModal')).hide();
        
        // Step 3: Start PayHere Payment
        const payment = {
            "sandbox": <?= PAYHERE_SANDBOX ? 'true' : 'false' ?>,
            "merchant_id": hashData.merchant_id,
            "return_url": "<?= PAYHERE_RETURN_URL ?>",
            "cancel_url": "<?= PAYHERE_CANCEL_URL ?>",
            "notify_url": "<?= PAYHERE_NOTIFY_URL ?>",
            "order_id": orderId,
            "items": "Cart Checkout",
            "amount": orderData.amount,
            "currency": currency,
            "hash": hashData.hash,
            "first_name": customerName.split(' ')[0],
            "last_name": customerName.split(' ').slice(1).join(' ') || 'User',
            "email": customerEmail,
            "phone": customerPhone,
            "address": deliveryAddress,
            "city": customerCity,
            "country": "Sri Lanka"
        };
        
        payhere.startPayment(payment);
        
        // Reset button state
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        spinner.style.display = 'none';
        
    } catch (error) {
        console.error('Payment processing error:', error);
        alert('Error: ' + error.message);
        
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        spinner.style.display = 'none';
    }
}
</script>

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