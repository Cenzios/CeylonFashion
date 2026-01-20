<?php
require_once 'config.php';

echo "Database Fix Script Started...\n";
echo "Connected to: " . $db_name . "\n\n";

function runQuery($mysqli, $query, $description) {
    echo "Executing: $description...\n";
    try {
        if ($mysqli->query($query) === TRUE) {
            echo "✅ Success\n";
        } else {
            // Check if error is due to duplicate key or existing constraint, which is fine
            if (strpos($mysqli->error, 'Duplicate key') !== false || strpos($mysqli->error, 'already exists') !== false) {
                 echo "⚠️ Skipped (Already exists/Duplicate)\n";
            } else {
                 echo "❌ Error: " . $mysqli->error . "\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    echo "---------------------------------------------------\n";
}

// 1. Fix cart_items FK (carts -> cart)
// First, delete orphans to avoid error 1452
runQuery($mysqli, "DELETE FROM cart_items WHERE cart_id NOT IN (SELECT id FROM cart)", "Cleaning orphaned cart_items");
runQuery($mysqli, "ALTER TABLE cart_items DROP FOREIGN KEY IF EXISTS fk_cart_items_cart", "Dropping bad FK on cart_items");
// Note: Foreign key names must be unique in the database
runQuery($mysqli, "ALTER TABLE cart_items ADD CONSTRAINT fk_cart_items_cart_fixed FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE", "Adding correct FK to cart_items");

// 2. Link Reseller Products to Users
runQuery($mysqli, "DELETE FROM reseller_products WHERE user_id NOT IN (SELECT id FROM users)", "Cleaning orphaned reseller_products");
runQuery($mysqli, "ALTER TABLE reseller_products ADD CONSTRAINT fk_reseller_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE", "Adding FK for Reseller Products");

// 3. Link Orders to Products
// Some orders might reference deleted products. We'll set them to NULL or just skip adding constraint if they fail? 
// Safer: Delete constraint if exists, then try add. If fails, user knows.
// Actually, strict FK on orders might break historical data if products are hard-deleted.
// But valid E-commerce should soft-delete products. 
// Let's try to add it.
runQuery($mysqli, "ALTER TABLE orders ADD CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE NO ACTION", "Adding FK for Orders (Product)");

// 4. Link Reviews to Products
runQuery($mysqli, "DELETE FROM product_reviews WHERE product_id NOT IN (SELECT id FROM products)", "Cleaning orphaned reviews");
runQuery($mysqli, "ALTER TABLE product_reviews ADD CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE", "Adding FK for Reviews");

// 5. Link Questions to Products
runQuery($mysqli, "DELETE FROM product_questions WHERE product_id NOT IN (SELECT id FROM products)", "Cleaning orphaned questions");
runQuery($mysqli, "ALTER TABLE product_questions ADD CONSTRAINT fk_questions_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE", "Adding FK for Questions");

echo "\nDone!\n";
?>
