<?php
require_once __DIR__ . '/config/app.php';

// Prevent access by unauthorized users (if run in browser)
if (php_sapi_name() !== 'cli') {
    include_once INCLUDES_PATH . '/auth_check.php';
    if (!isSuperAdmin()) {
        http_response_code(403);
        die("Unauthorized: Super Admin privilege required.");
    }
}

$conn = getConnection();
if (!$conn) {
    die("ERROR: Could not connect to the database. Please ensure MySQL is running.\n");
}

$is_cli = (php_sapi_name() === 'cli');
$br = $is_cli ? "\n" : "<br>\n";

// Columns to migrate from VARCHAR to TEXT
// These fields use <textarea> inputs in the UI but had length-restricted VARCHAR types
$migrations = [
    [
        'table'  => 'cr_list',
        'column' => 'impacted_area',
        'type'   => 'TEXT NOT NULL',
        'reason' => 'CR Impacted Area textarea - was VARCHAR(254)',
    ],
    [
        'table'  => 'ssl_certificate',
        'column' => 'issues',
        'type'   => 'TEXT NOT NULL',
        'reason' => 'SSL Issues textarea - was VARCHAR(254)',
    ],
    [
        'table'  => 'campaign',
        'column' => 'description',
        'type'   => 'TEXT NOT NULL',
        'reason' => 'Campaign Description textarea - was VARCHAR(254)',
    ],
    [
        'table'  => 'promo_banner',
        'column' => 'subject_line',
        'type'   => 'TEXT NOT NULL',
        'reason' => 'Promo Banner Subject Line textarea - was VARCHAR(254)',
    ],
    [
        'table'  => 'security_mail',
        'column' => 'subject_line',
        'type'   => 'TEXT NOT NULL',
        'reason' => 'Security Mail Subject Line textarea - was VARCHAR(254)',
    ],
    [
        'table'  => 'enable_disable',
        'column' => 'service_name',
        'type'   => 'TEXT NOT NULL',
        'reason' => 'Enable/Disable Service Name textarea - was VARCHAR(80)',
    ],
];

echo "=== Textarea Column Migration to TEXT ===" . $br;
echo "Timestamp: " . date('Y-m-d H:i:s') . $br . $br;

$success_count = 0;
$skip_count    = 0;
$error_count   = 0;

foreach ($migrations as $m) {
    $table  = $m['table'];
    $column = $m['column'];
    $type   = $m['type'];
    $reason = $m['reason'];

    echo "[$table.$column] $reason" . $br;
    echo "  => Checking current type... ";

    $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");

    if (!$check || mysqli_num_rows($check) === 0) {
        echo "SKIPPED (Column not found in table)." . $br;
        $skip_count++;
        continue;
    }

    $col_info     = mysqli_fetch_assoc($check);
    $current_type = strtolower($col_info['Type']);

    if ($current_type === 'text' || $current_type === 'mediumtext' || $current_type === 'longtext') {
        echo "Already $current_type. No change needed." . $br;
        $skip_count++;
        continue;
    }

    echo "Currently '$current_type'. Altering to TEXT... ";

    $sql = "ALTER TABLE `$table` MODIFY COLUMN `$column` $type";
    if (mysqli_query($conn, $sql)) {
        echo "SUCCESS." . $br;
        $success_count++;
    } else {
        log_error("Failed to alter column", ['table' => $table, 'column' => $column, 'error' => mysqli_error($conn)]);
        echo "ERROR: Alteration failed (check logs)." . $br;
        $error_count++;
    }
}

echo $br;
echo "=== Migration Complete ===" . $br;
echo "  Altered : $success_count column(s)" . $br;
echo "  Skipped : $skip_count column(s) (already correct type or not found)" . $br;
echo "  Errors  : $error_count column(s)" . $br;
?>
