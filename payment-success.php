<?php
session_start();
require_once 'config.php';

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';

// WORKAROUND FOR LOCALHOST: Force update status to 'paid'
// Since payment-notify.php cannot be reached by PayHere on localhost,
// we update the status here when the user is redirected back.
if ($order_id) {
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