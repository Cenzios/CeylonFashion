<nav class="navbar navbar-expand-lg navbar-purple">
  <div class="container-fluid">
    <a href="index.php" class="navbar-brand">
      <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav">
        <a class="nav-link" href="index.php#newArrivalsSection">New Arrivals</a>
        <a class="nav-link" href="index.php#bridalAttireSection">Bridal Attire</a>
        <a class="nav-link" href="index.php#brideMaidsSection">Bridemaids Attire</a>
        <a class="nav-link" href="index.php#partyWearSection">Party Wear</a>
        <a class="nav-link" href="index.php#usedCollectionSection">Used Collection</a>
        
        <!-- Start Reselling Button - Visible to all -->
        <div class="text-center">
          <a href="start-reselling.php" class="btn btn-primary" id="startResellingBtn">Start Reselling</a>
        </div>
      </div>
      <div class="icons" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); display: flex; gap: 15px; align-items: center; font-size: 32px; color: white; z-index: 1040;">
        
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
          <!-- Profile Dropdown when logged in -->
          <div class="dropdown" style="position: relative;">
            <i class="bi bi-person-circle" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;"></i>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown" style="position: absolute; z-index: 1050;">
              <li><a class="dropdown-item" href="account.php"><i class="bi bi-person"></i> Profile</a></li>
              <li><a class="dropdown-item" href="orders.php"><i class="bi bi-box-seam"></i> My Orders</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
          </div>
          
          <!-- Wishlist and Cart links (logged in users) -->
          <?php
          require_once __DIR__ . '/../lib/guest-cart.php';
          if (!isset($mysqli)) {
              require_once __DIR__ . '/../config.php';
          }
          
          // Check if user is deleted (Active Session Invalidation)
          $checkUserStmt = $mysqli->prepare("SELECT is_deleted FROM users WHERE id = ?");
          $checkUserStmt->bind_param("i", $_SESSION['user_id']);
          $checkUserStmt->execute();
          $checkUserRes = $checkUserStmt->get_result();
          if ($checkUserRow = $checkUserRes->fetch_assoc()) {
              if ($checkUserRow['is_deleted'] == 1) {
                  // User is deleted, destroy session and redirect
                  // Use JS redirect since headers are likely sent
                  echo "<script>alert('Your account is no longer active.'); window.location.href='logout.php';</script>";
                  exit;
              }
          }
          $checkUserStmt->close();

          $user_id = (int)$_SESSION['user_id'];
          $wishlistCount = getUserWishlistCount($mysqli, $user_id);
          $cartCount = getUserCartCount($mysqli, $user_id);
          ?>
          <a href="wishlist.php" style="text-decoration: none; color: white; position: relative;">
            <i class="bi bi-heart" id="wishlistIcon" style="cursor: pointer;"></i>
            <?php if ($wishlistCount > 0): ?>
              <span id="wishlistBadge" class="badge bg-danger" style="position: absolute; top: -8px; right: -8px; font-size: 10px; padding: 2px 5px;"><?php echo $wishlistCount; ?></span>
            <?php endif; ?>
          </a>
          <a href="cart.php" style="text-decoration: none; color: white; position: relative;">
            <i class="bi bi-cart2" id="cartIcon" style="cursor: pointer;"></i>
            <?php if ($cartCount > 0): ?>
              <span id="cartBadge" class="badge bg-danger" style="position: absolute; top: -8px; right: -8px; font-size: 10px; padding: 2px 5px;"><?php echo $cartCount; ?></span>
            <?php endif; ?>
          </a>
          
        <?php else: ?>
          <!-- Show Login button when not logged in -->
          <button 
            class="btn btn-light btn-sm" 
            style="font-size: 14px; padding: 6px 16px;" 
            type="button" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#loginSidebar" 
            aria-controls="loginSidebar">
            Login
          </button>
          
          <!-- Wishlist and Cart icons (guest users can access) -->
          <a href="wishlist.php" style="text-decoration: none; color: white; position: relative;">
            <i class="bi bi-heart" id="wishlistIcon" style="cursor: pointer;"></i>
            <?php 
            require_once __DIR__ . '/../lib/guest-cart.php';
            $wishlistCount = getGuestWishlistCount();
            if ($wishlistCount > 0): ?>
              <span id="wishlistBadge" class="badge bg-danger" style="position: absolute; top: -8px; right: -8px; font-size: 10px; padding: 2px 5px;"><?php echo $wishlistCount; ?></span>
            <?php endif; ?>
          </a>
          <a href="cart.php" style="text-decoration: none; color: white; position: relative;">
            <i class="bi bi-cart2" id="cartIcon" style="cursor: pointer;"></i>
            <?php 
            $cartCount = getGuestCartCount();
            if ($cartCount > 0): ?>
              <span id="cartBadge" class="badge bg-danger" style="position: absolute; top: -8px; right: -8px; font-size: 10px; padding: 2px 5px;"><?php echo $cartCount; ?></span>
            <?php endif; ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<style>
/* Fix z-index for navbar and dropdown */
.navbar-purple {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 1030 !important;
}

body {
  padding-top: 80px;
}

/* Navbar brand/logo link */
.navbar-brand {
  padding: 0;
  margin-right: 1rem;
}

.navbar-brand:hover {
  opacity: 0.8;
}

/* Ensure offcanvas appears above everything */
.offcanvas {
  z-index: 1060 !important;
}

.offcanvas-backdrop {
  z-index: 1055 !important;
}

/* Custom styling for the profile dropdown */
.dropdown-menu {
  min-width: 180px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  z-index: 1050 !important;
  position: absolute !important;
}

.dropdown-item {
  padding: 10px 20px;
  font-size: 15px;
}

.dropdown-item i {
  margin-right: 8px;
  width: 16px;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}

/* Ensure the profile icon has hover effect */
#profileDropdown:hover {
  opacity: 0.8;
}

/* Make login button match the theme */
.btn-light {
  background-color: white;
  border: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-light:hover {
  background-color: #f8f9fa;
  transform: scale(1.05);
}

/* Icon hover effects */
.icons a:hover i,
.icons i:hover {
  opacity: 0.8;
  transform: scale(1.1);
  transition: all 0.2s ease;
}

/* Fix carousel z-index if needed */
.carousel,
#heroCarousel,
.hero-section {
  position: relative;
  z-index: 1 !important;
}

/* Login message styles */
.login-message-success {
  background-color: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}

.login-message-error {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

/* Responsive navbar icons */
@media (max-width: 991px) {
  .icons {
    position: relative !important;
    right: auto !important;
    top: auto !important;
    transform: none !important;
    margin-top: 15px;
    justify-content: center;
  }
  
  .navbar-nav {
    text-align: center;
  }
  
  .navbar-nav .text-center {
    margin-top: 10px;
  }
}
</style>