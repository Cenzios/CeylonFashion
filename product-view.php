<?php
// ------------------------------
// product-view.php (FIXED - Enhanced UI)
// ------------------------------
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
require_once 'config.php';

// Include component handlers
require_once 'components/reviews-handler.php';

// Include Guest Cart Library
if (file_exists('lib/guest-cart.php')) {
    require_once 'lib/guest-cart.php';
}

// ------------------------------
// Helpers
// ------------------------------
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function csrf_token() {
  if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
  return $_SESSION['csrf_token'];
}
function csrf_verify($token) {
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}
function redirect_self(array $extra = []) {
  $params = array_merge($_GET, $extra);
  $qs = http_build_query($params);
  header("Location: ".$_SERVER['PHP_SELF'].'?'.$qs);
  exit;
}

// ------------------------------
// Auth flags
// ------------------------------
$isLoggedIn = isset($_SESSION['username']);
$isAdmin    = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
$currentUser = $isLoggedIn ? $_SESSION['username'] : null;

// ------------------------------
// Get Product ID
// ------------------------------
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  http_response_code(400);
  die("Invalid product ID.");
}
$product_id = (int)$_GET['id'];

// ------------------------------
// Fetch Product
// ------------------------------
$stmt = $mysqli->prepare("SELECT id, product_name, product_code, product_img1, product_img2, product_img3, product_img4, product_desc, category FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
  http_response_code(404);
  die("Product not found.");
}

// Parse images
$images = [];
for ($i = 1; $i <= 4; $i++) {
  $imgField = 'product_img' . $i;
  if (!empty($product[$imgField])) {
    $images[] = trim($product[$imgField]);
  }
}
if (empty($images)) {
  $images[] = 'placeholder.png';
}
while (count($images) < 4) $images[] = $images[count($images)-1];

