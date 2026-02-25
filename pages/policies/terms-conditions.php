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
    <title>Terms & Conditions - Ceylon Fashion</title>
</head>
<body>

<?php include_once __DIR__ . '/../../includes/navbar.php'; ?>

<div class="policy-section">
    <div class="container">
        <h1 class="page-title">Terms & Conditions</h1>
        
        <div class="policy-content">
            <h3>1. Introduction</h3>
            <p>Welcome to Ceylon Fashion. These terms and conditions outline the rules and regulations for the use of Ceylon Fashion's Website.</p>

            <h3>2. Intellectual Property Rights</h3>
            <p>Other than the content you own, under these Terms, Ceylon Fashion and/or its licensors own all the intellectual property rights and materials contained in this Website.</p>

            <h3>3. Restrictions</h3>
            <p>You are specifically restricted from all of the following:</p>
            <ul>
                <li>Publishing any Website material in any other media;</li>
                <li>Selling, sublicensing and/or otherwise commercializing any Website material;</li>
                <li>Publicly performing and/or showing any Website material;</li>
                <li>Using this Website in any way that is or may be damaging to this Website;</li>
                <li>Using this Website in any way that impacts user access to this Website;</li>
            </ul>

            <h3>4. Limitation of Liability</h3>
            <p>In no event shall Ceylon Fashion, nor any of its officers, directors and employees, be held liable for anything arising out of or in any way connected with your use of this Website whether such liability is under contract.  Ceylon Fashion, including its officers, directors and employees shall not be held liable for any indirect, consequential or special liability arising out of or in any way related to your use of this Website.</p>

            <h3>5. Governing Law & Jurisdiction</h3>
            <p>These Terms will be governed by and interpreted in accordance with the laws of Sri Lanka, and you submit to the non-exclusive jurisdiction of the state and federal courts located in Sri Lanka for the resolution of any disputes.</p>
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
