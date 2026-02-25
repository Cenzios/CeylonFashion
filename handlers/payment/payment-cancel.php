<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>
<?php include 'includes/navbar.php'; ?>

<div style="max-width: 600px; margin: 100px auto; text-align: center; padding: 40px;">
    <div style="font-size: 80px; color: #ef4444;">✕</div>
    <h1 style="color: #ef4444; margin: 20px 0;">Payment Cancelled</h1>
    <p style="font-size: 18px; color: #666;">You cancelled the payment process.</p>
    <a href="index.php" style="display: inline-block; margin-top: 30px; padding: 12px 30px; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 8px;">Back to Shop</a>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>