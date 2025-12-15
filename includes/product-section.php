<?php
function renderProductSection($options) {
  global $mysqli;

  // Pre-fetch wishlist items
  $wishlistItems = [];
  if (isset($_SESSION['user_id'])) {
      $uid = (int)$_SESSION['user_id'];
      $wStmt = $mysqli->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
      $wStmt->bind_param("i", $uid);
      $wStmt->execute();
      $wRes = $wStmt->get_result();
      while ($wRow = $wRes->fetch_assoc()) {
          $wishlistItems[] = $wRow['product_id'];
      }
      $wStmt->close();
  } elseif (isset($_SESSION['guest_wishlist'])) {
      // guest_wishlist is [product_id => [...]]
      $wishlistItems = array_keys($_SESSION['guest_wishlist']);
  }
  
  $sectionId = $options['id'] ?? 'productSection';
  $title = $options['title'] ?? 'Products';
  $sql = $options['sql'] ?? "
    SELECT p.id, p.product_code, p.product_name, p.product_desc, p.product_img1, p.product_img2, p.product_img3, p.product_img4, p.category
    FROM products p
    ORDER BY p.id DESC
    LIMIT 6
  ";
  $viewAllLink = $options['view_all_link'] ?? '#';
  $requireLogin = $options['require_login'] ?? false;
  $fallback = 'assets/no-image.png';
  
  $result = $mysqli->query($sql);
?>

<div class="product-section" id="<?php echo htmlspecialchars($sectionId); ?>">
  <div class="section-header">
    <h2 class="section-title"><?php echo htmlspecialchars($title); ?></h2>
  </div>

  <div class="products-grid">
    <?php
    if ($result && $result->num_rows > 0):
      while ($product = $result->fetch_assoc()):
        $productId = (int)$product['id'];
        $pname = htmlentities($product['product_name'], ENT_QUOTES, 'UTF-8');
        $pcode = htmlentities($product['product_code'], ENT_QUOTES, 'UTF-8');
        
        // Get first available image from product_img1, product_img2, product_img3, product_img4
        $firstImage = '';
        for ($i = 1; $i <= 4; $i++) {
          $imgField = 'product_img' . $i;
          if (!empty($product[$imgField])) {
            $firstImage = $product[$imgField];
            break;
          }
        }
        
        $imgPath = 'images/products/' . $firstImage;
        if (empty($firstImage) || !file_exists($imgPath)) {
          $imgPath = $fallback;
        }

        // Fetch lowest fabric price for this product
        $fabricSql = "SELECT MIN(fabric_price) as min_price FROM product_fabrics WHERE product_id = ? AND fabric_qty > 0";
        $fabricStmt = $mysqli->prepare($fabricSql);
        $fabricStmt->bind_param("i", $productId);
        $fabricStmt->execute();
        $fabricResult = $fabricStmt->get_result();
        $fabricData = $fabricResult->fetch_assoc();
        $minPrice = $fabricData['min_price'] ?? null;
        $fabricStmt->close();
    ?>
        <!-- Product Card -->
        <div class="product-card">
          <!-- Action Icons -->
          <div class="card-actions">
            <?php 
            $inWishlist = in_array($productId, $wishlistItems);
            $activeClass = $inWishlist ? 'active' : '';
            ?>
            <button class="action-btn wishlist-btn <?php echo $activeClass; ?>" onclick="toggleWishlist(<?php echo $productId; ?>)" title="<?php echo $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>" aria-label="Wishlist">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
            </button>
            <!-- <button class="action-btn quickview-btn" onclick="quickView(<?php echo $productId; ?>)" title="Quick View" aria-label="Quick View">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button> -->
          </div>

          <a href="product-view.php?id=<?php echo $productId; ?>" class="card-link">
            <div class="product-image">
              <img src="<?php echo $imgPath; ?>" alt="<?php echo $pname; ?>" />
            </div>
          </a>
            
          <div class="product-info">
            <a href="product-view.php?id=<?php echo $productId; ?>" class="product-link">
              <h3 class="product-name"><?php echo $pname; ?></h3>
            </a>
            <?php if ($minPrice !== null): ?>
              <p class="product-price">Rs <?php echo number_format($minPrice, 2); ?></p>
            <?php else: ?>
              <p class="product-price">Price unavailable</p>
            <?php endif; ?>
          </div>

          <!-- Quick Add Button -->
          <div class="card-footer">
            <button class="btn-quick-add" onclick="quickView(<?php echo $productId; ?>)">QUICK ADD</button>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="no-products">No products found.</p>
    <?php endif; ?>
  </div>



  <div class="view-all-container">
    <a href="<?php echo htmlspecialchars($viewAllLink); ?>" class="btn-view-all" target="_blank">View All</a>
  </div>
</div>

<style>
  .product-section {
    width: 100%;
    padding: 40px 20px;
    background: #fff;
  }

  .section-header {
    text-align: center;
    margin-bottom: 30px;
  }

  .section-title {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    margin: 0;
  }

  /* Grid: 3 columns, 2 rows (shows 6 products) */
  .products-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .product-card {
    position: relative;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
  }

  /* Action Icons - Top Right */
  .card-actions {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 10;
  }

  .action-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    padding: 0;
  }

  .action-btn:hover {
    background: #fff;
    transform: scale(1.08);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }

  .action-btn svg {
    width: 22px;
    height: 22px;
    display: block;
    color: #1a1a1a;
    stroke-width: 2;
  }

  .wishlist-btn:hover svg {
    color: #e74c3c;
    stroke: #e74c3c;
  }

  .wishlist-btn.active svg {
    fill: #e74c3c;
    stroke: #e74c3c;
    color: #e74c3c;
  }

  .quickview-btn:hover svg {
    color: #2c3e50;
    stroke: #2c3e50;
  }

  .card-link {
    text-decoration: none;
    color: inherit;
    display: block;
  }

  .product-image {
    width: 100%;
    height: 400px;
    overflow: hidden;
    background: #f5f5f5;
  }

  .product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }

  .product-card:hover .product-image img {
    transform: scale(1.05);
  }

  .product-info {
    padding: 20px;
    background: #fff;
    text-align: center;
  }

  .product-link {
    text-decoration: none;
    color: inherit;
    display: block;
  }

  .product-name {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 10px 0;
    line-height: 1.3;
  }

  .product-price {
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
  }

  /* Quick Add Button */
  .card-footer {
    padding: 0 20px 20px 20px;
  }

  .btn-quick-add {
    width: 100%;
    padding: 14px 20px;
    background: #fff;
    color: #1a1a5e;
    border: 2px solid #1a1a5e;
    border-radius: 6px;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
  }

  .btn-quick-add:hover {
    background: #1a1a5e;
    color: #fff;
  }

  .no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #666;
    font-size: 18px;
  }

  /* View All Button */
  .view-all-container {
    text-align: center;
    margin-top: 40px;
  }

  .btn-view-all {
    display: inline-block;
    padding: 14px 40px;
    background: #7c3aed;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    transition: background 0.3s ease;
  }

  .btn-view-all:hover {
    background: #6d28d9;
  }

  /* Responsive Design */
  @media (max-width: 1024px) {
    .products-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .product-image {
      height: 350px;
    }
  }

  @media (max-width: 640px) {
    .product-section {
      padding: 30px 15px;
    }

    .section-title {
      font-size: 24px;
    }

    .products-grid {
      grid-template-columns: 1fr;
      gap: 20px;
      padding: 0 10px;
    }

    .product-image {
      height: 300px;
    }

    .card-actions {
      top: 10px;
      right: 10px;
      gap: 6px;
    }

    .action-btn {
      width: 40px;
      height: 40px;
    }

    .action-btn svg {
      width: 20px;
      height: 20px;
    }

    .product-name {
      font-size: 16px;
    }

    .product-price {
      font-size: 18px;
    }

    .btn-quick-add {
      padding: 12px 16px;
      font-size: 13px;
    }

    .btn-view-all {
      padding: 12px 30px;
      font-size: 14px;
    }
  }
</style>

<?php
}
?>