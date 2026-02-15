<?php
require_once __DIR__ . '/../../includes/connection_file.php';
$tables = [
    'observations', 'promo_banner', 'campaign', 'cr_list', 
    'enable_disable', 'service_outage', 'pending_mail', 
    'security_mail', 'ssl_certificate'
];

foreach ($tables as $table) {
    echo "Table: $table\n";
    $result = mysqli_query($conn, "DESCRIBE $table");
    while ($row = mysqli_fetch_assoc($result)) {
        if (in_array($row['Field'], ['created_by', 'created_at', 'time_stamp', 'submitted_by'])) {
            echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }
    echo "\n";
}
?>
