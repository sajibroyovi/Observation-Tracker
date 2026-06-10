<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';

$conn = getConnection();
$sql = "CREATE TABLE IF NOT EXISTS l1_instructions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instruction_text TEXT NOT NULL,
    created_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table created successfully.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>
