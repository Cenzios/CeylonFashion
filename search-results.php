<?php
session_start();
include_once 'config/config.php';
include_once 'includes/head.php';

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$fallback = 'assets/no-image.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - <?= htmlspecialchars($searchTerm); ?></title>
</head>
<body>

<?php include_once 'includes/navbar.php'; ?>

<div class="search-results-section">
    <div class="container">
        <!-- Search Header -->
        <div class="search-header">
            <h2 class="search-title">Search Results for "<?= htmlspecialchars($searchTerm); ?>"</h2>
            

        </div>

        <?php
        if (!empty($searchTerm)) {
            // Search query
            $stmt = $mysqli->prepare("
                SELECT * FROM products 
                WHERE (product_name LIKE ? 
                OR category LIKE ?
                OR product_desc LIKE ?)
                AND is_deleted = 0
                ORDER BY product_name ASC
            ");
            $searchParam = "%{$searchTerm}%";
            $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
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
                        $pimg  = htmlentities($product['product_img1'] ?? '', ENT_QUOTES, 'UTF-8');

                        // Get first image from comma-separated list
                        $images = array_filter(array_map('trim', explode(',', $pimg)));
                        $firstImage = !empty($images) ? $images[0] : '';
                        
                        $imgPath = 'assets/images/products/' . $firstImage;
                        if (empty($firstImage) || !file_exists($imgPath)) {
                            $imgPath = $fallback;
                        }

                        // Fetch lowest fabric price for this product
                        $fabricSql = "SELECT MIN(fabric_price) as min_price, SUM(fabric_qty) as total_qty FROM product_fabrics WHERE product_id = ? AND fabric_qty > 0";
                        $fabricStmt = $mysqli->prepare($fabricSql);
                        $fabricStmt->bind_param("i", $productId);
                        $fabricStmt->execute();
                        $fabricResult = $fabricStmt->get_result();
                        $fabricData = $fabricResult->fetch_assoc();
                        $minPrice = $fabricData['min_price'] ?? null;
                        $totalQty = isset($fabricData['total_qty']) ? (int)$fabricData['total_qty'] : 0;
                        $fabricStmt->close();

                        // Badge Logic
                        $badgeLabel = $totalQty > 0 ? 'AVAILABLE' : 'SOLD OUT';
                        $badgeClass = $totalQty > 0 ? 'badge-available' : 'badge-sold-out';
                    ?>
                        <!-- Product Card -->
                        <div class="product-card">
                            <a href="pages/products/product-view.php?id=<?php echo $productId; ?>" class="card-link">
                                <div class="product-image">
                                    <img src="<?php echo $imgPath; ?>" alt="<?php echo $pname; ?>" />
                                    <span class="product-badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
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
                        <p>We couldn't find any products matching "<?= htmlspecialchars($searchTerm); ?>"</p>
                        <a href="index.php" class="btn-back-home">Browse All Products</a>
                    </div>
                </div>
            <?php endif;
            
            $stmt->close();
        } else {
            echo '<div class="alert-warning">Please enter a search term.</div>';
        }
        ?>
    </div>
</div>

<style>
    /* Search Results Section */
    .search-results-section {
        width: 100%;
        min-height: 70vh;
        padding: 60px 20px;
        background: #f9fafb;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Search Header */
    .search-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .search-title {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin: 0 0 30px 0;
    }

    .search-form {
        display: flex;
        gap: 15px;
        justify-content: center;
        max-width: 600px;
        margin: 0 auto;
    }

    .search-input {
        flex: 1;
        padding: 12px 20px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 16px;
        outline: none;
        transition: border-color 0.3s;
    }

    .search-input:focus {
        border-color: #7c3aed;
    }

    .search-btn {
        padding: 12px 30px;
        background: #7c3aed;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
        white-space: nowrap;
    }

    .search-btn:hover {
        background: #6d28d9;
    }

    /* Results Count */
    .results-count {
        text-align: center;
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 30px 0;
    }

    /* Products Grid - Same as your original */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .product-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .product-image {
        width: 100%;
        height: 400px;
        overflow: hidden;
        background: #f5f5f5;
        position: relative;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .product-card:hover .product-image img {
        transform: scale(1.05);
    }

    .product-info {
        padding: 20px;
        background: linear-gradient(135deg, #d8b4e2 0%, #c9a8d8 100%);
        text-align: left;
    }

    .product-name {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin: 0 0 8px 0;
        line-height: 1.3;
    }

    .product-price {
        font-size: 20px;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }

    /* No Results */
    .no-results {
        text-align: center;
        padding: 60px 20px;
    }

    .no-results-content {
        max-width: 500px;
        margin: 0 auto;
        background: white;
        padding: 40px 30px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .no-results-content h3 {
        font-size: 24px;
        color: #333;
        margin: 0 0 15px 0;
    }

    .no-results-content p {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 25px 0;
    }

    .btn-back-home {
        display: inline-block;
        padding: 12px 30px;
        background: #7c3aed;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: background 0.3s;
    }

    .btn-back-home:hover {
        background: #6d28d9;
    }

    .alert-warning {
        text-align: center;
        padding: 20px;
        background: #fef3c7;
        border: 1px solid #fcd34d;
        border-radius: 8px;
        color: #92400e;
    }
    
    /* Badge Styles */
    .product-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        padding: 5px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        z-index: 10;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .badge-available { background-color: #10b981; color: white; }
    .badge-sold-out { background-color: #ef4444; color: white; }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .products-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .product-image {
            height: 350px;
        }
    }

    @media (max-width: 640px) {
        .search-results-section {
            padding: 40px 15px;
        }

        .search-title {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .search-form {
            flex-direction: column;
        }

        .search-input,
        .search-btn {
            width: 100%;
        }

        .products-grid {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 0 10px;
        }

        .product-image {
            height: 300px;
        }

        .product-name {
            font-size: 16px;
        }

        .product-price {
            font-size: 18px;
        }

        .no-results-content {
            padding: 30px 20px;
        }
    }
</style>

<?php include_once 'includes/footer.php'; ?>
<?php include_once 'includes/scripts.php'; ?>

</body>
</html>