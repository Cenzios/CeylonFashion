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
            <div class="fw-bold text-primary fs-5" id="subtotal-<?php echo $row['cart_id']; ?>">Rs. <?php echo number_format($subtotal,2); ?></div>

            <!-- Quantity Update -->
            <div class="qty-controls d-flex align-items-center gap-2">
              <?php if ($isLoggedIn): ?>
                <button onclick="updateQty(<?php echo $row['cart_id']; ?>, 'decrease')" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-dash"></i>
                </button>
                <span class="qty-display" id="qty-<?php echo $row['cart_id']; ?>"><?php echo (int)$row['quantity']; ?></span>
                <button onclick="updateQty(<?php echo $row['cart_id']; ?>, 'increase')" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-plus"></i>
                </button>
              <?php else: ?>
                <!-- For guest, we pass cart_id (which is product_id_fabric_id_... key) -->
                <button onclick="updateQty('<?php echo $row['cart_id']; ?>', 'decrease')" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-dash"></i>
                </button>
                <span class="qty-display" id="qty-<?php echo $row['cart_id']; ?>"><?php echo (int)$row['quantity']; ?></span>
                <button onclick="updateQty('<?php echo $row['cart_id']; ?>', 'increase')" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-plus"></i>
                </button>
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
          <h4 class="mb-0 text-primary" id="grand-total">Rs. <?php echo number_format($grand,2); ?></h4>
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
              <div class="invalid-feedback-custom"></div>
            </div>
            <div class="col-md-6 mb-3">
              <label for="customerEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="customerEmail" 
                     value="<?= htmlspecialchars($_SESSION['email'] ?? ''); ?>" 
                     required>
              <div class="invalid-feedback-custom"></div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="customerPhone" class="form-label">Phone Number <span class="text-danger">*</span></label>
              <input type="tel" class="form-control" id="customerPhone" 
                     value="<?= htmlspecialchars($_SESSION['phone'] ?? ''); ?>" 
                     placeholder="0771234567"
                     required>
              <div class="invalid-feedback-custom"></div>
              <small class="text-muted">9-12 digits only</small>
            </div>
            <div class="col-md-6 mb-3">
              <label for="customerCity" class="form-label">City <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="customerCity" 
                     value="<?= htmlspecialchars($_SESSION['city'] ?? ''); ?>" 
                     required>
              <div class="invalid-feedback-custom"></div>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="deliveryAddress" class="form-label">Delivery Address <span class="text-danger">*</span></label>
            <textarea class="form-control" id="deliveryAddress" rows="3" required><?= htmlspecialchars($_SESSION['address'] ?? ''); ?></textarea>
            <div class="invalid-feedback-custom"></div>
            <small class="text-muted">Street address, house/apartment number</small>
          </div>
          
          <div class="order-summary-box">
            <h6>Cart Summary</h6>
            <div class="summary-row">
                <span>Total Items:</span>
                <span id="summary-total-items"><?php echo array_sum(array_column($items, 'quantity')); ?></span>
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
  
  /* Validation Styles */
  .invalid-feedback-custom {
    color: red !important;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    display: none;
  }
  
  .form-control.is-invalid-custom {
    border-color: #dc3545 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    padding-right: calc(1.5em + 0.75rem);
  }
  
  .form-control.is-invalid-custom ~ .invalid-feedback-custom {
    display: block;
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

function validateCartField(input, type) {
    const value = input.value.trim();
    let errorMsg = '';
    
    const errorDiv = input.nextElementSibling.classList.contains('invalid-feedback-custom') 
                   ? input.nextElementSibling 
                   : null;

    if (!errorDiv) return true;

    if (type === 'name') {
        if (!value) errorMsg = 'Full Name is required';
        else if (!/^[A-Za-z\s]+$/.test(value)) errorMsg = 'Full Name must contain only letters';
        else if (value.length < 2) errorMsg = 'Name must be at least 2 characters';
    } else if (type === 'email') {
        if (!value) errorMsg = 'Email is required';
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) errorMsg = 'Please enter a valid email address';
    } else if (type === 'phone') {
        if (!value) errorMsg = 'Phone Number is required';
        else if (!/^[0-9]{9,12}$/.test(value)) errorMsg = 'Phone must be 9-12 digits';
    } else if (type === 'city') {
        if (!value) errorMsg = 'City is required';
        else if (!/^[A-Za-z\s]+$/.test(value)) errorMsg = 'City must contain only letters';
    } else if (type === 'address') {
        if (!value) errorMsg = 'Address is required';
    }

    if (errorMsg) {
        input.classList.add('is-invalid-custom');
        errorDiv.textContent = errorMsg;
        return false;
    } else {
        input.classList.remove('is-invalid-custom');
        errorDiv.textContent = '';
        return true;
    }
}

// Setup live validation
document.addEventListener('DOMContentLoaded', function() {
    const fields = [
        { id: 'customerName', type: 'name' },
        { id: 'customerEmail', type: 'email' },
        { id: 'customerPhone', type: 'phone' },
        { id: 'customerCity', type: 'city' },
        { id: 'deliveryAddress', type: 'address' }
    ];

    fields.forEach(f => {
        const input = document.getElementById(f.id);
        if (input) {
            input.addEventListener('input', () => validateCartField(input, f.type));
            input.addEventListener('blur', () => validateCartField(input, f.type));
        }
    });
});

async function processCartPayment() {
    // Get customer details from form
    const nameInput = document.getElementById('customerName');
    const emailInput = document.getElementById('customerEmail');
    const phoneInput = document.getElementById('customerPhone');
    const cityInput = document.getElementById('customerCity');
    const addrInput = document.getElementById('deliveryAddress');
    
    // Validate all
    const v1 = validateCartField(nameInput, 'name');
    const v2 = validateCartField(emailInput, 'email');
    const v3 = validateCartField(phoneInput, 'phone');
    const v4 = validateCartField(cityInput, 'city');
    const v5 = validateCartField(addrInput, 'address');
    
    if (!v1 || !v2 || !v3 || !v4 || !v5) {
        return; 
    }

    const customerName = nameInput.value.trim();
    const customerEmail = emailInput.value.trim();
    const customerPhone = phoneInput.value.trim();
    const deliveryAddress = addrInput.value.trim();
    const customerCity = cityInput.value.trim();
    
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

function updateQty(cartId, action) {
    fetch(`cart-update.php?id=${encodeURIComponent(cartId)}&action=${action}&ajax=1`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update Quantity Display
            document.getElementById(`qty-${cartId}`).innerText = data.new_qty;
            
            // Update Subtotal Display
            document.getElementById(`subtotal-${cartId}`).innerText = 'Rs. ' + data.new_subtotal;
            
            // Update Grand Total Display
            document.getElementById(`grand-total`).innerText = 'Rs. ' + data.grand_total;
            
            // Update Cart Summary Total Items
            const summaryItems = document.getElementById('summary-total-items');
            if (summaryItems && data.total_qty_sum !== undefined) {
                summaryItems.innerText = data.total_qty_sum;
            }
            
            // Update Navbar Cart Badge
            const badge = document.getElementById('cartBadge');
            if (badge) {
                if (data.cart_count > 0) {
                   badge.innerText = data.cart_count;
                   badge.style.display = 'inline-block';
                } else {
                   badge.style.display = 'none';
                }
            }
            
            // Optional: If qty changed to 0 (though backend logic keeps at 1 min usually), 
            // reload or remove element. Current logic min is 1.

        } else {
            alert(data.error || 'Failed to update quantity');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
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