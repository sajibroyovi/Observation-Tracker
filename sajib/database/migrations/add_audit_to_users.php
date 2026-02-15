<?php
require_once __DIR__ . '/../../includes/connection_file.php';

echo "<h2>Adding Audit Columns to Users Table</h2>";

$table = 'users';

// Add edited_by column
$sql1 = "ALTER TABLE `$table` ADD COLUMN `edited_by` VARCHAR(50) NULL";
if (mysqli_query($conn, $sql1)) {
    echo "✓ Added 'edited_by' column to $table<br>";
} else {
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate column') !== false) {
        echo "- 'edited_by' column already exists in $table<br>";
    } else {
        echo "✗ Error adding 'edited_by' to $table: $error<br>";
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
        echo "✗ Error adding 'edited_at' to $table: $error<br>";
    }
}

mysqli_close($conn);
?>
