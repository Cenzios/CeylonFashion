<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
require_once 'config.php';

// Derive auth flags (adjust to your session strategy)
$isAdmin    = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
$isLoggedIn = isset($_SESSION['username']); // or use user_id/email if you prefer

// --- Helpers ---
function h($v) { return htmlentities($v ?? '', ENT_QUOTES, 'UTF-8'); }
function get_user_id(mysqli $db) {
  if (!empty($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
  if (!empty($_SESSION['email'])) {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $_SESSION['email']);
    $stmt->execute();
    $stmt->bind_result($uid);
    if ($stmt->fetch()) {
      $_SESSION['user_id'] = (int)$uid;
      $stmt->close();
      return (int)$uid;
    }
    $stmt->close();
  }
  return 0;
}

// --- Validate product id ---
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  http_response_code(400);
  exit('Invalid product id');
}
$productId = (int)$_GET['id'];

// --- Optional: handle POST for new review/question ---
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
  $uid = get_user_id($mysqli);

  // Add Review
  if (isset($_POST['form_type']) && $_POST['form_type'] === 'review') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $title  = trim($_POST['title'] ?? '');
    $body   = trim($_POST['body'] ?? '');
    if ($rating < 1 || $rating > 5) $errors[] = 'Rating must be between 1 and 5.';
    if ($title === '' || $body === '') $errors[] = 'Title and review are required.';

    if (!$errors) {
      $stmt = $mysqli->prepare("INSERT INTO product_reviews (product_id, user_id, rating, title, body) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("iiiss", $productId, $uid, $rating, $title, $body);
      if ($stmt->execute()) {
        $messages[] = 'Review submitted!';
      } else {
        $errors[] = 'Failed to save review.';
      }
      $stmt->close();
    }
  }

  // Add Question
  if (isset($_POST['form_type']) && $_POST['form_type'] === 'qna') {
    $question = trim($_POST['question'] ?? '');
    if ($question === '') $errors[] = 'Question is required.';
    if (!$errors) {
      $stmt = $mysqli->prepare("INSERT INTO product_qna (product_id, user_id, question) VALUES (?, ?, ?)");
      $stmt->bind_param("iis", $productId, $uid, $question);
      if ($stmt->execute()) {
        $messages[] = 'Question posted!';
      } else {
        $errors[] = 'Failed to post question.';
      }
      $stmt->close();
    }
  }
}

