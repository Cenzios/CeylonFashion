<?php
require_once 'config.php';

// Create settings table
$sql = "CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($mysqli->query($sql) === TRUE) {
    echo "Table 'settings' created successfully or already exists.\n";
    
    // Insert default values if not exist
    $defaults = [
        'resale_percentage' => '60',
        'resale_period' => '3'
    ];
    
    $stmt = $mysqli->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    
    foreach ($defaults as $key => $val) {
        $stmt->bind_param("ss", $key, $val);
        $stmt->execute();
    }
    $stmt->close();
    echo "Default settings initialized.\n";
    
} else {
    echo "Error creating table: " . $mysqli->error . "\n";
}

$mysqli->close();
?>
