<?php
session_start();
require_once 'config.php';

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';

// Clear cart for the logged-in user
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $mysqli->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}
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
    <a href="index.php" style="display: inline-block; margin-top: 30px; padding: 12px 30px; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 8px;">Continue Shopping</a>
</div>

<?php include 'includes/footer.php'; ?>
<?php include 'includes/scripts.php'; ?>
</body>
</html>