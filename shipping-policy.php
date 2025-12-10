<?php
session_start();
include_once 'config.php';
include_once 'includes/head.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Policy - Ceylon Fashion</title>
</head>
<body>

<?php include_once 'includes/navbar.php'; ?>

<div class="policy-section">
    <div class="container">
        <h1 class="page-title">Shipping Policy</h1>
        
        <div class="policy-content">
            <h3>1. Order Processing</h3>
            <p>All orders are processed within 1-2 business days. Orders are not shipped or delivered on weekends or holidays. If we are experiencing a high volume of orders, shipments may be delayed by a few days. Please allow additional days in transit for delivery.</p>

            <h3>2. Shipping Rates & Delivery Estimates</h3>
            <p>Shipping charges for your order will be calculated and displayed at checkout. We offer the following shipping methods:</p>
            <ul>
                <li><strong>Standard Shipping:</strong> 3-5 business days - Rs. 350</li>
                <li><strong>Express Shipping:</strong> 1-2 business days - Rs. 650</li>
                <li><strong>Free Shipping:</strong> On orders over Rs. 15,000</li>
            </ul>

            <h3>3. Shipment Confirmation & Order Tracking</h3>
            <p>You will receive a Shipment Confirmation email once your order has shipped containing your tracking number(s). The tracking number will be active within 24 hours.</p>

            <h3>4. Customs, Duties and Taxes</h3>
            <p>Ceylon Fashion is not responsible for any customs and taxes applied to your order. All fees imposed during or after shipping are the responsibility of the customer (tariffs, taxes, etc.).</p>

            <h3>5. Damages</h3>
            <p>Ceylon Fashion is not liable for any products damaged or lost during shipping. If you received your order damaged, please contact the shipment carrier to file a claim. Please save all packaging materials and damaged goods before filing a claim.</p>
        </div>
    </div>
</div>

<style>
    .policy-section {
        padding: 60px 20px;
        background: #f9fafb;
        min-height: 60vh;
    }
    .container {
        max-width: 1000px;
        margin: 0 auto;
        background: #fff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 30px;
        text-align: center;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 20px;
    }
    .policy-content h3 {
        font-size: 20px;
        font-weight: 600;
        color: #4b0082;
        margin-top: 30px;
        margin-bottom: 15px;
    }
    .policy-content p, .policy-content li {
        font-size: 16px;
        line-height: 1.6;
        color: #555;
        margin-bottom: 15px;
    }
    .policy-content ul {
        padding-left: 20px;
        margin-bottom: 20px;
    }
</style>

<?php include_once 'includes/footer.php'; ?>
<?php include_once 'includes/scripts.php'; ?>

</body>
</html>
