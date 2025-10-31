<nav class="navbar navbar-expand-lg navbar-purple">
  <div class="container-fluid">
    <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav">
        <a class="nav-link" href="#newArrivalsSection">New Arrivals</a>
        <a class="nav-link" href="#bridalAttireSection">Bridal Attire</a>
        <a class="nav-link" href="#brideMaidsSection">Bridemaids Attire</a>
        <a class="nav-link" href="#partyWearSection">Party Wear</a>
        <a class="nav-link" href="#usedCollectionSection">Used Collection</a>
        
        <!-- Start Reselling Button - Show only when logged in -->
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
          <div class="text-center">
            <a href="start-reselling.php" class="btn btn-primary">Start Reselling</a>
          </div>
        <?php endif; ?>
      </div>

      <div class="icons" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); display: flex; gap: 15px; font-size: 32px; color: white; cursor: pointer;">
        
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
          <!-- Show logout button when logged in -->
          <a href="logout.php" style="text-decoration: none; color: white;" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
          </a>
        <?php else: ?>
          <!-- Show profile/login icon when not logged in -->
          <i class="bi bi-person-circle" id="personIcon"></i>
        <?php endif; ?>
        
        <i class="bi bi-heart" id="wishlistIcon"></i>
        <i class="bi bi-cart2" id="cartIcon"></i>
      </div>
    </div>
  </div>
</nav>