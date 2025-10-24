<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
include 'config.php';

// Flags
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
$isLoggedIn = isset($_SESSION['username']); 
?>
<!doctype html>
<html class="no-js" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Products || Ceylon Fashion.lk</title>
    <link rel="stylesheet" href="css/foundation.css" />
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <script src="js/vendor/modernizr.js"></script>
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

  <div class="bg-light" style="background-color:blueviolet;">
    <div class="container-fluid pt-1">
      <div class="cards-container mt-2">
        <div class="row cards-container">

          <?php
          $today = date('Y-m-d');
          $lastMonth = date('Y-m-d', strtotime('-1 month'));

          // Fetch bridemaid attire products
          $sql = "SELECT * FROM products ORDER BY id DESC";
          $newArrivals = $mysqli->query($sql);

          // Fallback image
          $fallback = 'assets/no-image.png'; // adjust path if needed

          if ($newArrivals && $newArrivals->num_rows > 0):
            while ($product = $newArrivals->fetch_assoc()):
              $pname = htmlentities($product['product_name'], ENT_QUOTES, 'UTF-8');
              $pimg  = htmlentities($product['product_img_name'], ENT_QUOTES, 'UTF-8');
              $price = number_format((float)$product['price'], 2);

              // Set proper image path
              $imgPath = 'images/products/' . $pimg;
              if (empty($pimg) || !file_exists($imgPath)) {
                $imgPath = $fallback;
              }
          ?>
            <!-- Product Card -->
            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
              <div class="card h-100 shadow-sm" style="background-color: white; border: 1px solid #ddd; display: flex; flex-direction: column; position: relative; overflow: hidden;">
                <!-- Product Image -->
                <div class="image-container position-relative">
                  <a href="product-view.php?id=<?php echo (int)$product['id']; ?>">
                    <img src="<?php echo $imgPath; ?>" class="card-img-top" alt="<?php echo $pname; ?>" />
                  </a>

                  <!-- Wishlist Button (heart icon in the top-right corner) -->
                  <div class="wishlist-wrapper d-flex align-items-center position-absolute top-0 end-0 m-2">
                    <button class="wishlist-btn btn btn-outline-danger d-flex align-items-center justify-content-center">
                      <i class="bi bi-heart"></i>
                    </button>
                  </div>

                  <!-- Cart Button (cart icon below the wishlist) -->
                  <div class="cart-wrapper d-flex align-items-center position-absolute top-50 end-0 m-2">
                    <button class="cart-btn btn btn-danger d-flex align-items-center justify-content-center">
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
            <p class="text-center text-light">No new arrivals found.</p>
          <?php endif; ?>


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
      /* Add gap between rows */
    }

    /* Make sure the product name and price are centered */
    .card-body {
      text-align: center;
    }

    /* Price alignment */
    .item-price {
      text-align: center;
    }

    /* Wishlist and Cart buttons styling */
    .wishlist-wrapper,
    .cart-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Button for the wishlist and cart */
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

    /* Wishlist button color */
    .wishlist-btn {
      border: 1px solid #dc3545;
      color: #dc3545;
    }

    /* Cart button color */
    .cart-btn {
      border: 1px solid #dc3545;
      color: white;
    }

    /* Show the icons only when hovering over the card */
    .card:hover .wishlist-btn,
    .card:hover .cart-btn {
      opacity: 1;
    }

    /* Cart button positioning */
    .cart-wrapper {
      top: 60% !important;
    }

    /* Ensure 5 products per row on large screens */
    @media (min-width: 992px) {
      .col-lg-2 {
        width: 20% !important;
        /* 5 products per row */
      }
    }

    /* Adjust for medium screens (3 cards per row) */
    @media (min-width: 768px) and (max-width: 991px) {
      .col-md-4 {
        width: 33.33% !important;
      }
    }

    /* Adjust for small screens (2 cards per row) */
    @media (min-width: 576px) and (max-width: 767px) {
      .col-sm-6 {
        width: 50% !important;
      }
    }

    /* Full width for very small screens (1 card per row) */
    @media (max-width: 575px) {
      .col-12 {
        width: 100% !important;
      }
    }
  </style>
  </body>
</html>
