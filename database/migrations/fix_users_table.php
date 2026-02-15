<?php
// Database migration to fix users table
require_once __DIR__ . '/../../includes/connection_file.php';

echo "<h2>Fixing Users Table</h2>";

$table = 'users';

// Add allowed_modules column
$sql1 = "ALTER TABLE `$table` ADD COLUMN `allowed_modules` TEXT NULL";
if (mysqli_query($conn, $sql1)) {
    echo "✓ Added 'allowed_modules' column to $table<br>";
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate column') !== false) {
        echo "- 'allowed_modules' column already exists in $table<br>";
    } else {
        echo "✗ Error adding 'allowed_modules' to $table: $error<br>";
    }
}

// Add created_by column
$sql2 = "ALTER TABLE `$table` ADD COLUMN `created_by` VARCHAR(50) DEFAULT 'system'";
if (mysqli_query($conn, $sql2)) {
    echo "✓ Added 'created_by' column to $table<br>";
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate column') !== false) {
        echo "- 'created_by' column already exists in $table<br>";
    } else {
        echo "✗ Error adding 'created_by' to $table: $error<br>";
    }
}

mysqli_close($conn);
?>
