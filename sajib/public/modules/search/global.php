<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';
include 'auth_check.php';
include 'connection_file.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';

if (strlen($query) < 2) {
    echo json_encode(['results' => [], 'message' => 'Please enter at least 2 characters']);
    exit;
}

$results = [];
$searchPattern = "%$query%";

// Define modules with their search configuration
$modules = [
    [
        'name' => 'Enable/Disable',
        'table' => 'enable_disable',
        'title_field' => 'service_name',
        'date_field' => 'action_date',
        'view_url' => 'viewdata/view_ed.php',
        'icon' => 'fa-toggle-on',
        'color' => '#4361ee'
    ],
    [
        'name' => 'Pending Mail',
        'table' => 'pending_mail',
        'title_field' => 'subject_line',
        'date_field' => 'created_at',
        'view_url' => 'viewdata/view_pd.php',
        'icon' => 'fa-envelope',
        'color' => '#f72585'
    ],
    [
        'name' => 'Security Mail',
        'table' => 'security_mail',
        'title_field' => 'subject_line',
        'date_field' => 'created_at',
        'view_url' => 'viewdata/view_sc.php',
        'icon' => 'fa-shield-halved',
        'color' => '#ff9f1c'
    ],
    [
        'name' => 'CR List',
        'table' => 'cr_list',
        'title_field' => 'cr_subject',
        'date_field' => 'cr_start_time',
        'view_url' => 'viewdata/view_cr.php',
        'icon' => 'fa-file-invoice',
        'color' => '#4cc9f0'
    ],
    [
        'name' => 'Promo Banner',
        'table' => 'promo_banner',
        'title_field' => 'subject_line',
        'date_field' => 'start_time',
        'view_url' => 'viewdata/view_banner.php',
        'icon' => 'fa-image',
        'color' => '#7209b7'
    ],
    [
        'name' => 'Service Outage',
        'table' => 'service_outage',
        'title_field' => 'details',
        'date_field' => 'created_at',
        'view_url' => 'viewdata/view_outage.php',
        'icon' => 'fa-triangle-exclamation',
        'color' => '#ef233c'
    ],
    [
        'name' => 'SSL Certificate',
        'table' => 'ssl_certificate',
        'title_field' => 'certificate_name',
        'date_field' => 'expiration_date',
        'view_url' => 'viewdata/view_ssl.php',
        'icon' => 'fa-lock',
        'color' => '#560bad'
    ],
    [
        'name' => 'Campaign',
        'table' => 'campaign',
        'title_field' => 'campaign_name',
        'date_field' => 'start_date',
        'view_url' => 'viewdata/view_campaign.php',
        'icon' => 'fa-bullhorn',
        'color' => '#3a0ca3'
    ],
    [
        'name' => 'Observations',
        'table' => 'observations',
        'title_field' => 'observation_names',
        'date_field' => 'start_date',
        'view_url' => 'viewdata/view_observations.php',
        'icon' => 'fa-clipboard-check',
        'color' => '#2a9d8f'
    ]
];

foreach ($modules as $module) {
    $sql = "SELECT serial_no, {$module['title_field']} as title, {$module['date_field']} as date_field, created_by 
            FROM {$module['table']} 
            WHERE {$module['title_field']} LIKE ? 
            ORDER BY {$module['date_field']} DESC 
            LIMIT 5";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $searchPattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = [
                'id' => $row['serial_no'],
                'title' => $row['title'],
                'module' => $module['name'],
                'date' => $row['date_field'],
                'created_by' => $row['created_by'] ?? 'N/A',
                'url' => $module['view_url'],
                'icon' => $module['icon'],
                'color' => $module['color']
            ];
        }
        mysqli_stmt_close($stmt);
    }
}

// Sort by date (most recent first)
usort($results, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Limit to top 10 results for autocomplete
$autocompleteResults = array_slice($results, 0, 10);

echo json_encode([
    'results' => $autocompleteResults,
    'total' => count($results),
    'query' => $query
]);

mysqli_close($conn);
?>




