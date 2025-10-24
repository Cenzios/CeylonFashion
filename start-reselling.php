<?php
session_start();
include_once 'config.php'; // Database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect if the user is not logged in
    exit();
}

// Handle product addition form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addProduct'])) {
    // Fetch form data
    $productName = $_POST['product_name'];
    $productDescription = $_POST['product_description'];
    $price = $_POST['price'];
    $category = $_POST['category']; // This will be 'used'
    $imageName = ''; // Handle image upload logic here

    // Validate the product description
    if (empty($productDescription)) {
        echo "Product description is required.";
        exit(); // Prevent further execution if the description is missing
    }

    // Handle image upload
    if ($_FILES['product_image']['error'] == 0) {
        $imageName = basename($_FILES['product_image']['name']);
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create the directory if it doesn't exist
        }
        $uploadPath = $uploadDir . $imageName;
        move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadPath);
    }

    // Insert product into the database
    $sql = "INSERT INTO products (product_name, product_desc, price, category, product_img_name, status, reseller_id)
            VALUES (?, ?, ?, ?, ?, 'pending', ?)";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("ssdsis", $productName, $productDescription, $price, $category, $imageName, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect or refresh after successful insertion
    header("Location: start-reselling.php"); // Redirect to the same page to show the updated list
    exit();
}

// Include your header here
include_once $_SERVER['DOCUMENT_ROOT'] . '/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller - Add Product</title>
    <link rel="stylesheet" href="css/foundation.css" />
    <link rel="stylesheet" href="style.css">
    <script src="js/vendor/modernizr.js"></script>
</head>
<body>

  <!-- Reseller Dashboard -->
  <div class="container mt-5">
      <h2>Reseller Dashboard</h2>

      <!-- Add Product Form -->
      <h3>Add Product for Reselling</h3>
      <form action="start-reselling.php" method="POST" enctype="multipart/form-data">
          <div class="form-group">
              <label for="product_name">Product Name</label>
              <input type="text" name="product_name" class="form-control" required>
          </div>

          <div class="form-group">
              <label for="product_description">Description</label>
              <textarea name="product_description" class="form-control" required></textarea>
          </div>

          <div class="form-group">
              <label for="price">Price</label>
              <input type="number" name="price" class="form-control" step="0.01" required>
          </div>

          <!-- Category (auto-selected as 'used') -->
          <div class="form-group">
              <label for="category">Category</label>
              <select name="category" class="form-control" required disabled>
                  <option value="used" selected>Used</option>
              </select>
          </div>

          <div class="form-group">
              <label for="product_image">Product Image</label>
              <input type="file" name="product_image" class="form-control" accept="image/*">
          </div>

          <button type="submit" name="addProduct" class="btn btn-primary">Add Product</button>
      </form>

      <hr>

      <h3>Your Added Products</h3>
      <?php
      // Fetch products added by the current user (either normal user or reseller)
      $sql = "SELECT * FROM products WHERE reseller_id = ? AND status = 'pending'";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $_SESSION['user_id']);
      $stmt->execute();
      $result = $stmt->get_result();
      
      while ($product = $result->fetch_assoc()) {
          echo "<div class='card'>
                  <img src='uploads/{$product['product_img_name']}' alt='{$product['product_name']}' />
                  <h5>{$product['product_name']}</h5>
                  <p>{$product['product_description']}</p>
                  <p>Price: Rs. {$product['price']}</p>
                  <p>Status: Waiting for approval</p>
                </div>";
      }
      ?>
  </div>

  <!-- Include your footer here -->
  <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
