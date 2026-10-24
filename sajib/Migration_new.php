<?php
/**
 * Database Migration
 * ------------------
 * - Creates recycle_bin table
 * - Creates l1_instructions table
 * - Adds handover columns to existing tables
 */

$appConfigPath = __DIR__ . '/config/app.php';
if (!file_exists($appConfigPath)) {
    $appConfigPath = __DIR__ . '/config/config/app.php';
}

if (!file_exists($appConfigPath)) {
    throw new RuntimeException("Migration configuration file not found: $appConfigPath");
}

require_once $appConfigPath;

// Prevent access by unauthorized users (if run in browser)
if (php_sapi_name() !== 'cli') {
    include_once INCLUDES_PATH . '/auth_check.php';
    if (!isSuperAdmin()) {
        http_response_code(403);
        die("Unauthorized: Super Admin privilege required.");
    }
}

$conn = getConnection();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * ----------------------------------------------------
 * Helper Functions
 * ----------------------------------------------------
 */

if (!function_exists('tableExists')) {
    function tableExists($conn, $table)
    {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        return mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column)
    {
        $result = mysqli_query(
            $conn,
            "SHOW COLUMNS FROM `$table` LIKE '$column'"
        );

        return mysqli_num_rows($result) > 0;
    }
}

if (!function_exists('addColumnIfMissing')) {
    function addColumnIfMissing($conn, $table, $column, $definition)
    {
        if (!columnExists($conn, $table, $column)) {

            mysqli_query(
                $conn,
                "ALTER TABLE `$table`
                 ADD COLUMN `$column` $definition"
            );

            echo "✔ Added $column to $table<br>";
        } else {
            echo "✓ $table.$column already exists<br>";
        }
    }
}

try {

    mysqli_begin_transaction($conn);

    /**
     * ----------------------------------------------------
     * 1. Create recycle_bin
     * ----------------------------------------------------
     */

    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS recycle_bin (

            id INT AUTO_INCREMENT PRIMARY KEY,

            module_name VARCHAR(100) NOT NULL,

            table_name VARCHAR(100) NOT NULL,

            original_id INT NOT NULL,

            data_payload LONGTEXT NOT NULL,

            deleted_by VARCHAR(100) NOT NULL,

            deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP

        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci
    ");

    echo "✔ recycle_bin checked<br>";

    /**
     * ----------------------------------------------------
     * 2. Create l1_instructions
     * ----------------------------------------------------
     */

    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS l1_instructions (

            id INT AUTO_INCREMENT PRIMARY KEY,

            instruction_text TEXT NOT NULL,

            created_by VARCHAR(50),

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_by	varchar(100),

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

        )
    ");

    echo "✔ l1_instructions checked<br>";

    /**
     * ----------------------------------------------------
     * 3. Add handover columns
     * ----------------------------------------------------
     */

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

        if (!tableExists($conn, $table)) {
            echo "⚠ Table '$table' not found. Skipped.<br>";
            continue;
        }

        addColumnIfMissing(
            $conn,
            $table,
            'handed_over_to',
            'VARCHAR(50) DEFAULT NULL'
        );

        addColumnIfMissing(
            $conn,
            $table,
            'handover_date',
            'DATE DEFAULT NULL'
        );

        echo "<hr>";
    }

    /**
     * ----------------------------------------------------
     * 4. Add technician_name to observation table
     * ----------------------------------------------------
     */

    if (tableExists($conn, 'observations')) {
        addColumnIfMissing(
            $conn,
            'observations',
            'technician_name',
            'VARCHAR(100) DEFAULT NULL'
        );
    } else {
        echo "⚠ Table 'observations' not found. Skipped.<br>";
    }

    /**
     * ----------------------------------------------------
     * 5. Add email and phone_number to users table
     * ----------------------------------------------------
     */

    if (tableExists($conn, 'users')) {
        addColumnIfMissing(
            $conn,
            'users',
            'email',
            'VARCHAR(255) DEFAULT NULL'
        );

        addColumnIfMissing(
            $conn,
            'users',
            'phone_number',
            'VARCHAR(20) DEFAULT NULL'
        );
    } else {
        echo "⚠ Table 'users' not found. Skipped.<br>";
    }

    /**
     * ----------------------------------------------------
     * 6. Modify problem_ticket column in service_outage table
     * ----------------------------------------------------
     */

    if (tableExists($conn, 'service_outage')) {
        mysqli_query(
            $conn,
            "ALTER TABLE service_outage MODIFY COLUMN problem_ticket VARCHAR(50) NULL DEFAULT NULL"
        );
        echo "✔ Modified problem_ticket column in service_outage<br>";
    } else {
        echo "⚠ Table 'service_outage' not found. Skipped.<br>";
    }

    mysqli_commit($conn);

    echo "<h3 style='color:green'>Migration completed successfully.</h3>";

} catch (Exception $e) {

    mysqli_rollback($conn);

    // Log full details server-side only (never expose internals to browser)
    error_log('[Migration_new] ' . $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine());

    echo "<h3 style='color:red'>Migration failed. Check server error log for details.</h3>";

}

mysqli_close($conn);