<?php
if (session_id() == '' || !isset($_SESSION)) {
    session_start();
}

require_once 'config.php';
require_once 'lib/guest-cart.php';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

$product_id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
$quantity = isset($_GET['qty']) && ctype_digit($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
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
    $_SESSION['cart_error'] = 'Product not found.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Get total available quantity and lowest price from all fabrics
$stmt = $mysqli->prepare("SELECT SUM(fabric_qty) as total_qty, MIN(fabric_price) as min_price FROM product_fabrics WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$fabricData = $result->fetch_assoc();
$stmt->close();

if (!$fabricData || $fabricData['total_qty'] <= 0) {
    $_SESSION['cart_error'] = 'Product is out of stock.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

$total_available = (int)$fabricData['total_qty'];
$price = (float)$fabricData['min_price'];

// Check if total quantity requested is available
if ($total_available < $quantity) {
    $_SESSION['cart_error'] = 'Insufficient stock. Available: ' . $total_available . ' units.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

if ($isLoggedIn) {
    // Logged in user - use database
    // Check if item already in cart
    $stmt = $mysqli->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Update existing cart item
        $new_qty = $existing['quantity'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($new_qty > $total_available) {
            $_SESSION['cart_error'] = 'Cannot add more. Total would exceed available stock (' . $total_available . ' units).';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
            exit;
        }
        
        $stmt = $mysqli->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $new_qty, $existing['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new cart item
        $stmt = $mysqli->prepare("INSERT INTO cart (user_id, product_id, quantity, price, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $price);
        $stmt->execute();
        $stmt->close();
    }
} else {
    // Guest user - use session
    $guestCart = getGuestCart();
    
    if (isset($guestCart[$product_id])) {
        // Update existing cart item
        $new_qty = $guestCart[$product_id]['quantity'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($new_qty > $total_available) {
            $_SESSION['cart_error'] = 'Cannot add more. Total would exceed available stock (' . $total_available . ' units).';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
            exit;
        }
        
        updateGuestCartQty($product_id, $new_qty);
    } else {
        // Add new cart item
        addToGuestCart($product_id, $quantity, $price);
    }
}

$_SESSION['cart_success'] = 'Item added to cart successfully!';
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
exit;
?>