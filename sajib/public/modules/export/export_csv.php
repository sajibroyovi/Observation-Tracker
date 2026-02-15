<?php
// Define table and columns mapping
$module_config = [
    'observations' => [
        'table' => 'observations',
        'columns' => ['serial_no', 'observation_names', 'team_name', 'start_date', 'l1_observation', 'l1_observations_by', 'l2_observation', 'l2_observations_by', 'created_at'],
        'filename' => 'observations_export'
    ],
    'enable_disable' => [
        'table' => 'enable_disable',
        'columns' => ['serial_no', 'service_name', 'action_date', 'action_taken', 'action_taken_by', 'reference', 'created_at'],
        'filename' => 'service_status_export'
    ],
    'pending_mail' => [
        'table' => 'pending_mail',
        'columns' => ['serial_no', 'subject', 'received_date', 'status', 'assigned_to', 'reference', 'created_at'],
        'filename' => 'pending_mails_export'
    ],
    'security_mail' => [
        'table' => 'security_mail',
        'columns' => ['serial_no', 'subject', 'security_level', 'status', 'assigned_to', 'reference', 'created_at'],
        'filename' => 'security_mails_export'
    ],
    'cr_list' => [
        'table' => 'cr_list',
        'columns' => ['serial_no', 'cr_number', 'description', 'status', 'start_time', 'end_time', 'created_at'],
        'filename' => 'cr_list_export'
    ],
    'promo_banner' => [
        'table' => 'promo_banner',
        'columns' => ['serial_no', 'banner_name', 'platform', 'status', 'start_date', 'end_date', 'created_at'],
        'filename' => 'promo_banners_export'
    ],
    'service_outage' => [
        'table' => 'service_outage',
        'columns' => ['serial_no', 'service_name', 'outage_start', 'outage_end', 'reason', 'status', 'created_at'],
        'filename' => 'service_outages_export'
    ],
    'ssl_certificate' => [
        'table' => 'ssl_certificate',
        'columns' => ['serial_no', 'domain_name', 'expiry_date', 'provider', 'status', 'created_at'],
        'filename' => 'ssl_certificates_export'
    ],
    'campaign' => [
        'table' => 'campaign',
        'columns' => ['serial_no', 'campaign_name', 'start_date', 'end_date', 'status', 'created_at'],
        'filename' => 'campaigns_export'
    ]
];

if (!isset($module_config[$module])) {
    die("Invalid module specified.");
}

$config = $module_config[$module];
$table = $config['table'];
$columns = $config['columns'];
$filename = $config['filename'] . "_" . date('Ymd_His') . ".csv";

// Build filter logic (similar to view pages)
$where = " WHERE 1=1";
$params = [];
$types = "";

if ($module === 'observations') {
    if (!empty($_GET['start_date'])) {
        $where .= " AND date(start_date) >= ?";
        $params[] = $_GET['start_date'];
        $types .= "s";
    }
    if (!empty($_GET['end_date'])) {
        $where .= " AND date(start_date) <= ?";
        $params[] = $_GET['end_date'];
        $types .= "s";
    }
    if (!empty($_GET['search'])) {
        $searchTerm = "%" . $_GET['search'] . "%";
        $where .= " AND (observation_names LIKE ? OR team_name LIKE ? OR l1_observation LIKE ? OR l2_observation LIKE ? OR l1_observations_by LIKE ? OR l2_observations_by LIKE ?)";
        for($i=0; $i<6; $i++){ $params[] = $searchTerm; $types .= "s"; }
    }
} else {
    // Basic search for other modules if passed
    if (!empty($_GET['search'])) {
        $searchTerm = "%" . $_GET['search'] . "%";
        // Attempt to search in primary visible columns
        if ($module === 'enable_disable') {
            $where .= " AND (service_name LIKE ? OR action_taken_by LIKE ? OR reference LIKE ?)";
            for($i=0; $i<3; $i++){ $params[] = $searchTerm; $types .= "s"; }
        }
        // Add more module-specific searches here if needed
    }
}

$sql = "SELECT " . implode(", ", $columns) . " FROM $table " . $where . " ORDER BY serial_no DESC";
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Output column headers
fputcsv($output, array_map('ucwords', array_map(function($c){ return str_replace('_', ' ', $c); }, $columns)));

// Output data rows
while ($row = mysqli_fetch_assoc($result)) {
    // Basic data sanitization
    foreach ($row as $key => $value) {
        if ($key === 'action_taken' && $module === 'enable_disable') {
            $status_map = ['0' => 'Enabled', '1' => 'Disabled', '2' => 'Hidden', '3' => 'Unhidden'];
            $row[$key] = $status_map[$value] ?? $value;
        }
    }
    fputcsv($output, $row);
}

fclose($output);
mysqli_close($conn);
exit;
?>




