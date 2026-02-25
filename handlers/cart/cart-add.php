<?php
if (session_id() == '' || !isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/guest-cart.php';

function respond($success, $message, $redirectInfo = null) {
    global $mysqli;
    
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        header('Content-Type: application/json');
        
        // Calculate new cart count
        $cartCount = 0;
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $cartCount = getUserCartCount($mysqli, (int)$_SESSION['user_id']);
        } else {
            $cartCount = getGuestCartCount();
        }
        
        echo json_encode([
            'status' => $success ? 'ok' : 'error', 
            'message' => $message,
            'cartCount' => $cartCount
        ]);
        exit;
    }
    if ($success) {
        $_SESSION['cart_success'] = $message;
    } else {
        $_SESSION['cart_error'] = $message;
    }
    
    // Redirect logic
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
    header('Location: ' . $redirect);
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$product_id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
$quantity   = isset($_GET['qty']) && ctype_digit($_GET['qty']) ? (int)$_GET['qty'] : 1;
$fabric_id  = isset($_GET['fabric_id']) && ctype_digit($_GET['fabric_id']) ? (int)$_GET['fabric_id'] : 0;
$size       = isset($_GET['size']) ? trim($_GET['size']) : '';

if ($product_id <= 0) {
    respond(false, 'Invalid product.');
}

// 1. Fetch Product Basic Info
$stmt = $mysqli->prepare("SELECT product_name, product_code FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    respond(false, 'Product not found.');
}

// 2. Validate Fabric and Get Specific Price/Qty
// If fabric_id is provided, use it. If not (legacy behavior), we might default to first available or fail.
// Given strict reqs, we should ideally require fabric_id.
// However, to avoid breaking legacy adds (if any), check logic:
$price = 0;
$total_available = 0;

if ($fabric_id > 0) {
    $stmt = $mysqli->prepare("SELECT fabric_qty, fabric_price FROM product_fabrics WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $fabric_id, $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $fabricData = $res->fetch_assoc();
    $stmt->close();
    
    if (!$fabricData) {
        respond(false, 'Selected fabric not available.');
    }
    
    $total_available = (int)$fabricData['fabric_qty'];
    $price = (float)$fabricData['fabric_price'];
} else {
    // Fallback: Get total qty and min price (legacy)
    // BUT we encourage selecting a fabric. 
    // If no fabric selected, we might pick the first one or error out.
    // Let's error out if fabrics exist but none selected, otherwise (products with no fabrics?) fallback.
    $stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM product_fabrics WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $hasFabrics = $stmt->get_result()->fetch_assoc()['cnt'] > 0;
    $stmt->close();
    
    if ($hasFabrics) {
         respond(false, 'Please select a fabric.');
    }
    
    // If product strictly relies on fabrics for stock/price, we can't proceed.
    // Assuming all products have fabrics as per system design:
    respond(false, 'Product configuration error (no fabric).');
}

if ($total_available <= 0) {
    respond(false, 'Selected option provides 0 items in stock.');
}

if ($total_available < $quantity) {
    respond(false, 'Insufficient stock. Available: ' . $total_available . ' units.');
}

// 3. Logic: Add/Update Cart
if ($isLoggedIn) {
    $user_id = (int)$_SESSION['user_id'];
    
    // Check if item exists with SAME product_id + fabric_id + size
    // Note: size is string, treat empty as null or empty string consistent
    $checkSql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND fabric_id = ? AND size = ?";
    $stmt = $mysqli->prepare($checkSql);
    $stmt->bind_param("iiis", $user_id, $product_id, $fabric_id, $size);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $new_qty = $existing['quantity'] + $quantity;
        if ($new_qty > $total_available) {
            respond(false, 'Cannot add more. Total would exceed available stock (' . $total_available . ' units).');
        }
        
        $stmt = $mysqli->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $new_qty, $existing['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $mysqli->prepare("INSERT INTO cart (user_id, product_id, fabric_id, size, quantity, price, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiisid", $user_id, $product_id, $fabric_id, $size, $quantity, $price);
        $stmt->execute();
        $stmt->close();
    }
} else {
    // Guest User
    // Use composite key for unique identification in session
    // Format: "pid_fid_size"
    $itemKey = $product_id . '_' . $fabric_id . '_' . $size;
    
    $guestCart = getGuestCart();
    
    // Check existing quantity in guest cart
    $existingItemsQty = 0;
    if (isset($guestCart[$itemKey])) {
        $existingItemsQty = (int)$guestCart[$itemKey]['quantity'];
    }
    
    if (($existingItemsQty + $quantity) > $total_available) {
        respond(false, 'Cannot add more. You already have ' . $existingItemsQty . ' in cart. Total available: ' . $total_available);
    }
    
    // Check if key exists (or check logic inside lib)
    // Since our lib uses product_id as key, we need to UPDATE THE LIB or handle it here.
    // Since we are updating lib/guest-cart.php next, let's assume we use addToGuestCart with extra params
    // OR we manage the unique key logic inside lib.
    // Ideally, lib should abstract this.
    // Let's call the updated function signature (which we will implement next).
    
    // For now, let's just pass the data. The lib update will handle the composite key storage.
    // Actually, `addToGuestCart` in current lib takes ($product_id, $quantity, $price).
    // We will update it to ($product_id, $quantity, $price, $fabric_id, $size).
    // So we can call it here:
    
    $res = addToGuestCart($product_id, $quantity, $price, $fabric_id, $size);
    if ($res !== true) {
         // If addToGuestCart returns error message (string) or false
         if (is_string($res)) respond(false, $res);
         respond(false, 'Failed to add to guest cart.');
    }
}

respond(true, 'Item added to cart successfully!');
?>