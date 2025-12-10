<?php
session_start();
include_once 'config.php';
include_once 'includes/head.php';

$pageTitle = 'Party Wear';
$fallback = 'assets/no-image.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?></title>
</head>
<body>

<?php include_once 'includes/navbar.php'; ?>

<div class="search-results-section">
    <div class="container">
        <!-- Header -->
        <div class="search-header">
            <h2 class="search-title"><?= htmlspecialchars($pageTitle); ?></h2>
        </div>

        <?php
        // Party Wear Query
        $stmt = $mysqli->prepare("
            SELECT * FROM products 
            WHERE category = 'partyWear' 
            ORDER BY id DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0): ?>
            <!-- Results Count -->
            <p class="results-count">Found <?= $result->num_rows; ?> product(s)</p>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php while ($product = $result->fetch_assoc()):
                    $productId = (int)$product['id'];
                    $pname = htmlentities($product['product_name'], ENT_QUOTES, 'UTF-8');
                    $pcode = htmlentities($product['product_code'], ENT_QUOTES, 'UTF-8');
                    
                    // Get first available image
                    $firstImage = '';
                    for ($i = 1; $i <= 4; $i++) {
                        $imgField = 'product_img' . $i;
                        if (!empty($product[$imgField])) {
                            $firstImage = $product[$imgField];
                            break;
                        }
                    }
                    
                    $imgPath = 'images/products/' . $firstImage;
                    if (empty($firstImage) || !file_exists($imgPath)) {
                        $imgPath = $fallback;
                    }

                    // Fetch lowest fabric price for this product
                    $fabricSql = "SELECT MIN(fabric_price) as min_price FROM product_fabrics WHERE product_id = ? AND fabric_qty > 0";
                    $fabricStmt = $mysqli->prepare($fabricSql);
                    $fabricStmt->bind_param("i", $productId);
                    $fabricStmt->execute();
                    $fabricResult = $fabricStmt->get_result();
                    $fabricData = $fabricResult->fetch_assoc();
                    $minPrice = $fabricData['min_price'] ?? null;
                    $fabricStmt->close();
                ?>
                    <!-- Product Card -->
                    <div class="product-card">
                        <a href="product-view.php?id=<?php echo $productId; ?>" class="card-link">
                            <div class="product-image">
                                <img src="<?php echo $imgPath; ?>" alt="<?php echo $pname; ?>" />
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-name"><?php echo $pname; ?></h3>
                                <?php if ($minPrice !== null): ?>
                                    <p class="product-price">Rs : <?php echo number_format($minPrice, 2); ?></p>
                                <?php else: ?>
                                    <p class="product-price">Price unavailable</p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- No Results Found -->
            <div class="no-results">
                <div class="no-results-content">
                    <h3>No products found!</h3>
                    <p>We couldn't find any party wear at the moment.</p>
                    <a href="index.php" class="btn-back-home">Browse All Products</a>
                </div>
            </div>
        <?php endif;
        
        $stmt->close();
        ?>
    </div>
</div>

<style>
    /* Reusing styles from search-results.php */
    .search-results-section { width: 100%; min-height: 70vh; padding: 60px 20px; background: #f9fafb; }
    .container { max-width: 1400px; margin: 0 auto; }
    .search-header { text-align: center; margin-bottom: 40px; }
    .search-title { font-size: 32px; font-weight: 700; color: #333; margin: 0 0 30px 0; }
    .results-count { text-align: center; font-size: 16px; color: #6b7280; margin: 0 0 30px 0; }
    .products-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin: 0 auto; padding: 0 20px; }
    .product-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
    .card-link { text-decoration: none; color: inherit; display: block; }
    .product-image { width: 100%; height: 400px; overflow: hidden; background: #f5f5f5; }
    .product-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
    .product-card:hover .product-image img { transform: scale(1.05); }
    .product-info { padding: 20px; background: linear-gradient(135deg, #d8b4e2 0%, #c9a8d8 100%); text-align: left; }
    .product-name { font-size: 18px; font-weight: 600; color: #333; margin: 0 0 8px 0; line-height: 1.3; }
    .product-price { font-size: 20px; font-weight: 700; color: #2c3e50; margin: 0; }
    .no-results { text-align: center; padding: 60px 20px; }
    .no-results-content { max-width: 500px; margin: 0 auto; background: white; padding: 40px 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
    .no-results-content h3 { font-size: 24px; color: #333; margin: 0 0 15px 0; }
    .no-results-content p { font-size: 16px; color: #6b7280; margin: 0 0 25px 0; }
    .btn-back-home { display: inline-block; padding: 12px 30px; background: #7c3aed; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: background 0.3s; }
    .btn-back-home:hover { background: #6d28d9; }
    @media (max-width: 1024px) { .products-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; } .product-image { height: 350px; } }
    @media (max-width: 640px) { .search-results-section { padding: 40px 15px; } .search-title { font-size: 24px; margin-bottom: 20px; } .products-grid { grid-template-columns: 1fr; gap: 20px; padding: 0 10px; } .product-image { height: 300px; } .product-name { font-size: 16px; } .product-price { font-size: 18px; } .no-results-content { padding: 30px 20px; } }
</style>

<?php include_once 'includes/footer.php'; ?>
<?php include_once 'includes/scripts.php'; ?>

</body>
</html>
