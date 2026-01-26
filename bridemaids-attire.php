<?php
session_start();
include_once 'config.php';
include_once 'includes/head.php';

$pageTitle = "Bridemaid's Attire";
$fallback = 'assets/no-image.png';

// Filter Parameters
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : '';
$selectedColors = isset($_GET['colors']) && is_array($_GET['colors']) ? $_GET['colors'] : [];

// Base Query
$sql = "SELECT p.*, MIN(pf.fabric_price) as min_price, SUM(pf.fabric_qty) as total_qty
        FROM products p 
        LEFT JOIN product_fabrics pf ON p.id = pf.product_id 
        LEFT JOIN product_colors pc ON p.id = pc.product_id 
        WHERE p.category = 'bridemaidAttire' AND p.is_deleted = 0";

$params = [];
$types = "";

// Price Filter
if ($minPrice !== '') {
    $sql .= " AND pf.fabric_price >= ?";
    $params[] = $minPrice;
    $types .= "d";
}
if ($maxPrice !== '') {
    $sql .= " AND pf.fabric_price <= ?";
    $params[] = $maxPrice;
    $types .= "d";
}

// Color Filter (Filter by product_colors table)
if (!empty($selectedColors)) {
    $placeholders = implode(',', array_fill(0, count($selectedColors), '?'));
    $sql .= " AND pc.color_name IN ($placeholders)";
    foreach ($selectedColors as $color) {
        $params[] = $color;
        $types .= "s";
    }
}

$sql .= " GROUP BY p.id ORDER BY p.id DESC";

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
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

        <div class="page-layout">
            <!-- Sidebar -->
            <div class="sidebar-col">
                <?php 
                // Configure filters for this page
                $showCategoryFilter = false;
                $showColorFilter = true;
                $showPriceFilter = true;
                $selectedMinPrice = $minPrice;
                $selectedMaxPrice = $maxPrice;
                $selectedColors = $selectedColors;
                
                include 'includes/filter-sidebar.php'; 
                ?>
            </div>

            <!-- Content -->
            <div class="content-col">
                <?php if ($result->num_rows > 0): ?>
                    <!-- Results Count -->
                    <p class="results-count">Found <?= $result->num_rows; ?> product(s)</p>

                    <!-- Products Grid -->
                    <div class="products-grid">
                        <?php while ($product = $result->fetch_assoc()):
                            $productId = (int)$product['id'];
                            $pname = htmlentities($product['product_name'], ENT_QUOTES, 'UTF-8');
                            
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

                            $minPriceDisplay = $product['min_price'] ?? null;
                            
                            // Badge Logic
                            $totalQty = isset($product['total_qty']) ? (int)$product['total_qty'] : 0;
                            $badgeLabel = $totalQty > 0 ? 'AVAILABLE' : 'SOLD OUT';
                            $badgeClass = $totalQty > 0 ? 'badge-available' : 'badge-sold-out';
                        ?>
                            <!-- Product Card -->
                            <div class="product-card">
                                <a href="product-view.php?id=<?php echo $productId; ?>" class="card-link">
                                    <div class="product-image">
                                        <img src="<?php echo $imgPath; ?>" alt="<?php echo $pname; ?>" />
                                        <span class="product-badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
                                    </div>
                                    
                                    <div class="product-info">
                                        <h3 class="product-name"><?php echo $pname; ?></h3>
                                        <?php if ($minPriceDisplay !== null): ?>
                                            <p class="product-price">Rs : <?php echo number_format($minPriceDisplay, 2); ?></p>
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
                            <p>Try adjusting your filters.</p>
                            <a href="bridemaids-attire.php" class="btn-back-home">Clear Filters</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php $stmt->close(); ?>
    </div>
</div>

<style>
    /* Layout */
    .page-layout {
        display: flex;
        gap: 30px;
        align-items: flex-start;
    }
    .sidebar-col {
        flex: 0 0 280px;
    }
    .content-col {
        flex: 1;
    }

    /* Reusing styles */
    .search-results-section { width: 100%; min-height: 70vh; padding: 60px 20px; background: #f9fafb; }
    .container { max-width: 1400px; margin: 0 auto; }
    .search-header { text-align: center; margin-bottom: 40px; }
    .search-title { font-size: 32px; font-weight: 700; color: #333; margin: 0 0 30px 0; }
    .results-count { font-size: 16px; color: #6b7280; margin: 0 0 20px 0; }
    
    .products-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
    
    .product-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
    .card-link { text-decoration: none; color: inherit; display: block; }
    .product-image { width: 100%; height: 300px; overflow: hidden; background: #f5f5f5; position: relative; }
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

    @media (max-width: 1024px) {
        .page-layout { flex-direction: column; }
        .sidebar-col { width: 100%; }
        .products-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .products-grid { grid-template-columns: 1fr; }
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
</style>

<?php include_once 'includes/footer.php'; ?>
<?php include_once 'includes/scripts.php'; ?>

</body>
</html>
