<?php
function renderProductSection($options) {
  global $mysqli;
  
  $sectionId = $options['id'] ?? 'productSection';
  $title = $options['title'] ?? 'Products';
  $sql = $options['sql'] ?? "
    SELECT p.id, p.product_code, p.product_name, p.product_desc, p.product_img_name, p.category
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
        $pimg  = htmlentities($product['product_img_name'], ENT_QUOTES, 'UTF-8');

        // Get first image from comma-separated list
        $images = array_filter(array_map('trim', explode(',', $pimg)));
        $firstImage = !empty($images) ? $images[0] : '';
        
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
          <a href="product-view.php?id=<?php echo $productId; ?>" class="card-link">
            <div class="product-image">
              <img src="<?php echo $imgPath; ?>" alt="<?php echo $pname; ?>" />
            </div>
            
            <div class="product-info">
              <h3 class="product-name"><?php echo $pname; ?></h3>
              <?php if ($minPrice !== null): ?>
                <p class="product-price">Rs : <?php echo number_format($minPrice, 2); ?></p>
              <?php else: ?>
                <p class="product-price">Price unavailable</p>
              <?php endif; ?>
            </div>
          </a>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="no-products">No products found.</p>
    <?php endif; ?>
  </div>

  <div class="view-all-container">
    <a href="<?php echo htmlspecialchars($viewAllLink); ?>" class="btn-view-all">View All</a>
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
    background: linear-gradient(135deg, #d8b4e2 0%, #c9a8d8 100%);
    text-align: left;
  }

  .product-name {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0 0 8px 0;
    line-height: 1.3;
  }

  .product-price {
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
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

    .product-name {
      font-size: 16px;
    }

    .product-price {
      font-size: 18px;
    }

    .btn-view-all {
      padding: 12px 30px;
      font-size: 14px;
    }
  }
</style>

<script>
  function toggleWishlist(productId) {
    fetch('wishlist-toggle.php?id=' + productId)
      .then(response => response.json())
      .then(data => {
        if (data.status === 'ok') {
          alert('Wishlist updated!');
        } else if (data.status === 'login_required') {
          alert('Please log in to add to wishlist.');
        } else {
          alert('Failed to update wishlist.');
        }
      })
      .catch(err => console.error(err));
  }
</script>

<?php
}
?>