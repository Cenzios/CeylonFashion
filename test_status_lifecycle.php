<?php
include 'config.php';

// 1. Create a dummy reseller product
$uid = 1; // Assuming user ID 1 exists
$pcode = 'TEST001';
$sql = "INSERT INTO reseller_products (product_id, product_code, user_id, first_name, status, created_at) 
        VALUES (1, '$pcode', $uid, 'Test', 'approved', NOW())";
if (!$mysqli->query($sql)) {
    die("Insert failed: " . $mysqli->error);
}
$id = $mysqli->insert_id;
echo "Inserted ID: $id, Status: " . getStatus($id) . "\n";

// 2. Update to 'sold'
updateStatus($id, 'sold');
echo "Updated to 'sold', Status: " . getStatus($id) . "\n";

// 3. Update to 'available'
updateStatus($id, 'available');
echo "Updated to 'available', Status: " . getStatus($id) . "\n";

// Cleanup
$mysqli->query("DELETE FROM reseller_products WHERE id = $id");

function getStatus($id) {
    global $mysqli;
    $res = $mysqli->query("SELECT status FROM reseller_products WHERE id = $id");
    $row = $res->fetch_assoc();
    return "'" . $row['status'] . "'";
}

function updateStatus($id, $status) {
    global $mysqli;
    // Simulate update-resale-status.php logic
    $stmt = $mysqli->prepare("UPDATE reseller_products SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
}
?>
