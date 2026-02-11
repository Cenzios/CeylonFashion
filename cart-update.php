<?php
session_start();
require_once 'config.php';
require_once 'lib/guest-cart.php';

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_id = $is_logged_in ? (int)$_SESSION['user_id'] : 0;

$action = isset($_GET['action']) ? $_GET['action'] : '';
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Get ID - can be int (user) or string (guest)
$cart_id = isset($_GET['id']) ? $_GET['id'] : 0;

if (!$cart_id || !in_array($action, ['increase', 'decrease'])) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }
    header('Location: cart.php');
    exit;
}

$new_qty = 0;
$current_qty = 0;
$price = 0.0;
$error_msg = "";
$items_count = 0;
$total_qty_sum = 0;
$grand_total = 0.0;
$new_subtotal = 0.0;

if ($is_logged_in) {
    // LOGGED IN USER LOGIC
    if (!ctype_digit((string)$cart_id)) {
        if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Invalid ID format for user']); exit; }
        header('Location: cart.php'); exit;
    }

    $cart_id = (int)$cart_id; // Safe cast

    // Fetch cart item to verify ownership and get current details
    $stmt = $mysqli->prepare("SELECT product_id, fabric_id, quantity, price FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if (!$item) {
        if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Item not found']); exit; }
        header('Location: cart.php'); exit;
    }

    $product_id = (int)$item['product_id'];
    $fabric_id = (int)$item['fabric_id'];
    $current_qty = (int)$item['quantity'];
    $price = (float)$item['price'];
    $new_qty = $current_qty;

    if ($action === 'increase') {
        // Check stock availability (Specific Fabric or Total)
        $available_qty = 0;
        if ($fabric_id > 0) {
            $fStmt = $mysqli->prepare("SELECT fabric_qty FROM product_fabrics WHERE id = ?");
            $fStmt->bind_param("i", $fabric_id);
            $fStmt->execute();
            $res = $fStmt->get_result()->fetch_assoc();
            $available_qty = $res ? (int)$res['fabric_qty'] : 0;
            $fStmt->close();
        } else {
            // Fallback
            $fStmt = $mysqli->prepare("SELECT SUM(fabric_qty) as total_qty FROM product_fabrics WHERE product_id = ?");
            $fStmt->bind_param("i", $product_id);
            $fStmt->execute();
            $res = $fStmt->get_result()->fetch_assoc();
            $available_qty = $res ? (int)$res['total_qty'] : 0;
            $fStmt->close();
        }
        
        if ($current_qty < $available_qty) {
            $new_qty++;
        } else {
            $error_msg = "Cannot add more. Only $available_qty items available.";
        }
        
    } elseif ($action === 'decrease') {
        if ($current_qty > 1) {
            $new_qty--;
        }
    }

    // Update if quantity changed
    if ($new_qty !== $current_qty && empty($error_msg)) {
        $stmt = $mysqli->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $new_qty, $cart_id);
        if (!$stmt->execute()) {
             $error_msg = "Failed to update cart.";
        }
        $stmt->close();
    }
    
    // Recalculate Totals for User
    $new_subtotal = $new_qty * $price;
    
    $gStmt = $mysqli->prepare("SELECT SUM(quantity * price) as grand_total FROM cart WHERE user_id = ?");
    $gStmt->bind_param("i", $user_id);
    $gStmt->execute();
    $gRes = $gStmt->get_result()->fetch_assoc();
    $grand_total = $gRes ? (float)$gRes['grand_total'] : 0;
    $gStmt->close();
    
    $items_count = getUserCartCount($mysqli, $user_id);
    
    // Get total quantity sum for cart summary
    $qStmt = $mysqli->prepare("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = ?");
    $qStmt->bind_param("i", $user_id);
    $qStmt->execute();
    $qRes = $qStmt->get_result()->fetch_assoc();
    $total_qty_sum = $qRes ? (int)$qRes['total_qty'] : 0;
    $qStmt->close();

} else {
    // GUEST USER LOGIC
    $guestCart = getGuestCart();
    
    if (!isset($guestCart[$cart_id])) {
        if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Item not found in guest cart']); exit; }
        header('Location: cart.php'); exit;
    }
    
    $item = $guestCart[$cart_id];
    $product_id = $item['product_id'];
    $fabric_id = $item['fabric_id'] ?? 0;
    $current_qty = $item['quantity'];
    $price = $item['price'];
    $new_qty = $current_qty;
    
    if ($action === 'increase') {
        // Check stock availability
        $available_qty = 0;
        if ($fabric_id > 0) {
            $fStmt = $mysqli->prepare("SELECT fabric_qty FROM product_fabrics WHERE id = ?");
            $fStmt->bind_param("i", $fabric_id);
            $fStmt->execute();
            $res = $fStmt->get_result()->fetch_assoc();
            $available_qty = $res ? (int)$res['fabric_qty'] : 0;
            $fStmt->close();
        } else {
            $fStmt = $mysqli->prepare("SELECT SUM(fabric_qty) as total_qty FROM product_fabrics WHERE product_id = ?");
            $fStmt->bind_param("i", $product_id);
            $fStmt->execute();
            $res = $fStmt->get_result()->fetch_assoc();
            $available_qty = $res ? (int)$res['total_qty'] : 0;
            $fStmt->close();
        }
        
        if ($current_qty < $available_qty) {
            $new_qty++;
        } else {
            $error_msg = "Cannot add more. Only $available_qty items available.";
        }
        
    } elseif ($action === 'decrease') {
        if ($current_qty > 1) {
            $new_qty--;
        }
    }
    
    // Update Session
    if ($new_qty !== $current_qty && empty($error_msg)) {
        updateGuestCartQty($cart_id, $new_qty);
    }
    
    // Recalculate Totals for Guest
    $new_subtotal = $new_qty * $price;
    
    // Calculate Grand Total from Session
    $guestCart = getGuestCart(); // Refresh
    $grand_total = 0;
    foreach ($guestCart as $itm) {
        $grand_total += $itm['quantity'] * $itm['price'];
    }
    
    $items_count = getGuestCartCount();
    
    // Get total quantity sum for cart summary
    $total_qty_sum = 0;
    foreach ($guestCart as $itm2) {
        $total_qty_sum += (int)$itm2['quantity'];
    }
}


// RESPONSE
if ($is_ajax) {
    if (!empty($error_msg)) {
        echo json_encode(['success' => false, 'error' => $error_msg]);
    } else {
        echo json_encode([
            'success' => true, 
            'new_qty' => $new_qty, 
            'new_subtotal' => number_format($new_subtotal, 2),
            'grand_total' => number_format($grand_total, 2),
            'cart_count' => $items_count,
            'total_qty_sum' => $total_qty_sum
        ]);
    }
} else {
    // Non-AJAX fallback
    if (!empty($error_msg)) {
        $_SESSION['cart_error'] = $error_msg;
    }
    header('Location: cart.php');
    exit;
}