// ------------------------------
// Fetch Product Color
// ------------------------------
$stmt = $mysqli->prepare("SELECT color_name, color_code FROM product_colors WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$colorResult = $stmt->get_result();
$productColor = $colorResult->fetch_assoc();
$stmt->close();

// ------------------------------
// Fetch Fabrics
// ------------------------------
$stmt = $mysqli->prepare("SELECT id, fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$fabricsResult = $stmt->get_result();
$fabrics = [];
$totalAvailableQty = 0;
while ($f = $fabricsResult->fetch_assoc()) {
  $fabrics[] = $f;
  $totalAvailableQty += (int)$f['fabric_qty'];
}
$stmt->close();

// ------------------------------
// Fetch Available Sizes
// ------------------------------
$stmt = $mysqli->prepare("SELECT size FROM product_sizes WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$sizeResult = $stmt->get_result();
$availableSizes = [];
while ($row = $sizeResult->fetch_assoc()) {
  $availableSizes[] = $row['size'];
}
$stmt->close();

// Fallback for legacy products (if no sizes defined, show all)
if (empty($availableSizes)) {
  $availableSizes = ['XS','S','M','L','XL'];
}
// Sort sizes logically
$sizeOrder = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
usort($availableSizes, function($a, $b) use ($sizeOrder) {
    return ($sizeOrder[$a] ?? 99) <=> ($sizeOrder[$b] ?? 99);
});

// ------------------------------
// Handle POST (Reviews / Q&A) - Using Component Handler
// ------------------------------
handle_reviews_post($mysqli, $product_id, $currentUser, $isLoggedIn);

// ------------------------------
// Aggregate: Avg Rating & Counts
// ------------------------------
$reviewStats = get_review_stats($mysqli, $product_id);
$avg = $reviewStats['average'];
$totalReviews = $reviewStats['total'];

// ------------------------------
// Pagination
// ------------------------------
$perPage = 5;
$pageR = isset($_GET['pageR']) && ctype_digit($_GET['pageR']) ? max(1, (int)$_GET['pageR']) : 1;
$offR  = ($pageR - 1) * $perPage;
$pageQ = isset($_GET['pageQ']) && ctype_digit($_GET['pageQ']) ? max(1, (int)$_GET['pageQ']) : 1;
$offQ  = ($pageQ - 1) * $perPage;

$reviewsCount = get_reviews_count($mysqli, $product_id);
$questionsCount = get_questions_count($mysqli, $product_id);

// Fetch reviews and questions using component functions
$reviews = fetch_reviews($mysqli, $product_id, $perPage, $offR);
$questions = fetch_questions($mysqli, $product_id, $perPage, $offQ);

// Check if product is in wishlist
$inWishlist = false;
if ($isLoggedIn) {
    if (function_exists('getUserWishlistCount')) {
         // We can query directly
         $stmt = $mysqli->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
         $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
         $stmt->execute();
         $inWishlist = $stmt->get_result()->num_rows > 0;
         $stmt->close();
    }
} else {
    if (function_exists('isInGuestWishlist')) {
        $inWishlist = isInGuestWishlist($product_id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>

<?php include 'includes/navbar.php'; ?>
<script type="text/javascript" src="https://www.payhere.lk/lib/payhere.js"></script>
<?php include 'includes/scripts.php'; ?>

<div class="product-container">
  <div class="product-top">
    
    <!-- Left: Main Image with thumbnails on the right -->
    <div class="left-col">
      <div class="image-layout">
        <div class="main-image">
          <img id="mainProductImage" src="images/products/<?= e($images[0]); ?>" alt="<?= e($product['product_name']); ?>">
        </div>
        
        <!-- Thumbnails on right side -->
        <div class="thumbs-col">
          <?php foreach (array_slice($images, 0, 3) as $idx => $img): ?>
            <div class="thumb-item <?= $idx === 0 ? 'active' : ''; ?>" data-img="<?= e($img); ?>">
              <img src="images/products/<?= e($img); ?>" alt="<?= e($product['product_name']); ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Right: Product Details (Enhanced Layout) -->
    <div class="right-col">
      <?php if ($isAdmin): ?>
        <a href="admin/edit-product.php?id=<?= $product_id; ?>" class="edit-pill">✏️ Edit</a>
      <?php endif; ?>

      <h1 class="title"><?= e($product['product_name']); ?></h1>

      <!-- SKU Info -->
      <div class="product-meta">
        <div class="meta-item">
          <span class="meta-label">Availability:</span>
          <span class="meta-value stock-badge">
            <?php if ($totalAvailableQty > 0): ?>
              <span class="in-stock">In Stock</span>
            <?php else: ?>
              <span class="out-stock">Out of Stock</span>
            <?php endif; ?>
          </span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Product Type:</span>
          <span class="meta-value"><?= e($product['category'] ?? 'Men T Shirt'); ?></span>
        </div>
      </div>

      <!-- Size Guide Button -->
      <button type="button" class="size-guide-btn" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
          <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319z"/>
        </svg>
        Size Guide
      </button>

      <!-- Size Selection -->
      <div class="option-section">
        <label class="section-label">
          Size: <span class="selected-value" id="selectedSizeDisplay">M</span>
        </label>
        <div class="size-buttons" id="sizeList">
          <?php foreach ($availableSizes as $s): ?>
            <button type="button" 
                    class="size-btn <?= $s === 'M' ? 'selected' : ''; ?>" 
                    data-size="<?= e($s); ?>">
              <?= e($s); ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Fabric Selection -->
      <?php if (!empty($fabrics)): ?>
      <div class="option-section">
        <label class="section-label">Select Fabric type</label>
        <div class="fabric-buttons" id="fabricList">
          <?php foreach ($fabrics as $f): ?>
            <button type="button"
                    class="fabric-btn"
                    data-fabric-id="<?= (int)$f['id']; ?>"
                    data-price="<?= number_format((float)$f['fabric_price'], 2, '.', ''); ?>"
                    data-qty="<?= (int)$f['fabric_qty']; ?>">
              <?= e($f['fabric_type']); ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Color Display -->
      <?php if ($productColor): ?>
      <div class="option-section">
        <label class="section-label">Color:</label>
        <div class="color-display">
          <div class="color-swatch" style="background-color: <?= e($productColor['color_code']); ?>;"></div>
          <span class="color-name"><?= e($productColor['color_name']); ?></span>
        </div>
      </div>
      <?php endif; ?>

      <!-- Subtotal Section -->
      <div class="subtotal-section">
        <div class="subtotal-row">
          <span class="subtotal-label">Subtotal:</span>
          <span class="subtotal-price" id="subtotalPrice">Rs 0.00</span>
        </div>
      </div>

      <!-- Action Form -->
      <form id="addToCartForm" action="cart-add.php" method="GET" onsubmit="return prepareCartForm(this);">
        <input type="hidden" name="id" value="<?= $product_id; ?>">
        <input type="hidden" name="fabric_id" id="formFabricId" value="">
        <input type="hidden" name="color" id="formColor" value="">
        <input type="hidden" name="size" id="formSize" value="M">
        
        <label class="section-label mb-2">Quantity:</label>
        
        <div class="actions-row">
          <!-- Quantity Selector -->
          <div class="quantity-controls">
            <button type="button" class="qty-btn" id="qtyMinus">−</button>
            <input type="number" name="qty" id="qtyField" value="1" min="1" class="qty-input" readonly>
            <button type="button" class="qty-btn" id="qtyPlus">+</button>
          </div>

          <?php if (!$isAdmin): ?>
            <!-- Add to Cart -->
            <button type="submit" class="btn-add-cart">ADD TO CART</button>
            
            <!-- Wishlist Button -->
            <button type="button" class="btn-wishlist-circle <?= $inWishlist ? 'active' : '' ?>" id="wishlistBtn" onclick="toggleProductWishlist(<?= $product_id; ?>)" title="<?= $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?>">
              <svg width="20" height="20" fill="<?= $inWishlist ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
              </svg>
            </button>
          <?php endif; ?>
        </div>

        <?php if (!$isAdmin): ?>
        <div class="buy-now-row mt-3">
          <?php if ($isLoggedIn): ?>
            <button type="button" class="btn-buy-now buy-now-btn" data-product-id="<?= $product_id; ?>" data-product-name="<?= htmlspecialchars($product['product_name']); ?>">BUY IT NOW</button>
          <?php else: ?>
            <button type="button" class="btn-buy-now" onclick="showLoginModal(window.location.href)">BUY IT NOW</button>
          <?php endif; ?>
        </div>
        <div class="delivery-banner mt-3" data-bs-toggle="modal" data-bs-target="#deliveryTermsModal">
          <div class="d-flex align-items-center gap-2">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="delivery-icon">
              <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-1.1 0-2 .9-2 2v4c0 1.1.9 2 2 2h1" stroke="#8b5a2b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 17h6" stroke="#8b5a2b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="7" cy="17" r="2" stroke="#8b5a2b" stroke-width="2"/>
              <circle cx="17" cy="17" r="2" stroke="#8b5a2b" stroke-width="2"/>
            </svg>
            <span class="delivery-text">Delivery within 5 – 12 Business Days</span>
          </div>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="info-icon-q">
            <circle cx="12" cy="12" r="10" stroke="#a1a1aa" stroke-width="1.5"/>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="#a1a1aa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 17h.01" stroke="#a1a1aa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <?php endif; ?>

      </form>

    </div>
  </div>

  <!-- Reviews & Q&A Section -->
  <div class="reviews-section">
    <div class="tabs-header">
      <div class="tab-buttons">
        <button class="tab-button active" id="reviewsTabBtn">Reviews (<?= (int)$reviewsCount; ?>)</button>
        <!-- <button class="tab-button" id="qnaTabBtn">Q&amp;A (<?= (int)$questionsCount; ?>)</button> -->
      </div>
      <button class="add-btn" id="openDialogBtn" <?= $isLoggedIn ? '' : 'onclick="showLoginModal(window.location.href); return false;"' ?>>+ Add</button>
    </div>

    <?php 
    // Include Reviews Component
    include 'components/reviews-section.php'; 
    
    // Include Q&A Component
    // include 'components/qna-section.php'; 
    ?>
  </div>
</div>

<?php 
// Include Review/Question Dialog Component
include 'components/review-dialog.php'; 
?>

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
            <h6>Order Summary</h6>
            <div id="orderSummaryContent"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmOrderBtn" onclick="processPayment()">
          <span id="confirmBtnText">Proceed to Payment</span>
          <span id="confirmSpinner" class="spinner-border spinner-border-sm" style="display:none;" role="status" aria-hidden="true"></span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Login/Register Sidebars -->
<?php include 'includes/login-sidebar.php'; ?>
<!-- Size Guide Modal -->
<div class="modal fade" id="sizeGuideModal" tabindex="-1" aria-labelledby="sizeGuideLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="sizeGuideLabel">Size Grid</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table size-chart-table align-middle">
            <thead class="bg-light">
              <tr>
                <th scope="col" class="py-3">Size</th>
                <th scope="col" class="py-3">Bust (inches)</th>
                <th scope="col" class="py-3">Waist (inches)</th>
                <th scope="col" class="py-3">Hip (inches)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="fw-bold py-3">XS</td>
                <td class="py-3">32</td>
                <td class="py-3">24</td>
                <td class="py-3">34</td>
              </tr>
              <tr>
                <td class="fw-bold py-3">S</td>
                <td class="py-3">34</td>
                <td class="py-3">26</td>
                <td class="py-3">36</td>
              </tr>
              <tr>
                <td class="fw-bold py-3">M</td>
                <td class="py-3">36</td>
                <td class="py-3">28</td>
                <td class="py-3">38</td>
              </tr>
              <tr>
                <td class="fw-bold py-3">L</td>
                <td class="py-3">38</td>
                <td class="py-3">30</td>
                <td class="py-3">40</td>
              </tr>
              <tr>
                <td class="fw-bold py-3">XL</td>
                <td class="py-3">40</td>
                <td class="py-3">32</td>
                <td class="py-3">42</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delivery Terms Modal -->
<div class="modal fade" id="deliveryTermsModal" tabindex="-1" aria-labelledby="deliveryTermsLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="deliveryTermsLabel">Delivery Terms</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="mb-3 text-muted" style="font-size: 0.95rem;">
          At <strong>Ceylon Fashion</strong>, every piece is exclusively tailored to your order with great care and craftsmanship. To ensure the highest quality, please review our delivery terms below:
        </p>
        <ul class="delivery-terms-list">
          <li><strong>Tailoring Time:</strong> Each item is custom-made. Please allow <strong>5–12 working days</strong> for tailoring, depending on availability and design complexity.</li>
          <li><strong>Order Processing:</strong> Production begins only after full payment and confirmation of customization details.</li>
          <li><strong>Shipping:</strong> Local deliveries typically arrive within <strong>2–5 business days</strong> after dispatch.</li>
          <li><strong>Express Service:</strong> Urgent tailoring requests may be accommodated with an additional fee. Please contact our team for availability.</li>
          <li><strong>Unforeseen Delays:</strong> While we aim to deliver within the stated timeframes, delays may occur due to public holidays, fabric sourcing, or courier disruptions.</li>
          <li><strong>No Returns/Exchanges:</strong> As each garment is uniquely tailored, customized items are non-returnable.</li>
        </ul>
        <p class="mt-4 mb-1 text-muted" style="font-size: 0.95rem;">
          We appreciate your trust in <strong>Ceylon Fashion</strong>. Our team is committed to delivering you a one-of-a-kind piece, crafted with elegance and precision.
        </p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Login/Register Sidebars -->
<?php include 'includes/register-sidebar.php'; ?>

<?php include 'includes/footer.php'; ?>

<style>
  :root {
    --primary: #5a2d82;
    --accent: #7c3aed;
    --soft-bg: #fafbfd;
    --muted: #6c757d;
    --border: #e5e7eb;
    --success: #10b981;
  }
  
  body { 
    background: #fff; 
    margin: 0; 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #222; 
  }
  
  .product-container { 
    width: 100%;
    margin: 0;
    padding: 30px 40px;
    box-sizing: border-box;
  }

  /* Top Layout: Left wider, Right narrower */
  .product-top { 
    display: flex; 
    gap: 40px; 
    width: 100%;
  }

  .left-col { 
    flex: 0 0 62%;
    display: flex;
    flex-direction: column;
  }

  .right-col { 
    flex: 0 0 35%;
    padding: 0; 
    position: relative;
    display: flex;
    flex-direction: column;
  }

  /* Image layout with thumbnails on right */
  .image-layout {
    display: flex;
    gap: 15px;
    align-items: flex-start;
  }

  .main-image { 
    flex: 1;
    height: 650px; 
    border-radius: 12px; 
    overflow: hidden; 
    background: #fafafa; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    border: 1px solid #eee;
  }
  
  .main-image img { 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
  }

  /* Thumbnails vertical on right side */
  .thumbs-col { 
    display: flex; 
    flex-direction: column;
    gap: 15px;
    flex-shrink: 0;
  }
  
  .thumb-item { 
    width: 120px; 
    height: 200px; 
    border-radius: 8px; 
    overflow: hidden; 
    border: 2px solid #eee; 
    cursor: pointer; 
    background: #fafafa;
    transition: all 0.2s;
  }
  
  .thumb-item.active { 
    border-color: var(--accent); 
    box-shadow: 0 4px 12px rgba(124,58,237,0.3);
  }
  
  .thumb-item:hover {
    border-color: #bbb;
  }
  
  .thumb-item img { 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
  }
  
  .edit-pill { 
    position: absolute; 
    right: 0; 
    top: 0; 
    background: var(--primary); 
    color: #fff; 
    padding: 6px 12px; 
    border-radius: 4px; 
    font-size: 13px; 
    text-decoration: none; 
    z-index: 10;
  }

  .title { 
    color: #000; 
    margin: 0 0 16px 0; 
    font-size: 26px; 
    font-weight: 600; 
    line-height: 1.3;
  }

  /* Product Meta */
  .product-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
  }

  .meta-item {
    display: flex;
    align-items: center;
    font-size: 14px;
  }

  .meta-label {
    color: #666;
    margin-right: 8px;
    min-width: 110px;
  }

  .meta-value {
    color: #000;
    font-weight: 500;
  }

  .stock-badge .in-stock {
    color: var(--success);
    font-weight: 600;
  }

  .stock-badge .out-stock {
    color: #ef4444;
    font-weight: 600;
  }

  /* Size Guide Button */
  .size-guide-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f3f4f6;
    border: 1px solid var(--border);
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: #000;
    margin-bottom: 20px;
    transition: all 0.2s;
  }

  .size-guide-btn:hover {
    background: #e5e7eb;
  }

  /* Option Sections */
  .option-section {
    margin-bottom: 20px;
  }

  .section-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #000;
    margin-bottom: 10px;
  }

  .selected-value {
    font-weight: 700;
    color: var(--accent);
  }

  .mb-2 {
    margin-bottom: 10px;
  }

  /* Size Buttons */
  .size-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .size-btn {
    min-width: 48px;
    padding: 10px 16px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    color: #000;
    transition: all 0.2s;
  }

  .size-btn:hover {
    border-color: #999;
  }

  .size-btn.selected {
    background: #000;
    border-color: #000;
    color: #fff;
  }

  /* Fabric Buttons */
  .fabric-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .fabric-btn {
    padding: 10px 16px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
    color: #000;
    transition: all 0.2s;
  }

  .fabric-btn:hover {
    border-color: #999;
  }

  .fabric-btn.selected {
    background: #f0e6ff;
    border-color: var(--accent);
    color: var(--accent);
  }

  /* Color Display */
  .color-display {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .color-swatch {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid var(--border);
    box-shadow: inset 0 0 3px rgba(0,0,0,0.1);
  }

  .color-name {
    font-size: 14px;
    font-weight: 500;
    color: #000;
  }

  /* Subtotal Section */
  .subtotal-section {
    padding: 16px 0;
    margin-bottom: 20px;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
  }

  .subtotal-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .subtotal-label {
    font-size: 15px;
    font-weight: 600;
    color: #000;
  }

  .subtotal-price {
    font-size: 18px;
    font-weight: 700;
    color: #000;
  }

  /* FIXED: Actions Row Layout */
  .actions-row {
    display: flex;
    align-items: stretch;
    gap: 10px;
    width: 100%;
  }

  /* FIXED: Quantity Controls */
  .quantity-controls {
    display: flex;
    align-items: center;
    border: 1px solid #e5e7eb;
    border-radius: 8px; /* Softer corners */
    overflow: hidden;
    background: #fff;
    height: 48px;
    width: 140px; /* Fixed width for consistency */
    flex-shrink: 0;
  }
  
  .qty-btn {
    width: 40px !important;
    padding: 0;
    height: 100%;
    background: #fff;
    border: none;
    cursor: pointer;
    font-weight: 400;
    font-size: 20px;
    color: #555;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .qty-btn:hover { 
    background: #f9fafb;
    color: #000;
  }

  .qty-btn:active {
    background: #f3f4f6;
  }
  
  .qty-input {
    flex: 1;
    width: 100%;
    height: 100%;
    border: none;
    border-left: 1px solid #f3f4f6;
    border-right: 1px solid #f3f4f6;
    text-align: center;
    font-weight: 600;
    font-size: 16px;
    color: #000;
    background: #fff !important; /* Override generic readonly gray */
    opacity: 1;
    -moz-appearance: textfield;
    appearance: none;
    outline: none;
  }
  
  .qty-input:focus {
    outline: none;
  }
  
  .qty-input::-webkit-outer-spin-button,
  .qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }
  
  /* Size Guide Modal Styles */
  .size-chart-table {
    text-align: center;
    border: 1px solid #dee2e6;
  }
  .size-chart-table thead th {
    font-weight: 600;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
  }
  .size-chart-table td {
    font-size: 15px;
    color: #444;
  }
  .size-chart-table .fw-bold {
    color: #000;
  }
  
  /* FIXED: Add To Cart Button */
  .btn-add-cart {
    flex: 1;
    background: var(--primary);
    color: #fff;
    border: none;
    height: 48px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    min-width: 140px;
  }
  
  .btn-add-cart:hover {
    background: #4a1f6b;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(90, 45, 130, 0.3);
  }

  .btn-add-cart:active {
    transform: translateY(0);
  }
  
  /* FIXED: Wishlist Circle Button */
  .btn-wishlist-circle {
    width: 48px;
    height: 48px;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #333;
    transition: all 0.3s ease;
    flex-shrink: 0;
    padding: 0;
  }
  
  .btn-wishlist-circle:hover {
    border-color: var(--primary);
    background: #f9f5ff;
    transform: translateY(-1px);
  }
  
  .btn-wishlist-circle.active {
    background: #fff0f0;
    border-color: #ef4444;
    color: #ef4444;
  }

  .btn-wishlist-circle.active:hover {
    background: #ffe0e0;
  }
  
  .btn-wishlist-circle.active svg {
    fill: currentColor;
  }

  .btn-wishlist-circle svg {
    transition: all 0.3s ease;
  }

  .btn-wishlist-circle:hover svg {
    transform: scale(1.1);
  }
  
  /* Buy Now Row */
  .buy-now-row {
    width: 100%;
  }

  .mt-3 {
    margin-top: 16px;
  }

  .btn-buy-now {
    width: 100%;
    height: 48px;
    background: #fff;
    border: 2px solid var(--primary);
    color: var(--primary);
    border-radius: 6px;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
  }
  
  .btn-buy-now:hover {
    background: var(--primary);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(90, 45, 130, 0.3);
  }

  .btn-buy-now:active {
    transform: translateY(0);
  }

  /* Delivery Banner */
  .delivery-banner {
    background: #fffff0; /* Light yellow from image */
    border-radius: 6px;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
  }
  
  .delivery-banner:hover {
    background: #fdfde0;
    border-color: #e5e7eb;
  }

  .delivery-text {
    font-size: 14px;
    color: #8b5a2b; /* Brownish tone from image */
    font-weight: 500;
  }
  
  .delivery-icon path, .delivery-icon circle {
    stroke: #8b5a2b;
  }

  .info-icon-q circle, .info-icon-q path {
    stroke: #aaa; /* Muted gray for question mark */
  }

  /* Delivery Modal List */
  .delivery-terms-list {
    padding-left: 20px;
    font-size: 0.95rem;
    color: #444;
    line-height: 1.6;
  }
  .delivery-terms-list li {
    margin-bottom: 12px;
  }
  .delivery-terms-list strong {
    color: #000;
  }

  /* Reviews Section */
  .reviews-section {
    margin-top: 40px;
    background: #fff;
    padding: 30px 40px;
    border-top: 1px solid #eee;
  }

  .tabs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
  }

  .tab-buttons {
    display: flex;
    gap: 10px;
  }

  .tab-button {
    border: none;
    background: none;
    padding: 10px 20px;
    cursor: pointer;
    font-weight: 600;
    color: #666;
    border-bottom: 3px solid transparent;
    margin-bottom: -12px;
    transition: all 0.2s;
  }

  .tab-button.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
  }

  .add-btn {
    background: var(--success);
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .add-btn:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  }

  .order-summary-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
  }
  
  .order-summary-box h6 {
    color: var(--primary);
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
    color: var(--primary);
    margin-top: 10px;
    padding-top: 10px;
    border-top: 2px solid var(--primary);
  }

  /* Responsive */
  @media (max-width: 1200px) {
    .product-container {
      padding: 20px;
    }
    
    .product-top {
      gap: 30px;
    }
    
    .left-col {
      flex: 0 0 58%;
    }

    .right-col {
      flex: 0 0 38%;
    }
  }

  @media (max-width: 968px) {
    .product-top {
      flex-direction: column;
    }

    .left-col,
    .right-col {
      flex: unset;
      width: 100%;
    }

    .main-image {
      height: 550px;
    }

    .thumb-item {
      width: 100px;
      height: 170px;
    }
  }

  @media (max-width: 640px) {
    .product-container {
      padding: 15px;
    }

    .image-layout {
      flex-direction: column;
    }

    .thumbs-col {
      flex-direction: row;
      width: 100%;
      justify-content: center;
    }

    .thumb-item {
      width: 90px;
      height: 110px;
    }

    .actions-row {
      flex-wrap: wrap;
    }

    .quantity-controls {
      width: 100%;
      margin-bottom: 10px;
    }

    .btn-add-cart {
      width: calc(100% - 58px);
    }

    .btn-wishlist-circle {
      width: 48px;
    }
    
    .main-image {
      height: 400px;
    }
    
    .reviews-section {
      padding: 20px 15px;
    }

    .title {
      font-size: 22px;
    }

    .subtotal-price {
      font-size: 16px;
    }
  }

  /* FIXED: Review Dialog Styles */
  .dialog-overlay {
    display: none;
    position: fixed;
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    z-index: 2000;
  }
  
  .dialog-box {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  }

  .dialog-box h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 20px;
    font-weight: 700;
    color: #333;
  }

  .dialog-box label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #555;
  }

  .dialog-box select,
  .dialog-box textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 20px;
    font-family: inherit;
  }

  .dialog-box .cancel-btn {
    background: #f3f4f6;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    color: #555;
    transition: all 0.2s;
  }

  .dialog-box .cancel-btn:hover {
    background: #e5e7eb;
    color: #333;
  }
  /* Lightbox Styles */
  .lightbox-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.95);
    z-index: 10000;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    backdrop-filter: blur(5px);
  }
  .lightbox-content-wrapper {
    position: relative;
    width: 90%;
    height: 80%;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
  }
  .lightbox-img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.2s ease-out;
    cursor: grab;
    transform-origin: center center;
  }
  .lightbox-img:active {
    cursor: grabbing;
  }
  .lightbox-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: #fff;
    font-size: 40px;
    cursor: pointer;
    z-index: 10002;
    line-height: 1;
    transition: color 0.2s;
  }
  .lightbox-close:hover { color: var(--accent); }
  
  .lightbox-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    color: #fff;
    font-size: 40px;
    cursor: pointer;
    padding: 15px;
    z-index: 10002;
    user-select: none;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }
  .lightbox-nav-btn:hover {
    background: rgba(255,255,255,0.3);
    color: var(--accent);
  }
  .lightbox-prev { left: 30px; }
  .lightbox-next { right: 30px; }
  
  .lightbox-zoom-controls {
    position: absolute;
    bottom: 30px;
    display: flex;
    gap: 15px;
    z-index: 10002;
    background: rgba(0,0,0,0.5);
    padding: 10px 20px;
    border-radius: 30px;
  }
  .zoom-btn {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.5);
    color: #fff;
    padding: 6px 15px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .zoom-btn:hover {
    background: rgba(255,255,255,0.2);
    border-color: #fff;
  }
