<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }

if (!isset($_SESSION["username"])) {
  echo '<h1>Invalid Login! Redirecting...</h1>';
  header("Refresh: 3; url=index.php");
  exit;
}

if ($_SESSION["type"] === "admin") {
  header("location: admin.php");
  exit;
}

include 'config.php';

// Ensure session variables exist
$id   = $_SESSION['id'] ?? 0;
$fname = $_SESSION['fname'] ?? '';
$lname = $_SESSION['lname'] ?? '';
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Account || Ceylon Fashion.lk</title>
  <link rel="stylesheet" href="css/foundation.css" />
  <script src="js/vendor/modernizr.js"></script>
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
      <li class="active"><a href="account.php">My Account</a></li>
      <li><a href="logout.php">Log Out</a></li>
    </ul>
  </section>
</nav>

<div class="row" style="margin-top:30px;">
  <div class="small-12">
    <p><h3>Hi <?php echo htmlspecialchars($fname); ?></h3></p>
    <p><h4>Account Details</h4></p>
    <p>Below are your details in the database. If you wish to change anything, then just enter new data in the text box and click on update.</p>
  </div>
</div>

<form method="POST" action="update.php" style="margin-top:30px;">
  <div class="row">
    <div class="small-12">
      <?php
      if ($id > 0) {
        $result = $mysqli->query("SELECT * FROM users WHERE id = $id");
        if ($result && $obj = $result->fetch_object()) {
          echo '<div class="row"><div class="small-3 columns"><label for="fname" class="right inline">First Name</label></div>
                <div class="small-8 columns end"><input type="text" id="fname" placeholder="'.htmlspecialchars($obj->fname).'" name="fname"></div></div>';

          echo '<div class="row"><div class="small-3 columns"><label for="lname" class="right inline">Last Name</label></div>
                <div class="small-8 columns end"><input type="text" id="lname" placeholder="'.htmlspecialchars($obj->lname).'" name="lname"></div></div>';

          echo '<div class="row"><div class="small-3 columns"><label for="address" class="right inline">Address</label></div>
                <div class="small-8 columns end"><input type="text" id="address" placeholder="'.htmlspecialchars($obj->address).'" name="address"></div></div>';

          echo '<div class="row"><div class="small-3 columns"><label for="city" class="right inline">City</label></div>
                <div class="small-8 columns end"><input type="text" id="city" placeholder="'.htmlspecialchars($obj->city).'" name="city"></div></div>';

          echo '<div class="row"><div class="small-3 columns"><label for="pin" class="right inline">Pin Code</label></div>
                <div class="small-8 columns end"><input type="text" id="pin" placeholder="'.htmlspecialchars($obj->pin).'" name="pin"></div></div>';

          echo '<div class="row"><div class="small-3 columns"><label for="email" class="right inline">Email</label></div>
                <div class="small-8 columns end"><input type="email" id="email" placeholder="'.htmlspecialchars($obj->email).'" name="email"></div></div>';
        }
      }
      ?>

      <div class="row">
        <div class="small-3 columns"><label for="pwd" class="right inline">Password</label></div>
        <div class="small-8 columns end"><input type="password" id="pwd" name="pwd"></div>
      </div>

      <div class="row">
        <div class="small-4 columns"></div>
        <div class="small-8 columns">
          <input type="submit" value="Update" style="background:#0078A0;border:none;color:#fff;font-size:1em;padding:10px;">
          <input type="reset" value="Reset" style="background:#0078A0;border:none;color:#fff;font-size:1em;padding:10px;">
        </div>
      </div>
    </div>
  </div>
</form>

<div class="row" style="margin-top:30px;">
  <div class="small-12">
    <footer><p style="text-align:center;font-size:0.8em;">&copy; Ceylon Fashion.lk. All Rights Reserved.</p></footer>
  </div>
</div>

<script src="js/vendor/jquery.js"></script>
<script src="js/foundation.min.js"></script>
<script>$(document).foundation();</script>
</body>
</html>
