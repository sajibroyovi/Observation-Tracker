<?php
// Database setup script for observations table
require_once __DIR__ . '/../../config/app.php';

// Create observations table
$create_table = "CREATE TABLE IF NOT EXISTS observations (
    serial_no INT AUTO_INCREMENT PRIMARY KEY,
    observation_names TEXT NOT NULL,
    team_name TEXT NOT NULL,
    start_date DATETIME NOT NULL,
    l1_observation TEXT NOT NULL,
    l1_image VARCHAR(255) DEFAULT NULL,
    l1_image_2 VARCHAR(255) DEFAULT NULL,
    l1_observations_by VARCHAR(50) DEFAULT NULL,
    l2_observation TEXT DEFAULT NULL,
    l2_observations_by VARCHAR(50) DEFAULT NULL,
    created_by VARCHAR(50) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edited_by VARCHAR(50) DEFAULT NULL,
    edited_at TIMESTAMP NULL DEFAULT NULL
)";

if (mysqli_query($conn, $create_table)) {
    echo "Observations table created or already exists successfully.<br>";
} else {
    log_error("Error creating observations table", ["error" => mysqli_error($conn)]);
    echo "Error creating table. Check logs.<br>";
}

mysqli_close($conn);
?>