</style>

<script>
  // Thumbnail click handler
  const thumbItems = document.querySelectorAll('.thumb-item');
  const mainImg = document.getElementById('mainProductImage');

  thumbItems.forEach(t => {
    t.addEventListener('click', function(){
      const img = this.getAttribute('data-img');
      if (img) mainImg.src = 'images/products/' + img;

      document.querySelectorAll('.thumb-item').forEach(x => x.classList.remove('active'));
      this.classList.add('active');
    });
  });

  // Update prices function
  function updatePrices() {
    const selectedFabric = document.querySelector('.fabric-btn.selected');
    const qtyField = document.getElementById('qtyField');
    const subtotalDisplay = document.getElementById('subtotalPrice');

    if (selectedFabric) {
      const basePrice = parseFloat(selectedFabric.dataset.price) || 0;
      const qty = parseInt(qtyField.value) || 1;
      const total = basePrice * qty;

      if (subtotalDisplay) {
        subtotalDisplay.textContent = 'Rs ' + total.toFixed(2);
      }
    }
  }

  // Fabric selection
  const fabricBtns = document.querySelectorAll('.fabric-btn');
  const formFabricId = document.getElementById('formFabricId');

  fabricBtns.forEach(b => {
    b.addEventListener('click', function(){
      fabricBtns.forEach(x => x.classList.remove('selected'));
      this.classList.add('selected');
      formFabricId.value = this.dataset.fabricId || '';
      updatePrices();
    });
  });

  // Auto-select first fabric
  if (fabricBtns.length > 0) {
    fabricBtns[0].click();
  }

  // Size selection
  const sizeBtns = document.querySelectorAll('.size-btn');
  const selectedSizeDisplay = document.getElementById('selectedSizeDisplay');
  
  sizeBtns.forEach(s => s.addEventListener('click', function(){
    sizeBtns.forEach(x => x.classList.remove('selected'));
    this.classList.add('selected');
    const sizeValue = this.dataset.size || '';
    document.getElementById('formSize').value = sizeValue;
    if (selectedSizeDisplay) {
      selectedSizeDisplay.textContent = sizeValue;
    }
  }));

  // Quantity handlers
  const qtyMinus = document.getElementById('qtyMinus');
  const qtyPlus = document.getElementById('qtyPlus');
  const qtyField = document.getElementById('qtyField');

  if (qtyMinus) {
    qtyMinus.addEventListener('click', () => {
      const cur = Math.max(1, parseInt(qtyField.value || 1) - 1);
      qtyField.value = cur;
      updatePrices();
    });
  }

  if (qtyPlus) {
    qtyPlus.addEventListener('click', () => {
      const cur = Math.max(1, parseInt(qtyField.value || 1) + 1);
      qtyField.value = cur;
      updatePrices();
    });
  }

  // Form validation & AJAX Add to Cart
  function prepareCartForm(form) {
    if (!form.fabric_id.value) {
      alert('Please select a fabric type.');
      return false;
    }
    if (!form.size.value) {
      alert('Please select a size.');
      return false;
    }
    if (!form.color.value) {
      form.color.value = 'default';
    }
    const qtyInput = document.getElementById('qtyField');
    if (parseInt(qtyInput.value) < 1) qtyInput.value = 1;

    // AJAX Submission
    const formData = new FormData(form);
    const params = new URLSearchParams(formData).toString();

    fetch('cart-add.php?' + params + '&ajax=1')
      .then(res => res.json())
      .then(data => {
        if (data.status === 'ok') {
          if (typeof updateBadge === 'function' && data.cartCount !== undefined) {
             updateBadge('cartBadge', data.cartCount);
          }
          alert(data.message || 'Product added to cart successfully!');
        } else {
          alert(data.message || 'Failed to add to cart.');
        }
      })
      .catch(err => {
        console.error(err);
        alert('Network error. Please try again.');
      });

    return false;
  }

  // Wishlist toggle
  function toggleWishlist(productId) {
    const btn = document.getElementById('wishlistBtn');
    if (btn) {
      fetch('wishlist-toggle.php?id=' + productId)
        .then(r => r.json())
        .then(data => {
          if (data.status === 'ok') {
            btn.classList.toggle('active');
            const wishlistBadge = document.querySelector('#wishlistIcon + .badge');
            if (wishlistBadge) {
              const currentCount = parseInt(wishlistBadge.textContent) || 0;
              if (data.action === 'added') {
                wishlistBadge.textContent = currentCount + 1;
                wishlistBadge.style.display = 'inline-block';
              } else {
                const newCount = Math.max(0, currentCount - 1);
                if (newCount > 0) {
                  wishlistBadge.textContent = newCount;
                } else {
                  wishlistBadge.style.display = 'none';
                }
              }
            }
          }
        })
        .catch(() => {});
    }
  }

  // Tabs
  const reviewsTabBtn = document.getElementById('reviewsTabBtn');
  const qnaTabBtn = document.getElementById('qnaTabBtn');
  const reviewsTab = document.getElementById('reviewsTab');
  const qnaTab = document.getElementById('qnaTab');
  const dialogOverlay = document.getElementById('dialogOverlay');
  const openDialogBtn = document.getElementById('openDialogBtn');
  const closeDialogBtn = document.getElementById('closeDialogBtn');
  const reviewForm = document.getElementById('reviewForm');
  const questionForm = document.getElementById('questionForm');
  let currentTab = 'reviews';

  function setTab(tab) {
    currentTab = tab;
    if (tab === 'reviews') {
      reviewsTabBtn.classList.add('active');
      qnaTabBtn.classList.remove('active');
      reviewsTab.style.display = 'block';
      qnaTab.style.display = 'none';
    } else {
      qnaTabBtn.classList.add('active');
      reviewsTabBtn.classList.remove('active');
      reviewsTab.style.display = 'none';
      qnaTab.style.display = 'block';
    }
  }

  if (reviewsTabBtn) reviewsTabBtn.onclick = () => setTab('reviews');
  if (qnaTabBtn) qnaTabBtn.onclick = () => setTab('qna');

  <?php if ($isLoggedIn): ?>
  if (openDialogBtn) {
    openDialogBtn.onclick = () => {
      dialogOverlay.style.display = 'flex';
      
      const reviewInputs = reviewForm.querySelectorAll('input, select, textarea, button');
      const questionInputs = questionForm.querySelectorAll('input, select, textarea, button');

      if (currentTab === 'reviews') {
        reviewForm.style.display = 'block';
        questionForm.style.display = 'none';
        
        // Enable Review inputs, Disable Question inputs
        reviewInputs.forEach(el => el.disabled = false);
        questionInputs.forEach(el => el.disabled = true);
      } else {
        reviewForm.style.display = 'none';
        questionForm.style.display = 'block';
        
        // Enable Question inputs, Disable Review inputs
        reviewInputs.forEach(el => el.disabled = true);
        questionInputs.forEach(el => el.disabled = false);
      }
    };
  }
  if (closeDialogBtn) {
    closeDialogBtn.onclick = () => dialogOverlay.style.display = 'none';
  }
  <?php endif; ?>

  // Login modal
  function showLoginModal() {
    try {
      const loginSidebar = new bootstrap.Offcanvas(document.getElementById('loginSidebar'));
      loginSidebar.show();
    } catch (e) {
      alert('Please login to continue.');
    }
  }

  // Order summary modal
  document.getElementById('customerDetailsModal').addEventListener('show.bs.modal', function() {
    const orderDetails = window.currentOrderDetails;
    if (orderDetails) {
      const summaryHtml = `
        <div class="summary-row">
          <span>Product:</span>
          <span>${orderDetails.productName}</span>
        </div>
        <div class="summary-row">
          <span>Size:</span>
          <span>${orderDetails.size}</span>
        </div>
        <div class="summary-row">
          <span>Quantity:</span>
          <span>${orderDetails.quantity}</span>
        </div>
        <div class="summary-row">
          <span>Price per unit:</span>
          <span>Rs. ${parseFloat(orderDetails.pricePerUnit).toFixed(2)}</span>
        </div>
        <div class="summary-row">
          <span>Total Amount:</span>
          <span>Rs. ${parseFloat(orderDetails.amount).toFixed(2)}</span>
        </div>
      `;
      document.getElementById('orderSummaryContent').innerHTML = summaryHtml;
    }
  });

  // Initialize prices on load
  updatePrices();
