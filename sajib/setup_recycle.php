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

if (mysqli_query($conn, $sql)) {
    echo "Table created successfully.";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
