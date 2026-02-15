<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';

// This file handles high-performance CSV exports for all operational modules
$module = isset($_GET['module']) ? $_GET['module'] : '';
if (empty($module)) {
    die("No module specified.");
}

// Configuration for modules based on verified database schema
$config = [
    'enable_disable' => [
        'table' => 'enable_disable',
        'columns' => ['serial_no', 'service_name', 'action_date', 'action_taken', 'action_taken_by', 'reference', 'created_at'],
        'headers' => ['#', 'Service Name', 'Action Date', 'Status', 'Action By', 'Reference', 'Created At']
    ],
    'pending_mail' => [
        'table' => 'pending_mail',
        'columns' => ['serial_no', 'subject_line', 'priority', 'status', 'created_by', 'created_at'],
        'headers' => ['#', 'Subject Line', 'Priority', 'Status', 'Created By', 'Created At']
    ],
    'security_mail' => [
        'table' => 'security_mail',
        'columns' => ['serial_no', 'subject_line', 'priority', 'status', 'created_by', 'created_at'],
        'headers' => ['#', 'Subject Line', 'Priority', 'Status', 'Created By', 'Created At']
    ],
    'cr_list' => [
        'table' => 'cr_list',
        'columns' => ['serial_no', 'cr_subject', 'impacted_area', 'cr_start_time', 'cr_end_time', 'downtime', 'cr_meeting_attended', 'created_by', 'created_at'],
        'headers' => ['#', 'CR Subject', 'Impacted Area', 'Start Time', 'End Time', 'Downtime', 'Meeting Attended', 'Created By', 'Created At']
    ],
    'promo_banner' => [
        'table' => 'promo_banner',
        'columns' => ['serial_no', 'subject_line', 'status', 'start_time', 'created_by'],
        'headers' => ['#', 'Subject Line', 'Status', 'Start Time', 'Created By']
    ],
    'service_outage' => [
        'table' => 'service_outage',
        'columns' => ['serial_no', 'details', 'incident_id', 'problem_ticket', 'status', 'technician', 'created_by', 'created_at'],
        'headers' => ['#', 'Details', 'Incident ID', 'Problem Ticket', 'Status', 'Technician', 'Created By', 'Created At']
    ],
    'ssl_certificate' => [
        'table' => 'ssl_certificate',
        'columns' => ['serial_no', 'certificate_name', 'expiration_date', 'renewal_status', 'issues', 'created_by', 'created_at'],
        'headers' => ['#', 'Certificate Name', 'Expiration Date', 'Renewal Status', 'Issues', 'Created By', 'Created At']
    ],
    'campaign' => [
        'table' => 'campaign',
        'columns' => ['serial_no', 'campaign_name', 'start_date', 'status', 'description', 'created_by', 'created_at'],
        'headers' => ['#', 'Campaign Name', 'Start Date', 'Status', 'Description', 'Created By', 'Created At']
    ],
    'observations' => [
        'table' => 'observations',
        'columns' => ['serial_no', 'observation_names', 'team_name', 'start_date', 'l1_observation', 'l1_observations_by', 'l2_observation', 'l2_observations_by', 'created_at'],
        'headers' => ['#', 'Observation Name', 'Team Name', 'Start Date', 'L1 Obs', 'L1 By', 'L2 Obs', 'L2 By', 'Created At', 'Status']
    ]
];

if (!isset($config[$module])) {
    die("Invalid module specified.");
}

$m = $config[$module];
$where = " WHERE 1=1";
$params = [];
$types = "";

// Unified Filter Logic
if (!empty($_GET['start_date'])) {
    // Attempt to filter by common date columns
    $date_col = in_array('start_date', $m['columns']) ? 'start_date' : (in_array('action_date', $m['columns']) ? 'action_date' : (in_array('created_at', $m['columns']) ? 'created_at' : ''));
    if ($date_col) {
        $where .= " AND date($date_col) >= ?";
        $params[] = $_GET['start_date'];
        $types .= "s";
    }
}
if (!empty($_GET['end_date'])) {
    $date_col = in_array('start_date', $m['columns']) ? 'start_date' : (in_array('action_date', $m['columns']) ? 'action_date' : (in_array('created_at', $m['columns']) ? 'created_at' : ''));
    if ($date_col) {
        $where .= " AND date($date_col) <= ?";
        $params[] = $_GET['end_date'];
        $types .= "s";
    }
}
if (!empty($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $search_cols = [];
    foreach ($m['columns'] as $col) {
        if (strpos($col, 'name') !== false || strpos($col, 'subject') !== false || strpos($col, 'obs') !== false || strpos($col, 'detail') !== false || $col === 'reference') {
            $search_cols[] = "$col LIKE ?";
        }
    }
    if (!empty($search_cols)) {
        $where .= " AND (" . implode(" OR ", $search_cols) . ")";
        foreach ($search_cols as $sc) {
            $params[] = $searchTerm;
            $types .= "s";
        }
    }
}

// Special filter for Observations Status
if ($module === 'observations' && !empty($_GET['status'])) {
    if ($_GET['status'] === 'Complete') {
        $where .= " AND l2_observation IS NOT NULL AND l2_observation != ''";
    } elseif ($_GET['status'] === 'Pending') {
        $where .= " AND (l2_observation IS NULL OR l2_observation = '')";
    }
}

$sql = "SELECT " . implode(", ", $m['columns']) . " FROM " . $m['table'] . $where . " ORDER BY serial_no DESC";
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// CSV Export Stream
$filename = $module . "_export_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, $m['headers']);

while ($row = mysqli_fetch_assoc($result)) {
    // Status normalization for enable_disable
    if ($module === 'enable_disable' && isset($row['action_taken'])) {
        $status_map = ['0' => 'Enabled', '1' => 'Disabled', '2' => 'Hidden', '3' => 'Unhidden'];
        $row['action_taken'] = $status_map[$row['action_taken']] ?? $row['action_taken'];
    }
    
    // Status calculation for observations
    if ($module === 'observations') {
        $status = (!empty($row['l2_observation'])) ? 'COMPLETE' : 'PENDING';
        $row['status'] = $status;
    }
    
    fputcsv($output, $row);
}

fclose($output);
mysqli_close($conn);
exit;
?>




