<?php
session_start();
include_once 'config.php';
include_once 'includes/head.php';

$pageTitle = 'Used Collection';
$fallback = 'assets/no-image.png';

// Check if user is logged in (as required in index.php)
/*
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?login_required=1');
    exit;
}
*/

// Filter Parameters
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : '';
$selectedColors = isset($_GET['colors']) && is_array($_GET['colors']) ? $_GET['colors'] : [];
$availableOnly = isset($_GET['available_only']) && $_GET['available_only'] == '1';

// Pagination
$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Base Conditions
$conditions = ["p.category != 'used'", "p.is_deleted = 0"];
$params = [];
$types = "";

if ($availableOnly) {
    $conditions[] = "rp.status IN ('approved', 'available')";
} else {
    // Show approved, available, and sold items
    $conditions[] = "rp.status IN ('approved', 'available', 'sold')";
}

// Price Filter
if ($minPrice !== '') {
    $conditions[] = "rp.resale_price >= ?";
    $params[] = $minPrice;
    $types .= "d";
}
if ($maxPrice !== '') {
    $conditions[] = "rp.resale_price <= ?";
    $params[] = $maxPrice;
    $types .= "d";
}

// Color Filter
if (!empty($selectedColors)) {
    $placeholders = implode(',', array_fill(0, count($selectedColors), '?'));
    // Note: We need to JOIN product_colors properly first if we filter by it.
    // Original query joined it.
    $conditions[] = "pc.color_name IN ($placeholders)";
    foreach ($selectedColors as $color) {
        $params[] = $color;
        $types .= "s";
    }
}

$whereClause = "WHERE " . implode(" AND ", $conditions);

// 1. Count Total Items for Pagination
$countSql = "SELECT COUNT(DISTINCT rp.id) as total 
             FROM reseller_products rp
             INNER JOIN products p ON rp.product_id = p.id
             LEFT JOIN product_colors pc ON p.id = pc.product_id 
             $whereClause";

$cStmt = $mysqli->prepare($countSql);
if (!empty($params)) {
    $cStmt->bind_param($types, ...$params);
}
$cStmt->execute();
$totalRows = $cStmt->get_result()->fetch_assoc()['total'];
$cStmt->close();
$totalPages = ceil($totalRows / $limit);


// 2. Fetch Data
$sql = "SELECT DISTINCT rp.*, 
               p.product_code, p.product_name, p.product_desc, 
               p.product_img1, p.product_img2, p.product_img3, p.product_img4,
               rp.resale_price as min_price, rp.original_price, rp.status as reseller_status,
               pc.color_name
        FROM reseller_products rp
        INNER JOIN products p ON rp.product_id = p.id
        LEFT JOIN product_colors pc ON p.id = pc.product_id 
        $whereClause
        ORDER BY rp.created_at DESC
        LIMIT ?, ?";

