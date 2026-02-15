<?php
// Database setup script for users table
require_once __DIR__ . '/../../config/app.php';

// Create users table
$create_table = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'l1', 'l2') NOT NULL,
    allowed_modules TEXT DEFAULT NULL,
    created_by VARCHAR(50) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ";

if (mysqli_query($conn, $create_table)) {
    echo "Users table created successfully.<br>";
} else {
    log_error("Error creating users table", ["error" => mysqli_error($conn)]);
    echo "Error creating table. Check logs.<br>";
}

// Hash passwords
$superadmin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);

// Insert sample users
$all_modules = 'Enable/Disable,Pending Mail,Security Mail,CR List,Promo Banner,Service Outage,SSL Certificate,Campaign,Observations';
$insert_users = "INSERT INTO users (username, password, role, allowed_modules, created_by) VALUES 
    ('superadmin', '$superadmin_password', 'super_admin', '$all_modules', 'system'),
    ('admin', '$admin_password', 'admin', '$all_modules', 'system')
ON DUPLICATE KEY UPDATE password=VALUES(password), allowed_modules=VALUES(allowed_modules)";

if (mysqli_query($conn, $insert_users)) {
    echo "Sample users created successfully.<br>";
    echo "Super Admin - Username: superadmin, Password: admin123<br>";
    echo "Admin - Username: admin, Password: admin123<br>";
} else {
    log_error("Error inserting sample users", ["error" => mysqli_error($conn)]);
    echo "Error inserting users. Check logs.<br>";
}

mysqli_close($conn);
