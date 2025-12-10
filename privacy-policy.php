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
    <title>Privacy Policy - Ceylon Fashion</title>
</head>
<body>

<?php include_once 'includes/navbar.php'; ?>

<div class="policy-section">
    <div class="container">
        <h1 class="page-title">Privacy Policy</h1>
        
        <div class="policy-content">
            <h3>1. Information We Collect</h3>
            <p>We collect information from you when you register on our site, place an order, subscribe to our newsletter or fill out a form. When ordering or registering on our site, as appropriate, you may be asked to enter your: name, e-mail address, mailing address, phone number or credit card information.</p>

            <h3>2. How We Use Your Information</h3>
            <p>Any of the information we collect from you may be used in one of the following ways:</p>
            <ul>
                <li>To personalize your experience (your information helps us to better respond to your individual needs)</li>
                <li>To improve our website (we continually strive to improve our website offerings based on the information and feedback we receive from you)</li>
                <li>To improve customer service (your information helps us to more effectively respond to your customer service requests and support needs)</li>
                <li>To process transactions</li>
                <li>To send periodic emails</li>
            </ul>

            <h3>3. How We Protect Your Information</h3>
            <p>We implement a variety of security measures to maintain the safety of your personal information when you place an order or enter, submit, or access your personal information.</p>

            <h3>4. Cookies</h3>
            <p>We use cookies to help us remember and process the items in your shopping cart, understand and save your preferences for future visits and compile aggregate data about site traffic and site interaction so that we can offer better site experiences and tools in the future.</p>

            <h3>5. Third Party Links</h3>
            <p>Occasionally, at our discretion, we may include or offer third party products or services on our website. These third party sites have separate and independent privacy policies. We therefore have no responsibility or liability for the content and activities of these linked sites.</p>
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
