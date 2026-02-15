<?php
require_once __DIR__ . '/../../includes/connection_file.php';

echo "<h2>Updating Security Role Enum</h2>";

// 1. Update the schema to include l1 and l2
$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'l1', 'l2') NOT NULL";

if (mysqli_query($conn, $sql)) {
    echo "✓ Successfully updated role ENUM to include 'l1' and 'l2'.<br>";
} else {
    echo "✗ Error updating role ENUM: " . mysqli_error($conn) . "<br>";
}

// 2. Data repair - based on user feedback/context
// Simran_5187 and Rafia have empty roles because of the previous restriction.
// I will set them to 'l1' as they are likely operators, but this can be changed in the UI.
$repair_sql = "UPDATE users SET role = 'l1' WHERE (username = 'Simran_5187' OR username = 'Rafia') AND (role = '' OR role IS NULL)";

if (mysqli_query($conn, $repair_sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "✓ Successfully repaired $affected user records.<br>";
} else {
    echo "✗ Error repairing users: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
?>