</script>

<script>
  // Wishlist toggle handler specific for this page
  function toggleProductWishlist(productId) {
    const btn = document.getElementById('wishlistBtn');
    const svg = btn.querySelector('svg');
    
    // Optimistic update
    const isAdded = btn.classList.toggle('active');
    if (isAdded) {
      svg.setAttribute('fill', 'currentColor');
      btn.setAttribute('title', 'Remove from Wishlist');
    } else {
      svg.setAttribute('fill', 'none');
      btn.setAttribute('title', 'Add to Wishlist');
    }

    fetch('wishlist-toggle.php?id=' + productId)
      .then(response => response.json())
      .then(data => {
        if (data.status === 'ok') {
           // Update badge
           if (typeof updateBadge === 'function' && data.wishlistCount !== undefined) {
              updateBadge('wishlistBadge', data.wishlistCount);
           }
        } else {
           // Revert if error
           btn.classList.toggle('active');
           if (!isAdded) {
              svg.setAttribute('fill', 'currentColor');
           } else {
              svg.setAttribute('fill', 'none');
           }
           console.error(data.message);
        }
      })
      .catch(err => {
         console.error(err);
         // Revert
         btn.classList.toggle('active');
      });
  }

  // PayHere Event Handlers
payhere.onCompleted = function(orderId) {
    console.log("Payment completed. OrderID:" + orderId);
    alert("Payment completed! Order ID: " + orderId);
    window.location.href = "payment-success.php?order_id=" + orderId;
};

