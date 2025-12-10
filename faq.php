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
    <title>FAQ - Ceylon Fashion</title>
</head>
<body>

<?php include_once 'includes/navbar.php'; ?>

<div class="policy-section">
    <div class="container">
        <h1 class="page-title">Frequently Asked Questions</h1>
        
        <div class="policy-content">
            <div class="faq-item">
                <h3>How do I place an order?</h3>
                <p>Simply browse our website, add items to your cart, and proceed to checkout. You can checkout as a guest or create an account for faster future purchases.</p>
            </div>

            <div class="faq-item">
                <h3>What payment methods do you accept?</h3>
                <p>We accept Visa, MasterCard, American Express, and cash on delivery (for select locations).</p>
            </div>

            <div class="faq-item">
                <h3>Can I cancel or modify my order?</h3>
                <p>Orders can be modified or cancelled within 24 hours of placement. Please contact our support team immediately if you need to make changes.</p>
            </div>

            <div class="faq-item">
                <h3>Do you offer international shipping?</h3>
                <p>Yes, we ship internationally. Shipping costs and delivery times vary by location.</p>
            </div>

            <div class="faq-item">
                <h3>How can I track my order?</h3>
                <p>Once your order ships, you will receive an email with a tracking number. You can use this number to track your package on our carrier's website.</p>
            </div>

            <div class="faq-item">
                <h3>What if I receive a damaged item?</h3>
                <p>Please contact us within 48 hours of receiving your order with photos of the damaged item. We will arrange for a replacement or refund.</p>
            </div>
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
    .faq-item {
        margin-bottom: 25px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }
    .faq-item:last-child {
        border-bottom: none;
    }
    .policy-content h3 {
        font-size: 18px;
        font-weight: 600;
        color: #4b0082;
        margin-bottom: 10px;
    }
    .policy-content p {
        font-size: 16px;
        line-height: 1.6;
        color: #555;
        margin: 0;
    }
</style>

<?php include_once 'includes/footer.php'; ?>
<?php include_once 'includes/scripts.php'; ?>

</body>
</html>
