<?php

if (session_id() == '' || !isset($_SESSION)) {
  session_start();
}
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
include_once 'config.php';

$sql = "SELECT id, product_name, price, product_img_name FROM products WHERE category = 'used' ORDER BY id DESC";
$result = $mysqli->query($sql);
// $imgPath = 'Images/' . htmlentities($product['product_img_name']);
// if (empty($product['product_img_name']) || !file_exists($imgPath)) {
//     $imgPath = 'Images/default.png'; // fallback image
// }




?>

<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ceylon Fashion.lk</title>
  <link rel="stylesheet" href="css/foundation.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bootstrap demo</title>
  <style>


  </style>
  <link rel="stylesheet" href="style.css">
  </div>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

  <script src="js/vendor/modernizr.js"></script>
</head>

<body>











  <script src="js/vendor/jquery.js"></script>
  <script src="js/foundation.min.js"></script>
  <script>
    $(document).foundation();
  </script>
</body>



<body>
  <nav class="navbar navbar-expand-lg navbar-purple">
    <div class="container-fluid">
      <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
        <div class="navbar-nav">
    <a class="nav-link" href="#newArrivalsSection">New Arrivals</a>
        <a class="nav-link" href="#newArrivalsSection">Bridal Attire</a>

            <a class="nav-link" href="#brideMaidsSection">Bridemaids Attire</a>

                <a class="nav-link" href="#newArrivalsSection">Party Wear</a>

                    <a class="nav-link" href="#usedCollectionSection">Used Collection</a>
          <div class="text-center">
            <a href="/start-reselling.php" class="btn btn-primary">Start Reselling</a>
          </div>

        </div>

        <div class="icons" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); display: flex; gap: 15px; font-size: 32px; color: white; cursor: pointer;">
          <i class="bi bi-person-circle" id="personIcon"></i>
          <i class="bi bi-heart"></i>
          <i class="bi bi-cart2"></i>
        </div>

      </div>
    </div>
  </nav>





  <div class="container-fluid pt-3">
    <div class="row">
      <div class="col-12 col-md-6 col-lg-4">
        <form class="d-flex" role="search">
          <input class="form-control me-2 search-input" type="search" placeholder="Search by colour or Name" aria-label="Search" style="height: 38px;" />
          <button class="btn btn-search" type="submit" style="height: 38px;">Search</button>
        </form>
      </div>
    </div>
  </div>


  <div class="container-fluid">

    <div id="carouselExampleSlidesOnly" class="carousel slide" data-bs-interval="600" data-bs-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="Images/slider image-1.png" class="d-block w-100 banner-img" alt="...">
        </div>
        <div class="carousel-item">
          <img src="Images/slider image-2.png" class="d-block w-100 banner-img" alt="...">
        </div>
        <div class="carousel-item">
          <img src="Images/slider image-3.jpg" class="d-block w-100 banner-img" alt="...">
        </div>
      </div>
    </div>

    <!-- Promo cards Section -->
    <div class="container-fluid" style="padding: 0;">
      <div class="row g-3 pt-3">
        <div class="col-12 col-md-6">
          <div class="card h-100">
            <img src="Images/Banner image -01.png" class="card-img-top" alt="Background Image">
            <div class="card-body">
              <h5 class="card-title">
                Resell Your Ceylon-Fashion Outfit - Earn Up to 60% Back!
              </h5>
              <ul class="list-group list-group-flush">
                <li class="list-group-item">Only for verified City Fashion buyers</li>
                <li class="list-group-item">List within 3 months of purchase — zero fees!</li>
                <li class="list-group-item">Admin-approved listings for trust & quality</li>
                <li class="list-group-item">Give luxury outfits a second life & earn cash</li>
              </ul>
              <div class="mt-3 text-center">
                <a href="#" class="btn btn-primary" id="startResellingBtn">Start Reselling</a>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <div class="card h-100">
            <img src="Images/Banner image -2.png" class="card-img-top" alt="Background Image">
            <div class="card-body">
              <h5 class="card-title">
                Design Your Perfect outfit with Ceylon Fashion
              </h5>
              <ul class="list-group list-group-flush">
                <li class="list-group-item">Design Yours in 3 steps</li>
                <li class="list-group-item">Tailor Every Garment to Your style and size</li>
                <li class="list-group-item">Start by selecting Your favourite design</li>
                <li class="list-group-item">Make Your dream day reality with Unique a</li>
              </ul>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>

  <h2 class="text-center mb-4">Popular Colours in 2025</h2>

  <div class="d-flex justify-content-center gap-4 flex-wrap">
    <!-- Example button + label -->
    <div class="text-center">
      <button type="button" class="custom-btn btn-secondary"></button>
      <div class="mt-2">Grey</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-success"></button>
      <div class="mt-2">Green</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-danger"></button>
      <div class="mt-2">Red</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-warning"></button>
      <div class="mt-2">Orange</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-info"></button>
      <div class="mt-2">Blue</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-light text-dark"></button>
      <div class="mt-2">White</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-dark"></button>
      <div class="mt-2">Black</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-pink"></button>
      <div class="mt-2">Pink</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-purple"></button>
      <div class="mt-2">Purple</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-brown"></button>
      <div class="mt-2">Brown</div>
    </div>

    <div class="text-center">
      <button type="button" class="custom-btn btn-yellow"></button>
      <div class="mt-2">Yellow</div>
    </div>
  </div>

  <div class="bg-light" id="newArrivalsSection" style="background-color:blueviolet;">
    <div class="container-fluid pt-1">
      <div class="cards-container" style="width: 100%;">
        <h2>New Arrivals</h2>
        <div class="row cards-container">

          <?php
          // Calculate date range: last month to today
          $today = date('Y-m-d');
          $lastMonth = date('Y-m-d', strtotime('-1 month'));

          // Fetch new arrival products
          $sql = "SELECT * FROM products WHERE created BETWEEN '$lastMonth 00:00:00' AND '$today 23:59:59' ORDER BY created DESC";
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

          <div style="text-align: center; width: 100%;">
            <a href="new-arrivals.php" class="btn btn-primary mt-3">View All</a>
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










  <script>
    document.querySelectorAll('.wishlist-wrapper').forEach(wrapper => {
      const wishlistBtn = wrapper.querySelector('.wishlist-btn');
      const wishlistIcon = wishlistBtn.querySelector('i');
      const wishlistMsg = wrapper.querySelector('.wishlist-msg');

      wishlistBtn.addEventListener('click', (e) => {
        e.preventDefault();

        // Toggle heart icon + button color
        wishlistIcon.classList.toggle('bi-heart');
        wishlistIcon.classList.toggle('bi-heart-fill');
        wishlistBtn.classList.toggle('btn-danger');
        wishlistBtn.classList.toggle('btn-outline-danger');

        // Show/hide message
        if (wishlistIcon.classList.contains('bi-heart-fill')) {
          wishlistMsg.textContent = "Added to wishlist";
          wishlistMsg.style.display = "inline";
        } else {
          wishlistMsg.textContent = "";
          wishlistMsg.style.display = "none";
        }
      });
    });
  </script>

  <div class="bg-light" id="usedCollectionSection" style="background-color:blueviolet;">
    <div class="container-fluid pt-1">
      <div class="cards-container" style="width: 100%;">
        <h2>Used Collection</h2>
        <div class="row cards-container">

          <?php
          $today = date('Y-m-d');
          $lastMonth = date('Y-m-d', strtotime('-1 month'));

          // Fetch new arrival products
          $sql = "SELECT * FROM products WHERE category = 'used' ORDER BY id DESC";
          $usedSection = $mysqli->query($sql);

          // Fallback image
          $fallback = 'assets/no-image.png'; // adjust path if needed

          if ($usedSection && $usedSection->num_rows > 0):
            while ($product = $usedSection->fetch_assoc()):
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

          <div style="text-align: center; width: 100%;">
            <a href="new-arrivals.php" class="btn btn-primary mt-3">View All</a>
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




  <div class="bg-light" id="usedCollectionSection" style="background-color:blueviolet;">
    <div class="container-fluid pt-1">
      <div class="cards-container" style="width: 100%;">
        <h2>Bridal Attire</h2>
        <div class="row cards-container">

          <?php
          $today = date('Y-m-d');
          $lastMonth = date('Y-m-d', strtotime('-1 month'));

          // Fetch new arrival products
          $sql = "SELECT * FROM products WHERE category = 'bridalAttire' ORDER BY id DESC";
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

          <div style="text-align: center; width: 100%;">
            <a href="new-arrivals.php" class="btn btn-primary mt-3">View All</a>
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

  </div>

  <div class="bg-light" id="brideMaidsSection" style="background-color:blueviolet;">
    <div class="container-fluid pt-1">
      <div class="cards-container" style="width: 100%;">
        <h2>Bridemaid's Attire</h2>
        <div class="row cards-container">

          <?php
          $today = date('Y-m-d');
          $lastMonth = date('Y-m-d', strtotime('-1 month'));

          // Fetch new arrival products
          $sql = "SELECT * FROM products WHERE category = 'bridemaidAttire' ORDER BY id DESC";
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

          <div style="text-align: center; width: 100%;">
            <a href="new-arrivals.php" class="btn btn-primary mt-3">View All</a>
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


  <div class="bg-light" id="" style="background-color:blueviolet;">
    <div class="container-fluid pt-1">
      <div class="cards-container" style="width: 100%;">
        <h2>Party Wear</h2>
        <div class="row cards-container">

          <?php
          $today = date('Y-m-d');
          $lastMonth = date('Y-m-d', strtotime('-1 month'));

          // Fetch new arrival products
          $sql = "SELECT * FROM products WHERE category = 'partyWear' ORDER BY id DESC";
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

          <div style="text-align: center; width: 100%;">
            <a href="new-arrivals.php" class="btn btn-primary mt-3">View All</a>
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

  </div>


  <!-- This is the testomonial section -->

  <div class="bg-light">
    <div class="container-fluid pt-1 pb-4">
      <div class="cards-container">
        <h2 class="mb-2">What Our Brides Say</h2>
        <div id="carouselExampleCaptions" class="carousel slide">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
          </div>
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="Images/Testomonial image -1.jpg" class="d-block w-100" alt="...">
              <div class="carousel-caption d-none d-md-block">
                <h5>Shanika Perera-Colombo</h5>
                <p>“From the moment I tried on the saree, I knew it was the one. The detailing was exquisite, and the fabric felt luxurious yet breathable—perfect for my outdoor ceremony. What truly impressed me was the team’s attention to my preferences and the way they guided me through styling options. I received so many compliments, and I felt radiant the entire day. This platform made my bridal shopping feel personal and effortless.</p>
              </div>
            </div>
            <div class="carousel-item">
              <img src="Images/Testomonial image-2.jpg" class="d-block w-100" alt="...">
              <div class="carousel-caption d-none d-md-block">
                <h5>Dilani Fernando – Galle</h5>
                <p>“After my sister’s wedding, we listed her reception dress for resale through the platform. I was amazed at how smooth the process was—from uploading photos to setting a price, everything was intuitive. Within a week, we had multiple inquiries, and the final buyer was thrilled. It’s reassuring to know these beautiful outfits can have a second life, and the platform makes it feel like a community, not just a transaction.”</p>
              </div>
            </div>
            <div class="carousel-item">
              <img src="Images/Testomonial image-3.jpg" class="d-block w-100" alt="...">
              <div class="carousel-caption d-none d-md-block">
                <h5>Nuwani Jayasuriya – Kandy</h5>
                <p>“I needed a standout outfit for a corporate gala, and I found the perfect fusion saree here. The color was bold, the fit was flawless, and the delivery was prompt. What I loved most was the styling tips included with the product—small touches like that make a big difference. I’ve bookmarked the site for future events, and I’ve already recommended it to three friends. It’s rare to find fashion that’s both glamorous and grounded in real user care.”</p>
              </div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>
    </div>
  </div>
  </div>


  <div class="offcanvas offcanvas-end" tabindex="-1" id="loginSidebar" aria-labelledby="loginSidebarLabel">
    <div class="offcanvas-header"> Log In </div>
    <div class="offcanvas-body">
      <form id="offcanvasLoginForm"> <!-- 👈 add this id -->
        <div class="mb-3">
          <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
          <input type="email" class="form-control" id="email" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
          <input type="password" class="form-control" id="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">LOG IN</button>
        <div class="mt-3 text-center"><a href="#">Forgot your password?</a></div>
        <hr>
        <button type="button"
          class="btn btn-outline-secondary w-100"
          data-bs-toggle="offcanvas"
          data-bs-target="#registerSidebar"
          aria-controls="registerSidebar">
          CREATE ACCOUNT
        </button>
      </form>
    </div>
  </div>

  <!-- REGISTER SIDEBAR -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="registerSidebar" aria-labelledby="registerSidebarLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="registerSidebarLabel">Create Account</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <form method="POST" action="insert.php" id="offcanvasRegisterForm">
        <div class="mb-3">
          <label for="fname" class="form-label">First Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="fname" name="fname" placeholder="Nayan" required>
        </div>
        <div class="mb-3">
          <label for="lname" class="form-label">Last Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="lname" name="lname" placeholder="Seth" required>
        </div>
        <div class="mb-3">
          <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="address" name="address" placeholder="Infinite Loop" required>
        </div>
        <div class="mb-3">
          <label for="city" class="form-label">City <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="city" name="city" placeholder="Mumbai" required>
        </div>
        <div class="mb-3">
          <label for="pin" class="form-label">Pin Code <span class="text-danger">*</span></label>
          <input type="number" class="form-control" id="pin" name="pin" placeholder="400056" required>
        </div>
        <div class="mb-3">
          <label for="emailReg" class="form-label">Email <span class="text-danger">*</span></label>
          <input type="email" class="form-control" id="emailReg" name="email" placeholder="nayantronix@gmail.com" required>
        </div>
        <div class="mb-3">
          <label for="pwd" class="form-label">Password <span class="text-danger">*</span></label>
          <input type="password" class="form-control" id="pwd" name="pwd" required>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary w-100">REGISTER</button>
        </div>
        <div class="mt-3 text-center">
          <small>Already have an account? <a href="#" data-bs-toggle="offcanvas" data-bs-target="#loginSidebar" aria-controls="loginSidebar">Log in</a></small>
        </div>
      </form>
    </div>
  </div>



  <footer class="custom-footer text-dark pt-5 pb-4 w-100 mt-5">
    <div class="container text-center text-md-start">
      <div class="row text-center text-md-start" style="">

        <!-- About Us Section -->
        <div class="col-md- 3 col-lg-3 col-xl-3 mx-auto mt-3">
          <h5 class="text-uppercase mb-4 fw-bold">About Us</h5>
          <p>
            At Ceylon Fashion.lk, we bring you the finest bridal, party, and traditional wear collections.
            Our goal is to make every moment memorable with timeless fashion.
          </p>
          <h6 class="fw-bold mt-3">
            <a href="#contact" class="text-dark text-decoration-none">Contact Us</a>
          </h6>
          <p class="mb-1"><i class="bi bi-geo-alt-fill me-2"></i> 123 Main Street, Colombo, Sri Lanka</p>
          <p class="mb-1"><i class="bi bi-telephone-fill me-2"></i> +94 77 123 4567</p>
          <p><i class="bi bi-envelope-fill me-2"></i> support@ceylonfashion.lk</p>
        </div>

        <!-- Information Section (NEW) -->
        <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
          <h5 class="text-uppercase mb-4 fw-bold">Information</h5>
          <p><a href="#" class="text-dark text-decoration-none">Shipping Policy</a></p>
          <p><a href="#" class="text-dark text-decoration-none">Returns & Exchanges</a></p>
          <p><a href="#" class="text-dark text-decoration-none">Terms & Conditions</a></p>
          <p><a href="#" class="text-dark text-decoration-none">Privacy Policy</a></p>
          <p><a href="#" class="text-dark text-decoration-none">FAQ</a></p>
        </div>

        <!-- Products Section -->
        <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mt-3">
          <h5 class="text-uppercase mb-4 fw-bold">Products</h5>
          <p><a href="#" class="text-dark text-decoration-none">Bridal Attire</a></p>
          <p><a href="#" class="text-dark text-decoration-none">Bridemaid's Attire</a></p>
          <p><a href="#" class="text-dark text-decoration-none">Party Wear</a></p>
          <p><a href="#" class="text-dark text-decoration-none">Used Collection</a></p>
        </div>

        <!-- Newsletter Section -->
        <div class="col-md-5 col-lg-5 col-xl-5 mx-auto mt-3">
          <h5 class="text-uppercase mb-4 fw-bold">Subscribe to Newsletter</h5>
          <form class="d-flex">
            <input type="email" class="form-control me-2 search-input" placeholder="Enter your email" style="height: 50px;">
            <button class="btn btn-search" style="height: 50px;">Subscribe</button>
          </form>
        </div>

      </div>

      <hr class="my-4 text-white">

      <!-- Copyright -->
      <div class="row">
        <div class="col text-center">
          <p class="mb-0">©2025 Ceylon Fashion.lk All Rights Reserved</p>
        </div>
      </div>
    </div>
  </footer>

