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
    WHERE o.username = ? AND o.payment_status = 'paid' AND o.is_deleted = 0
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
        
        <?php
        // 1. Group results by Order ID
        $groupedOrders = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $oid = $row['order_id'];
                if (!isset($groupedOrders[$oid])) {
                    $groupedOrders[$oid] = [
                        'meta' => $row, // basic order details (date, status, payment_id etc)
                        'items' => []
                    ];
                }
                $groupedOrders[$oid]['items'][] = $row;
            }
        }
        ?>

        <div class="orders-list">
            <?php if (empty($groupedOrders)): ?>
                <div class="text-center p-5 bg-white rounded shadow-sm">
                    <h4 class="text-muted">No orders found.</h4>
                    <a href="index.php" class="btn btn-primary mt-3">Start Shopping</a>
                </div>
            <?php else: ?>
                <?php foreach($groupedOrders as $oid => $orderData): 
                    $meta = $orderData['meta'];
                    $items = $orderData['items'];
                    $totalItems = count($items);
                    
                    // Recalculate total from items just in case, or use meta's total (which is per row? wait, DB schema?)
                    // In payment-success, we insert 'total_amount' per item? No.
                    // create-order puts 'total_amount' per item. 
                    // So Order Total = Sum of Item Totals.
                    $orderTotal = 0;
                    foreach($items as $i) $orderTotal += $i['total_amount'];
                ?>
                    <div class="order-card p-4 mb-4 bg-white border rounded shadow-sm">
                        <!-- Order Header -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 flex-wrap gap-2">
                            <div>
                                <h5 class="mb-1" style="color:#430160; font-weight:700;">Order #<?php echo htmlspecialchars($oid); ?></h5>
                                <small class="text-muted">
                                    Placed on <?php echo date('M d, Y', strtotime($meta['created_at'])); ?> 
                                    <?php if($meta['payment_id']): ?> | Pay ID: <?php echo htmlspecialchars($meta['payment_id']); ?><?php endif; ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-1 text-success fw-bold">Rs. <?php echo number_format($orderTotal, 2); ?></h5>
                                <?php 
                                    $pStatus = $meta['payment_status'];
                                    $dStatus = $meta['delivery_status'];
                                    
                                    $pBadge = match($pStatus) { 'paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', default => 'secondary' };
                                    $dBadge = match($dStatus) { 'delivered' => 'success', 'shipped' => 'primary', 'processing' => 'info', 'cancelled' => 'danger', default => 'secondary' };
                                ?>
                                <span class="badge bg-<?php echo $pBadge; ?> mb-1"><?php echo ucfirst($pStatus); ?></span>
                                <span class="badge bg-<?php echo $dBadge; ?> mb-1"><?php echo ucfirst($dStatus); ?></span>
                            </div>
                        </div>

                        <!-- Order Items Table -->
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <thead class="text-muted" style="font-size:0.85rem; border-bottom:1px solid #eee;">
                                    <tr>
                                        <th>Item</th>
                                        <th>Details</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($items as $item): ?>
                                    <tr>
                                        <td class="align-middle py-3" style="width: 40%;">
                                            <div class="d-flex align-items-center gap-3">
                                                <!-- If you have images, showing them would be nice. Assuming placeholder for now. -->
                                                <!-- <div style="width:50px; height:50px; background:#eee; border-radius:4px;"></div> -->
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['product_code'] ?? ''); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle py-3">
                                            <small class="d-block text-muted">Fabric: <span class="text-dark fw-medium"><?php echo htmlspecialchars($item['fabric_type']); ?></span></small>
                                            <small class="d-block text-muted">Size: <span class="text-dark fw-medium"><?php echo htmlspecialchars($item['size'] ?: 'N/A'); ?></span></small>
                                        </td>
                                        <td class="align-middle text-center py-3">
                                            x<?php echo (int)$item['quantity']; ?>
                                        </td>
                                        <td class="align-middle text-end py-3 fw-bold text-dark">
                                            Rs. <?php echo number_format($item['total_amount'], 2); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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