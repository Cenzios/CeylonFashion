<?php
// =======================================
// FILE: wishlist.php
// Display user wishlist items
// =======================================

session_start();
require_once 'config.php'; // must define $mysqli
require_once 'lib/guest-cart.php';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$wishlist_items = [];

if ($isLoggedIn) {
    // Logged in user - fetch from database
    $user_id = (int) $_SESSION['user_id'];

    // Handle optional remove by GET (fallback)
    if (isset($_GET['remove']) && ctype_digit($_GET['remove'])) {
        $remove_id = (int) $_GET['remove'];
        $stmt = $mysqli->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $remove_id, $user_id);
        $stmt->execute();
        $stmt->close();
        header('Location: wishlist.php');
        exit;
    }

    // Fetch wishlist items with product details
    $query = "
        SELECT 
            w.id AS wishlist_id,
            w.product_id,
            p.product_name,
            p.product_code,
            p.product_desc,
            p.product_img1,
            p.product_img2,
            p.product_img3,
            p.product_img4,
            p.category
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?
    ";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("SQL Error: " . $mysqli->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $wishlist_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get first available image for each item
    foreach ($wishlist_items as &$item) {
        $firstImage = '';
        for ($i = 1; $i <= 4; $i++) {
            $imgField = 'product_img' . $i;
            if (!empty($item[$imgField])) {
                $firstImage = $item[$imgField];
                break;
            }
        }
        $item['product_img_name'] = $firstImage;
    }
    unset($item);
} else {
    // Guest user - fetch from session
    $guestWishlist = getGuestWishlist();
    
    if (!empty($guestWishlist)) {
        $productIds = array_keys($guestWishlist);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $query = "
            SELECT 
                id AS product_id,
                product_name,
                product_code,
                product_desc,
                product_img1,
                product_img2,
                product_img3,
                product_img4,
                category
            FROM products
            WHERE id IN ($placeholders)
        ";
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($product = $result->fetch_assoc()) {
                // Get first available image from product_img1-4
                $firstImage = '';
                for ($i = 1; $i <= 4; $i++) {
                    $imgField = 'product_img' . $i;
                    if (!empty($product[$imgField])) {
                        $firstImage = $product[$imgField];
                        break;
                    }
                }
                
                $wishlist_items[] = [
                    'wishlist_id' => 'guest_' . $product['product_id'], // Unique identifier for guest items
                    'product_id' => (int)$product['product_id'],
                    'product_name' => $product['product_name'],
                    'product_code' => $product['product_code'],
                    'product_desc' => $product['product_desc'],
                    'product_img_name' => $firstImage,
                    'category' => $product['category']
                ];
            }
            $stmt->close();
        }
    }
}

    // For each wishlist item, fetch fabric details AND sizes
    foreach ($wishlist_items as &$item) {
        $product_id = (int)$item['product_id'];
        
        // Fetch fabric details
        $fabricStmt = $mysqli->prepare("SELECT id, fabric_type, fabric_qty, fabric_price FROM product_fabrics WHERE product_id = ?");
        $fabricStmt->bind_param("i", $product_id);
        $fabricStmt->execute();
        $fabricResult = $fabricStmt->get_result();
        
        $fabrics = [];
        $minPrice = null;
        $maxPrice = null;
        $totalQty = 0;
        
        while ($f = $fabricResult->fetch_assoc()) {
            $fabrics[] = $f;
            $price = (float)$f['fabric_price'];
            $qty = (int)$f['fabric_qty'];
            
            if ($minPrice === null || $price < $minPrice) $minPrice = $price;
            if ($maxPrice === null || $price > $maxPrice) $maxPrice = $price;
            $totalQty += $qty;
        }
        
        $item['fabrics'] = $fabrics;
        $item['min_price'] = $minPrice;
        $item['max_price'] = $maxPrice;
        $item['total_qty'] = $totalQty;
        
        $fabricStmt->close();

        // Fetch Sizes
        $sizeStmt = $mysqli->prepare("SELECT size FROM product_sizes WHERE product_id = ?");
        $sizeStmt->bind_param("i", $product_id);
        $sizeStmt->execute();
        $sizeResult = $sizeStmt->get_result();
        $sizes = [];
        while ($row = $sizeResult->fetch_assoc()) {
            $sizes[] = $row['size'];
        }
        $sizeStmt->close();
        
        // Sort sizes logically (do NOT add default sizes anymore)
        $sizeOrder = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6];
        usort($sizes, function($a, $b) use ($sizeOrder) {
            return ($sizeOrder[$a] ?? 99) <=> ($sizeOrder[$b] ?? 99);
        });

        $item['sizes'] = $sizes;
    }
    unset($item);
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container wishlist-container">
    <h3 class="mb-4 text-center">❤️ My Wishlist</h3>

    <?php if (!empty($wishlist_items)): ?>
      <?php foreach ($wishlist_items as $item): ?>
        <?php
          $uniqueId = $item['wishlist_id']; // Use wishlist ID (or valid unique id) for unique element IDs
          $imgPath = 'images/products/' . ($item['product_img_name'] ?: 'no-image.png');
          if (!file_exists($imgPath)) {
              $imgPath = 'assets/no-image.png';
          }
        ?>
        <div class="wishlist-item">
          <div class="d-flex align-items-start gap-3 flex-grow-1">
            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
            <div class="meta flex-grow-1">
              <h5 class="mb-1">
                <a href="product-view.php?id=<?php echo (int)$item['product_id']; ?>" class="text-decoration-none text-dark">
                  <?php echo htmlspecialchars($item['product_name']); ?>
                </a>
              </h5>
              <div class="small text-muted mb-1">Code: <?php echo htmlspecialchars($item['product_code']); ?></div>
              
              <!-- Price Range -->
              <?php if ($item['min_price'] !== null): ?>
                <div class="small fw-bold text-success mb-2">
                  <?php if ($item['min_price'] === $item['max_price']): ?>
                    Price: Rs. <?php echo number_format($item['min_price'], 2); ?>
                  <?php else: ?>
                    Price: Rs. <?php echo number_format($item['min_price'], 2); ?> - Rs. <?php echo number_format($item['max_price'], 2); ?>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <!-- Selection Area -->
              <div class="selection-box mt-2">
                 <div class="row g-2">
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold mb-1">Fabric</label>
                        <select class="form-select form-select-sm" 
                                id="fabric_<?php echo $uniqueId; ?>" 
                                onchange="validateWishlistItem('<?php echo $uniqueId; ?>')">
                            <option value="">Select Fabric</option>
                            <?php foreach ($item['fabrics'] as $fabric): ?>
                                <option value="<?php echo $fabric['id']; ?>" <?php echo ((int)$fabric['fabric_qty'] <= 0) ? 'disabled' : ''; ?>>
                                    <?php echo htmlspecialchars($fabric['fabric_type']); ?> 
                                    (Rs. <?php echo number_format($fabric['fabric_price'], 2); ?>)
                                    <?php if((int)$fabric['fabric_qty'] <= 0) echo ' - Out of Stock'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($item['sizes'])): ?>
                    <div class="col-sm-6">
                        <label class="form-label small fw-bold mb-1">Size</label>
                        <select class="form-select form-select-sm" 
                                id="size_<?php echo $uniqueId; ?>" 
                                onchange="validateWishlistItem('<?php echo $uniqueId; ?>')">
                            <option value="">Select Size</option>
                            <?php foreach ($item['sizes'] as $size): ?>
                                <option value="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                 </div>
              </div>

              <!-- Availability -->
              <div class="mt-2">
                <?php if ($item['total_qty'] > 0): ?>
                  <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i> In Stock
                  </span>
                <?php else: ?>
                  <span class="badge bg-danger">
                    <i class="bi bi-x-circle"></i> Out of Stock
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="text-end d-flex flex-column align-items-end gap-2 actions-col">
            <!-- View Details button -->
            <a href="product-view.php?id=<?php echo (int)$item['product_id']; ?>" class="btn btn-outline-primary btn-sm w-100">
              <i class="bi bi-eye"></i> View
            </a>

            <?php if ($item['total_qty'] > 0): ?>
              <!-- Add to Cart button (DISABLED BY DEFAULT) -->
              <button type="button" 
                      class="btn btn-success btn-sm w-100" 
                      id="btn_add_<?php echo $uniqueId; ?>" 
                      onclick="addWishlistItemToCart(<?php echo (int)$item['product_id']; ?>, '<?php echo $uniqueId; ?>')" 
                      disabled>
                <i class="bi bi-cart-plus"></i> Add to Cart
              </button>
            <?php endif; ?>

            <!-- Remove form -->
            <?php if ($isLoggedIn): ?>
              <form action="wishlist-remove.php" method="post" onsubmit="return confirm('Remove this item from wishlist?');" class="mb-0 w-100">
                <input type="hidden" name="wishlist_id" value="<?php echo (int)$item['wishlist_id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger w-100" title="Remove">
                  <i class="bi bi-trash"></i> Remove
                </button>
              </form>
            <?php else: ?>
              <form action="wishlist-remove.php" method="post" onsubmit="return confirm('Remove this item from wishlist?');" class="mb-0 w-100">
                <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger w-100" title="Remove">
                  <i class="bi bi-trash"></i> Remove
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="text-center mt-4">
        <a href="index.php" class="btn btn-outline-primary">Continue Shopping</a>
      </div>
    <?php else: ?>
      <div class="text-center py-5">
        <i class="bi bi-heart" style="font-size: 4rem; color: #e9ecef;"></i>
        <p class="lead mt-3">Your wishlist is empty.</p>
        <a href="index.php" class="btn btn-primary">Continue Shopping</a>
      </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<style>
  body { background:#f8f9fa; }
  .wishlist-container {
    max-width: 1000px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,.08);
  }
  .wishlist-item {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    border-bottom:1px solid #eee;
    padding:20px 0;
    gap:20px;
  }
  .wishlist-item:last-child {
    border-bottom: none;
  }
  .wishlist-item img {
    width:120px;
    height:120px;
    object-fit:cover;
    border-radius:10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  .selection-box {
    background: #fdfdfd; 
    border: 1px dashed #ced4da; 
    padding: 8px; 
    border-radius: 6px;
    max-width: 400px;
  }
  .actions-col {
      min-width: 140px;
  }
  
  @media (max-width: 768px) {
    .wishlist-item {
      flex-direction: column;
      align-items: stretch;
    }
    .wishlist-item img {
      width: 100px;
      height: 100px;
    }
    .selection-box {
        max-width: 100%;
    }
    .d-flex.align-items-start.gap-3 {
        align-items: center !important;
    }
    .actions-col {
        width: 100%;
        flex-direction: row !important;
        justify-content: space-between;
    }
    .actions-col .btn {
        flex: 1;
    }
  }
</style>

<?php include 'includes/scripts.php'; ?>

<script>
    function validateWishlistItem(uniqueId) {
        const fabricSelect = document.getElementById('fabric_' + uniqueId);
        const sizeSelect = document.getElementById('size_' + uniqueId);
        const addBtn = document.getElementById('btn_add_' + uniqueId);
        
        if (fabricSelect && addBtn) {
            // If size selector exists, both must have values
            // If size selector doesn't exist (no sizes for product), only fabric matters
            const fabricValid = fabricSelect.value !== "";
            const sizeValid = sizeSelect ? sizeSelect.value !== "" : true;
            
            if (fabricValid && sizeValid) {
                addBtn.disabled = false;
            } else {
                addBtn.disabled = true;
            }
        }
    }

    function addWishlistItemToCart(productId, uniqueId) {
        const fabricSelect = document.getElementById('fabric_' + uniqueId);
        const sizeSelect = document.getElementById('size_' + uniqueId);
        
        const fabricId = fabricSelect ? fabricSelect.value : '';
        const size = sizeSelect ? sizeSelect.value : '';

        // Validation: fabric is required, size only if selector exists
        if (!fabricId) {
            alert("Please select a Fabric.");
            return;
        }
        if (sizeSelect && !size) {
            alert("Please select a Size.");
            return;
        }

        // Add to cart with Ajax
        // Note: The cart-add.php might need updates to handle fabric_id and size parameters if it doesn't already.
        // For now, we pass them as GET parameters.
        const url = `cart-add.php?id=${productId}&qty=1&ajax=1&fabric_id=${fabricId}&size=${encodeURIComponent(size)}`;

        fetch(url)
          .then(response => response.json())
          .then(data => {
            if (data.status === 'ok') {
              alert(data.message || 'Item added successfully to cart');
              if (data.cartCount !== undefined) {
                updateBadge('cartBadge', data.cartCount);
              }
            } else {
              alert(data.message || 'Failed to add to cart.');
            }
          })
          .catch(err => {
              console.error(err);
              alert("An error occurred while adding to cart.");
          });
    }
</script>

</body>
</html>
