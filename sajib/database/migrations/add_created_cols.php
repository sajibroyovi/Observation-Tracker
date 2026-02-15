<?php
require_once __DIR__ . '/../../config/app.php';

$tables = [
    'observations', 'promo_banner', 'campaign', 'cr_list', 
    'enable_disable', 'service_outage', 'pending_mail', 
    'security_mail', 'ssl_certificate'
];

foreach ($tables as $table) {
    $sql = "ALTER TABLE $table 
            ADD COLUMN created_by VARCHAR(50) DEFAULT NULL AFTER serial_no,
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER created_by";
            
    // For tables that might not have serial_no as first column, we'll just add at the end or adjust.
    // Assuming serial_no exists or we just add it. 
    // Actually, usually serial_no is first.
    // Let's just ADD columns without AFTER to be safe, or check first.
    // Safe bet: ADD COLUMN created_by VARCHAR(50), ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP.
    
    $sql = "ALTER TABLE $table 
            ADD COLUMN created_by VARCHAR(50),
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";

    if (mysqli_query($conn, $sql)) {
        echo "Updated $table successfully.<br>";
    } else {
        log_error("Error updating $table in migration", ["error" => mysqli_error($conn)]);
        echo "Error updating $table. Check logs.<br>";
    }
}
?>