// Add limit params
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

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

        <!-- Pre-Loved Header Section -->
        <div class="pre-loved-header">
            <h2 class="main-title">Pre-Loved Elegance: Discover Affordable Bridal & Partywear</h2>
            <h4 class="sub-title">Why Choose Pre Loved?</h4>
            
            <div class="info-grid">
                <!-- Card 1 -->
                <div class="info-card">
                    <h3>Stylish Savings</h3>
                    <p>Score up to 40% off on premium bridal and party outfits—luxury that's easy on the wallet.</p>
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="info-card">
                    <h3>Trusted Sellers</h3>
                    <p>Each outfit is verified for authenticity and quality, so you shop with confidence.</p>
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-cart"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="info-card">
                    <h3>Sustainable & Smart</h3>
                    <p>Resold within 3 months of original purchase ensuring freshness and style relevance</p>
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-feather"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"></path><line x1="16" y1="8" x2="2" y2="22"></line><line x1="17.5" y1="15" x2="9" y2="15"></line></svg>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="info-card">
                    <h3>Direct Seller Contact</h3>
                    <p>Connect directly with sellers for personalized inquiries and hassle-free purchases.</p>
                    <div class="icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                </div>
            </div>

            <!-- Availability Filter (Moved here) -->
            <div class="availability-toggle-section">
                <label class="toggle-checkbox">
                    <input type="checkbox" id="availableOnlyToggle" <?php echo $availableOnly ? 'checked' : ''; ?> onchange="toggleAvailability(this)">
                    <span class="toggle-label">Show Only Available Items</span>
                </label>
            </div>
        </div>

        <script>
        function toggleAvailability(checkbox) {
            const urlParams = new URLSearchParams(window.location.search);
            if (checkbox.checked) {
                urlParams.set('available_only', '1');
            } else {
                urlParams.delete('available_only');
            }
            urlParams.set('page', '1'); // Reset to page 1
            window.location.search = urlParams.toString();
        }
        </script>

        <div class="page-layout">
            <!-- Sidebar -->
            <div class="sidebar-col">
                <?php 
                // Configure filters for this page
                $showCategoryFilter = false;
                $showColorFilter = true;
                $showPriceFilter = true;
                $showStatusFilter = false; // Disable sidebar status filter (moved to header)
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
                    <p class="results-count">Found <?= $totalRows; ?> product(s)</p>

                    <!-- Products Grid -->
                    <div class="products-grid">
                        <?php while ($resellerProduct = $result->fetch_assoc()):
                            $resellerId = (int)$resellerProduct['id'];
                            $pname = htmlentities($resellerProduct['product_name'], ENT_QUOTES, 'UTF-8');
                            
                            // Get first available image
                            $firstImage = '';
                            for ($i = 1; $i <= 4; $i++) {
                                $imgField = 'product_img' . $i;
                                if (!empty($resellerProduct[$imgField])) {
                                    $firstImage = $resellerProduct[$imgField];
                                    break;
                                }
                            }
                            
                            $imgPath = 'images/products/' . $firstImage;
                            if (empty($firstImage) || !file_exists($imgPath)) {
                                $imgPath = $fallback;
                            }

                            $resalePrice = $resellerProduct['min_price'] ?? 0;
                            $originalPrice = $resellerProduct['original_price'] ?? 0;
                            
                            // Status Badge
                            $badgeLabel = 'Available';
                            $badgeClass = 'badge-available'; // Green
                            if ($resellerProduct['reseller_status'] === 'sold') {
                                $badgeLabel = 'Sold Out';
                                $badgeClass = 'badge-sold-out';
                            }
                        ?>
                            <!-- Product Card (New Design) -->
                            <div class="product-card">
                                <a href="product-view-used.php?id=<?php echo $resellerId; ?>" class="card-link" target="_self">
                                    <div class="product-image">
                                        <img src="<?php echo $imgPath; ?>" alt="<?php echo $pname; ?>" />
                                        <span class="product-badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
                                    </div>
                                    
                                    <div class="used-product-info">
                                        <h3 class="used-product-name"><?php echo $pname; ?></h3>
                                        
                                        <div class="used-price-row">
                                            <span class="used-price-label">Original price:</span> 
                                            <span class="used-price-val">LKR<?php echo number_format($originalPrice, 0); ?></span>
                                        </div>
                                        
                                        <div class="used-price-row">
                                            <span class="used-price-label">Resale price:</span> 
                                            <span class="used-price-resale-val">LKR<?php echo number_format($resalePrice, 0); ?></span>
                                        </div>

                                        <div class="used-eye-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                              <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                              <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php 
                        // Preserve query params
                        $queryParams = $_GET;
                        // Function to build link
                        function buildPageLink($p, $queryParams) {
                            $queryParams['page'] = $p;
                            return '?' . http_build_query($queryParams);
                        }
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?= buildPageLink($page - 1, $queryParams) ?>" class="page-link prev">Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= buildPageLink($i, $queryParams) ?>" class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?= buildPageLink($page + 1, $queryParams) ?>" class="page-link next">Next</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- No Results Found -->
                    <div class="no-results">
                        <div class="no-results-content">
                            <h3>No products found!</h3>
                            <p>Try adjusting your filters.</p>
                            <a href="used-collection.php" class="btn-back-home">Clear Filters</a>
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
    .product-image { width: 100%; height: 300px; overflow: hidden; background: #f5f5f5; }
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

    /* Pre-Loved Header Styles */
    .pre-loved-header {
        text-align: center;
        margin-bottom: 50px;
    }
    .main-title {
        font-family: serif; /* Closer to the image look */
        color: #2e004d; /* Dark Purple */
        font-size: 28px;
        margin-bottom: 10px;
        font-weight: 700;
    }
    .sub-title {
        color: #777;
        font-size: 16px;
        font-weight: 400;
        margin-bottom: 30px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }
    .info-card {
        background-color: #d8bce6; /* Match the lavender color from previous task */
        padding: 30px 20px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100%;
    }
    .info-card h3 {
        font-size: 18px;
        color: #2e004d;
        margin-bottom: 15px;
        font-weight: 700;
    }
    .info-card p {
        font-size: 13px;
        color: #444;
        line-height: 1.5;
        margin-bottom: 20px;
        flex-grow: 1; /* Pushes icon down */
    }
    .icon-box {
        margin-top: auto;
        color: #2e004d; /* Icon color */
        /* Optional: Add a circle background if needed, but SVG is enough based on request */
    }

    .availability-toggle-section {
        margin-top: 40px;
        display: flex;
        justify-content: center;
    }
    .toggle-checkbox {
        display: inline-flex; /* Use inline-flex to shrink wrap if centered */
        align-items: center;
        gap: 12px; /* Increased gap slightly */
        font-size: 16px; /* Adjusted font size */
        font-weight: 600;
        color: #2e004d;
        cursor: pointer;
        padding: 12px 24px; /* More padding */
        background: white;
        border: 2px solid #2e004d;
        border-radius: 8px;
        transition: all 0.2s;
        line-height: 1; /* Fix vertical alignment */
    }
    .toggle-checkbox:hover {
        background: #fdf5ff; /* Lighter hover color */
    }
    .toggle-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #2e004d;
        cursor: pointer;
        margin: 0; /*  alignment */
    }

    @media (max-width: 1024px) {
        .page-layout { flex-direction: column; }
        .sidebar-col { width: 100%; }
        .products-grid { grid-template-columns: repeat(2, 1fr); }
        .info-grid { grid-template-columns: repeat(2, 1fr); } 
    }
    @media (max-width: 640px) {
        .products-grid { grid-template-columns: 1fr; }
        .info-grid { grid-template-columns: 1fr; }
    }

    /* Start Card Styles Update */
    .product-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        padding: 5px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        z-index: 10;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .badge-available { background-color: #10b981; color: white; }
    .badge-sold-out { background-color: #ef4444; color: white; }

    .used-product-info {
        padding: 15px 15px 25px 15px; 
        background: #d3bce6; 
        text-align: left;
        position: relative;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    .used-product-name {
        font-size: 15px;
        font-weight: 600;
        color: #111;
        margin: 0 0 8px 0;
        line-height: 1.3;
    }
    .used-price-row {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 2px;
        font-size: 13px;
        color: #222;
    }
    .used-price-label { font-weight: 500; }
    .used-price-val { font-weight: 500; }
    .used-price-resale-val { font-weight: 700; color: #a51d2a; }
    .used-eye-icon {
        position: absolute;
        bottom: 12px;
        right: 15px;
        color: #333;
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    .used-eye-icon:hover { opacity: 1; }
    /* End Card Styles Update */

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 50px;
    }
    .page-link {
        padding: 10px 16px;
        background: white;
        border: 1px solid #ddd;
        color: #333;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s;
    }
    .page-link:hover {
        background: #f3f4f6;
        border-color: #bbb;
    }
    .page-link.active {
        background: #7c3aed;
        color: white;
        border-color: #7c3aed;
    }
    .page-link.prev, .page-link.next {
        font-weight: 600;
    }
</style>

<?php include_once 'includes/footer.php'; ?>
<?php include_once 'includes/scripts.php'; ?>

</body>
</html>
