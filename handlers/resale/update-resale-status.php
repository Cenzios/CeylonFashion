<?php
session_start();
include __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$resaleId = (int)$input['id'];
$status = $input['status'];

// Validate status
$allowedStatuses = ['available', 'sold'];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$userId = $_SESSION['user_id'];

// Initial Query to check ownership
$checkStmt = $mysqli->prepare("SELECT id FROM reseller_products WHERE id = ? AND user_id = ?");
$checkStmt->bind_param("ii", $resaleId, $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Item not found or access denied']);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Update status
$stmt = $mysqli->prepare("UPDATE reseller_products SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $resaleId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
?>
