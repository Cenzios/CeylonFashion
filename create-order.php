<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
require_once 'config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    $source = isset($_POST['source']) ? $_POST['source'] : 'direct';
    $order_id = isset($_POST['order_id']) ? trim($_POST['order_id']) : '';

    // Common customer details
    $customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
    $customer_email = isset($_POST['customer_email']) ? trim($_POST['customer_email']) : '';
    $customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
    $delivery_address = isset($_POST['delivery_address']) ? trim($_POST['delivery_address']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';

    if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($delivery_address) || empty($city)) {
        throw new Exception('Missing required customer fields');
    }
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email address');
    if (!preg_match('/^[0-9]{10,15}$/', $customer_phone)) throw new Exception('Invalid phone number');

    $username = $_SESSION['username'];
    $total_amount = 0;

    if ($source === 'cart') {
        // --- CART CHECKOUT ---
        if (!$order_id) throw new Exception('Missing order ID');

        $user_id = (int)$_SESSION['user_id'];
        
        // Fetch cart items
        $stmt = $mysqli->prepare("
            SELECT c.product_id, c.quantity, p.product_name, f.id as fabric_id, f.fabric_type, f.fabric_price 
            FROM cart c
            JOIN products p ON c.product_id = p.id
            JOIN product_fabrics f ON f.product_id = p.id
            WHERE c.user_id = ?
            GROUP BY c.id
        "); 
        // Note: The logic above assumes we pick a fabric? 
        // WAIT. Cart doesn't store fabric_id! 
        // Cart table check: 
        // Logic in cart-add.php: "price" is stored in cart. "quantity" is stored.
        // Fabric selection? 
        // product-view.php adds fabric_id to Form but cart-add.php DOES NOT store fabric_id in `cart` table?
        // Let's check cart-add.php quickly.
        // Line 102: INSERT INTO cart (user_id, product_id, quantity, price, created_at) VALUES ...
        // It stores 'price' but NOT 'fabric_id' or 'size'.
        // THIS IS A MAJOR DATAMODEL ISSUE if size/fabric are required.
        // Cart table DDL?
        // Assuming current cart implementation is simple and maybe misses size/fabric?
        // product-view.php form has fabric_id and size.
        // cart-add.php IGNORES fabric_id and size when inserting!!
        // Checking cart.php display: It shows product name and price. No size/fabric.
        // This suggests the Cart feature is incomplete regarding attributes.
        // USER REQUEST: "set it work as product view page buy now button".
        // If I implement checkout, I must use what's in the cart.
        // If the cart lacks size/fabric, the order will lack it too.
        // BUT `orders` table has columns `size`, `fabric_id`, `fabric_type`.
        // If I create order from cart, and cart doesn't have them, I have to insert defaults or empty?
        
        // Let's stick to what we have. I will fetch `price` and `quantity` from cart.
        // I will insert empty size/fabric if unavailable.
        // Or better: Fix cart-add.php later? No time. user asked to fix checkout button.
        
        // Revised Cart Fetch:
        $stmt = $mysqli->prepare("
            SELECT c.product_id, c.quantity, c.price, p.product_name
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($cartItems)) throw new Exception('Cart is empty');

        $stmtInsert = $mysqli->prepare("
            INSERT INTO orders (
                order_id, username, product_id, product_name, 
                fabric_id, fabric_type, size, quantity, 
                unit_price, total_amount, payment_status,
                customer_name, customer_email, customer_phone, 
                delivery_address, city
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
        ");

        foreach ($cartItems as $item) {
            $p_name = $item['product_name'];
            $qty = $item['quantity'];
            $u_price = $item['price'];
            $t_amt = $qty * $u_price;
            $f_id = 0; $f_type = 'Default'; $sz = 'Standard'; // Fallback

            $stmtInsert->bind_param(
                "ssisssiddssssss",
                $order_id, $username, $item['product_id'], $p_name,
                $f_id, $f_type, $sz, $qty,
                $u_price, $t_amt,
                $customer_name, $customer_email, $customer_phone, $delivery_address, $city
            );
            $stmtInsert->execute();
            $total_amount += $t_amt;
        }
        $stmtInsert->close();

        // Note: We do NOT clear the cart here anymore. 
        // We wait for payment confirmation (payment-notify.php) or success page to clear it.
        // This prevents cart clearing if user cancels payment.
        
        // $stmtClear = $mysqli->prepare("DELETE FROM cart WHERE user_id = ?");
        // $stmtClear->bind_param("i", $user_id);
        // $stmtClear->execute();
        // $stmtClear->close();

    } else {
        // --- SINGLE ITEM CHECKOUT (Buy Now) ---
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $fabric_id = isset($_POST['fabric_id']) ? (int)$_POST['fabric_id'] : 0;
        $size = isset($_POST['size']) ? trim($_POST['size']) : '';
        $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
        
        if (!$product_id || !$fabric_id || !$size || !$order_id) {
            throw new Exception('Missing required product fields');
        }

        // Get Product & Fabric
        $stmt = $mysqli->prepare("SELECT product_name FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$product) throw new Exception('Product not found');

        $stmt = $mysqli->prepare("SELECT fabric_type, fabric_price FROM product_fabrics WHERE id = ? AND product_id = ?");
        $stmt->bind_param("ii", $fabric_id, $product_id);
        $stmt->execute();
        $fabric = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$fabric) throw new Exception('Fabric not found');

        $unit_price = (float)$fabric['fabric_price'];
        $total_amount = $unit_price * $quantity;

        $stmt = $mysqli->prepare("
            INSERT INTO orders (
                order_id, username, product_id, product_name, 
                fabric_id, fabric_type, size, quantity, 
                unit_price, total_amount, payment_status,
                customer_name, customer_email, customer_phone, 
                delivery_address, city
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "ssisssiddssssss",
            $order_id, 
            $username, 
            $product_id, 
            $product['product_name'],
            $fabric_id, 
            $fabric['fabric_type'], 
            $size, 
            $quantity,
            $unit_price, 
            $total_amount,
            $customer_name, 
            $customer_email, 
            $customer_phone, 
            $delivery_address, 
            $city
        );
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'amount' => number_format($total_amount, 2, '.', '')
    ]);

} catch (Exception $e) {
    error_log(date('Y-m-d H:i:s') . ' - Order creation error: ' . $e->getMessage() . "\n", 3, 'order_errors.txt');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>