// --- Fetch product ---
$stmt = $mysqli->prepare("SELECT id, product_code, product_name, product_desc, price, qty, product_img_name FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();

if (!$product) {
  http_response_code(404);
  exit('Product not found');
}

// --- Fetch reviews + avg rating ---
$stmt = $mysqli->prepare("
  SELECT r.id, r.rating, r.title, r.body, r.created_at, u.username
  FROM product_reviews r
  LEFT JOIN users u ON u.id = r.user_id
  WHERE r.product_id = ?
  ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$avgRating = null;
$reviewCount = count($reviews);
if ($reviewCount) {
  $sum = 0;
  foreach ($reviews as $rv) $sum += (int)$rv['rating'];
  $avgRating = round($sum / $reviewCount, 1);
}

// --- Fetch Q&A (questions with any answers) ---
$stmt = $mysqli->prepare("
  SELECT q.id as qid, q.question, q.created_at as q_created_at, uq.username as q_user,
         a.id as aid, a.answer, a.created_at as a_created_at, ua.username as a_user
  FROM product_qna q
  LEFT JOIN product_qna_answers a ON a.qna_id = q.id
  LEFT JOIN users uq ON uq.id = q.user_id
  LEFT JOIN users ua ON ua.id = a.user_id
  WHERE q.product_id = ?
  ORDER BY q.created_at DESC, a.created_at ASC
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$qnaRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group Q&A
$qna = [];
foreach ($qnaRows as $row) {
  $qid = (int)$row['qid'];
  if (!isset($qna[$qid])) {
    $qna[$qid] = [
      'question' => $row['question'],
      'q_user'   => $row['q_user'],
      'q_time'   => $row['q_created_at'],
      'answers'  => []
    ];
  }
  if (!empty($row['aid'])) {
    $qna[$qid]['answers'][] = [
      'answer' => $row['answer'],
      'a_user' => $row['a_user'],
      'a_time' => $row['a_created_at'],
    ];
  }
}

$currency = $currency ?? '$'; // fallback if not set in config.php
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
  <meta charset="utf-8" />
  <title><?=h($product['product_name'])?> | Ceylon Fashion.lk</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css/foundation.css" />
  <script src="js/vendor/modernizr.js"></script>
  <style>
    .product-hero { display:flex; gap:24px; flex-wrap:wrap; }
    .product-hero img { max-width: 420px; width:100%; height:auto; border-radius:6px; }
    .meta { font-size:14px; color:#555; }
    .price { font-size:22px; font-weight:bold; margin:8px 0; }
    .badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:12px; background:#eee; }
    .stars { color:#ffb400; font-size:14px; }
    .section { margin-top:28px; }
    .qna-item { border:1px solid #eee; border-radius:6px; padding:12px; margin-bottom:12px; }
    .answer { background:#fafafa; padding:10px; border-radius:6px; margin-top:8px; }
    .muted { color:#777; font-size:12px; }
    .form-card { border:1px solid #eee; border-radius:6px; padding:12px; margin-top:12px; }
  </style>
</head>
<body>

  <nav class="top-bar" data-topbar role="navigation">
    <ul class="title-area">
      <li class="name"><h1><a href="index.php">Ceylon Fashion.lk</a></h1></li>
      <li class="toggle-topbar menu-icon"><a href="#"><span></span></a></li>
    </ul>
    <section class="top-bar-section">
      <ul class="right">
        <li><a href="about.php">About</a></li>
        <li><a href="products.php">Products</a></li>
        <li><a href="cart.php">View Cart</a></li>
        <li><a href="orders.php">My Orders</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php
          if ($isLoggedIn) {
            if ($isAdmin) echo '<li><a href="add-product.php">Add Product</a></li>';
            echo '<li><a href="account.php">My Account</a></li>';
            echo '<li><a href="logout.php">Log Out</a></li>';
          } else {
            echo '<li><a href="login.php">Log In</a></li>';
            echo '<li><a href="register.php">Register</a></li>';
          }
        ?>
      </ul>
    </section>
  </nav>

  <div class="row" style="margin-top:20px;">
    <div class="small-12">
      <?php if ($errors): ?>
        <div class="alert-box alert" style="margin-bottom:12px;">
          <?=h(implode(' ', $errors))?>
          <a href="#" class="close">&times;</a>
        </div>
      <?php endif; ?>
      <?php if ($messages): ?>
        <div class="alert-box success" style="margin-bottom:12px;">
          <?=h(implode(' ', $messages))?>
          <a href="#" class="close">&times;</a>
        </div>
      <?php endif; ?>

      <!-- Product Hero -->
      <div class="product-hero">
        <div>
          <img src="images/products/<?=h($product['product_img_name'])?>" alt="<?=h($product['product_name'])?>">
        </div>
        <div style="min-width:260px; max-width:560px;">
          <h2><?=h($product['product_name'])?></h2>
          <div class="meta">
            <span class="badge">Code: <?=h($product['product_code'])?></span>
            <?php if ($avgRating !== null): ?>
              <span style="margin-left:8px;" class="stars">★ <?=h($avgRating)?>/5</span>
              <span class="muted">(<?=h($reviewCount)?> reviews)</span>
            <?php else: ?>
              <span style="margin-left:8px;" class="muted">No reviews yet</span>
            <?php endif; ?>
          </div>

          <p style="margin-top:10px;"><?=nl2br(h($product['product_desc']))?></p>

          <div class="price"><?=$currency?><?=number_format((float)$product['price'], 2)?></div>

          <div style="margin-top:8px;">
            <?php if ((int)$product['qty'] > 0): ?>
              <span class="badge" style="background:#e8f7e8;color:#1c7c1c;">In stock: <?= (int)$product['qty'] ?></span>
            <?php else: ?>
              <span class="badge" style="background:#fdeaea;color:#b02222;">Out of stock</span>
            <?php endif; ?>
          </div>

          <div style="margin-top:14px; display:flex; gap:8px; flex-wrap:wrap;">
            <?php if ($isLoggedIn && !$isAdmin): ?>
              <a class="button small" href="wishlist-toggle.php?id=<?=$productId?>">❤ Wishlist</a>
              <?php if ((int)$product['qty'] > 0): ?>
                <a class="button small success" href="cart-add.php?id=<?=$productId?>&qty=1">Add to Cart</a>
              <?php endif; ?>
            <?php else: ?>
              <?php if (!$isLoggedIn): ?>
                <a class="button small" href="login.php">❤ Wishlist (login)</a>
                <?php if ((int)$product['qty'] > 0): ?>
                  <a class="button small success" href="login.php">Add to Cart (login)</a>
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($isAdmin): ?>
                <a class="button small" href="edit-product.php?id=<?=$productId?>">✏️ Edit Product</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Reviews -->
      <div id="reviews" class="section">
        <h3>Reviews</h3>
        <?php if ($reviews): ?>
          <?php foreach ($reviews as $rv): ?>
            <div style="border-bottom:1px solid #eee; padding:10px 0;">
              <div class="stars">★ <?= (int)$rv['rating'] ?>/5</div>
              <strong><?= h($rv['title']) ?></strong>
              <div class="muted">by <?= h($rv['username'] ?? 'Anonymous') ?> • <?= h($rv['created_at']) ?></div>
              <p style="margin-top:6px;"><?= nl2br(h($rv['body'])) ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="muted">No reviews yet. Be the first to review!</p>
        <?php endif; ?>

        <?php if ($isLoggedIn && !$isAdmin): ?>
          <div class="form-card">
            <form method="post">
              <input type="hidden" name="form_type" value="review" />
              <div class="row">
                <div class="large-4 columns">
                  <label>Rating (1-5)
                    <select name="rating" required>
                      <option value="">Select</option>
                      <?php for ($i=1;$i<=5;$i++): ?>
                        <option value="<?=$i?>"><?=$i?></option>
                      <?php endfor; ?>
                    </select>
                  </label>
                </div>
                <div class="large-8 columns">
                  <label>Title
                    <input type="text" name="title" maxlength="120" required />
                  </label>
                </div>
              </div>
              <label>Your Review
                <textarea name="body" rows="4" required></textarea>
              </label>
              <button type="submit" class="button small">Submit Review</button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <!-- Q&A -->
      <div id="qna" class="section">
        <h3>Q&A</h3>

        <?php if ($qna): ?>
          <?php foreach ($qna as $qid => $item): ?>
            <div class="qna-item">
              <div><strong>Q:</strong> <?= nl2br(h($item['question'])) ?></div>
              <div class="muted">asked by <?= h($item['q_user'] ?? 'User') ?> • <?= h($item['q_time']) ?></div>

              <?php if (!empty($item['answers'])): ?>
                <?php foreach ($item['answers'] as $ans): ?>
                  <div class="answer">
                    <div><strong>A:</strong> <?= nl2br(h($ans['answer'])) ?></div>
                    <div class="muted">by <?= h($ans['a_user'] ?? 'Staff') ?> • <?= h($ans['a_time']) ?></div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="muted" style="margin-top:6px;">No answers yet.</div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="muted">No questions yet.</p>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
          <div class="form-card">
            <form method="post">
              <input type="hidden" name="form_type" value="qna" />
              <label>Ask a question
                <textarea name="question" rows="3" placeholder="Ask about sizing, material, delivery…" required></textarea>
              </label>
              <button type="submit" class="button small">Post Question</button>
            </form>
          </div>
        <?php else: ?>
          <p class="muted">Please <a href="login.php">log in</a> to ask a question.</p>
        <?php endif; ?>
      </div>

      <div class="section">
        <a href="products.php" class="button tiny secondary">← Back to Products</a>
      </div>
    </div>
  </div>

  <div class="row" style="margin-top:10px;">
    <div class="small-12">
      <footer style="margin-top:10px;">
        <p style="text-align:center; font-size:0.8em;clear:both;">&copy; Ceylon Fashion.lk. All Rights Reserved.</p>
      </footer>
    </div>
  </div>

  <script src="js/vendor/jquery.js"></script>
  <script src="js/foundation.min.js"></script>
  <script>$(document).foundation();</script>
</body>
</html>
