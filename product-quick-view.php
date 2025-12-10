<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
require_once 'config.php';

// Validate product id
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  echo '<p>Invalid product id</p>';
  exit;
}
$productId = (int)$_GET['id'];

// Fetch product
$stmt = $mysqli->prepare("SELECT id, product_code, product_name, product_desc, price, qty, product_img_name FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();

if (!$product) {
  echo '<p>Product not found</p>';
  exit;
}

// Fetch avg rating
$stmt = $mysqli->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM product_reviews WHERE product_id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$ratingData = $stmt->get_result()->fetch_assoc();
$stmt->close();

$avgRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : null;
$reviewCount = $ratingData['review_count'];

$currency = $currency ?? '$';
?>
<div class="row">
  <div class="large-6 columns">
    <img src="images/products/<?=htmlentities($product['product_img_name'])?>" alt="<?=htmlentities($product['product_name'])?>" style="width:100%; border-radius:6px;">
  </div>
  <div class="large-6 columns">
    <h3><?=htmlentities($product['product_name'])?></h3>
    <div class="meta" style="margin-bottom:10px; color:#666; font-size:0.9em;">
      <span class="badge secondary">Code: <?=htmlentities($product['product_code'])?></span>
      <?php if ($avgRating !== null): ?>
        <span style="margin-left:8px; color:#ffb400;">★ <?=htmlentities($avgRating)?>/5</span>
        <span style="color:#888;">(<?=htmlentities($reviewCount)?> reviews)</span>
      <?php endif; ?>
    </div>

    <p><?=nl2br(htmlentities($product['product_desc']))?></p>

    <div class="price" style="font-size:1.5em; font-weight:bold; margin:15px 0; color:#333;">
      <?=$currency?><?=number_format((float)$product['price'], 2)?>
    </div>

    <div style="margin-bottom:15px;">
      <?php if ((int)$product['qty'] > 0): ?>
        <span class="label success">In stock: <?= (int)$product['qty'] ?></span>
      <?php else: ?>
        <span class="label alert">Out of stock</span>
      <?php endif; ?>
    </div>

    <div style="display:flex; gap:10px;">
      <a href="product-view.php?id=<?=$productId?>" class="button small secondary" target="_blank">View Full Details</a>
      <?php if ((int)$product['qty'] > 0): ?>
        <button class="button small success" onclick="quickAddToCart(<?=$productId?>)">Add to Cart</button>
      <?php endif; ?>
    </div>
  </div>
</div>
<a class="close-reveal-modal" aria-label="Close">&#215;</a>
