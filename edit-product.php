<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
include 'config.php';

// Restrict to admin only
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'admin') {
  header("Location: login.php");
  exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  die('Invalid Product ID.');
}

// Fetch existing product
$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  die('Product not found.');
}
$product = $result->fetch_assoc();
$stmt->close();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $product_code = $mysqli->real_escape_string($_POST['product_code']);
  $product_name = $mysqli->real_escape_string($_POST['product_name']);
  $product_desc = $mysqli->real_escape_string($_POST['product_desc']);
  $qty = (int)$_POST['qty'];
  $price = (float)$_POST['price'];
  $product_img_name = $product['product_img_name'];

  // Handle image upload (optional)
  if (!empty($_FILES['product_img_name']['name'])) {
    $upload_dir = "images/products/";
    $img_name = basename($_FILES['product_img_name']['name']);
    $target_file = $upload_dir . $img_name;

    if (move_uploaded_file($_FILES['product_img_name']['tmp_name'], $target_file)) {
      $product_img_name = $img_name;
    } else {
      $error = "Failed to upload image.";
    }
  }

  if (empty($error)) {
    $update = $mysqli->prepare("UPDATE products 
      SET product_code=?, product_name=?, product_desc=?, product_img_name=?, qty=?, price=? 
      WHERE id=?");
    $update->bind_param("ssssidi", $product_code, $product_name, $product_desc, $product_img_name, $qty, $price, $id);

    if ($update->execute()) {
      $success = "Product updated successfully!";
      // Refresh data
      $product['product_code'] = $product_code;
      $product['product_name'] = $product_name;
      $product['product_desc'] = $product_desc;
      $product['qty'] = $qty;
      $product['price'] = $price;
      $product['product_img_name'] = $product_img_name;
    } else {
      $error = "Error updating product: " . $mysqli->error;
    }
    $update->close();
  }
}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Product || Admin</title>
  <link rel="stylesheet" href="css/foundation.css" />
  <style>
    body { margin: 30px; }
    .button { background: #0078A0; color: white; border: none; padding: 10px 20px; border-radius: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    img.preview { max-width: 200px; margin-top: 10px; border-radius: 4px; }
  </style>
</head>
<body>

  <h2>Edit Product</h2>

  <?php if ($success) echo "<p class='success'>$success</p>"; ?>
  <?php if ($error) echo "<p class='error'>$error</p>"; ?>

  <form method="POST" enctype="multipart/form-data">
    <label>Product Code</label>
    <input type="text" name="product_code" value="<?php echo htmlentities($product['product_code']); ?>" required>

    <label>Product Name</label>
    <input type="text" name="product_name" value="<?php echo htmlentities($product['product_name']); ?>" required>

    <label>Description</label>
    <textarea name="product_desc" rows="4" required><?php echo htmlentities($product['product_desc']); ?></textarea>

    <label>Quantity</label>
    <input type="number" name="qty" min="0" value="<?php echo (int)$product['qty']; ?>" required>

    <label>Price</label>
    <input type="number" name="price" min="0" step="0.01" value="<?php echo number_format((float)$product['price'], 2, '.', ''); ?>" required>

    <label>Replace Product Image (optional)</label>
    <input type="file" name="product_img_name" accept="image/*">
    <?php if (!empty($product['product_img_name'])): ?>
      <img src="images/products/<?php echo $product['product_img_name']; ?>" class="preview" alt="Product Image">
    <?php endif; ?>

    <br><br>
    <button type="submit" class="button">Save Changes</button>
    <a href="products.php" class="button secondary">← Back to Products</a>
  </form>

  <footer style="margin-top:20px;">
    <p style="text-align:center; font-size:0.8em;">&copy; Ceylon Fashion.lk. All Rights Reserved.</p>
  </footer>

</body>
</html>
