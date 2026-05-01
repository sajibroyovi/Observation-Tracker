<?php
require_once __DIR__ . '/config/app.php';

$tables = [
    'enable_disable',
    'pending_mail',
    'security_mail',
    'cr_list',
    'promo_banner',
    'service_outage',
    'ssl_certificate',
    'campaign'
];

foreach ($tables as $table) {
    echo "Processing table: $table... ";
    
    // Check if columns exist
    $check_handed = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'handed_over_to'");
    if (mysqli_num_rows($check_handed) == 0) {
        $sql1 = "ALTER TABLE `$table` ADD COLUMN `handed_over_to` VARCHAR(50) DEFAULT NULL";
        if (mysqli_query($conn, $sql1)) {
            echo "added handed_over_to. ";
        } else {
            echo "error adding handed_over_to: " . mysqli_error($conn) . ". ";
        }
    } else {
        echo "handed_over_to exists. ";
    }

    $check_date = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE 'handover_date'");
    if (mysqli_num_rows($check_date) == 0) {
        $sql2 = "ALTER TABLE `$table` ADD COLUMN `handover_date` DATE DEFAULT NULL";
        if (mysqli_query($conn, $sql2)) {
            echo "added handover_date. ";
        } else {
            echo "error adding handover_date: " . mysqli_error($conn) . ". ";
        }
    } else {
        echo "handover_date exists. ";
    }
    
    echo "<br>";
}
?>
