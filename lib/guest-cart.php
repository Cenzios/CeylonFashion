<?php
/**
 * Guest Cart and Wishlist Helper Functions
 * Handles session-based cart and wishlist for non-logged-in users
 */

if (session_id() == '' || !isset($_SESSION)) {
    session_start();
}

/**
 * Initialize guest cart/wishlist in session if not exists
 */
function initGuestCart() {
    if (!isset($_SESSION['guest_cart'])) {
        $_SESSION['guest_cart'] = [];
    }
}

function initGuestWishlist() {
    if (!isset($_SESSION['guest_wishlist'])) {
        $_SESSION['guest_wishlist'] = [];
    }
}

/**
 * Add product to guest cart
 */
function addToGuestCart($product_id, $quantity = 1, $price = 0) {
    initGuestCart();
    
    if (isset($_SESSION['guest_cart'][$product_id])) {
        $_SESSION['guest_cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['guest_cart'][$product_id] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $price
        ];
    }
    
    return true;
}

/**
 * Remove product from guest cart
 */
function removeFromGuestCart($product_id) {
    initGuestCart();
    if (isset($_SESSION['guest_cart'][$product_id])) {
        unset($_SESSION['guest_cart'][$product_id]);
        return true;
    }
    return false;
}

/**
 * Update guest cart quantity
 */
function updateGuestCartQty($product_id, $quantity) {
    initGuestCart();
    if (isset($_SESSION['guest_cart'][$product_id])) {
        if ($quantity <= 0) {
            unset($_SESSION['guest_cart'][$product_id]);
        } else {
            $_SESSION['guest_cart'][$product_id]['quantity'] = $quantity;
        }
        return true;
    }
    return false;
}

/**
 * Get guest cart items
 */
function getGuestCart() {
    initGuestCart();
    return $_SESSION['guest_cart'];
}

/**
 * Get guest cart count
 */
function getGuestCartCount() {
    initGuestCart();
    $count = 0;
    foreach ($_SESSION['guest_cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

/**
 * Toggle product in guest wishlist
 */
function toggleGuestWishlist($product_id) {
    initGuestWishlist();
    
    if (isset($_SESSION['guest_wishlist'][$product_id])) {
        unset($_SESSION['guest_wishlist'][$product_id]);
        return 'removed';
    } else {
        $_SESSION['guest_wishlist'][$product_id] = [
            'product_id' => $product_id,
            'added_at' => time()
        ];
        return 'added';
    }
}

/**
 * Check if product is in guest wishlist
 */
function isInGuestWishlist($product_id) {
    initGuestWishlist();
    return isset($_SESSION['guest_wishlist'][$product_id]);
}

/**
 * Get guest wishlist items
 */
function getGuestWishlist() {
    initGuestWishlist();
    return $_SESSION['guest_wishlist'];
}

/**
 * Get guest wishlist count
 */
function getGuestWishlistCount() {
    initGuestWishlist();
    return count($_SESSION['guest_wishlist']);
}

/**
 * Clear guest cart (useful when user logs in and migrates to database)
 */
function clearGuestCart() {
    if (isset($_SESSION['guest_cart'])) {
        unset($_SESSION['guest_cart']);
    }
}

/**
 * Clear guest wishlist (useful when user logs in and migrates to database)
 */
function clearGuestWishlist() {
    if (isset($_SESSION['guest_wishlist'])) {
        unset($_SESSION['guest_wishlist']);
    }
}

/**
 * Get cart count for logged-in user from database
 */
function getUserCartCount($mysqli, $user_id) {
    $stmt = $mysqli->prepare("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = ?");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data ? (int)$data['total_qty'] : 0;
}

/**
 * Get wishlist count for logged-in user from database
 */
function getUserWishlistCount($mysqli, $user_id) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data ? (int)$data['total'] : 0;
}
/**
 * Migrate guest data to user account (Cart & Wishlist)
 */
function migrateGuestData($mysqli, $user_id) {
    initGuestCart();
    initGuestWishlist();

    // 1. Migrate Cart
    if (!empty($_SESSION['guest_cart'])) {
        $stmtCheck = $mysqli->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmtInsert = $mysqli->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmtUpdate = $mysqli->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");

        foreach ($_SESSION['guest_cart'] as $pid => $item) {
            $qty = $item['quantity'];
            
            // Check if exists
            $stmtCheck->bind_param("ii", $user_id, $pid);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result();
            
            if ($row = $res->fetch_assoc()) {
                // Update
                $stmtUpdate->bind_param("ii", $qty, $row['id']);
                $stmtUpdate->execute();
            } else {
                // Insert
                $stmtInsert->bind_param("iii", $user_id, $pid, $qty);
                $stmtInsert->execute();
            }
        }
        $stmtCheck->close();
        $stmtInsert->close();
        $stmtUpdate->close();
        
        // Clear guest cart
        unset($_SESSION['guest_cart']);
    }

    // 2. Migrate Wishlist
    if (!empty($_SESSION['guest_wishlist'])) {
        $stmtCheckW = $mysqli->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmtInsertW = $mysqli->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");

        foreach ($_SESSION['guest_wishlist'] as $pid => $item) {
            // Check if exists
            $stmtCheckW->bind_param("ii", $user_id, $pid);
            $stmtCheckW->execute();
            if ($stmtCheckW->get_result()->num_rows == 0) {
                // Insert
                $stmtInsertW->bind_param("ii", $user_id, $pid);
                $stmtInsertW->execute();
            }
        }
        $stmtCheckW->close();
        $stmtInsertW->close();
        
        // Clear guest wishlist
        unset($_SESSION['guest_wishlist']);
    }
}
?>

