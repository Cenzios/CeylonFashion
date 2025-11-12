<?php
// admin/download-all-reports-pdf.php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
include_once '../config.php'; // adjust path if necessary

// Access control: only admin
$isAdmin = isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
if (!$isAdmin) {
    header('Location: ../index.php');
    exit;
}

// include FPDF
require_once __DIR__ . '/lib/fpdf.php'; // path to fpdf.php

// --- Fetch Order History (4 columns: Product ID, Product Name, Purchase Date, Status) ---
$orderHistorySQL = "SELECT 
    o.product_id,
    o.product_name,
    o.created_at as purchase_date,
    o.delivery_status as status
FROM orders o 
ORDER BY o.created_at DESC";
$orderHistoryResult = $mysqli->query($orderHistorySQL);

$totalOrders = 0;
$deliveredCount = 0;
$pendingCount = 0;

if ($orderHistoryResult && $orderHistoryResult->num_rows > 0) {
    $totalOrders = $orderHistoryResult->num_rows;
    $orderHistoryResult->data_seek(0);
    while($row = $orderHistoryResult->fetch_assoc()) {
        if ($row['status'] === 'delivered') $deliveredCount++;
        if ($row['status'] === 'pending') $pendingCount++;
    }
    $orderHistoryResult->data_seek(0);
}

// ---- Fetch Resale History (4 columns: Order ID, Item Name, Listed On, Status) ----
$resaleHistorySQL = "SELECT 
    r.id as order_id,
    r.product_id,
    r.created_at as listed_on,
    r.status
FROM reseller_products r
ORDER BY r.created_at DESC";
$resaleHistoryResult = $mysqli->query($resaleHistorySQL);

$totalResaleItems = 0;
$soldCount = 0;
$approvedCount = 0;
$pendingResaleCount = 0;

if ($resaleHistoryResult && $resaleHistoryResult->num_rows > 0) {
    $totalResaleItems = $resaleHistoryResult->num_rows;
    $resaleHistoryResult->data_seek(0);
    while($row = $resaleHistoryResult->fetch_assoc()) {
        if ($row['status'] === 'sold') $soldCount++;
        if ($row['status'] === 'approved') $approvedCount++;
        if ($row['status'] === 'pending') $pendingResaleCount++;
    }
    $resaleHistoryResult->data_seek(0);
}

// --- Prepare PDF with FPDF ---
class PDF_Report extends FPDF {
    // Simple header for each page
    function Header() {
        $this->SetFont('Arial','B',16);
        $this->SetTextColor(67,1,96);
        $this->Cell(0,10,'Ceylon Fashion - Complete Business Report',0,1,'C');
        $this->SetFont('Arial','',10);
        $this->SetTextColor(100);
        $this->Cell(0,6,'Generated on: '.date('F d, Y \a\t h:i A'),0,1,'C');
        $this->SetDrawColor(67,1,96);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY()+2, 200, $this->GetY()+2);
        $this->Ln(5);
    }

    // Footer with page number
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(120);
        $this->Cell(0,10,utf8_decode('Ceylon Fashion Admin Panel - Page '.$this->PageNo().'/{nb}'),0,0,'C');
    }
}

// Create PDF
$pdf = new PDF_Report('P','mm','A4');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// --- Summary Statistics ---
$pdf->SetFont('Arial','B',13);
$pdf->SetTextColor(67,1,96);
$pdf->Cell(0,8,'Summary Statistics',0,1);
$pdf->Ln(2);

$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0);
$lineHeight = 6;

// Orders Statistics
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Order History:',0,1);
$pdf->SetFont('Arial','',9);
$pdf->Cell(10,5,'',0,0); // indent
$pdf->Cell(60,$lineHeight,"Total Orders:",0,0);
$pdf->Cell(0,$lineHeight, $totalOrders,0,1);
$pdf->Cell(10,5,'',0,0);
$pdf->Cell(60,$lineHeight,"Delivered Orders:",0,0);
$pdf->Cell(0,$lineHeight, $deliveredCount,0,1);
$pdf->Cell(10,5,'',0,0);
$pdf->Cell(60,$lineHeight,"Pending Orders:",0,0);
$pdf->Cell(0,$lineHeight, $pendingCount,0,1);
$pdf->Ln(3);

// Resale Statistics
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Resale History:',0,1);
$pdf->SetFont('Arial','',9);
$pdf->Cell(10,5,'',0,0);
$pdf->Cell(60,$lineHeight,"Total Resale Items:",0,0);
$pdf->Cell(0,$lineHeight, $totalResaleItems,0,1);
$pdf->Cell(10,5,'',0,0);
$pdf->Cell(60,$lineHeight,"Sold Items:",0,0);
$pdf->Cell(0,$lineHeight, $soldCount,0,1);
$pdf->Cell(10,5,'',0,0);
$pdf->Cell(60,$lineHeight,"Approved Items:",0,0);
$pdf->Cell(0,$lineHeight, $approvedCount,0,1);
$pdf->Cell(10,5,'',0,0);
$pdf->Cell(60,$lineHeight,"Pending Approval:",0,0);
$pdf->Cell(0,$lineHeight, $pendingResaleCount,0,1);

$pdf->Ln(8);

// ------------------ Order History Table (4 columns) ------------------
$pdf->SetFont('Arial','B',13);
$pdf->SetFillColor(67,1,96);
$pdf->SetTextColor(255);
$pdf->Cell(0,8,'Order History',0,1,'L',true);
$pdf->Ln(2);

