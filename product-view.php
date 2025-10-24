<?php
// ------------------------------
// Product View (Secure + UX)
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
$stmt = $mysqli->prepare("SELECT id, product_name, product_code, product_img_name, product_desc, price, qty FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
  http_response_code(404);
  die("Product not found.");
}

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

  // REVIEW SUBMIT
  if (isset($_POST['submit_review'])) {
    // Sanitize input and ensure the values are correct
    $rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Ensure that both rating and comment are valid
    if ($rating >= 1 && $rating <= 5 && $comment !== '') {
      // Prepare and execute the insert statement
      $stmt = $mysqli->prepare("INSERT INTO product_reviews (product_id, username, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
      
      if ($stmt === false) {
        die('MySQL prepare failed: ' . $mysqli->error);
      }

      $stmt->bind_param("isis", $product_id, $currentUser, $rating, $comment);
      $executeResult = $stmt->execute();

      if ($executeResult) {
        $stmt->close();
        redirect_self(['msg'=>'review_added']);
      } else {
        // Error if the query fails
        die('Failed to insert review: ' . $stmt->error);
      }
    } else {
      redirect_self(['msg'=>'invalid_review']);
    }
  }

  // Q&A SUBMIT
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

// Count totals
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

// Fetch paginated reviews
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

// Fetch paginated questions
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

// Pagination helpers
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
<head>
  <meta charset="UTF-8">
  <title><?= e($product['product_name']); ?> | Ceylon Fashion.lk</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
  <link rel="stylesheet" href="css/foundation.css" />
  <script src="js/vendor/modernizr.js"></script>
  <style>
    body { background:#f8f9fa; }
    .product-view { max-width: 1000px; margin: 30px auto; padding: 20px; border-radius: 8px; background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.1); }
    .product-view img { width: 100%; max-width: 420px; border-radius: 8px; }
    .product-info { padding: 20px; }
    .price { color:#0078A0; font-size:1.3em; font-weight:700; }
    .actions-row { display:flex; gap:10px; margin-top:15px; flex-wrap:wrap; align-items:center; }
    .btn-cart, .btn-wishlist, .btn-login {
      padding:10px 14px; border-radius:4px; text-decoration:none; border:none; display:inline-block;
    }
    .btn-cart { background:#0078A0; color:#fff; }
    .btn-wishlist { background:#eee; color:#c00; }
    .btn-login { background:#f3f3f3; color:#333; }
    .badge { display:inline-block; background:#ffeeba; color:#856404; padding:4px 8px; border-radius:999px; font-size:12px; margin-left:8px; }
    .stars { color:#f5c518; letter-spacing:2px; }
    .edit-pill {
      background:#2a75bb; color:#fff; padding:6px 10px; border-radius:4px; text-decoration:none; font-size:12px; float:right;
    }

    .container { margin-top: 30px; }
    .tabs-header { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .tab-buttons { display:flex; gap:8px; }
    .tab-button { border:1px solid #ddd; background:#fafafa; padding:8px 14px; border-radius:6px; }
    .tab-button.active { background:#0078A0; color:#fff; border-color:#0078A0; }
    .add-btn { background:#28a745; color:#fff; border:none; padding:8px 14px; border-radius:6px; }
    .add-btn[disabled] { opacity:0.6; cursor:not-allowed; }
    .tab-content .item { border-bottom:1px solid #eee; padding:12px 0; }
    .meta { color:#666; font-size:12px; }
    .pager { display:flex; gap:6px; margin-top:12px; }
    .pager a { border:1px solid #ddd; padding:6px 10px; border-radius:999px; text-decoration:none; color:#333; }
    .pager a.active { background:#0078A0; color:#fff; border-color:#0078A0; }

    /* Dialog */
    .dialog-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.35); display:none; align-items:center; justify-content:center; z-index:999; }
    .dialog-box { width: 96%; max-width: 520px; background:#fff; border-radius:10px; padding:20px; box-shadow:0 10px 40px rgba(0,0,0,0.25); }
    .dialog-box h3 { margin-top:0; }
    textarea, select { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; resize:vertical; }
    button { background:#0078A0; color:#fff; border:none; padding:10px 16px; border-radius:4px; margin-top:10px; }
    button:hover { filter:brightness(0.95); cursor:pointer; }
    .cancel-btn { background:#e9ecef; color:#333; }
  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-purple">
    <div class="container-fluid">
      <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
        <div class="navbar-nav">
          <a class="nav-link" href="#">New Arrivals</a>
          <a class="nav-link" href="#">Bridal Attire</a>
          <a class="nav-link" href="#">Bridemaids Attire</a>
          <a class="nav-link" href="#">Party Wear</a>
          <a class="nav-link" href="#">Used Collection</a>

        </div>
        <div class="icons" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); display: flex; gap: 15px; font-size: 32px; color: white; cursor: pointer;">
          <i class="bi bi-person-circle" id="personIcon"></i>
          <i class="bi bi-heart"></i>
          <i class="bi bi-cart2"></i>
        </div>

      </div>
    </div>
  </nav>

<div class="product-view row">
  <div class="small-12 medium-6 columns">
    <img src="images/products/<?= e($product['product_img_name']); ?>"
         alt="<?= e($product['product_name']); ?>">
  </div>

  <div class="small-12 medium-6 columns product-info">
    <?php if ($isAdmin): ?>
      <a href="edit-product.php?id=<?= $product_id; ?>" class="edit-pill">✏️ Edit</a>
    <?php endif; ?>

    <h2 style="margin-bottom:6px;"><?= e($product['product_name']); ?></h2>

    <div class="meta">
      <strong>Product Code:</strong> <?= e($product['product_code']); ?>
      <?php if ($totalReviews > 0): ?>
        <span class="badge">
          <span class="stars">★</span> <?= e($avg) ?> (<?= (int)$totalReviews; ?>)
        </span>
      <?php endif; ?>
    </div>

    <p class="price" style="margin-top:8px;">Price: <?= e($currency) . number_format((float)$product['price'], 2); ?></p>
    <p><strong>Available Units:</strong> <?= (int)$product['qty']; ?></p>

    <p><strong>Description:</strong></p>
    <p><?= nl2br(e($product['product_desc'])); ?></p>

    <div class="actions-row">
      <?php if (!$isAdmin): ?>
        <?php if ($isLoggedIn): ?>
          <a class="btn-wishlist" href="wishlist-toggle.php?id=<?= $product_id; ?>">❤ Wishlist</a>
          <?php if ((int)$product['qty'] > 0): ?>
            <a class="btn-cart" href="cart-add.php?id=<?= $product_id; ?>&qty=1">Add to Cart</a>
          <?php else: ?>
            <span style="color:#c00; font-weight:bold;">Out Of Stock!</span>
          <?php endif; ?>
        <?php else: ?>
          <a class="btn-login" href="login.php">❤ Wishlist (Login)</a>
          <a class="btn-login" href="login.php">Add to Cart (Login)</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg'])): ?>
      <p class="meta" style="margin-top:10px;">
        <?php
          $m = $_GET['msg'];
          $messages = [
            'review_added' => 'Your review has been posted. Thank you!',
            'invalid_review' => 'Please provide a rating (1–5) and a comment.',
            'question_added' => 'Your question has been posted.',
            'invalid_question' => 'Please enter a question.',
            'login_required' => 'Please log in to post.',
          ];
          echo e($messages[$m] ?? '');
        ?>
      </p>
    <?php endif; ?>
  </div>

  <div class="container small-12 columns">
    <!-- Tabs Header -->
    <div class="tabs-header">
      <div class="tab-buttons">
        <button class="tab-button active" id="reviewsTabBtn">Reviews (<?= (int)$reviewsCount; ?>)</button>
        <button class="tab-button" id="qnaTabBtn">Q&amp;A (<?= (int)$questionsCount; ?>)</button>
      </div>
      <button class="add-btn" id="openDialogBtn" <?= $isLoggedIn ? '' : 'disabled title="Login to post"' ?>>+ Add</button>
    </div>

    <!-- Reviews Section -->
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

    <!-- Q&A Section -->
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

<!-- Dialog Box -->
<!-- Dialog Box -->
<div class="dialog-overlay" id="dialogOverlay">
  <div class="dialog-box">
    <?php if ($isLoggedIn): ?>
    <form method="POST" id="dialogForm" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">

      <!-- Review Form -->
      <div id="reviewForm" style="display:<?= $currentTab === 'reviews' ? 'block' : 'none' ?>;">
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

      <!-- Question Form -->
      <div id="questionForm" style="display:<?= $currentTab === 'qna' ? 'block' : 'none' ?>;">
        <h3>Ask a Question</h3>
        <textarea name="question" rows="3" placeholder="Ask about this product..." required maxlength="1000"></textarea>
        <button type="submit" name="submit_question" class="add-btn">Post Question</button>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="cancel-btn" id="closeDialogBtn">Cancel</button>
      </div>
    </form>
    <?php else: ?>
      <p>Please <a href="login.php">log in</a> to post a review or question.</p>
      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" class="cancel-btn" id="closeDialogBtn">Close</button>
      </div>
    <?php endif; ?>
  </div>
</div>


<footer style="text-align:center; margin-top:20px;">
  <p>&copy; <?= date("Y"); ?> Ceylon Fashion.lk. All Rights Reserved.</p>
</footer>

<script>
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

  reviewsTabBtn.onclick = () => setTab('reviews');
  qnaTabBtn.onclick = () => setTab('qna');

  if (openDialogBtn) {
    openDialogBtn.onclick = () => {
      if (openDialogBtn.disabled) return;
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
  if (closeDialogBtn) closeDialogBtn.onclick = () => dialogOverlay.style.display = 'none';
</script>
<script src="js/vendor/jquery.js"></script>
<script src="js/foundation.min.js"></script>
<script>$(document).foundation();</script>

</body>
</html>
