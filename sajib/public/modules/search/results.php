<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';
include 'auth_check.php';
include 'connection_file.php';

$query = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$results = [];

if (strlen($query) >= 2) {
    $searchPattern = "%$query%";
    
    // Define modules with their search configuration (consistent with global_search.php)
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
        // ... (rest of the modules as in global_search.php)
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
                ORDER BY {$module['date_field']} DESC";
        
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results | Shift Handover</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; include 'sidebar.php'; ?>
        <div class="main-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-magnifying-glass text-primary me-2"></i> Search Results</h1>
                        <p class="text-muted small mb-0">Showing results for: <b><?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo htmlspecialchars($query); ?></b></p>
                    </div>
                </div>

                <div class="glass-card p-4 shadow-sm">
                    <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; if (empty($results)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-search fa-4x mb-3 opacity-25"></i>
                            <h3>No results found</h3>
                            <p class="text-muted">Try searching for something else or check your spelling.</p>
                            <a href="index.php" class="btn btn-primary px-4 rounded-pill mt-3">Back to Dashboard</a>
                        </div>
                    <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; else: ?>
                        <div class="mb-3 text-muted">
                            Found <b><?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo count($results); ?></b> matching records across all modules.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle custom-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Module</th>
                                        <th>Title / Details</th>
                                        <th>Date</th>
                                        <th>Created By</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; foreach ($results as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 30px; height: 30px; background-color: <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo $item['color']; ?>15;">
                                                        <i class="fa-solid <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo $item['icon']; ?> small" style="color: <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo $item['color']; ?>;"></i>
                                                    </div>
                                                    <span class="badge" style="background-color: <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo $item['color']; ?>20; color: <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo $item['color']; ?>;">
                                                        <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo $item['module']; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="fw-bold"><?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo htmlspecialchars($item['title']); ?></td>
                                            <td><i class="fa-solid fa-calendar-day me-1 text-muted"></i> <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo date('M d, Y', strtotime($item['date'])); ?></td>
                                            <td><i class="fa-solid fa-user me-1 text-muted"></i> <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo htmlspecialchars($item['created_by']); ?></td>
                                            <td class="text-end">
                                                <a href="<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; echo $item['url']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    <i class="fa-solid fa-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php'; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/1dc05809f5.js" crossorigin="anonymous"></script>
</body>
</html>




