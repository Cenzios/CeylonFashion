<?php
include 'config.php';

try {
    // Check if columns exist first to avoid duplicate errors
    $check = $mysqli->query("SHOW COLUMNS FROM products LIKE 'created_at'");
    if ($check->num_rows == 0) {
        $mysqli->query("ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Added created_at column.<br>";
    } else {
        echo "ℹ️ created_at already exists.<br>";
    }

    $check = $mysqli->query("SHOW COLUMNS FROM products LIKE 'updated_at'");
    if ($check->num_rows == 0) {
        $mysqli->query("ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "✅ Added updated_at column.<br>";
    } else {
        echo "ℹ️ updated_at already exists.<br>";
    }

    echo "🎉 Database update complete!";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
