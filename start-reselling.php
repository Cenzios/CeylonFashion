<?php
session_start();
include_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle product addition form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addProduct'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $contactNumber = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $notes = trim($_POST['notes']);
    $itemCode = trim($_POST['item_code']);
    $fabricType = trim($_POST['fabric_type']);
    
    // Get product details based on item code
    $stmt = $mysqli->prepare("SELECT * FROM products WHERE product_code = ?");
    $stmt->bind_param("s", $itemCode);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($product) {
        // Get fabric price based on fabric type
        $stmt = $mysqli->prepare("SELECT fabric_price FROM product_fabrics WHERE product_id = ? AND fabric_type = ?");
        $stmt->bind_param("is", $product['id'], $fabricType);
        $stmt->execute();
        $fabricResult = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($fabricResult && isset($fabricResult['fabric_price'])) {
            $originalPrice = floatval($fabricResult['fabric_price']);
            
            // Calculate resale price (60% of original)
            $resalePrice = $originalPrice * 0.6;
            
            // Insert into reseller_products table
            $sql = "INSERT INTO reseller_products (product_id, user_id, first_name, last_name, contact_number, email, address, notes, fabric_type, resale_price, original_price, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("iisssssssdd", 
                    $product['id'], 
                    $_SESSION['user_id'], 
                    $firstName, 
                    $lastName, 
                    $contactNumber, 
                    $email, 
                    $address, 
                    $notes, 
                    $fabricType, 
                    $resalePrice, 
                    $originalPrice
                );
                
                if ($stmt->execute()) {
                    $message = 'Your item has been listed under Used Collection. Buyers may contact you directly.';
                    $messageType = 'success';
                } else {
                    $message = 'Error listing your item. Please try again.';
                    $messageType = 'error';
                }
                $stmt->close();
            }
        } else {
            $message = 'Price information not found for the selected fabric type. Please contact support.';
            $messageType = 'error';
        }
    } else {
        $message = 'Product not found. Please check the item code and try again.';
        $messageType = 'error';
    }
}