payhere.onDismissed = function() {
    console.log("Payment dismissed");
    alert("Payment was cancelled. Please try again.");
};

payhere.onError = function(error) {
    console.log("Error:" + error);
    alert("Payment error: " + error);
};

// Buy Now button handler
document.addEventListener('DOMContentLoaded', function() {
    const buyNowBtns = document.querySelectorAll('.buy-now-btn');
    
    buyNowBtns.forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Check if user is logged in
            <?php if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])): ?>
                // Guest user: Set redirect URL and show login
                const currentUrl = window.location.href;
                const redirectInput = document.getElementById('loginRedirectUrl');
                if (redirectInput) {
                    redirectInput.value = currentUrl;
                }
                
                // Show login modal
                const loginSidebarEl = document.getElementById('loginSidebar');
                if (loginSidebarEl) {
                    const loginOffcanvas = new bootstrap.Offcanvas(loginSidebarEl);
                    loginOffcanvas.show();
                    
                    // Optional: Show message
                    const loginMsg = document.getElementById('loginMessage');
                    if (loginMsg) {
                        loginMsg.textContent = "Please login or register to complete your purchase.";
                        loginMsg.style.display = 'block';
                        loginMsg.className = 'login-message-error'; // Use error style for visibility or custom class
                    }
                } else {
                    // Fallback if modal not present
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(currentUrl);
                }
                return; // Stop execution
            <?php endif; ?>

            const fabricId = document.getElementById('formFabricId').value;
            const size = document.getElementById('formSize').value;
            const qty = parseInt(document.getElementById('qtyField').value) || 1;
            const productId = this.dataset.productId;
            
            if (!fabricId) {
                alert('Please select a fabric type.');
                return;
            }
            if (!size) {
                alert('Please select a size.');
                return;
            }
            
            const selectedFabric = document.querySelector('.fabric-btn.selected');
            if (!selectedFabric) {
                alert('Please select a fabric type.');
                return;
            }
            
            const pricePerUnit = parseFloat(selectedFabric.dataset.price) || 0;
            const amount = (pricePerUnit * qty).toFixed(2);
            
            if (amount <= 0) {
                alert('Invalid amount. Please check your selections.');
                return;
            }
            
            window.currentOrderDetails = {
                productId: productId,
                fabricId: fabricId,
                size: size,
                quantity: qty,
                amount: amount,
                pricePerUnit: pricePerUnit,
                productName: this.dataset.productName
            };
            
            showCustomerDetailsModal();
        });
    });
});

