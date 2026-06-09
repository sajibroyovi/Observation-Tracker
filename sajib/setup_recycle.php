<?php
require_once 'c:/xampp/htdocs/sajib/sajib/config/app.php';

$sql = "
CREATE TABLE IF NOT EXISTS recycle_bin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_name VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    original_id INT NOT NULL,
    data_payload LONGTEXT NOT NULL,
    deleted_by VARCHAR(100) NOT NULL,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$conn = getConnection();

if (mysqli_query($conn, $sql)) {
    echo "Table created successfully.";
} else {
    // Prevent Information Exposure by using a generic error message and logging the actual error
    log_error("Error creating recycle_bin table", ['error' => mysqli_error($conn)]);
    echo "Error creating table. Please check system logs.";
}

mysqli_close($conn);
?>
