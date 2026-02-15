<?php
// Database migration script to add audit trail columns
require_once __DIR__ . '/../../includes/connection_file.php';

echo "<h2>Adding Audit Trail Columns</h2>";

// List of all tables that need audit columns
$tables = [
    'observations',
    'promo_banner',
    'campaign',
    'cr_list',
    'enable_disable',
    'service_outage',
    'pending_mail',
    'security_mail',
    'ssl_certificate'
];

foreach ($tables as $table) {
    echo "<h3>Updating table: $table</h3>";

    // Add edited_by column
    $sql1 = "ALTER TABLE `$table` ADD COLUMN `edited_by` VARCHAR(50) NULL";
    if (mysqli_query($conn, $sql1)) {
        echo "✓ Added 'edited_by' column to $table<br>";
    } else {
        $error = mysqli_error($conn);
        if (strpos($error, 'Duplicate column') !== false) {
            echo "- 'edited_by' column already exists in $table<br>";
        } else {
            log_error("Error adding 'edited_by' to $table", ["error" => $error]);
            echo "✗ Operation failed for $table. Check logs.<br>";
        }
    }

    // Add edited_at column
    $sql2 = "ALTER TABLE `$table` ADD COLUMN `edited_at` TIMESTAMP NULL";
    if (mysqli_query($conn, $sql2)) {
        echo "✓ Added 'edited_at' column to $table<br>";
    } else {
        $error = mysqli_error($conn);
        if (strpos($error, 'Duplicate column') !== false) {
            echo "- 'edited_at' column already exists in $table<br>";
        } else {
            log_error("Error adding 'edited_at' to $table", ["error" => $error]);
            echo "✗ Operation failed for $table. Check logs.<br>";
        }
    }

    echo "<br>";
}

echo "<h3>Migration Complete!</h3>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";

mysqli_close($conn);