function showCustomerDetailsModal() {
    const modal = new bootstrap.Modal(document.getElementById('customerDetailsModal'));
    modal.show();
}

async function processPayment() {
    const orderDetails = window.currentOrderDetails;
    if (!orderDetails) {
        alert('Order details not found');
        return;
    }
    
    const customerName = document.getElementById('customerName').value.trim();
    const customerEmail = document.getElementById('customerEmail').value.trim();
    const customerPhone = document.getElementById('customerPhone').value.trim();
    const deliveryAddress = document.getElementById('deliveryAddress').value.trim();
    const customerCity = document.getElementById('customerCity').value.trim();
    
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
    
    const submitBtn = document.getElementById('confirmOrderBtn');
    const btnText = document.getElementById('confirmBtnText');
    const spinner = document.getElementById('confirmSpinner');
    
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    spinner.style.display = 'inline-block';
    
    try {
        const orderId = 'ORD-' + Date.now();
        const currency = 'LKR';
        
        const orderResponse = await fetch('create-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                order_id: orderId,
                product_id: orderDetails.productId,
                fabric_id: orderDetails.fabricId,
                size: orderDetails.size,
                quantity: orderDetails.quantity,
                customer_name: customerName,
                customer_email: customerEmail,
                customer_phone: customerPhone,
                delivery_address: deliveryAddress,
                city: customerCity
            })
        });
        
        const orderData = await orderResponse.json();
        
        if (!orderData.success) {
            throw new Error(orderData.error || 'Failed to create order');
        }
        
        const hashResponse = await fetch('generate-hash.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                order_id: orderId,
                amount: orderDetails.amount,
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
        
        bootstrap.Modal.getInstance(document.getElementById('customerDetailsModal')).hide();
        
        const payment = {
            sandbox: <?= PAYHERE_SANDBOX ? 'true' : 'false' ?>,
            merchant_id: hashData.merchant_id,
            return_url: "<?= PAYHERE_RETURN_URL ?>",
            cancel_url: "<?= PAYHERE_CANCEL_URL ?>",
            notify_url: "<?= PAYHERE_NOTIFY_URL ?>",
            order_id: orderId,
            items: orderDetails.productName,
            amount: orderDetails.amount,
            currency: currency,
            hash: hashData.hash,
            first_name: customerName.split(' ')[0],
            last_name: customerName.split(' ').slice(1).join(' ') || 'User',
            email: customerEmail,
            phone: customerPhone,
            address: deliveryAddress,
            city: customerCity,
            country: "Sri Lanka",
            custom_1: orderDetails.fabricId,
            custom_2: orderDetails.size
        };
        
        console.log('Starting payment with:', payment);
        payhere.startPayment(payment);
        
    } catch (error) {
        console.error('Payment error:', error);
        alert('Error initiating payment: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        spinner.style.display = 'none';
    }
}
</script>

