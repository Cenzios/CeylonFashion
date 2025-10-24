<?php
session_start();
include 'config.php';

// --- Admin Access Check ---
if (!isset($_SESSION['username']) || $_SESSION['type'] !== 'admin') {
  header("Location: login.php");
  exit();
}

// --- Add Product Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $product_code = $mysqli->real_escape_string($_POST['product_code']);
  $product_name = $mysqli->real_escape_string($_POST['product_name']);
  $product_desc = $mysqli->real_escape_string($_POST['product_desc']);
  $qty = (int)$_POST['qty'];
  $price = (float)$_POST['price'];

  // Handle Image Upload
  $img_name = "";
  if (!empty($_FILES['product_img_name']['name'])) {
    $target_dir = "images/products/";
    $img_name = basename($_FILES['product_img_name']['name']);
    $target_file = $target_dir . $img_name;
    move_uploaded_file($_FILES['product_img_name']['tmp_name'], $target_file);
  }

  $query = "INSERT INTO products (product_code, product_name, product_desc, product_img_name, qty, price)
            VALUES ('$product_code', '$product_name', '$product_desc', '$img_name', '$qty', '$price')";

  if ($mysqli->query($query)) {
    $success = "Product added successfully!";
  } else {
    $error = "Error: " . $mysqli->error;
  }
}
?>

<!doctype html>
<html class="no-js" lang="en">
<head>
  <meta charset="utf-8" />
  <title>Add Product || Admin Panel</title>
  <link rel="stylesheet" href="css/foundation.css" />
  <style>
    body { margin: 30px; }
    form { background: #f9f9f9; padding: 20px; border-radius: 8px; }
    input, textarea { width: 100%; margin-bottom: 15px; padding: 8px; }
    .button { background: #0078A0; color: white; padding: 10px 20px; border: none; border-radius: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
  </style>
</head>
<body>

<h2>Add New Product</h2>

<?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
<?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

<form method="POST" enctype="multipart/form-data">
  <label>Product Code</label>
  <input type="text" name="product_code" required>

  <label>Product Name</label>
  <input type="text" name="product_name" required>

  <label>Description</label>
  <textarea name="product_desc" rows="4" required></textarea>

  <label>Quantity</label>
  <input type="number" name="qty" min="1" required>

  <label>Price</label>
  <input type="number" name="price" min="0" step="0.01" required>

  <label>Product Image</label>
  <input type="file" name="product_img_name" accept="image/*" required>

  <input type="submit" class="button" value="Add Product">
</form>

<br>
<a href="products.php" style="color:#0078A0;">← Back to Products</a>

</body>
</html>
