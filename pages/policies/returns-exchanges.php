<?php
session_start();
include_once __DIR__ . '/../../config/config.php';
include_once __DIR__ . '/../../includes/head.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns & Exchanges - Ceylon Fashion</title>
</head>
<body>

<?php include_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="policy-section">
    <div class="container">
        <h1 class="page-title">Returns & Exchanges</h1>
        
        <div class="policy-content">
            <h3>1. Return Policy</h3>
            <p>We want you to be completely satisfied with your purchase. If you are not satisfied, you may return eligible items within 30 days of the delivery date for a refund or exchange.</p>

            <h3>2. Eligibility for Returns</h3>
            <p>To be eligible for a return, your item must be:</p>
            <ul>
                <li>Unused and in the same condition that you received it.</li>
                <li>In the original packaging with all tags attached.</li>
                <li>Accompanied by the receipt or proof of purchase.</li>
            </ul>
            <p><strong>Note:</strong> Custom-made items (Bridal Attire) and "Used Collection" items are final sale and cannot be returned unless defective.</p>

            <h3>3. Exchange Process</h3>
            <p>If you need to exchange an item for a different size or color, please contact our support team at support@ceylonfashion.lk. Once your return is received and inspected, we will send you an email to notify you that we have received your returned item and process the exchange.</p>

            <h3>4. Refunds</h3>
            <p>Once your return is received and inspected, we will notify you of the approval or rejection of your refund. If approved, your refund will be processed, and a credit will automatically be applied to your credit card or original method of payment within 7-10 business days.</p>

            <h3>5. Return Shipping</h3>
            <p>You will be responsible for paying for your own shipping costs for returning your item. Shipping costs are non-refundable. If you receive a refund, the cost of return shipping will be deducted from your refund.</p>
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

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
<?php include_once __DIR__ . '/../../includes/scripts.php'; ?>

</body>
</html>