</script>

<?php if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])): ?>
<script>
  // Ensure the login redirect URL is always set to the current product page for guests
  document.addEventListener('DOMContentLoaded', function() {
    const currentUrl = window.location.href;
    const redirectInput = document.getElementById('loginRedirectUrl');
    const registerRedirectInput = document.getElementById('registerRedirectUrl');
    
    if (redirectInput) {
      redirectInput.value = currentUrl;
    }
    if (registerRedirectInput) {
        registerRedirectInput.value = currentUrl;
    }
  });
</script>
<?php endif; ?>

<!-- Lightbox Markup -->
<div id="lightboxOverlay" class="lightbox-overlay">
  <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
  
  <div class="lightbox-nav-btn lightbox-prev" onclick="changeLightboxSlide(-1)">
    <i class="bi bi-chevron-left"></i>
  </div>
  
  <div class="lightbox-content-wrapper" id="lightboxWrapper">
    <img id="lightboxImage" class="lightbox-img" src="" alt="Product Full View" draggable="false">
  </div>
  
  <div class="lightbox-nav-btn lightbox-next" onclick="changeLightboxSlide(1)">
     <i class="bi bi-chevron-right"></i>
  </div>
  
  <div class="lightbox-zoom-controls">
    <button class="zoom-btn" onclick="zoomLightbox(0.25)">
        <i class="bi bi-zoom-in"></i> Zoom In
    </button>
    <button class="zoom-btn" onclick="zoomLightbox(-0.25)">
        <i class="bi bi-zoom-out"></i> Zoom Out
    </button>
    <button class="zoom-btn" onclick="resetZoom()">
        <i class="bi bi-arrow-counterclockwise"></i> Reset
    </button>
  </div>
