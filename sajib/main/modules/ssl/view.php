<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter logic
$where = " WHERE 1=1";
$filter_params = [];
$filter_types = "";

if (!empty($_GET['start_date'])) {
    $where .= " AND date(expiration_date) >= ?";
    $filter_params[] = $_GET['start_date'];
    $filter_types .= "s";
}
if (!empty($_GET['end_date'])) {
    $where .= " AND date(expiration_date) <= ?";
    $filter_params[] = $_GET['end_date'];
    $filter_types .= "s";
}
if (!empty($_GET['status_filter'])) {
    $where .= " AND renewal_status = ?";
    $filter_params[] = $_GET['status_filter'];
    $filter_types .= "s";
}

if (!empty($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $where .= " AND (certificate_name LIKE ? OR issues LIKE ?)";
    $filter_params[] = $searchTerm;
    $filter_params[] = $searchTerm;
    $filter_types .= "ss";
}

// Count total records with filters
$count_sql = "SELECT COUNT(*) as total FROM ssl_certificate" . $where;
$stmt_count = mysqli_prepare($conn, $count_sql);
if (!empty($filter_params)) {
    mysqli_stmt_bind_param($stmt_count, $filter_types, ...$filter_params);
}
mysqli_stmt_execute($stmt_count);
$total_records = mysqli_stmt_get_result($stmt_count)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Fetch data from ssl_certificate table with filters and limit
$sql = "SELECT * FROM ssl_certificate " . $where . " ORDER BY serial_no DESC LIMIT $limit OFFSET $offset";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($filter_params)) {
    mysqli_stmt_bind_param($stmt, $filter_types, ...$filter_params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View SSL Certificate</title>
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/uploads/bkash_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <script src="<?= ASSETS_URL ?>/js/script.js" defer></script>

</head>

<body>
    <div class="dashboard-container">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        <div class="main-content">
            <!-- Hidden toggle for script.js compatibility -->
            <div style="display: none;">
                <input type="checkbox" id="night-mode-toggle">
            </div>

            <div class="container-fluid ps-0 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-lock text-purple me-2"></i> SSL Certificate Records</h1>
                        <p class="text-muted small mb-0">Monitor certificate validity, track upcoming expirations, and renewal status.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if (canEditGlobal()): ?>
                        <button class="btn btn-primary btn-sm rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#staticBackdrop_SSLcertificate">
                            <i class="fa-solid fa-plus me-1"></i> Add New
                        </button>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/modules/export/export?module=ssl_certificate&<?= e(http_build_query($_GET)) ?>" class="btn btn-outline-success btn-sm rounded-pill shadow-sm px-3 bg-white border-0 btn-export-view"><i class="fa-solid fa-file-export me-1"></i> Export</a>
                        <a href="<?= BASE_URL ?>/" class="btn btn-outline-primary btn-sm rounded-pill shadow-sm px-3 border-0 bg-white btn-dashboard-view"><i class="fa-solid fa-house me-1"></i> Dashboard</a>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="glass-card mb-4 p-3 py-1">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">EXPIRY AFTER</label>
                            <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-light" value="<?php echo htmlspecialchars($_GET['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">EXPIRY BEFORE</label>
                            <input type="date" name="end_date" class="form-control form-control-sm border-0 bg-light" value="<?php echo htmlspecialchars($_GET['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted fw-bold">RENEWAL STATUS</label>
                            <select name="status_filter" class="form-select form-select-sm border-0 bg-light">
                                <option value="">All Status</option>
                                <option value="Renewed" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] === 'Renewed') ? 'selected' : ''; ?>>Renewed</option>
                                <option value="Pending" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted fw-bold">SEARCH</label>
                            <input type="text" name="search" class="form-control form-control-sm border-0 bg-light" placeholder="Cert Name, Issue..." value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 flex-grow-1"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                            <a href="view" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fa-solid fa-rotate me-1"></i> Reset</a>
                        </div>
                    </form>
                </div>

                <?php if (mysqli_num_rows($result) > 0) { ?>
                <div class="glass-card mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 dashboard-table">
                            <thead class="bg-light bg-opacity-50">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Certificate Name</th>
                                    <th>Expiration</th>
                                    <th>Status</th>
                                    <th>Renewal</th>
                                    <th>Issues</th>
                                    <th>Audit History</th>
                                    <?php if (canEditGlobal()): ?>
                                    <th class="text-end pe-4">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count = $offset + 1;
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $expiration_date = $row["expiration_date"] ?? null;
                                    $days_until_expiry = '-';
                                    $status_badge = '<span class="status-badge bg-secondary bg-opacity-10 text-secondary fw-bold p-2 px-3 rounded-pill">Unknown</span>';

                                    if ($expiration_date) {
                                        $expiry_timestamp = strtotime($expiration_date);
                                        $current_timestamp = time();
                                        $days_until_expiry = ceil(($expiry_timestamp - $current_timestamp) / (60 * 60 * 24));

                                        if ($days_until_expiry < 0) {
                                            $status_badge = '<span class="status-badge bg-danger bg-opacity-10 text-danger fw-bold p-2 px-3 rounded-pill"><i class="fa-solid fa-circle-exclamation me-1"></i> Expired</span>';
                                        } else if ($days_until_expiry <= 30) {
                                            $status_badge = '<span class="status-badge bg-warning bg-opacity-10 text-warning fw-bold p-2 px-3 rounded-pill"><i class="fa-solid fa-clock me-1"></i> Expiring Soon</span>';
                                        } else {
                                            $status_badge = '<span class="status-badge bg-success bg-opacity-10 text-success fw-bold p-2 px-3 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i> Valid</span>';
                                        }
                                    }

                                    $renewal_badge = '';
                                    $renewal_status_normalized = strtolower(trim($row["renewal_status"] ?? ""));
                                    $renewal_badge = '';
                                    $renewal_status_normalized = strtolower(trim($row["renewal_status"] ?? ""));
                                    $renewal_badge = '<span class="status-badge bg-secondary bg-opacity-10 text-secondary fw-bold p-2 px-3 rounded-pill">' . e($renewal_status_normalized ?: "N/A") . '</span>';
                                    if ($renewal_status_normalized == 'renewed') {
                                        $renewal_badge = '<span class="status-badge bg-success bg-opacity-10 text-success fw-bold p-2 px-3 rounded-pill">Renewed</span>';
                                    } else if ($renewal_status_normalized == 'pending') {
                                        $renewal_badge = '<span class="status-badge bg-warning bg-opacity-10 text-warning fw-bold p-2 px-3 rounded-pill">Pending</span>';
                                    } else if ($renewal_status_normalized == 'failed') {
                                        $renewal_badge = '<span class="status-badge bg-danger bg-opacity-10 text-danger fw-bold p-2 px-3 rounded-pill">Failed</span>';
                                    }
                                    
                                    echo "<tr>
                                            <td class='ps-4'>" . $count++ . "</td>
                                            <td class='fw-bold'>" . e($row["certificate_name"] ?? "N/A") . "</td>
                                            <td>
                                                <div class='small fw-bold'>" . ($expiration_date ? date('d M, Y', strtotime($expiration_date)) : 'N/A') . "</div>
                                                <div class='text-muted small'>" . ($days_until_expiry !== '-' ? ($days_until_expiry < 0 ? abs($days_until_expiry) . " days ago" : $days_until_expiry . " days left") : "") . "</div>
                                            </td>
                                            <td>" . $status_badge . "</td>
                                            <td>" . $renewal_badge . "</td>
                                            <td class='text-muted small' style='max-width: 150px;' title='" . e($row["issues"] ?? "") . "'>" . e($row["issues"] ?? "None") . "</td>
                                            <td>
                                                <div class='audit-stack'>
                                                    <div class='small text-muted mb-1' title='Created'>
                                                        <i class='fa-solid fa-circle-plus text-success me-1 scale-80'></i> <b>" . (!empty($row['created_by']) ? e($row['created_by']) : 'System') . "</b>
                                                        <span class='ms-1 opacity-75' style='font-size: 0.7rem;'>" . (!empty($row['created_at']) ? date('d/m H:i', strtotime($row['created_at'])) : '-') . "</span>
                                                    </div>";
                                    
                                    if (!empty($row['edited_by'])) {
                                        echo "<div class='small text-muted border-top pt-1 mt-1 font-italic' title='Last Edited'>
                                                <i class='fa-solid fa-user-pen text-info me-1 scale-80'></i> <b>" . e($row['edited_by']) . "</b>
                                                <span class='ms-1 opacity-75' style='font-size: 0.7rem;'>" . date('d/m H:i', strtotime($row['edited_at'])) . "</span>
                                            </div>";
                                    }
                                    echo "</div></td>";

                                    if (canEditGlobal()) {
                                        echo "<td class='text-end pe-4'>
                                                <div class='btn-group shadow-sm rounded'>
                                                    <a href='update?id=" . (int)$row['serial_no'] . "' class='btn btn-white btn-sm border-end text-primary' title='Edit Record'><i class='fa-solid fa-pen-to-square'></i></a>";
                                        
                                        if (isSuperAdmin()) {
                                            echo "<a href='delete?id=" . (int)$row['serial_no'] . "&csrf_token=" . urlencode($_SESSION['csrf_token']) . "' class='btn btn-white btn-sm text-danger' title='Delete Record' onclick='return confirm(\"Are you sure you want to delete this record?\")'><i class='fa-solid fa-trash-can'></i></a>";
                                        }
                                        echo "</div></td>";
                                    }
                                    
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php } else { ?>
                <div class="glass-card p-5 text-center my-5">
                    <i class="fa-solid fa-folder-open fa-3x text-muted opacity-25 mb-3"></i>
                    <h4 class="text-muted fw-bold">No Records Found</h4>
                    <p class="text-muted mb-0">Try adjusting your search or check back later.</p>
                </div>
            <?php } ?>

            <?php
            // Pagination links
            if ($total_pages > 1) {
                // Keep filter parameters in pagination links
                $params = $_GET;
                unset($params['page']);
                $query_string = !empty($params) ? '&' . http_build_query($params) : '';

                echo '<nav aria-label="Page navigation" class="mt-4 pb-5">
                        <ul class="pagination pagination-sm justify-content-center">';

                // Previous button
                $prev_disabled = ($page <= 1) ? 'disabled' : '';
                $prev_url = ($page <= 1) ? '#' : '?page=' . ($page - 1) . $query_string;
                echo '<li class="page-item ' . $prev_disabled . '">
                        <a class="page-link border-0 shadow-sm mx-1 rounded-3" href="' . $prev_url . '"><i class="fa-solid fa-chevron-left small"></i></a>
                      </li>';

                // Page numbers
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link border-0 shadow-sm mx-1 rounded-3" href="?page=1' . $query_string . '">1</a></li>';
                    if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>';
                }

                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active = ($page == $i) ? 'active' : '';
                    echo '<li class="page-item ' . $active . '">
                            <a class="page-link border-0 shadow-sm mx-1 rounded-3 ' . ($active ? 'bg-primary text-white' : '') . '" href="?page=' . $i . $query_string . '">' . $i . '</a>
                          </li>';
                }

                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>';
                    echo '<li class="page-item"><a class="page-link border-0 shadow-sm mx-1 rounded-3" href="?page=' . $total_pages . $query_string . '">' . $total_pages . '</a></li>';
                }

                // Next button
                $next_disabled = ($page >= $total_pages) ? 'disabled' : '';
                $next_url = ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) . $query_string;
                echo '<li class="page-item ' . $next_disabled . '">
                        <a class="page-link border-0 shadow-sm mx-1 rounded-3" href="' . $next_url . '"><i class="fa-solid fa-chevron-right small"></i></a>
                      </li>';

                echo '</ul></nav>';
            }

            mysqli_close($conn);
            ?>
            <?php include INCLUDES_PATH . '/modals.php'; ?>
        </div> <!-- End main-content -->
    </div> <!-- End dashboard-container -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>