// Check item availability
$eligibilityData = null;
$fabricOptions = [];
if (isset($_GET['check_item']) && isset($_GET['item_code'])) {
    $itemCode = trim($_GET['item_code']);
    
    // Get product details directly from products table
    $stmt = $mysqli->prepare("SELECT * FROM products WHERE product_code = ?");
    $stmt->bind_param("s", $itemCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $eligibilityData = $result->fetch_assoc();
    $stmt->close();
    
    if ($eligibilityData) {
        // Get all available fabric types and prices for this product
        $stmt = $mysqli->prepare("SELECT fabric_type, fabric_price FROM product_fabrics WHERE product_id = ? AND fabric_price IS NOT NULL AND fabric_price > 0 ORDER BY fabric_type");
        $stmt->bind_param("i", $eligibilityData['id']);
        $stmt->execute();
        $fabricResult = $stmt->get_result();
        
        while ($row = $fabricResult->fetch_assoc()) {
            $fabricOptions[] = $row;
        }
        $stmt->close();
        
        // Check if product was created within last 3 months (using created if available)
        $isEligible = true;
        $monthsDiff = 0;
        
        // Use the 'created' field from your table structure
        if (isset($eligibilityData['created']) && !empty($eligibilityData['created'])) {
            $createdDate = new DateTime($eligibilityData['created']);
            $currentDate = new DateTime();
            $interval = $createdDate->diff($currentDate);
            $monthsDiff = ($interval->y * 12) + $interval->m;
            $isEligible = $monthsDiff <= 3;
        }
        
        $eligibilityData['is_eligible'] = $isEligible;
        $eligibilityData['months_diff'] = $monthsDiff;
        $eligibilityData['purchase_date'] = $eligibilityData['created'] ?? date('Y-m-d');
        $eligibilityData['has_fabrics'] = !empty($fabricOptions);
    }
}

include_once 'includes/head.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resell Your Outfit</title>
</head>
<body>

<?php include_once 'includes/navbar.php'; ?>

<div class="reseller-container">
    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="main-title">Resell Your Outfit</h1>
        <p class="subtitle">Give your bridal or partywear a second life—quick, safe, and commission-free.</p>
    </div>

    <!-- Feature Cards -->
    <div class="features-grid">
        <div class="feature-card">
            <h3>Quick Cash Back</h3>
            <p>Get 60% of your purchase price back. Turn unworn or lightly used items into instant money</p>
            <div class="feature-icon">
                <img src="images/icons/cash.png" alt="Cash" onerror="this.style.display='none'">
                <div class="icon-placeholder">💰</div>
            </div>
        </div>

        <div class="feature-card">
            <h3>Hassle-Free Listing</h3>
            <p>We auto-build your resale post using your order details and images.</p>
            <div class="feature-icon">
                <img src="images/icons/listing.png" alt="Listing" onerror="this.style.display='none'">
                <div class="icon-placeholder">📋</div>
            </div>
        </div>

        <div class="feature-card">
            <h3>Support Sustainability</h3>
            <p>Reduce waste and let your outfit shine again. Resell outfit within 3 months</p>
            <div class="feature-icon">
                <img src="images/icons/sustainability.png" alt="Sustainability" onerror="this.style.display='none'">
                <div class="icon-placeholder">💡</div>
            </div>
        </div>
    </div>

    <!-- Check Eligibility Section -->
    <div class="eligibility-section">
        <h2 class="section-title">Check Resale Eligibility</h2>
        <p class="section-subtitle">Start by entering your Item code below to see if your outfit is eligible for resale.</p>
        
        <div class="check-form">
            <input type="text" id="itemCodeInput" placeholder="ex: BG0001" class="item-input" value="<?= isset($_GET['item_code']) ? htmlspecialchars($_GET['item_code']) : ''; ?>">
            <button type="button" id="checkAvailabilityBtn" class="check-btn">Check Availability</button>
        </div>

        <!-- Not Found Message (hidden by default) -->
        <div id="notFoundMessage" class="alert-message error-message" style="display: none;">
            <span>❌ Product not found. Please check the item code and try again.</span>
            <button class="close-alert" onclick="closeAlert('notFoundMessage')">✕</button>
        </div>

        <!-- Warning Message (hidden by default) -->
        <div id="warningMessage" class="alert-message warning-message" style="display: none;">
            <span>⚠️ Unfortunately, this item can't be resold as it's beyond the 3-month resale period. Please check your recent purchases for items that may still qualify.</span>
            <button class="close-alert" onclick="closeAlert('warningMessage')">✕</button>
        </div>

        <!-- No Fabric Message (hidden by default) -->
        <div id="noFabricMessage" class="alert-message error-message" style="display: none;">
            <span>❌ No fabric pricing information available for this product. Please contact support.</span>
            <button class="close-alert" onclick="closeAlert('noFabricMessage')">✕</button>
        </div>

        <!-- Success Message (hidden by default) -->
        <div id="successMessage" class="alert-message success-message" style="display: none;">
            <span>✓ Your item is eligible for resale. Please select your fabric type and proceed to confirm your details.</span>
        </div>

        <!-- Product Details Section (hidden by default) -->
        <div id="productDetailsSection" class="product-details-section" style="display: none;">
            <div class="details-grid">
                <div class="product-image-box">
                    <img id="productImage" src="" alt="Product">
                </div>
                <div class="product-info-box">
                    <div class="info-row">
                        <span class="info-label">Item ID:</span>
                        <span class="info-value" id="itemId"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Item Category:</span>
                        <span class="info-value" id="itemCategory"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Product Name:</span>
                        <span class="info-value" id="productName"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Purchase Date:</span>
                        <span class="info-value" id="purchaseDate"></span>
                    </div>
                    
                    <!-- Fabric Selection -->
                    <div class="fabric-selection">
                        <label class="fabric-label">Select Your Fabric Type:</label>
                        <select id="fabricTypeSelect" class="fabric-select">
                            <option value="">-- Choose Fabric Type --</option>
                            <?php foreach ($fabricOptions as $fabric): ?>
                                <option value="<?= htmlspecialchars($fabric['fabric_type']); ?>" data-price="<?= $fabric['fabric_price']; ?>">
                                    <?= htmlspecialchars($fabric['fabric_type']); ?> - Rs. <?= number_format($fabric['fabric_price'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="info-row" id="originalPriceRow" style="display: none;">
                        <span class="info-label">Original Price:</span>
                        <span class="info-value" id="originalPrice"></span>
                    </div>
                    <div class="info-row highlight" id="resalePriceRow" style="display: none;">
                        <span class="info-label">Resale price (60% of original price):</span>
                        <span class="info-value" id="resalePrice"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form Section (hidden by default) -->
        <div id="contactFormSection" class="contact-form-section" style="display: none;">
            <p class="form-intro">Almost there! Just complete the details below to publish your outfit in our Used Collection. Your contact information will help interested buyers reach you directly.</p>
            
            <form method="POST" id="resellForm" enctype="multipart/form-data">
                <input type="hidden" name="item_code" id="formItemCode">
                <input type="hidden" name="fabric_type" id="formFabricType">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" placeholder="First name" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" name="last_name" id="last_name" placeholder="Last name" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" name="contact_number" id="contact_number" placeholder="Contact number" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" placeholder="Email address" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="address">Address</label>
                    <input type="text" name="address" id="address" placeholder="Enter Address" required>
                </div>

                <div class="form-group full-width">
                    <label for="notes">Notes (optional)</label>
                    <textarea name="notes" id="notes" rows="3" placeholder="Enter Notes"></textarea>
                </div>

                <button type="submit" name="addProduct" class="submit-btn">Resell My Item</button>
            </form>

            <?php if ($message): ?>
            <div class="alert-message <?= $messageType === 'success' ? 'final-success-message' : 'error-message'; ?>">
                <span><?= htmlspecialchars($message); ?></span>
                <button class="close-alert" onclick="this.parentElement.style.display='none'">✕</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<style>
    :root {
        --primary-purple: #5B21B6;
        --light-purple: #DDD6FE;
        --medium-purple: #8B5CF6;
        --success-green: #10B981;
        --warning-yellow: #FEF3C7;
        --error-red: #FEE2E2;
        --text-dark: #1F2937;
        --text-muted: #6B7280;
        --border-color: #E5E7EB;
    }

    body {
        background: #F9FAFB;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
        margin: 0;
        padding: 0;
        color: var(--text-dark);
    }

    .reseller-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Hero Section */
    .hero-section {
        text-align: center;
        margin-bottom: 50px;
    }

    .main-title {
        font-size: 42px;
        font-weight: 700;
        color: var(--primary-purple);
        margin: 0 0 15px 0;
    }

    .subtitle {
        font-size: 18px;
        color: var(--text-muted);
        margin: 0;
    }

    /* Feature Cards */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
        margin-bottom: 60px;
    }

    .feature-card {
        background: var(--light-purple);
        padding: 35px 25px;
        border-radius: 12px;
        text-align: center;
        position: relative;
        min-height: 220px;
    }

    .feature-card h3 {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-dark);
        margin: 0 0 12px 0;
    }

    .feature-card p {
        font-size: 15px;
        color: var(--text-dark);
        line-height: 1.6;
        margin: 0;
        opacity: 0.9;
    }

    .feature-icon {
        margin-top: 25px;
        font-size: 60px;
    }

    .feature-icon img {
        width: 80px;
        height: 80px;
        object-fit: contain;
    }

    .icon-placeholder {
        font-size: 70px;
        opacity: 0.8;
    }

    /* Eligibility Section */
    .eligibility-section {
        max-width: 1000px;
        margin: 0 auto;
    }

    .section-title {
        font-size: 32px;
        font-weight: 700;
        color: var(--text-dark);
        text-align: center;
        margin: 0 0 12px 0;
    }

    .section-subtitle {
        font-size: 16px;
        color: var(--text-muted);
        text-align: center;
        margin: 0 0 35px 0;
    }

    /* Check Form */
    .check-form {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }

    .item-input {
        width: 100%;
        max-width: 500px;
        padding: 14px 20px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        font-size: 16px;
        outline: none;
        transition: border-color 0.3s;
    }

    .item-input:focus {
        border-color: var(--medium-purple);
    }

    .check-btn {
        background: var(--medium-purple);
        color: white;
        border: none;
        padding: 14px 35px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
        white-space: nowrap;
    }

    .check-btn:hover {
        background: var(--primary-purple);
    }

    /* Alert Messages */
    .alert-message {
        padding: 16px 20px;
        border-radius: 8px;
        margin: 25px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .warning-message {
        background: var(--warning-yellow);
        border: 1px solid #FCD34D;
        color: #92400E;
    }

    .success-message {
        background: #D1FAE5;
        border: 1px solid #86EFAC;
        color: #065F46;
    }

    .final-success-message {
        background: #D1FAE5;
        border: 1px solid #86EFAC;
        color: #065F46;
        margin-top: 20px;
    }

    .error-message {
        background: var(--error-red);
        border: 1px solid #FCA5A5;
        color: #991B1B;
    }

    .close-alert {
        background: transparent;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: inherit;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Product Details Section */
    .product-details-section {
        margin-top: 35px;
    }

    .details-grid {
        display: grid;
        grid-template-columns: 400px 1fr;
        gap: 40px;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .product-image-box {
        width: 100%;
        height: 400px;
        background: #F3F4F6;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-image-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-info-box {
        display: flex;
        flex-direction: column;
        gap: 18px;
        justify-content: center;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
    }

    .info-row.highlight {
        background: #FEF3C7;
        padding: 12px 15px;
        border-radius: 6px;
        border-bottom: none;
        margin-top: 10px;
    }

    .info-label {
        font-size: 15px;
        color: var(--text-muted);
        font-weight: 500;
    }

    .info-row.highlight .info-label {
        font-weight: 700;
        color: var(--text-dark);
    }

    .info-value {
        font-size: 16px;
        color: var(--text-dark);
        font-weight: 600;
    }

    /* Fabric Selection */
    .fabric-selection {
        padding: 20px 0;
        border-top: 2px solid var(--border-color);
        border-bottom: 2px solid var(--border-color);
        margin: 10px 0;
    }

    .fabric-label {
        display: block;
        font-size: 16px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 12px;
    }

    .fabric-select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        font-size: 15px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.3s;
        background: white;
        cursor: pointer;
    }

    .fabric-select:focus {
        border-color: var(--medium-purple);
    }

    /* Contact Form Section */
    .contact-form-section {
        margin-top: 40px;
        background: white;
        padding: 35px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .form-intro {
        font-size: 15px;
        color: var(--text-muted);
        margin: 0 0 30px 0;
        line-height: 1.6;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-dark);
    }

    .form-group input,
    .form-group textarea {
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        font-size: 15px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        border-color: var(--medium-purple);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .submit-btn {
        background: var(--medium-purple);
        color: white;
        border: none;
        padding: 14px 35px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
        margin-top: 10px;
    }

    .submit-btn:hover {
        background: var(--primary-purple);
    }

    .submit-btn:disabled {
        background: #9CA3AF;
        cursor: not-allowed;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .features-grid {
            grid-template-columns: 1fr;
        }

        .details-grid {
            grid-template-columns: 1fr;
        }

        .product-image-box {
            height: 350px;
        }
    }

    @media (max-width: 768px) {
        .reseller-container {
            padding: 25px 15px;
        }

        .main-title {
            font-size: 32px;
        }

        .subtitle {
            font-size: 16px;
        }

        .check-form {
            flex-direction: column;
        }

        .item-input,
        .check-btn {
            width: 100%;
            max-width: 100%;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .contact-form-section {
            padding: 25px 20px;
        }
    }
</style>

<script>
document.getElementById('checkAvailabilityBtn').addEventListener('click', function() {
    const itemCode = document.getElementById('itemCodeInput').value.trim();
    
    if (!itemCode) {
        alert('Please enter an item code');
        return;
    }

    // Reload page with parameters to check eligibility
    window.location.href = `?check_item=1&item_code=${encodeURIComponent(itemCode)}`;
});

<?php if (isset($_GET['check_item'])): ?>
    <?php if ($eligibilityData): ?>
        <?php if (!$eligibilityData['has_fabrics']): ?>
            // Show no fabric message
            document.getElementById('noFabricMessage').style.display = 'flex';
        <?php elseif ($eligibilityData['is_eligible']): ?>
            // Show success message and product details
            const successMsg = document.getElementById('successMessage');
            const detailsSection = document.getElementById('productDetailsSection');
            const formSection = document.getElementById('contactFormSection');

            successMsg.style.display = 'flex';
            detailsSection.style.display = 'block';

            // Get the first image from product_img_name (handle comma-separated values)
            const imgName = '<?= htmlspecialchars($eligibilityData['product_img_name']); ?>';
            const firstImage = imgName.split(',')[0].trim();

            // Populate product details
            document.getElementById('productImage').src = 'images/products/' + firstImage;
            document.getElementById('itemId').textContent = '<?= htmlspecialchars($eligibilityData['product_code']); ?>';
            document.getElementById('itemCategory').textContent = '<?= htmlspecialchars($eligibilityData['category'] ?? 'N/A'); ?>';
            document.getElementById('productName').textContent = '<?= htmlspecialchars($eligibilityData['product_name']); ?>';
            document.getElementById('purchaseDate').textContent = '<?= date('F d, Y', strtotime($eligibilityData['purchase_date'])); ?>';
            document.getElementById('formItemCode').value = '<?= htmlspecialchars($eligibilityData['product_code']); ?>';

            // Handle fabric type selection
            const fabricSelect = document.getElementById('fabricTypeSelect');
            const originalPriceRow = document.getElementById('originalPriceRow');
            const resalePriceRow = document.getElementById('resalePriceRow');
            const originalPriceEl = document.getElementById('originalPrice');
            const resalePriceEl = document.getElementById('resalePrice');
            const submitBtn = document.querySelector('.submit-btn');

            // Disable submit button initially
            submitBtn.disabled = true;

            fabricSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const fabricType = selectedOption.value;
                const price = parseFloat(selectedOption.getAttribute('data-price'));

                if (fabricType && price) {
                    const resalePrice = price * 0.6;

                    // Update hidden form field
                    document.getElementById('formFabricType').value = fabricType;

                    // Show price information
                    originalPriceEl.textContent = 'Rs. ' + price.toFixed(2);
                    resalePriceEl.textContent = 'Rs. ' + resalePrice.toFixed(2);
                    originalPriceRow.style.display = 'flex';
                    resalePriceRow.style.display = 'flex';

                    // Show contact form and enable submit button
                    formSection.style.display = 'block';
                    submitBtn.disabled = false;

                    // Scroll to form
                    setTimeout(() => {
                        formSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 100);
                } else {
                    // Hide price information and form
                    originalPriceRow.style.display = 'none';
                    resalePriceRow.style.display = 'none';
                    formSection.style.display = 'none';
                    submitBtn.disabled = true;
                }
            });
        <?php else: ?>
            // Show warning message
            document.getElementById('warningMessage').style.display = 'flex';
        <?php endif; ?>
    <?php else: ?>
        // Product not found
        document.getElementById('notFoundMessage').style.display = 'flex';
    <?php endif; ?>
<?php endif; ?>

function closeAlert(id) {
    document.getElementById(id).style.display = 'none';
}

// Form validation before submit
document.getElementById('resellForm').addEventListener('submit', function(e) {
    const fabricType = document.getElementById('formFabricType').value;
    
    if (!fabricType) {
        e.preventDefault();
        alert('Please select a fabric type before submitting.');
        return false;
    }
});
</script>

<?php include_once 'includes/scripts.php'; ?>

</body>
</html>