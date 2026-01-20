<?php
session_start();
require_once 'config.php';

$reseller_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reseller_id) {
    header('Location: index.php');
    exit;
}

// 1. Fetch Reseller Product Details from reseller_products table
$stmt = $mysqli->prepare("
    SELECT rp.*, 
           p.product_code, p.product_name, p.product_desc,
           p.product_img1, p.product_img2, p.product_img3, p.product_img4
    FROM reseller_products rp
    INNER JOIN products p ON rp.product_id = p.id
    WHERE rp.id = ? AND rp.status IN ('approved', 'available', 'sold') AND p.is_deleted = 0
");
$stmt->bind_param("i", $reseller_id);
$stmt->execute();
$resellerInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resellerInfo) {
    header('Location: index.php'); // Not found or not approved
    exit;
}

// Get product details from joined data
$product = [
    'product_code' => $resellerInfo['product_code'],
    'product_name' => $resellerInfo['product_name'],
    'product_desc' => $resellerInfo['product_desc'],
    'product_img1' => $resellerInfo['product_img1'],
    'product_img2' => $resellerInfo['product_img2'],
    'product_img3' => $resellerInfo['product_img3'],
    'product_img4' => $resellerInfo['product_img4']
];

// 2. Fetch Purchase Date from Orders
$purchaseDate = 'N/A';
$baseProductId = $resellerInfo['product_id'];
$sellerUserId = $resellerInfo['user_id'];

