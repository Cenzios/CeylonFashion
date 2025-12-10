<?php
if (session_id() == '' || !isset($_SESSION)) {
    session_start();
}

require_once 'config.php';
require_once 'lib/guest-cart.php';

function respond($success, $message) {
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
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$product_id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
$quantity = isset($_GET['qty']) && ctype_digit($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($product_id <= 0) {
    respond(false, 'Invalid product.');
}

if ($isLoggedIn) {
    $user_id = (int)$_SESSION['user_id'];
}

// Fetch product details
$stmt = $mysqli->prepare("SELECT product_name, product_code FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    respond(false, 'Product not found.');
}

// Get total available quantity and lowest price from all fabrics
$stmt = $mysqli->prepare("SELECT SUM(fabric_qty) as total_qty, MIN(fabric_price) as min_price FROM product_fabrics WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$fabricData = $result->fetch_assoc();
$stmt->close();

if (!$fabricData || $fabricData['total_qty'] <= 0) {
    respond(false, 'Product is out of stock.');
}

$total_available = (int)$fabricData['total_qty'];
$price = (float)$fabricData['min_price'];

if ($total_available < $quantity) {
    respond(false, 'Insufficient stock. Available: ' . $total_available . ' units.');
}

if ($isLoggedIn) {
    // Logged in user - use database
    $stmt = $mysqli->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
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
        $stmt = $mysqli->prepare("INSERT INTO cart (user_id, product_id, quantity, price, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $price);
        $stmt->execute();
        $stmt->close();
    }
} else {
    // Guest user - use session
    $guestCart = getGuestCart();
    
    if (isset($guestCart[$product_id])) {
        $new_qty = $guestCart[$product_id]['quantity'] + $quantity;
        if ($new_qty > $total_available) {
            respond(false, 'Cannot add more. Total would exceed available stock (' . $total_available . ' units).');
        }
        updateGuestCartQty($product_id, $new_qty);
    } else {
        addToGuestCart($product_id, $quantity, $price);
    }
}

respond(true, 'Item added to cart successfully!');
?>