</div>

<script>
  // Lightbox Logic
  const productImages = [<?php echo implode(',', array_map(function($i){ return "'".e($i)."'"; }, $images)); ?>];
  let currentLbIndex = 0;
  let currentZoom = 1;
  let isDragging = false;
  let startX, startY, translateX = 0, translateY = 0;

  const lbOverlay = document.getElementById('lightboxOverlay');
  const lbImage = document.getElementById('lightboxImage');
  const lbWrapper = document.getElementById('lightboxWrapper');
  const mainProductImage = document.getElementById('mainProductImage');

  // Open Lightbox
  if (mainProductImage) {
    mainProductImage.style.cursor = 'zoom-in';
    mainProductImage.addEventListener('click', function() {
      // Find index based on current src
      const currentSrc = this.src.split('/').pop().split('?')[0]; 
      let idx = productImages.findIndex(img => img === currentSrc);
      if (idx === -1) idx = 0;
      
      openLightbox(idx);
    });
  }

  function openLightbox(index) {
    currentLbIndex = index;
    updateLightboxImage();
    lbOverlay.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
  }

  function closeLightbox() {
    lbOverlay.style.display = 'none';
    document.body.style.overflow = '';
    resetZoom();
  }

  function updateLightboxImage() {
    // Add simple loading effect
    lbImage.style.opacity = '0.5';
    const newSrc = 'images/products/' + productImages[currentLbIndex];
    
    // Preload image to ensure smooth transition
    const tempImg = new Image();
    tempImg.onload = function() {
      lbImage.src = newSrc;
      lbImage.style.opacity = '1';
    };
    tempImg.src = newSrc;
    
    resetZoom();
  }

  function changeLightboxSlide(step) {
    currentLbIndex += step;
    if (currentLbIndex >= productImages.length) currentLbIndex = 0;
    if (currentLbIndex < 0) currentLbIndex = productImages.length - 1;
    updateLightboxImage();
  }

  function zoomLightbox(delta) {
    const newZoom = currentZoom + delta;
    if (newZoom >= 0.5 && newZoom <= 3.5) {
      currentZoom = newZoom;
      applyTransform();
    }
  }

  function resetZoom() {
    currentZoom = 1;
    translateX = 0;
    translateY = 0;
    applyTransform();
  }

  function applyTransform() {
    lbImage.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentZoom})`;
  }

  // Close on outside click
  lbOverlay.addEventListener('click', function(e) {
    if (e.target === lbOverlay || e.target === lbWrapper) {
      closeLightbox();
    }
  });

  // Keyboard navigation
  document.addEventListener('keydown', function(e) {
    if (lbOverlay.style.display === 'flex') {
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') changeLightboxSlide(-1);
      if (e.key === 'ArrowRight') changeLightboxSlide(1);
    }
  });

  // Pan / Drag implementation
  lbImage.addEventListener('mousedown', function(e) {
    if (currentZoom > 1) {
      isDragging = true;
      startX = e.clientX - translateX;
      startY = e.clientY - translateY;
      lbImage.style.cursor = 'grabbing';
      e.preventDefault(); // Prevent default drag behavior
    }
  });

  window.addEventListener('mouseup', function() {
    isDragging = false;
    lbImage.style.cursor = 'grab';
  });

  window.addEventListener('mousemove', function(e) {
    if (isDragging && currentZoom > 1) {
      e.preventDefault();
      translateX = e.clientX - startX;
      translateY = e.clientY - startY;
      applyTransform();
    }
  });
  
  // Wheel Zoom support
  lbWrapper.addEventListener('wheel', function(e) {
    if (lbOverlay.style.display === 'flex') {
      e.preventDefault();
      const delta = e.deltaY > 0 ? -0.1 : 0.1;
      zoomLightbox(delta);
    }
  }, { passive: false });

</script>

</body>
</html>