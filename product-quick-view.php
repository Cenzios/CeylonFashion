<?php
require_once 'config.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    echo '<div class="alert alert-danger">Invalid Product</div>';
    exit;
}

$product_id = (int)$_GET['id'];

// Fetch Product
$stmt = $mysqli->prepare("SELECT id, product_name, product_desc, product_img1, product_img2, product_img3, product_img4, category FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo '<div class="alert alert-danger">Product not found</div>';
    exit;
}

// Fetch Fabrics (for price)
$stmt = $mysqli->prepare("SELECT id, fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$fabricsRes = $stmt->get_result();
$fabrics = [];
while ($f = $fabricsRes->fetch_assoc()) {
    $fabrics[] = $f;
}
$stmt->close();

// Fetch Available Sizes
$stmt = $mysqli->prepare("SELECT size FROM product_sizes WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$sizeResult = $stmt->get_result();
$availableSizes = [];
while ($row = $sizeResult->fetch_assoc()) {
  $availableSizes[] = $row['size'];
}
$stmt->close();

// Fallback logic
// Fallback logic
// REMOVED: Support no-size products
// if (empty($availableSizes)) {
//   $availableSizes = ['XS','S','M','L','XL'];
// }
// Sort sizes
$sizeOrder = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
usort($availableSizes, function($a, $b) use ($sizeOrder) {
    return ($sizeOrder[$a] ?? 99) <=> ($sizeOrder[$b] ?? 99);
});

// Determine Price
$minPrice = 0.00;
if (!empty($fabrics)) {
    $minPrice = $fabrics[0]['fabric_price'];
    foreach ($fabrics as $f) {
        if ($f['fabric_price'] < $minPrice) {
            $minPrice = $f['fabric_price'];
        }
    }
}

// Collect Images
$images = [];
for ($i = 1; $i <= 4; $i++) {
    if (!empty($product['product_img'.$i])) {
        $images[] = $product['product_img'.$i];
    }
}
if (empty($images)) $images[] = 'placeholder.png'; // fallback

// Installment calculations (Mock logic based on image)
$kokoInstallment = $minPrice / 3;
$mintpayInstallment = $minPrice / 3;
$payzyInstallment = $minPrice / 4;

?>

<div class="modal-product-view-container">
    <button class="close-reveal-modal" aria-label="Close">&#215;</button>
    <div class="mp-row">
        <!-- Left Column: Image Slider -->
        <div class="mp-col-left">
            <div class="mp-slider-container">
                <button class="mp-arrow mp-prev" onclick="mpChangeSlide(-1)">&#10094;</button>
                <div class="mp-slider-wrapper">
                    <?php foreach ($images as $idx => $img): ?>
                        <div class="mp-slide <?php echo $idx === 0 ? 'active' : ''; ?>" data-idx="<?php echo $idx; ?>">
                            <img src="images/products/<?php echo htmlspecialchars($img); ?>" alt="Product Image">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="mp-arrow mp-next" onclick="mpChangeSlide(1)">&#10095;</button>
            </div>
        </div>

        <!-- Right Column: Details -->
        <div class="mp-col-right">
            <h2 class="mp-title"><?php echo htmlspecialchars($product['product_name']); ?></h2>
            
            <p class="mp-price">Rs <?php echo number_format($minPrice, 2); ?></p>
            
            <!-- Installments -->
            <!-- <div class="mp-installments">
                <div class="mp-inst-row">
                    <span>or pay in 3 x <strong>Rs <?php echo number_format($kokoInstallment, 2); ?></strong> with <span class="brand-koko">KOKO</span> <i class="info-icon">i</i></span>
                </div>
                <div class="mp-inst-row">
                    <span>3 X <strong>Rs <?php echo number_format($mintpayInstallment, 2); ?></strong> or 3% Cashback with <span class="brand-mintpay">mintpay</span> <i class="info-icon">i</i></span>
                </div>
                <div class="mp-inst-row">
                    <span>or up to 4 x <strong>Rs <?php echo number_format($payzyInstallment, 2); ?></strong> with <span class="brand-payzy">PayZy</span> <i class="info-icon">i</i></span>
                </div>
            </div> -->

            <form id="quickAddForm" action="cart-add.php" method="GET" onsubmit="return handleQuickAdd(this);">
                <input type="hidden" name="id" value="<?php echo $product_id; ?>">
                <!-- Fabric Selector -->
                <?php if (!empty($fabrics)): ?>
                <div class="mp-option-row">
                    <label>Fabric: <span id="mpSelectedFabricLabel"><?php echo htmlspecialchars($fabrics[0]['fabric_type']); ?></span></label>
                    <div class="mp-size-list">
                        <!-- Hidden inputs for fabric/price inside the form -->
                        <input type="hidden" name="fabric_id" id="mpFabricId" value="<?php echo $fabrics[0]['id']; ?>">
                        <input type="hidden" name="price" id="mpPrice" value="<?php echo $fabrics[0]['fabric_price']; ?>">

                        <?php foreach($fabrics as $idx => $f): ?>
                            <button type="button" 
                                    class="mp-fabric-btn <?php echo $idx === 0 ? 'selected' : ''; ?>" 
                                    onclick="mpSelectFabric(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars($f['fabric_type']); ?>', <?php echo $f['fabric_price']; ?>, <?php echo (int)$f['fabric_qty']; ?>, this)">
                                <?php echo htmlspecialchars($f['fabric_type']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <input type="hidden" name="size" id="mpSize" value="">

                <!-- Size Selector -->
                <?php if (!empty($availableSizes)): ?>
                <div class="mp-option-row">
                    <label>Size: <span id="mpSelectedSizeLabel"></span></label>
                    <div class="mp-size-list">
                        <?php foreach($availableSizes as $s): ?>
                            <button type="button" class="mp-size-btn" onclick="mpSelectSize('<?php echo htmlspecialchars($s); ?>', this)"><?php echo htmlspecialchars($s); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quantity & Buttons -->
                <div class="mp-actions-row">
                    <div class="mp-qty-box">
                        <button type="button" onclick="mpUpdateQty(-1)">&#8722;</button>
                        <input type="number" name="qty" id="mpQty" value="1" min="1" readonly>
                        <button type="button" onclick="mpUpdateQty(1)">&#43;</button>
                    </div>
                    
                    <button type="submit" class="mp-btn-add">ADD TO CART</button>
                </div>
            </form>

            <a href="product-view.php?id=<?php echo $product_id; ?>" class="mp-btn-view-full">VIEW FULL DETAILS</a>
        </div>
    </div>
</div>

<style>
    .modal-product-view-container {
        font-family: 'Segoe UI', sans-serif;
        position: relative;
        background: #fff;
        border-radius: 4px;
        max-width: 100%;
    }
    .modal-product-view-container * {
        box-sizing: border-box;
    }
    .close-reveal-modal {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 30px;
        line-height: 1;
        cursor: pointer;
        color: #aaa;
        background: none;
        border: none;
        z-index: 100;
        padding: 0;
    }
    .mp-row {
        display: flex;
        flex-wrap: wrap;
        margin: 0;
    }
    .mp-col-left {
        flex: 1;
        min-width: 300px; /* Prevent too narrow */
        padding: 20px;
        border-right: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .mp-col-right {
        flex: 1;
        min-width: 300px;
        padding: 40px 30px;
    }
    
    /* Slider */
    .mp-slider-container {
        position: relative;
        width: 100%;
        max-width: 350px;
        height: 450px;
        display: flex;
        align-items: center;
        margin: 0 auto;
    }
    .mp-slider-wrapper {
        width: 100%;
        height: 100%;
        overflow: hidden;
        position: relative;
    }
    .mp-slide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .mp-slide.active { opacity: 1; z-index: 2; }
    .mp-slide img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .mp-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255,255,255,0.8);
        border: 1px solid #ddd;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        z-index: 10;
        font-size: 20px;
        color: #1a1a5e;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        padding: 0;
    }
    .mp-prev { left: -20px; }
    .mp-next { right: -20px; }
    .mp-arrow:hover { background: #fff; }

    /* Details */
    .mp-title {
        font-size: 22px;
        font-weight: 600;
        margin: 0 0 10px;
        color: #333;
    }
    .mp-price {
        font-size: 18px;
        font-weight: 500;
        color: #333;
        margin-bottom: 20px;
    }
    
    /* Size */
    .mp-option-row { margin-bottom: 20px; }
    .mp-option-row label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
    .mp-size-list { 
        display: flex; 
        gap: 8px; 
        flex-wrap: wrap; /* Allow wrapping */
    }
    .mp-size-btn, .mp-fabric-btn {
        min-width: 40px;
        height: 40px;
        border: 1px solid #ddd;
        background: #fff;
        font-size: 14px;
        color:#000;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 10px;
        transition: all 0.2s;
    }
    .mp-fabric-btn {
        min-width: auto;
        padding: 0 15px;
    }
    .mp-size-btn:hover, .mp-fabric-btn:hover { border-color: #1a1a5e; }
    .mp-size-btn.selected, .mp-fabric-btn.selected {
        border-color: #1a1a5e;
        background: #fff; 
        color: #1a1a5e; 
        font-weight: 600;
        border-width: 2px;
    }
    
    /* Actions */
    .mp-actions-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap; /* Ensure robustness */
    }
    .mp-qty-box {
        display: flex;
        border: 1px solid #ddd;
        height: 44px;
    }
    .mp-qty-box button {
        width: 35px;
        border: none;
        background: #fff;
        cursor: pointer;
        font-size: 18px;
        color: #666;
        padding: 0;
    }
    .mp-qty-box input {
        width: 40px;
        border: none;
        text-align: center;
        font-size: 16px;
        color: #333;
        padding: 0;
    }

    .mp-btn-add {
        flex: 1;
        min-width: 120px;
        background: #001f5c;
        color: #fff;
        border: none;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 0 20px;
    }
    .mp-btn-add:hover { background: #001540; }

    .mp-btn-view-full {
        display: block;
        width: 100%;
        text-align: center;
        border: 1px solid #001f5c;
        padding: 12px;
        color: #001f5c;
        font-weight: 700;
        text-decoration: none;
        text-transform: uppercase;
        font-size: 13px;
    }
    .mp-btn-view-full:hover { background: #f0f4ff; }

    @media (max-width: 768px) {
        .mp-col-left { border-right: none; border-bottom: 1px solid #eee; padding: 10px; }
        .mp-col-right { padding: 20px; }
    }
</style>

<script>
    // --- Slider Logic ---
    let currentSlide = 0;
    const slides = document.querySelectorAll('.mp-slide');
    const totalSlides = slides.length;

    function mpChangeSlide(dir) {
        if(totalSlides > 0) {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + dir + totalSlides) % totalSlides;
            slides[currentSlide].classList.add('active');
        }
    }

    // --- Validation Helpers ---
    // Initialize maxQty based on PHP output available in script scope logic
    // We need to know initial available quantity. 
    // Since this is loaded via AJAX, we can rely on data attributes or inline JS variables if set.
    // Ideally, we pass it in mpSelectFabric.
    
    let mpMaxQty = 999; 
    
    function mpUpdateMaxQty(newMax) {
        mpMaxQty = parseInt(newMax);
        const input = document.getElementById('mpQty');
        input.max = mpMaxQty;
        
        const btn = document.querySelector('.mp-btn-add');
        
        if (mpMaxQty <= 0) {
            input.value = 0;
            // Disable Add to Cart
            if(btn) {
                btn.disabled = true;
                btn.textContent = 'Out of Stock';
            }
        } else {
             // Enable
            if(btn) {
                btn.disabled = false;
                btn.textContent = 'ADD TO CART';
            }
             
            let val = parseInt(input.value) || 1;
            if (val > mpMaxQty) input.value = mpMaxQty;
            if (val < 1) input.value = 1;
        }
    }

    // --- Fabric Selection ---
    function mpSelectFabric(id, name, price, qty, btn) {
        // Deselect all
        document.querySelectorAll('.mp-fabric-btn').forEach(b => b.classList.remove('selected'));
        // Select clicked
        btn.classList.add('selected');
        
        // Update hidden inputs
        document.getElementById('mpFabricId').value = id;
        document.getElementById('mpPrice').value = price;
        document.getElementById('mpSelectedFabricLabel').textContent = name;
        
        // Update displayed price
        document.querySelector('.mp-price').textContent = 'Rs ' + parseFloat(price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        // Update Max Qty
        mpUpdateMaxQty(qty);
    }

    // --- Size Selection ---
    function mpSelectSize(size, btn) {
        // Deselect all
        document.querySelectorAll('.mp-size-btn').forEach(b => b.classList.remove('selected'));
        // Select clicked
        btn.classList.add('selected');
        // Update hidden input
        document.getElementById('mpSize').value = size;
        document.getElementById('mpSelectedSizeLabel').textContent = size;
    }

    // --- Qty Logic ---
    function mpUpdateQty(change) {
        const input = document.getElementById('mpQty');
        let val = parseInt(input.value) || 1;
        val += change;
        
        if (val > mpMaxQty) {
             alert('Cannot add more. Max available: ' + mpMaxQty);
             val = mpMaxQty;
        }
        if (val < 1 && mpMaxQty > 0) val = 1;
        if (mpMaxQty <= 0) val = 0;
        
        input.value = val;
    }

    // --- Add to Cart Validation ---
    function handleQuickAdd(form) {
        const size = document.getElementById('mpSize').value;
        const sizeBtnsAvailable = document.querySelectorAll('.mp-size-btn').length > 0;
        
        if (sizeBtnsAvailable && !size) {
            alert('Please select a size');
            return false;
        }
        
        // Fabric validation
        const fabricId = document.getElementById('mpFabricId') ? document.getElementById('mpFabricId').value : null;
        const fabricBtnsAvailable = document.querySelectorAll('.mp-fabric-btn').length > 0;
        
        if (fabricBtnsAvailable && !fabricId) {
             alert('Please select a fabric');
             return false;
        }
        
        // Stock Check
        const qty = parseInt(document.getElementById('mpQty').value);
        if (qty > mpMaxQty) {
            alert('Cannot add more. Max available: ' + mpMaxQty);
            return false;
        }
        
        // AJAX submission
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();
        
        // Close modal
        $('.reveal-modal').foundation('reveal', 'close');
        
        fetch('cart-add.php?' + params + '&ajax=1')
          .then(res => res.json())
          .then(data => {
              if (data.status === 'ok') {
                  // Update cart badge using global function
                  if (typeof updateBadge === 'function' && data.cartCount !== undefined) {
                      updateBadge('cartBadge', data.cartCount);
                  }
                  // Show success message
                  alert('Added to cart successfully!');
              } else {
                  alert(data.message || 'Error adding to cart');
              }
          })
          .catch(err => {
              console.error(err);
              alert('Connection error');
          });

        return false; // Prevent real form submit
    }
    
    // Initial Run to set max qty for default selection
    <?php if (!empty($fabrics)): ?>
        // Initialize with first fabric's quantity
        mpUpdateMaxQty(<?php echo (int)$fabrics[0]['fabric_qty']; ?>);
    <?php endif; ?>


</script>
