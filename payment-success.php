<?php
session_start();
require_once 'config.php';

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';

// WORKAROUND FOR LOCALHOST & SESSION BASED CHECKOUT
// We retrieve order details from Session (set in create-order.php) and INSERT them now.

if ($order_id && isset($_SESSION['temp_orders'][$order_id])) {
    $orderDataItems = $_SESSION['temp_orders'][$order_id];
    
    $stmtInsert = $mysqli->prepare("
        INSERT INTO orders (
            order_id, username, product_id, product_code, product_name, 
            fabric_id, fabric_type, size, quantity, 
            unit_price, total_amount, payment_status,
            customer_name, customer_email, customer_phone, 
            delivery_address, city, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    if ($stmtInsert) {
        foreach ($orderDataItems as $item) {
             $stmtInsert->bind_param(
                "ssississiddsssss",
                $item['order_id'], 
                $item['username'], 
                $item['product_id'], 
                $item['product_code'], 
                $item['product_name'],
                $item['fabric_id'], 
                $item['fabric_type'], 
                $item['size'], 
                $item['quantity'],
                $item['unit_price'], 
                $item['total_amount'],
                $item['customer_name'], 
                $item['customer_email'], 
                $item['customer_phone'], 
                $item['delivery_address'], 
                $item['city']
            );
            $stmtInsert->execute();
        }
        $stmtInsert->close();
        
        // --- Reduce stock (fabric_qty) for each purchased item ---
        $stockStmt = $mysqli->prepare("UPDATE product_fabrics SET fabric_qty = GREATEST(fabric_qty - ?, 0) WHERE id = ?");
        if ($stockStmt) {
            foreach ($orderDataItems as $item) {
                $purchasedQty = (int)$item['quantity'];
                $fabricId = (int)$item['fabric_id'];
                if ($fabricId > 0 && $purchasedQty > 0) {
                    $stockStmt->bind_param("ii", $purchasedQty, $fabricId);
                    $stockStmt->execute();
                }
            }
            $stockStmt->close();
        }
        
        // Remove from session after saving
        unset($_SESSION['temp_orders'][$order_id]);
    } else {
        error_log("Prepare failed in payment-success.php: " . $mysqli->error);
    }
} else if ($order_id) {
    // Fallback: If order exists in DB (legacy/from earlier/webhook), just update status
    // But since we removed INSERT from create-order, this only runs if somehow webhook fired first (impossible on localhost)
    // or if this is an old order.
    $updateStmt = $mysqli->prepare("UPDATE orders SET payment_status = 'paid', updated_at = NOW() WHERE order_id = ?");
    $updateStmt->bind_param("s", $order_id);
    $updateStmt->execute();
    $updateStmt->close();
}

// Clear cart for the logged-in user
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $mysqli->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch product codes from order to show for resale
$product_codes_list = [];
if ($order_id) {
    // Assuming standard table structure: order_items table or just orders table if 1 row per order (unlikely for cart).
    // Let's check `orders` table or `order_items`. 
    // Wait, earlier files showed `orders` table has `product_id`. If `orders` table stores individual items row-by-row with same order_id, we can query it.
    // Based on `start-reselling.php`: "SELECT ... FROM orders WHERE username = ? AND product_id = ?"
    // This implies `orders` table holds item lines.
    
    $stmt = $mysqli->prepare("
        SELECT p.product_code 
        FROM orders o 
        JOIN products p ON o.product_id = p.id 
        WHERE o.order_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $product_codes_list[] = $row['product_code'];
        }
        $stmt->close();
    }
}
$product_codes_str = implode(', ', $product_codes_list);

?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>
<?php include 'includes/navbar.php'; ?>

<div style="max-width: 600px; margin: 100px auto; text-align: center; padding: 40px;">
    <div style="font-size: 80px; color: #10b981;">✓</div>
    <h1 style="color: #10b981; margin: 20px 0;">Payment Successful!</h1>
    <p style="font-size: 18px; color: #666;">Your order has been placed successfully.</p>
    <p style="font-size: 14px; color: #999;">Order ID: <strong><?= htmlspecialchars($order_id); ?></strong></p>
    
    <?php if (!empty($product_codes_str)): ?>
        <p style="margin-top: 20px; color: #5B21B6; font-weight: 600;">
            You can resell item using Product code- <?= htmlspecialchars($product_codes_str); ?>
        </p>
    <?php endif; ?>
    
    <a href="index.php" style="display: inline-block; margin-top: 30px; padding: 12px 30px; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 8px;">Continue Shopping</a>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>
</body>
</html>