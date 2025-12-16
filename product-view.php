<?php
// ------------------------------
// product-view.php (UPDATED - Main image wider, thumbnails on right side)
// ------------------------------
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
require_once 'config.php';

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
// Handle POST (Reviews / Q&A)
// ------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ok = isset($_POST['csrf_token']) && csrf_verify($_POST['csrf_token']);

  if (!$ok) {
    http_response_code(403);
    die("Invalid CSRF token.");
  }

  if (!$isLoggedIn) {
    redirect_self(['msg'=>'login_required']);
  }

  if (isset($_POST['submit_review'])) {
    $rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    if ($rating >= 1 && $rating <= 5 && $comment !== '') {
      $stmt = $mysqli->prepare("INSERT INTO product_reviews (product_id, username, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
      if ($stmt === false) { die('MySQL prepare failed: ' . $mysqli->error); }
      $stmt->bind_param("isis", $product_id, $currentUser, $rating, $comment);
      if ($stmt->execute()) {
        $stmt->close();
        redirect_self(['msg'=>'review_added']);
      } else {
        die('Failed to insert review: ' . $stmt->error);
      }
    } else {
      redirect_self(['msg'=>'invalid_review']);
    }
  }

  if (isset($_POST['submit_question'])) {
    $question = isset($_POST['question']) ? trim($_POST['question']) : '';
    if ($question !== '') {
      $stmt = $mysqli->prepare("INSERT INTO product_questions (product_id, username, question, created_at) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("iss", $product_id, $currentUser, $question);
      $stmt->execute();
      $stmt->close();
      redirect_self(['msg'=>'question_added']);
    } else {
      redirect_self(['msg'=>'invalid_question']);
    }
  }
}

// ------------------------------
// Aggregate: Avg Rating & Counts
// ------------------------------
$avg = 0.0; $totalReviews = 0;
$stmt = $mysqli->prepare("SELECT COALESCE(AVG(rating),0), COUNT(*) FROM product_reviews WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($avg, $totalReviews);
$stmt->fetch();
$stmt->close();
$avg = round((float)$avg, 1);

// ------------------------------
// Pagination
// ------------------------------
$perPage = 5;
$pageR = isset($_GET['pageR']) && ctype_digit($_GET['pageR']) ? max(1, (int)$_GET['pageR']) : 1;
$offR  = ($pageR - 1) * $perPage;
$pageQ = isset($_GET['pageQ']) && ctype_digit($_GET['pageQ']) ? max(1, (int)$_GET['pageQ']) : 1;
$offQ  = ($pageQ - 1) * $perPage;

$stmt = $mysqli->prepare("SELECT COUNT(*) FROM product_reviews WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($reviewsCount);
$stmt->fetch();
$stmt->close();

$stmt = $mysqli->prepare("SELECT COUNT(*) FROM product_questions WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($questionsCount);
$stmt->fetch();
$stmt->close();

$stmt = $mysqli->prepare("
  SELECT username, rating, comment, created_at
  FROM product_reviews
  WHERE product_id = ?
  ORDER BY created_at DESC
  LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $product_id, $perPage, $offR);
$stmt->execute();
$reviewsRes = $stmt->get_result();
$reviews = $reviewsRes->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $mysqli->prepare("
  SELECT username, question, created_at
  FROM product_questions
  WHERE product_id = ?
  ORDER BY created_at DESC
  LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $product_id, $perPage, $offQ);
$stmt->execute();
$questionsRes = $stmt->get_result();
$questions = $questionsRes->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function render_pager($total, $perPage, $currentKey, $currentPage) {
  $pages = (int)ceil(max(1, $total)/$perPage);
  if ($pages <= 1) return '';
  $out = '<div class="pager">';
  for ($p = 1; $p <= $pages; $p++) {
    $params = $_GET;
    $params[$currentKey] = $p;
    $link = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
    $cls = $p === (int)$currentPage ? 'class="active"' : '';
    $out .= "<a $cls href=\"".e($link)."\">$p</a>";
  }
  $out .= '</div>';
  return $out;
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

    <!-- Right: Product Details (Narrower) -->
    <div class="right-col">
      <?php if ($isAdmin): ?>
        <a href="admin/edit-product.php?id=<?= $product_id; ?>" class="edit-pill">✏️ Edit</a>
      <?php endif; ?>

      <h1 class="title"><?= e($product['product_name']); ?></h1>

      <!-- Fabrics -->
      <?php if (!empty($fabrics)): ?>
      <div class="option-row">
        <label class="opt-label">Select Fabric type</label>
        <div class="fabric-list" id="fabricList">
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

      <!-- Sizes -->
      <div class="option-row">
        <label class="opt-label">Select size</label>
        <div class="size-list" id="sizeList">
          <?php $sizes = ['XS','S','M','L','XL']; foreach ($sizes as $s): ?>
            <button type="button" class="size-btn" data-size="<?= e($s); ?>"><?= e($s); ?></button>
          <?php endforeach; ?>
        </div>
        <button type="button" class="view-size-grid">View Size Grid</button>
      </div>

      <!-- Info message -->
      <div class="info-message">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
          <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
        </svg>
        Please select <strong>colour</strong>, <strong>fabric</strong>, and <strong>size</strong> to check the price.
      </div>

      <!-- Quantity + Buttons at bottom -->
      <form id="addToCartForm" action="cart-add.php" method="GET" onsubmit="return prepareCartForm(this);">
        <input type="hidden" name="id" value="<?= $product_id; ?>">
        <input type="hidden" name="fabric_id" id="formFabricId" value="">
        <input type="hidden" name="color" id="formColor" value="">
        <input type="hidden" name="size" id="formSize" value="">
        
        <div class="bottom-actions">
          <div class="action-buttons">
  <?php if (!$isAdmin): ?>
    <button type="submit" class="btn-cart">🛒 Add to Cart</button>
    <?php if ($isLoggedIn): ?>
      <button type="button" class="btn-buy buy-now-btn" data-product-id="<?= $product_id; ?>" data-product-name="<?= htmlspecialchars($product['product_name']); ?>">Buy Now</button>
    <?php else: ?>
      <button type="button" class="buy-now-btn" onclick="showLoginModal()">Buy Now</button>
    <?php endif; ?>
  <?php endif; ?>
</div>

          <div class="qty-heart-row">
            <div class="qty-row">
              <button type="button" class="qty-btn" id="qtyMinus">-</button>
              <input type="number" name="qty" id="qtyField" value="1" min="1" class="qty-input">
              <button type="button" class="qty-btn" id="qtyPlus">+</button>
            </div>
            
            <?php if (!$isAdmin): ?>
              <button type="button" class="btn-wishlist" id="wishlistBtn" onclick="toggleWishlist(<?= $product_id; ?>)">♡</button>
            <?php endif; ?>
          </div>
        </div>

        <div class="delivery-info">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5v-7zm1.294 7.456A1.999 1.999 0 0 1 4.732 11h5.536a2.01 2.01 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456zM12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12v4zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
          </svg>
          Delivery within 5 – 12 Business Days
          <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="margin-left:6px; opacity:0.6; cursor:pointer;">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
            <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
          </svg>
        </div>
      </form>

    </div>
  </div>

  <!-- Reviews & Q&A Section -->
  <div class="reviews-section">
    <div class="tabs-header">
      <div class="tab-buttons">
        <button class="tab-button active" id="reviewsTabBtn">Reviews (<?= (int)$reviewsCount; ?>)</button>
        <button class="tab-button" id="qnaTabBtn">Q&amp;A (<?= (int)$questionsCount; ?>)</button>
      </div>
      <button class="add-btn" id="openDialogBtn" <?= $isLoggedIn ? '' : 'onclick="showLoginModal(); return false;"' ?>>+ Add</button>
    </div>

    <!-- Reviews Tab -->
    <div class="tab-content" id="reviewsTab">
      <?php if (!$reviews): ?>
        <p>No reviews yet.</p>
      <?php else: ?>
        <?php foreach ($reviews as $r): ?>
          <div class="item">
            <strong><?= e($r['username']); ?></strong>
            <div class="stars"><?= str_repeat('★', (int)$r['rating']); ?></div>
            <p><?= nl2br(e($r['comment'])); ?></p>
            <small class="meta"><?= e($r['created_at']); ?></small>
          </div>
        <?php endforeach; ?>
        <?= render_pager($reviewsCount, $perPage, 'pageR', $pageR); ?>
      <?php endif; ?>
    </div>

    <!-- Q&A Tab -->
    <div class="tab-content" id="qnaTab" style="display:none;">
      <?php if (!$questions): ?>
        <p>No questions yet.</p>
      <?php else: ?>
        <?php foreach ($questions as $q): ?>
          <div class="item">
            <p><strong>Q:</strong> <?= nl2br(e($q['question'])); ?></p>
            <small class="meta">By <?= e($q['username']); ?> • <?= e($q['created_at']); ?></small>
          </div>
        <?php endforeach; ?>
        <?= render_pager($questionsCount, $perPage, 'pageQ', $pageQ); ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Review/Question Dialog -->
<?php if ($isLoggedIn): ?>
<div class="dialog-overlay" id="dialogOverlay">
  <div class="dialog-box">
    <form method="POST" id="dialogForm" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

      <div id="reviewForm">
        <h3>Add Review</h3>
        <label>Rating:</label>
        <select name="rating" required>
          <option value="">Select rating</option>
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i; ?>"><?= $i; ?> ★</option>
          <?php endfor; ?>
        </select>
        <label>Comment:</label>
        <textarea name="comment" rows="3" placeholder="Write your review..." required maxlength="1000"></textarea>
        <button type="submit" name="submit_review" class="add-btn">Submit</button>
      </div>

      <div id="questionForm" style="display:none;">
        <h3>Ask a Question</h3>
        <textarea name="question" rows="3" placeholder="Ask about this product..." required maxlength="1000"></textarea>
        <button type="submit" name="submit_question" class="add-btn">Post Question</button>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="cancel-btn" id="closeDialogBtn">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

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
    margin-top: 10px;
    padding-top: 10px;
    border-top: 2px solid #430160;
  }
</style>

<script>
// Update order summary when modal opens
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
</script>

<!-- Login/Register Sidebars -->
<?php include 'includes/login-sidebar.php'; ?>
<?php include 'includes/register-sidebar.php'; ?>

<?php include 'includes/footer.php'; ?>

<style>
  :root {
    --primary: #4b0082;
    --accent: #6f42c1;
    --soft-bg: #fafbfd;
    --muted: #6c757d;
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
    padding: 10px 20px; 
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
    box-shadow: 0 4px 12px rgba(111,66,193,0.3);
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
    right: 20px; 
    top: 10px; 
    background: var(--primary); 
    color: #fff; 
    padding: 6px 12px; 
    border-radius: 4px; 
    font-size: 13px; 
    text-decoration: none; 
  }

  .title { 
    color: var(--primary); 
    margin: 0 0 25px 0; 
    font-size: 28px; 
    font-weight: 700; 
  }

  .option-row { 
    margin-bottom: 22px; 
  }
  
  .opt-label { 
    display: block; 
    font-weight: 600; 
    margin-bottom: 10px; 
    color: #000; 
    font-size: 14px; 
  }

  /* Fabric Buttons */
  .fabric-list { 
    display: flex; 
    gap: 10px; 
    flex-wrap: wrap; 
  }
  
  .fabric-btn { 
    background: #fff; 
    border: 1px solid #ddd; 
    padding: 10px 16px; 
    border-radius: 6px; 
    cursor: pointer; 
    font-weight: 600;
    font-size: 13px;
    color: #000;
    transition: all 0.2s;
  }
  
  .fabric-btn.selected { 
    background: #f0e6ff; 
    border-color: var(--accent); 
    color: var(--accent);
  }

  /* Size Buttons */
  .size-list { 
    display: flex; 
    gap: 10px;
    margin-bottom: 8px;
  }
  
  .size-btn { 
    padding: 10px 18px; 
    border: 1px solid #ddd; 
    border-radius: 6px; 
    background: #fff; 
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    color: #000;
    transition: all 0.2s;
  }
  
  .size-btn.selected { 
    background: #fff7ed; 
    border-color: #fb923c; 
    color: #c2410c;
  }
  
  .view-size-grid {
    background: none;
    border: none;
    color: #000;
    text-decoration: underline;
    cursor: pointer;
    padding: 4px 0;
    font-size: 13px;
    font-weight: 500;
  }

  /* Info Message */
  .info-message {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 6px;
    font-size: 13px;
    color: #075985;
    margin-bottom: 22px;
  }

  /* Bottom Actions */
  .bottom-actions {
    margin-top: auto;
    padding-top: 20px;
  }

  .action-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
  }

  .btn-cart {
    flex: 1;
    background: #7c3aed;
    color: #fff;
    border: none;
    padding: 14px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.2s;
  }

  .btn-cart:hover {
    background: #6d28d9;
  }

  .btn-buy {
    flex: 1;
    background: #10b981;
    color: #fff;
    border: none;
    padding: 14px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
  }

  .btn-buy:hover {
    background: #059669;
  }

  .qty-heart-row {
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .qty-row {
    display: flex;
    align-items: center;
    gap: 0;
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
  }

  .qty-btn {
    background: #fff;
    border: none;
    border-right: 1px solid #ddd;
    padding: 10px 14px;
    cursor: pointer;
    font-weight: 700;
    font-size: 15px;
    color: #666;
  }

  .qty-btn:last-of-type {
    border-right: none;
    border-left: 1px solid #ddd;
  }

  .qty-input {
    width: 55px;
    padding: 10px;
    border: none;
    text-align: center;
    font-weight: 600;
  }

  .btn-wishlist {
    width: 46px;
    height: 46px;
    background: #fff;
    border: 1px solid #ff4d6d;
    border-radius: 6px;
    color: #ff4d6d;
    cursor: pointer;
    font-size: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .btn-wishlist:hover {
    background: #fff1f2;
  }

  .btn-wishlist.active {
    background: #ff4d6d;
    color: #fff;
  }

  .delivery-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 11px;
    background: #fefce8;
    border-radius: 6px;
    font-size: 12px;
    color: #854d0e;
    margin-top: 12px;
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
    background: #10b981;
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
  }

  .tab-content {
    padding: 10px 0;
  }

  .item {
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
  }

  .item:last-child {
    border-bottom: none;
  }

  .stars {
    color: #fbbf24;
    font-size: 16px;
    margin: 5px 0;
  }

  .meta {
    color: #999;
    font-size: 13px;
  }

  .pager {
    margin-top: 20px;
    display: flex;
    gap: 8px;
    justify-content: center;
  }

  .pager a {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #666;
  }

  .pager a.active {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
  }

  /* Dialog */
  .dialog-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  }

  .dialog-box {
    width: 96%;
    max-width: 500px;
    background: #fff;
    border-radius: 10px;
    padding: 24px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  }

  .dialog-box h3 {
    margin: 0 0 16px 0;
    color: var(--primary);
  }

  .dialog-box label {
    display: block;
    font-weight: 600;
    margin: 12px 0 6px 0;
    color: #000;
  }

  .dialog-box select,
  .dialog-box textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: inherit;
    resize: vertical;
    color: #000;
  }

  .cancel-btn {
    background: #e5e7eb;
    color: #374151;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
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

    .action-buttons {
      flex-direction: column;
    }

    .qty-heart-row {
      justify-content: space-between;
    }
    
    .main-image {
      height: 400px;
    }
    
    .reviews-section {
      padding: 20px 15px;
    }

    .title {
      font-size: 24px;
    }
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

  // Fabric selection
  const fabricBtns = document.querySelectorAll('.fabric-btn');
  const formFabricId = document.getElementById('formFabricId');

  fabricBtns.forEach(b => {
    b.addEventListener('click', function(){
      fabricBtns.forEach(x => x.classList.remove('selected'));
      this.classList.add('selected');
      formFabricId.value = this.dataset.fabricId || '';
    });
  });

  // Auto-select first fabric
  if (fabricBtns.length > 0) {
    fabricBtns[0].click();
  }

  // Size selection
  const sizeBtns = document.querySelectorAll('.size-btn');
  sizeBtns.forEach(s => s.addEventListener('click', function(){
    sizeBtns.forEach(x => x.classList.remove('selected'));
    this.classList.add('selected');
    document.getElementById('formSize').value = this.dataset.size || '';
  }));

  // Quantity handlers
  const qtyMinus = document.getElementById('qtyMinus');
  const qtyPlus = document.getElementById('qtyPlus');
  const qtyField = document.getElementById('qtyField');

  if (qtyMinus) {
    qtyMinus.addEventListener('click', () => {
      const cur = Math.max(1, parseInt(qtyField.value || 1) - 1);
      qtyField.value = cur;
    });
  }

  if (qtyPlus) {
    qtyPlus.addEventListener('click', () => {
      const cur = Math.max(1, parseInt(qtyField.value || 1) + 1);
      qtyField.value = cur;
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
    const qtyInput = form.qty;
    if (parseInt(qtyInput.value) < 1) qtyInput.value = 1;

    // AJAX Submission
    const formData = new FormData(form);
    const params = new URLSearchParams(formData).toString();

    fetch('cart-add.php?' + params + '&ajax=1')
      .then(res => res.json())
      .then(data => {
        if (data.status === 'ok') {
          // Update cart badge if function exists
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

    return false; // Prevent default form submission
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
            // Update badge count if exists
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
      if (currentTab === 'reviews') {
        reviewForm.style.display = 'block';
        questionForm.style.display = 'none';
      } else {
        reviewForm.style.display = 'none';
        questionForm.style.display = 'block';
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
</script>
<script>
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
            
            // Validate selections
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
            
            // Get price from selected fabric
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
            
            // Store order details in modal
            window.currentOrderDetails = {
                productId: productId,
                fabricId: fabricId,
                size: size,
                quantity: qty,
                amount: amount,
                pricePerUnit: pricePerUnit,
                productName: this.dataset.productName
            };
            
            // Show customer details modal
            showCustomerDetailsModal();
        });
    });
});

// Show customer details modal
function showCustomerDetailsModal() {
    const modal = new bootstrap.Modal(document.getElementById('customerDetailsModal'));
    modal.show();
}

// Process payment after customer confirms details
async function processPayment() {
    const orderDetails = window.currentOrderDetails;
    if (!orderDetails) {
        alert('Order details not found');
        return;
    }
    
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
        const orderId = 'ORD-' + Date.now();
        const currency = 'LKR';
        
        // Step 1: Create order in database with customer details
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
        
        // Step 2: Get payment hash
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
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('customerDetailsModal')).hide();
        
        // Step 3: Start PayHere payment
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

<?php include 'includes/login-sidebar.php'; ?>
<?php include 'includes/register-sidebar.php'; ?>

<?php include 'includes/scripts.php'; ?>

</body>
</html>