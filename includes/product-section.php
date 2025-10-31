<?php
function renderProductSection($options) {
  global $mysqli;
  
  $sectionId = $options['id'] ?? 'productSection';
  $title = $options['title'] ?? 'Products';
  $sql = $options['sql'] ?? "SELECT * FROM products ORDER BY id DESC";
  $viewAllLink = $options['view_all_link'] ?? '#';
  $requireLogin = $options['require_login'] ?? false;
  $fallback = 'assets/no-image.png';
  
  $result = $mysqli->query($sql);
?>

<div class="bg-light" id="<?php echo htmlspecialchars($sectionId); ?>" style="background-color:blueviolet;">
  <div class="container-fluid pt-1">
    <div class="cards-container" style="width: 100%;">
      <h2><?php echo htmlspecialchars($title); ?></h2>
      <div class="row cards-container">

        <?php
        if ($result && $result->num_rows > 0):
          while ($product = $result->fetch_assoc()):
            $pname = htmlentities($product['product_name'], ENT_QUOTES, 'UTF-8');
            $pimg  = htmlentities($product['product_img_name'], ENT_QUOTES, 'UTF-8');
            $price = number_format((float)$product['price'], 2);
            $productId = (int)$product['id'];

            $imgPath = 'images/products/' . $pimg;
            if (empty($pimg) || !file_exists($imgPath)) {
              $imgPath = $fallback;
            }
            
            $wishlistOnclick = $requireLogin ? 'onclick="requireLogin(event, \'wishlist\')"' : '';
            $cartOnclick = $requireLogin ? 'onclick="requireLogin(event, \'cart\')"' : '';
        ?>
            <!-- Product Card -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
              <div class="card h-100 shadow-sm" style="background-color: white; border: 1px solid #ddd; display: flex; flex-direction: column; position: relative; overflow: hidden;">
                <!-- Product Image -->
                <div class="image-container position-relative">
                  <a href="product-view.php?id=<?php echo $productId; ?>">
                    <img src="<?php echo $imgPath; ?>" class="card-img-top" alt="<?php echo $pname; ?>" />
                  </a>

                  <!-- Wishlist Button -->
                  <div class="wishlist-wrapper d-flex align-items-center position-absolute top-0 end-0 m-2">
                    <button class="wishlist-btn btn btn-outline-danger d-flex align-items-center justify-content-center" <?php echo $wishlistOnclick; ?>>
                      <i class="bi bi-heart"></i>
                    </button>
                  </div>

                  <!-- Cart Button -->
                  <div class="cart-wrapper d-flex align-items-center position-absolute top-50 end-0 m-2">
                    <button class="cart-btn btn btn-danger d-flex align-items-center justify-content-center" <?php echo $cartOnclick; ?>>
                      <i class="bi bi-cart" style="width: 50%;"></i>
                    </button>
                  </div>
                </div>

                <!-- Card Body -->
                <div class="card-body text-center">
                  <h6 class="card-title"><?php echo $pname; ?></h6>
                  <p class="card-text item-price text-primary fw-bold" style="text-align: center;">Rs. <?php echo $price; ?></p>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-center text-light">No products found.</p>
        <?php endif; ?>

        <div style="text-align: center; width: 100%;">
          <a href="<?php echo htmlspecialchars($viewAllLink); ?>" class="btn btn-primary mt-3">View All</a>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
  .card-img-top {
    height: 250px;
    object-fit: cover;
    width: 100%;
  }

  .card {
    border-radius: 8px;
    margin: 10px;
    display: flex;
    flex-direction: column;
  }

  .row {
    row-gap: 20px;
  }

  .card-body {
    text-align: center;
  }

  .item-price {
    text-align: center;
  }

  .wishlist-wrapper,
  .cart-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .wishlist-btn,
  .cart-btn {
    font-size: 1.5rem;
    padding: 10px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s ease;
  }

  .wishlist-btn {
    border: 1px solid #dc3545;
    color: #dc3545;
  }

  .cart-btn {
    border: 1px solid #dc3545;
    color: white;
  }

  .card:hover .wishlist-btn,
  .card:hover .cart-btn {
    opacity: 1;
  }

  .cart-wrapper {
    top: 60% !important;
  }

  @media (min-width: 992px) {
    .col-lg-2 {
      width: 20% !important;
    }
  }

  @media (min-width: 768px) and (max-width: 991px) {
    .col-md-4 {
      width: 33.33% !important;
    }
  }

  @media (min-width: 576px) and (max-width: 767px) {
    .col-sm-6 {
      width: 50% !important;
    }
  }

  @media (max-width: 575px) {
    .col-12 {
      width: 100% !important;
    }
  }
</style>

<?php
}
?>