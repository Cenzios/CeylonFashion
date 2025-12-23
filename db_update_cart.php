<?php
require_once 'config.php';

// Add fabric_id if not exists
$check = $mysqli->query("SHOW COLUMNS FROM cart LIKE 'fabric_id'");
if ($check->num_rows == 0) {
    if ($mysqli->query("ALTER TABLE cart ADD COLUMN fabric_id INT NULL AFTER product_id")) {
        echo "Added fabric_id to cart.<br>";
    } else {
        echo "Error adding fabric_id: " . $mysqli->error . "<br>";
    }
} else {
    echo "fabric_id already exists.<br>";
}

// Add size if not exists
$check = $mysqli->query("SHOW COLUMNS FROM cart LIKE 'size'");
if ($check->num_rows == 0) {
    if ($mysqli->query("ALTER TABLE cart ADD COLUMN size VARCHAR(10) NULL AFTER fabric_id")) {
        echo "Added size to cart.<br>";
    } else {
        echo "Error adding size: " . $mysqli->error . "<br>";
    }
} else {
    echo "size already exists.<br>";
}

echo "Migration Complete.";
?>