<script>
  function showSection(section) {
    // Hide both sections by default
    document.getElementById('newArrivalsSection').style.display = 'none';
    document.getElementById('usedCollectionSection').style.display = 'none';
        document.getElementById('brideMaidsSection').style.display = 'none';
    document.getElementById('partySection').style.display = 'none';
        document.getElementById('bridalSection').style.display = 'none';

    // Show the selected section
    if (section === 'newArrivals') {
      document.getElementById('newArrivalsSection').style.display = 'block';
    } else if (section === 'usedCollection') {
      document.getElementById('usedCollectionSection').style.display = 'block';

        } else if (section === 'brideMaidCollection') {
      document.getElementById('brideMaidsSection').style.display = 'block';

        } else if (section === 'partyCollection') {
      document.getElementById('partySection').style.display = 'block';

        } else if (section === 'bridalCollection') {
      document.getElementById('bridalSection').style.display = 'block';
    }
  }
</script>



  <!-- This is the Footer section -->

  <script>
    (function() {
      const form = document.getElementById('offcanvasLoginForm');
      if (!form) return;

      const emailEl = document.getElementById('email');
      const pwdEl = document.getElementById('password');

      function getCsrf() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
      }

      form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = (emailEl.value || '').trim();
        const pwd = (pwdEl.value || '');

        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        if (!emailOk) {
          alert('Please enter a valid email address.');
          emailEl.focus();
          return;
        }
        if (pwd.length < 6) {
          alert('Password must be at least 6 characters.');
          pwdEl.focus();
          return;
        }

        const body = new URLSearchParams();
        body.set('username', email);
        body.set('pwd', pwd);
        body.set('csrf_token', getCsrf());

        try {
          const res = await fetch('verify.php', {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: body.toString()
          });

          if (!res.ok) {
            alert('Login failed. Please try again.');
            return;
          }

          const data = await res.json();
          if (data.ok) {
            window.location.href = data.redirect || 'index.php';
          } else {
            alert(data.message || 'Invalid email or password.');
          }
        } catch (err) {
          console.error(err);
          alert('Network error. Please try again.');
        }
      });
    })();
  </script>




  <script>
    document.querySelectorAll('.custom-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        // Remove "selected" from all color buttons
        document.querySelectorAll('.custom-btn').forEach(b => b.classList.remove('selected'));
        // Add "selected" to the clicked one
        btn.classList.add('selected');
      });
    });
  </script>




  <script>
    src = "script.js"
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const loginSidebar = new bootstrap.Offcanvas(document.getElementById('loginSidebar'));

      document.getElementById('startResellingBtn').addEventListener('click', function(e) {
        e.preventDefault();
        loginSidebar.show();
      });

      document.getElementById('personIcon').addEventListener('click', function() {
        loginSidebar.show();
      });
    });
  </script>

</body>



</html>