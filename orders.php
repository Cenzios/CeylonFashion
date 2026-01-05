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
        o.id, o.order_id, o.product_name, o.fabric_type, o.size, o.quantity,
        o.unit_price, o.total_amount, o.payment_status, o.payment_id, o.delivery_status,
        o.customer_name, o.customer_email, o.customer_phone, o.delivery_address, o.city,
        o.created_at, o.updated_at,
        p.product_code
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.username = ? 
    ORDER BY o.created_at DESC
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

<?php
    // Fetch Resale Items
    $resaleStmt = $mysqli->prepare("
        SELECT 
            rp.id, rp.product_id, rp.created_at, rp.status,
            p.product_name, p.product_code
        FROM reseller_products rp
        LEFT JOIN products p ON rp.product_id = p.id
        WHERE rp.user_id = ?
        ORDER BY rp.created_at DESC
    ");
    $resaleStmt->bind_param("i", $_SESSION['user_id']);
    $resaleStmt->execute();
    $resaleResult = $resaleStmt->get_result();
?>

    <div class="orders-container">
        
        <!-- Section 1: Order History -->
        <h2 style="font-weight:700; color:#333; margin-bottom:20px;">Order History</h2>
        
        <div class="table-responsive">
            <table class="table table-bordered align-middle" style="background:#fff;">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th style="width:15%; color:#555; font-weight:600;">Product Code</th>
                        <th style="width:40%; color:#555; font-weight:600;">Item Name</th>
                        <th style="width:20%; color:#555; font-weight:600;">Purchase Date</th>
                        <th style="width:25%; color:#555; font-weight:600;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($order = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="color:#555;"><?php echo htmlspecialchars($order['product_code']); ?></td>
                                <td style="color:#555;"><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td style="color:#555;"><?php echo date('Y.m.d', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <?php 
                                        // Map status to simpler text or keep as is
                                        $statusText = 'Processing';
                                        if ($order['delivery_status'] == 'delivered') $statusText = 'Delivered';
                                        elseif ($order['delivery_status'] == 'shipped') $statusText = 'Shipped';
                                        elseif ($order['delivery_status'] == 'cancelled') $statusText = 'Cancelled';
                                        elseif ($order['payment_status'] == 'paid' && $order['delivery_status'] == 'pending') $statusText = 'Ready to Deliver'; 
                                        
                                        echo htmlspecialchars($statusText);
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="border-top: 3px solid #ff0000; margin: 40px 0 20px 0;"></div>

        <!-- Section 2: Resale History -->
        <h2 style="font-weight:700; color:#333; margin-bottom:5px;">Resale History</h2>
        <p style="color:#666; margin-bottom:20px;">Sold your item? Great! Mark it as sold to keep your listings up to date."</p>
        
        <div class="table-responsive">
            <table class="table table-bordered align-middle" style="background:#fff;">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th style="width:15%; color:#555; font-weight:600;">Product Code</th>
                        <th style="width:40%; color:#555; font-weight:600;">Item Name</th>
                        <th style="width:20%; color:#555; font-weight:600;">Listed on</th>
                        <th style="width:25%; color:#555; font-weight:600;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resaleResult->num_rows > 0): ?>
                        <?php while($resale = $resaleResult->fetch_assoc()): ?>
                            <tr>
                                <td style="color:#555;">
                                    <?php 
                                        // Resale tracking ID or Product Code
                                        echo htmlspecialchars($resale['product_code'] ?? 'N/A'); 
                                    ?>
                                </td>
                                <td style="color:#555;"><?php echo htmlspecialchars($resale['product_name']); ?></td>
                                <td style="color:#555;"><?php echo date('Y.m.d', strtotime($resale['created_at'])); ?></td>
                                <td>
                                    <select class="form-select form-select-sm" 
                                            style="border-radius:4px; border-color:#ccc; color:#555;"
                                            onchange="updateResaleStatus(this, <?php echo $resale['id']; ?>)">
                                        <option value="available" <?php echo ($resale['status'] == 'available' || $resale['status'] == 'approved') ? 'selected' : ''; ?>>Available</option>
                                        <option value="sold" <?php echo ($resale['status'] == 'sold') ? 'selected' : ''; ?>>Sold Out</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">No resale items found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        $stmt->close(); 
        $resaleStmt->close();
        ?>
    </div>

    <!-- Script to handle status update -->
    <script>
    function updateResaleStatus(selectEl, resaleId) {
        const status = selectEl.value;
        const originalStatus = status === 'sold' ? 'available' : 'sold'; // fallback

        // Disable while updating
        selectEl.disabled = true;

        fetch('update-resale-status.php', {
            method: 'POST',
            body: JSON.stringify({id: resaleId, status: status}),
            headers: {'Content-Type': 'application/json'}
        })
        .then(response => response.json())
        .then(data => {
            selectEl.disabled = false;
            if (data.success) {
                // Optional: Show a toast or small success indicator
                // alert('Status updated');
                selectEl.style.borderColor = status === 'sold' ? '#ff0000' : '#ccc';
            } else {
                alert('Failed to update status: ' + (data.message || 'Unknown error'));
                // Revert
                selectEl.value = originalStatus;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            selectEl.disabled = false;
            selectEl.value = originalStatus;
            alert('An error occurred. Please try again.');
        });
    }
    </script>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>

</body>
</html>