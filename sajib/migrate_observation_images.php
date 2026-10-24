<?php
/**
 * Migration: Add l1_image and l1_image_2 columns to observations table
 * Run this once via browser: http://localhost/sajib/sajib/migrate_observation_images.php
 */
require_once __DIR__ . '/config/app.php';

// Prevent access by unauthorized users (if run in browser)
if (php_sapi_name() !== 'cli') {
    include_once INCLUDES_PATH . '/auth_check.php';
    if (!isSuperAdmin()) {
        http_response_code(403);
        die("Unauthorized: Super Admin privilege required.");
    }
}

echo "<h2>Observation Image Column Migration</h2>";
echo "<pre>";

$conn = getConnection();

function colExists($conn, $table, $col) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $res && mysqli_num_rows($res) > 0;
}

// Add l1_image column
if (!colExists($conn, 'observations', 'l1_image')) {
    if (mysqli_query($conn, "ALTER TABLE `observations` ADD COLUMN `l1_image` VARCHAR(255) DEFAULT NULL")) {
        echo "✔ Added column: l1_image\n";
    } else {
        log_error("Failed to add l1_image", ['error' => mysqli_error($conn)]);
        echo "✘ Failed to add l1_image (check logs)\n";
    }
} else {
    echo "✓ Column l1_image already exists.\n";
}

// Add l1_image_2 column
if (!colExists($conn, 'observations', 'l1_image_2')) {
    if (mysqli_query($conn, "ALTER TABLE `observations` ADD COLUMN `l1_image_2` VARCHAR(255) DEFAULT NULL")) {
        echo "✔ Added column: l1_image_2\n";
    } else {
        log_error("Failed to add l1_image_2", ['error' => mysqli_error($conn)]);
        echo "✘ Failed to add l1_image_2 (check logs)\n";
    }
} else {
    echo "✓ Column l1_image_2 already exists.\n";
}

// Show current columns of observations table
echo "\n--- Current columns in `observations` table ---\n";
$res = mysqli_query($conn, "SHOW COLUMNS FROM `observations`");
while ($row = mysqli_fetch_assoc($res)) {
    echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n--- Last 3 observation rows (image columns) ---\n";
$res2 = mysqli_query($conn, "SELECT serial_no, l1_image, l1_image_2 FROM observations ORDER BY serial_no DESC LIMIT 3");
if ($res2 && mysqli_num_rows($res2) > 0) {
    while ($r = mysqli_fetch_assoc($res2)) {
        echo "  serial_no=" . $r['serial_no'] . " | l1_image=" . ($r['l1_image'] ?: '(empty)') . " | l1_image_2=" . ($r['l1_image_2'] ?: '(empty)') . "\n";
    }
} else {
    echo "  No rows found or query failed.\n";
}

echo "</pre>";
echo "<p style='color:green;font-weight:bold;'>Migration check complete. You can now delete this file.</p>";
