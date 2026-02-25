<?php
// ==========================================
// FILE: wishlist-toggle.php
// Toggle product in wishlist (supports guest and logged-in users)
// ==========================================

if (session_id() == '' || !isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

// If you have guest-cart.php library, include it
if (file_exists(__DIR__ . '/../../lib/guest-cart.php')) {
    require_once __DIR__ . '/../../lib/guest-cart.php';
}

header('Content-Type: application/json');

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Get product ID
$product_id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
    exit;
}

// Check if product exists
$stmt = $mysqli->prepare("SELECT id FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product_exists = $result->num_rows > 0;
$stmt->close();

if (!$product_exists) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}

if ($isLoggedIn) {
    // Logged in user - use database
    $user_id = (int)$_SESSION['user_id'];

    // Check if already in wishlist
    $stmt = $mysqli->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Remove from wishlist
        $stmt = $mysqli->prepare("DELETE FROM wishlist WHERE id = ?");
        $stmt->bind_param("i", $existing['id']);
        $success = $stmt->execute();
        $stmt->close();
        
        $count = getUserWishlistCount($mysqli, $user_id);
        
        if ($success) {
            echo json_encode(['status' => 'ok', 'action' => 'removed', 'message' => 'Removed from wishlist', 'wishlistCount' => $count]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove from wishlist']);
        }
    } else {
        // Add to wishlist
        $stmt = $mysqli->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $user_id, $product_id);
        $success = $stmt->execute();
        $stmt->close();
        
        $count = getUserWishlistCount($mysqli, $user_id);
        
        if ($success) {
            echo json_encode(['status' => 'ok', 'action' => 'added', 'message' => 'Added to wishlist!', 'wishlistCount' => $count]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add to wishlist']);
        }
    }
} else {
    // Guest user - use library function
    $action = toggleGuestWishlist($product_id);
    $count = getGuestWishlistCount();
    
    if ($action === 'removed') {
        echo json_encode(['status' => 'ok', 'action' => 'removed', 'message' => 'Removed from wishlist', 'wishlistCount' => $count]);
    } else {
        echo json_encode(['status' => 'ok', 'action' => 'added', 'message' => 'Added to wishlist!', 'wishlistCount' => $count]);
    }
}

exit;
?>