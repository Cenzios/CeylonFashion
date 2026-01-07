<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];

// Fetch user's orders
$stmt = $mysqli->prepare("
    SELECT * FROM orders 
    WHERE username = ? AND payment_status = 'paid'
    ORDER BY created_at DESC
");
$stmt->bind_param("s", $username);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
    <h2>My Orders</h2>
    <div class="alert alert-info">
      Logged in as: <strong><?= htmlspecialchars($username); ?></strong>
    </div>
    
    <?php if (empty($orders)): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Fabric</th>
                    <th>Size</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Delivery</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_id']); ?></td>
                        <td><?= htmlspecialchars($order['product_name']); ?></td>
                        <td><?= htmlspecialchars($order['fabric_type']); ?></td>
                        <td><?= htmlspecialchars($order['size']); ?></td>
                        <td><?= $order['quantity']; ?></td>
                        <td>LKR <?= number_format($order['total_amount'], 2); ?></td>
                        <td><span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>"><?= $order['payment_status']; ?></span></td>
                        <td><span class="badge bg-info"><?= $order['delivery_status']; ?></span></td>
                        <td><?= date('Y-m-d', strtotime($order['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>