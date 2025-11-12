<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }

if (!isset($_SESSION["username"])) {
    header("location:index.php");
    exit;
}

include 'config.php';

$username = $_SESSION["username"];

// Fetch user's orders from new orders table
$stmt = $mysqli->prepare("
    SELECT 
        id, order_id, product_name, fabric_type, size, quantity,
        unit_price, total_amount, payment_status, payment_id, delivery_status,
        customer_name, customer_email, customer_phone, delivery_address, city,
        created_at, updated_at
    FROM orders 
    WHERE username = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Orders || Ceylon Fashion.lk</title>
    <?php include 'includes/head.php'; ?>
    <style>
        body {
            background: #f8f9fa;
        }
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .page-title {
            color: #430160;
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #430160;
        }
        .order-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: box-shadow 0.3s ease;
        }
        .order-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 10px;
        }
        .order-id {
            font-size: 1.2em;
            font-weight: bold;
            color: #430160;
        }
        .order-status {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid {
            background: #10b981;
            color: white;
        }
        .badge-pending {
            background: #f59e0b;
            color: white;
        }
        .badge-failed {
            background: #ef4444;
            color: white;
        }
        .badge-cancelled {
            background: #6b7280;
            color: white;
        }
        .badge-delivered {
            background: #10b981;
            color: white;
        }
        .badge-shipped {
            background: #3b82f6;
            color: white;
        }
        .badge-processing {
            background: #8b5cf6;
            color: white;
        }
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .detail-group {
            padding: 10px;
            background: #f9fafb;
            border-radius: 6px;
        }
        .detail-label {
            font-size: 0.85em;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .detail-value {
            font-weight: 600;
            color: #1f2937;
        }
        .product-info {
            background: #f0e6ff;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .product-specs {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .product-spec {
            font-size: 0.95em;
        }
        .total-amount {
            font-size: 1.5em;
            font-weight: bold;
            color: #430160;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 8px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .empty-state-icon {
            font-size: 4em;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        .empty-state h4 {
            color: #430160;
            margin-bottom: 15px;
        }
        .empty-state p {
            color: #6b7280;
            margin-bottom: 25px;
        }
        .btn-browse {
            background: #430160;
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .btn-browse:hover {
            background: #5a0180;
            color: white;
        }
        .order-timeline {
            margin-top: 15px;
            padding: 15px;
            background: #fef3c7;
            border-radius: 6px;
            border-left: 4px solid #f59e0b;
        }
        .order-timeline strong {
            display: block;
            margin-bottom: 5px;
        }
        .payment-id {
            font-family: monospace;
            font-size: 0.9em;
            color: #6b7280;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        .order-summary {
            background: #f0e6ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            text-align: center;
        }
        .order-summary h5 {
            margin: 0;
            color: #430160;
            font-size: 1.3em;
        }
        .last-updated {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        .last-updated small {
            color: #6b7280;
            font-size: 0.85em;
        }

        @media (max-width: 768px) {
            .orders-container {
                padding: 20px 15px;
            }
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-status {
                width: 100%;
            }
            .product-specs {
                flex-direction: column;
                gap: 10px;
            }
            .order-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="orders-container">
        <h1 class="page-title">My Orders</h1>

        <?php if ($result->num_rows === 0): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h4>No Orders Yet</h4>
                <p>You haven't placed any orders yet. Start shopping now!</p>
                <a href="products.php" class="btn-browse">Browse Products</a>
            </div>
        <?php else: ?>
            <?php 
            $orderCount = 0;
            while($order = $result->fetch_assoc()): 
                $orderCount++;
            ?>
                <div class="order-card">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                            <small style="color: #6b7280;">
                                Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?> 
                                at <?php echo date('g:i A', strtotime($order['created_at'])); ?>
                            </small>
                        </div>
                        <div class="order-status">
                            <?php
                            // Payment status badge
                            $paymentClass = 'badge-pending';
                            if ($order['payment_status'] === 'paid') $paymentClass = 'badge-paid';
                            elseif ($order['payment_status'] === 'failed') $paymentClass = 'badge-failed';
                            elseif ($order['payment_status'] === 'cancelled') $paymentClass = 'badge-cancelled';
                            
                            echo '<span class="badge ' . $paymentClass . '">' . ucfirst($order['payment_status']) . '</span>';
                            
                            // Delivery status badge
                            $deliveryClass = 'badge-pending';
                            if ($order['delivery_status'] === 'delivered') $deliveryClass = 'badge-delivered';
                            elseif ($order['delivery_status'] === 'shipped') $deliveryClass = 'badge-shipped';
                            elseif ($order['delivery_status'] === 'processing') $deliveryClass = 'badge-processing';
                            elseif ($order['delivery_status'] === 'cancelled') $deliveryClass = 'badge-cancelled';
                            
                            echo '<span class="badge ' . $deliveryClass . '">' . ucfirst($order['delivery_status']) . '</span>';
                            ?>
                        </div>
                    </div>

                    <!-- Product Information -->
                    <div class="product-info">
                        <h5 style="margin: 0 0 10px 0; color: #430160;">
                            <?php echo htmlspecialchars($order['product_name']); ?>
                        </h5>
                        <div class="product-specs">
                            <div class="product-spec">
                                <strong>Fabric:</strong> <?php echo htmlspecialchars($order['fabric_type']); ?>
                            </div>
                            <div class="product-spec">
                                <strong>Size:</strong> <?php echo htmlspecialchars($order['size']); ?>
                            </div>
                            <div class="product-spec">
                                <strong>Quantity:</strong> <?php echo (int)$order['quantity']; ?>
                            </div>
                            <div class="product-spec">
                                <strong>Price per unit:</strong> Rs. <?php echo number_format((float)$order['unit_price'], 2); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details Grid -->
                    <div class="order-details">
                        <div class="detail-group">
                            <div class="detail-label">Total Amount</div>
                            <div class="total-amount">Rs. <?php echo number_format((float)$order['total_amount'], 2); ?></div>
                        </div>

                        <?php if ($order['payment_id']): ?>
                        <div class="detail-group">
                            <div class="detail-label">Payment ID</div>
                            <div class="detail-value">
                                <span class="payment-id"><?php echo htmlspecialchars($order['payment_id']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="detail-group">
                            <div class="detail-label">Delivery Address</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($order['delivery_address']); ?><br>
                                <?php echo htmlspecialchars($order['city']); ?>
                            </div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Contact Information</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                                <?php echo htmlspecialchars($order['customer_email']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Timeline -->
                    <?php if ($order['payment_status'] === 'paid'): ?>
                    <div class="order-timeline">
                        <strong>📦 Delivery Status:</strong>
                        <?php
                        switch($order['delivery_status']) {
                            case 'pending':
                                echo 'Your order has been received and is being processed.';
                                break;
                            case 'processing':
                                echo 'Your order is being prepared for shipment.';
                                break;
                            case 'shipped':
                                echo 'Your order has been shipped and is on the way! Expected delivery: 5-12 business days.';
                                break;
                            case 'delivered':
                                echo '✅ Your order has been delivered! Thank you for shopping with us.';
                                break;
                            case 'cancelled':
                                echo 'This order has been cancelled.';
                                break;
                            default:
                                echo 'Status: ' . htmlspecialchars($order['delivery_status']);
                        }
                        ?>
                    </div>
                    <?php elseif ($order['payment_status'] === 'pending'): ?>
                    <div class="order-timeline" style="background: #fee2e2; border-left-color: #ef4444;">
                        <strong>⚠️ Payment Pending:</strong> Your payment is still being processed. Please wait for confirmation.
                    </div>
                    <?php elseif ($order['payment_status'] === 'failed'): ?>
                    <div class="order-timeline" style="background: #fee2e2; border-left-color: #ef4444;">
                        <strong>❌ Payment Failed:</strong> Your payment was not successful. Please try again or contact support.
                    </div>
                    <?php endif; ?>

                    <!-- Last Updated -->
                    <?php if ($order['updated_at'] !== $order['created_at']): ?>
                    <div class="last-updated">
                        <small>
                            Last updated: <?php echo date('F j, Y g:i A', strtotime($order['updated_at'])); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

            <!-- Summary -->
            <div class="order-summary">
                <h5>📊 Total Orders: <?php echo $orderCount; ?></h5>
            </div>
        <?php endif; ?>

        <?php $stmt->close(); ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>

</body>
</html>