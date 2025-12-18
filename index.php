<?php
if (session_id() == '' || !isset($_SESSION)) {
  session_start();
}
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
include_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>
  <?php include 'includes/navbar.php'; ?>
  
  <?php include 'includes/search-bar.php'; ?>
  
  <?php include 'includes/hero-carousel.php'; ?>
  
  <?php include 'includes/promo-cards.php'; ?>
  
  <!-- <?php include 'includes/color-buttons.php'; ?> -->
  
  <?php 
  // New Arrivals Section
  include 'includes/product-section.php'; 
  renderProductSection([
    'id' => 'newArrivalsSection',
    'title' => 'New Arrivals',
    'sql' => "SELECT * FROM products WHERE category != 'used' AND created BETWEEN '" . date('Y-m-d', strtotime('-1 month')) . " 00:00:00' AND '" . date('Y-m-d') . " 23:59:59' ORDER BY created DESC LIMIT 6",
    'view_all_link' => 'new-arrivals.php'
  ]);
  ?>
  
  <?php 
  // Used Collection Section
  renderProductSection([
    'id' => 'usedCollectionSection',
    'title' => 'Used Collection',
    'sql' => "SELECT * FROM products WHERE category = 'used' ORDER BY id DESC LIMIT 6",
    'view_all_link' => 'used-collection.php'
  ]);
  ?>
  
  <?php 
  // Bridal Attire Section
  renderProductSection([
    'id' => 'bridalAttireSection',
    'title' => 'Bridal Attire',
    'sql' => "SELECT * FROM products WHERE category = 'bridalAttire' ORDER BY id DESC LIMIT 6",
    'view_all_link' => 'bridal-attire.php'
  ]);
  ?>
  
  <?php 
  // Bridemaids Section
  renderProductSection([
    'id' => 'brideMaidsSection',
    'title' => "Bridemaid's Attire",
    'sql' => "SELECT * FROM products WHERE category = 'bridemaidAttire' ORDER BY id DESC LIMIT 6",
    'view_all_link' => 'bridemaids-attire.php'
  ]);
  ?>
  
  <?php 
  // Party Wear Section
  renderProductSection([
    'id' => 'partyWearSection',
    'title' => 'Party Wear',
    'sql' => "SELECT * FROM products WHERE category = 'partyWear' ORDER BY id DESC LIMIT 6",
    'view_all_link' => 'party-wear.php'
  ]);
  ?>
  
  <?php include 'includes/testimonials.php'; ?>
  
  <?php include 'includes/login-sidebar.php'; ?>
  
  <?php include 'includes/register-sidebar.php'; ?>
  
  <?php include 'includes/footer.php'; ?>
  
  <?php include 'includes/scripts.php'; ?>
</body>
</html>