// Table Header
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(240,240,240);
$pdf->SetTextColor(0);
$w1 = [30, 80, 40, 40]; // Product ID, Product Name, Purchase Date, Status
$pdf->Cell($w1[0],8,'Product ID',1,0,'C',true);
$pdf->Cell($w1[1],8,'Product Name',1,0,'C',true);
$pdf->Cell($w1[2],8,'Purchase Date',1,0,'C',true);
$pdf->Cell($w1[3],8,'Status',1,1,'C',true);

// Table Data
$pdf->SetFont('Arial','',9);
if ($orderHistoryResult && $orderHistoryResult->num_rows > 0) {
    $orderHistoryResult->data_seek(0);
    while($order = $orderHistoryResult->fetch_assoc()) {
        // Check page break
        if ($pdf->GetY() > 260) {
            $pdf->AddPage();
            // Reprint header
            $pdf->SetFont('Arial','B',13);
            $pdf->SetFillColor(67,1,96);
            $pdf->SetTextColor(255);
            $pdf->Cell(0,8,'Order History (continued)',0,1,'L',true);
            $pdf->Ln(2);
            $pdf->SetFont('Arial','B',10);
            $pdf->SetFillColor(240,240,240);
            $pdf->SetTextColor(0);
            $pdf->Cell($w1[0],8,'Product ID',1,0,'C',true);
            $pdf->Cell($w1[1],8,'Product Name',1,0,'C',true);
            $pdf->Cell($w1[2],8,'Purchase Date',1,0,'C',true);
            $pdf->Cell($w1[3],8,'Status',1,1,'C',true);
            $pdf->SetFont('Arial','',9);
        }

        $productId = $order['product_id'] ?: 'N/A';
        $productName = $order['product_name'] ?: 'N/A';
        $purchaseDate = date('Y.m.d', strtotime($order['purchase_date']));
        $status = ucfirst($order['status']);

        // Truncate long product names
        if (strlen($productName) > 45) {
            $productName = substr($productName, 0, 42) . '...';
        }

        $pdf->Cell($w1[0],7, $productId,1,0,'C');
        $pdf->Cell($w1[1],7, utf8_decode($productName),1,0,'L');
        $pdf->Cell($w1[2],7, $purchaseDate,1,0,'C');
        $pdf->Cell($w1[3],7, $status,1,1,'C');
    }
} else {
    $pdf->Cell(array_sum($w1),7,'No orders found.',1,1,'C');
}

$pdf->Ln(10);

// ------------------ Resale History Table (4 columns) ------------------
$pdf->SetFont('Arial','B',13);
$pdf->SetFillColor(67,1,96);
$pdf->SetTextColor(255);
$pdf->Cell(0,8,'Resale History',0,1,'L',true);
$pdf->Ln(2);

// Table Header
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(240,240,240);
$pdf->SetTextColor(0);
$w2 = [30, 80, 40, 40]; // Order ID, Item Name, Listed On, Status
$pdf->Cell($w2[0],8,'Order ID',1,0,'C',true);
$pdf->Cell($w2[1],8,'Item Name',1,0,'C',true);
$pdf->Cell($w2[2],8,'Listed On',1,0,'C',true);
$pdf->Cell($w2[3],8,'Status',1,1,'C',true);

// Table Data
$pdf->SetFont('Arial','',9);
if ($resaleHistoryResult && $resaleHistoryResult->num_rows > 0) {
    $resaleHistoryResult->data_seek(0);
    while($resale = $resaleHistoryResult->fetch_assoc()) {
        // Check page break
        if ($pdf->GetY() > 260) {
            $pdf->AddPage();
            // Reprint header
            $pdf->SetFont('Arial','B',13);
            $pdf->SetFillColor(67,1,96);
            $pdf->SetTextColor(255);
            $pdf->Cell(0,8,'Resale History (continued)',0,1,'L',true);
            $pdf->Ln(2);
            $pdf->SetFont('Arial','B',10);
            $pdf->SetFillColor(240,240,240);
            $pdf->SetTextColor(0);
            $pdf->Cell($w2[0],8,'Order ID',1,0,'C',true);
            $pdf->Cell($w2[1],8,'Item Name',1,0,'C',true);
            $pdf->Cell($w2[2],8,'Listed On',1,0,'C',true);
            $pdf->Cell($w2[3],8,'Status',1,1,'C',true);
            $pdf->SetFont('Arial','',9);
        }

        $orderId = 'RSL' . str_pad($resale['order_id'], 4, '0', STR_PAD_LEFT);
        $itemName = 'Product #' . $resale['product_id'];
        $listedOn = date('Y.m.d', strtotime($resale['listed_on']));
        $status = ucfirst($resale['status']);

        $pdf->Cell($w2[0],7, $orderId,1,0,'C');
        $pdf->Cell($w2[1],7, $itemName,1,0,'L');
        $pdf->Cell($w2[2],7, $listedOn,1,0,'C');
        $pdf->Cell($w2[3],7, $status,1,1,'C');
    }
} else {
    $pdf->Cell(array_sum($w2),7,'No resale items found.',1,1,'C');
}

// Footer block
$pdf->Ln(10);
$pdf->SetFont('Arial','I',8);
$pdf->SetTextColor(100);
$pdf->Cell(0,6,utf8_decode('© '.date('Y').' Ceylon Fashion. All rights reserved.'),0,1,'C');

// Send file as download
$filename = 'Ceylon_Fashion_Complete_Report_'.date('Ymd_His').'.pdf';
$pdf->Output('D', $filename); // 'D' forces download
exit;
?>