$stmt = $mysqli->prepare("SELECT created_at FROM orders WHERE product_id = ? AND username = (SELECT email FROM users WHERE id = ?) ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("ii", $baseProductId, $sellerUserId);
$stmt->execute();
$orderRes = $stmt->get_result()->fetch_assoc();
if ($orderRes) {
    $purchaseDate = date('d M Y', strtotime($orderRes['created_at']));
}
$stmt->close();

// Images
$img1 = !empty($product['product_img1']) ? 'images/products/' . $product['product_img1'] : 'assets/no-image.png';
$img2 = !empty($product['product_img2']) ? 'images/products/' . $product['product_img2'] : '';
$img3 = !empty($product['product_img3']) ? 'images/products/' . $product['product_img3'] : '';
$img4 = !empty($product['product_img4']) ? 'images/products/' . $product['product_img4'] : '';

// Prices from reseller_products table
$resalePrice = (float)$resellerInfo['resale_price'];
$originalPrice = (float)$resellerInfo['original_price'];
$savings = ($originalPrice > 0) ? round((($originalPrice - $resalePrice) / $originalPrice) * 100) : 0;

$pageTitle = $product['product_name'] . ' (Pre-Loved)';
include_once 'includes/head.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?></title>
    <style>
        .used-product-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .product-grid {
            display: grid;
            grid-template-columns: 45% 50%;
            gap: 5%;
        }
        
        /* Image Gallery */
        .image-gallery {
            display: flex;
            gap: 15px;
        }
        .main-image {
            flex: 1;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .main-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
            display: block;
        }
        .thumbnail-col {
            width: 100px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .thumb {
            width: 100%;
            height: 100px;
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
            background: #f8f9fa;
        }
        .thumb:hover, .thumb.active {
            opacity: 1;
            border: 2px solid #5B21B6;
        }
        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Product Info */
        .product-title {
            color: #4A148C; /* Deep Purple */
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .info-section-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .section-heading {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .detail-label {
            color: #555;
            font-weight: 500;
        }
        
        .detail-value {
            color: #111;
            font-weight: 400;
            text-align: right;
            max-width: 60%;
        }

        .price-section {
            margin-top: 20px;
        }
        
        .savings-box {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            color: #555;
            font-size: 14px;
        }
        
        .savings-highlight {
            color: #16a34a; /* Green */
            font-weight: 700;
        }

        .footer-warning {
            margin-top: 20px;
            font-size: 13px;
            color: #777;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Sold Out Badge (Optional style) */
        .badge-sold {
            background-color: #ef4444;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-available {
            color: #10b981; /* Success Green text */
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            .image-gallery {
                flex-direction: column-reverse;
            }
            .thumbnail-col {
                flex-direction: row;
                width: 100%;
                height: 80px;
            }
            .thumb {
                height: 100%;
                width: 80px;
            }
            .info-section-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
        

    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="used-product-container">
    <div class="product-grid">
        <!-- Left: Images -->
        <div class="image-gallery">
            <div class="thumbnail-col">
                <?php if ($img1): ?>
                    <div class="thumb active" onclick="changeImage(this, '<?= $img1 ?>')">
                        <img src="<?= $img1 ?>" alt="View 1">
                    </div>
                <?php endif; ?>
                <?php if ($img2): ?>
                    <div class="thumb" onclick="changeImage(this, '<?= $img2 ?>')">
                        <img src="<?= $img2 ?>" alt="View 2">
                    </div>
                <?php endif; ?>
                <?php if ($img3): ?>
                    <div class="thumb" onclick="changeImage(this, '<?= $img3 ?>')">
                        <img src="<?= $img3 ?>" alt="View 3">
                    </div>
                <?php endif; ?>
            </div>
            <div class="main-image">
                <img id="mainDisplayImage" src="<?= $img1 ?>" alt="<?= htmlspecialchars($product['product_name']); ?>">
            </div>
        </div>

        <!-- Right: Info -->
        <div class="product-info-col">
            <h1 class="product-title"><?= htmlspecialchars($product['product_name']); ?> (Pre-Loved)</h1>

            <div class="info-section-grid">
                <!-- Item Details -->
                <div class="details-section">
                    <h3 class="section-heading">Item Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Category:</span>
                        <span class="detail-value">Bridal / Party Wear</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Condition:</span>
                        <span class="detail-value">Used (Pre-Loved)</span>
                    </div>
                    <?php 
                        // Find color from name logic? Or just skip if unavailable.
                        // Simple parse: Usually in desc or name?
                        // We will check desc.
                    ?>
                    <!-- <div class="detail-row">
                        <span class="detail-label">Color:</span>
                        <span class="detail-value">Maroon</span>
                    </div> -->
                    <div class="detail-row">
                        <span class="detail-label">Fabric:</span>
                        <span class="detail-value"><?= htmlspecialchars($resellerInfo['fabric_type'] ?? 'Standard'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Purchased On:</span>
                        <span class="detail-value"><?= $purchaseDate; ?></span>
                    </div>
                    <!-- 
                    <div class="detail-row">
                        <span class="detail-label">Size:</span>
                        <span class="detail-value">M</span>
                    </div>
                    -->
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <?php if (isset($resellerInfo['status']) && $resellerInfo['status'] === 'sold'): ?>
                                <span class="badge-sold">Sold Out</span>
                            <?php else: ?>
                                <span class="badge-available">Available</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Contact Seller -->
                <div class="contact-section">
                    <h3 class="section-heading">
                        Contact Seller 
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone" viewBox="0 0 16 16">
                          <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
                        </svg>
                    </h3>
                    
                    <?php if ($resellerInfo): ?>
                        <div class="detail-row">
                            <span class="detail-label">Seller Name:</span>
                            <span class="detail-value"><?= htmlspecialchars($resellerInfo['first_name'] . ' ' . $resellerInfo['last_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone Number:</span>
                            <span class="detail-value"><a href="tel:<?= htmlspecialchars($resellerInfo['contact_number']); ?>"><?= htmlspecialchars($resellerInfo['contact_number']); ?></a></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">E-mail:</span>
                            <span class="detail-value"><a href="mailto:<?= htmlspecialchars($resellerInfo['email']); ?>"><?= htmlspecialchars($resellerInfo['email']); ?></a></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Address:</span>
                            <span class="detail-value"><?= htmlspecialchars($resellerInfo['address']); ?></span>
                        </div>
                        <?php if (!empty($resellerInfo['notes'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Seller Notes:</span>
                            <span class="detail-value"><?= nl2br(htmlspecialchars($resellerInfo['notes'])); ?></span>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-danger">Seller information unavailable.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="price-section">
                <h3 class="section-heading">Price Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Resale Price:</span>
                    <span class="detail-value" style="font-weight: 700; font-size: 18px;">LKR <?= number_format($resalePrice, 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Original Price:</span>
                    <span class="detail-value" style="text-decoration: line-through; color: #999;">LKR <?= number_format($originalPrice, 2); ?></span>
                </div>
                
                <div class="savings-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                      <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                    </svg>
                    <span>You Save: <span class="savings-highlight"><?= $savings ?>%</span> by purchasing a pre-loved Ceylon Fashion item</span>
                </div>
            </div>

            <div class="footer-warning">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                  <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                  <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995z"/>
                </svg>
                Preloved Item – Direct Seller Transaction
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-question-circle" viewBox="0 0 16 16" style="margin-left: 5px;">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                  <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>
<script>
    function changeImage(el, src) {
        document.getElementById('mainDisplayImage').src = src;
        document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
    }
</script>



</body>
</html>
