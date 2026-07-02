<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';
require_once INCLUDES_PATH . '/functions.php';

// Check permissions
if (!in_array($_SESSION['role'] ?? '', ['l1', 'super_admin'])) {
    showError("You don't have permission to view this module.");
    redirectTo(BASE_URL . '/');
}

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = " WHERE 1=1";
$filter_params = [];
$filter_types = "";

if (!empty($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $where .= " AND (instruction_text LIKE ?)";
    $filter_params[] = $searchTerm;
    $filter_types .= "s";
}

$count_sql = "SELECT COUNT(*) as total FROM l1_instructions" . $where;
$stmt_count = mysqli_prepare($conn, $count_sql);
if (!empty($filter_params)) {
    mysqli_stmt_bind_param($stmt_count, $filter_types, ...$filter_params);
}
mysqli_stmt_execute($stmt_count);
$total_records = mysqli_stmt_get_result($stmt_count)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$sql = "SELECT * FROM l1_instructions " . $where . " ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($filter_params)) {
    mysqli_stmt_bind_param($stmt, $filter_types, ...$filter_params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$_stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM l1_instructions")) ?: [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View L1 Instructions</title>
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
            <div style="display: none;">
                <input type="checkbox" id="night-mode-toggle">
            </div>

            <div class="container-fluid ps-0 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-bullhorn text-danger me-2"></i> L1 Instructions</h1>
                        <p class="text-muted small mb-0">Manage scrolling global instructions for L1 users.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-danger btn-sm rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#staticBackdrop_l1_instructions">
                            <i class="fa-solid fa-plus me-1"></i> Add New
                        </button>
                        <a href="<?= BASE_URL ?>/" class="btn btn-outline-primary btn-sm rounded-pill shadow-sm px-3 border-0 bg-white btn-dashboard-view"><i class="fa-solid fa-house me-1"></i> Dashboard</a>
                    </div>
                </div>

                <!-- Stats Strip -->
                <div class="glass-card mb-4 p-4 shadow-sm border-0">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-4 flex-wrap">
                                <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger rounded d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-bullhorn fa-xl"></i>
                                </div>
                                <div class="stat-item pe-4">
                                    <div class="small-caps text-muted mb-1">Total Instructions</div>
                                    <div class="h4 fw-bold mb-0 text-dark"><?= (int)($_stats['total'] ?? 0) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="glass-card mb-4 p-3 py-1">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">SEARCH</label>
                            <input type="text" name="search" class="form-control form-control-sm border-0 bg-light" placeholder="Search instruction text..." value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-6 d-flex gap-2">
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
                                    <th>Instruction Text</th>
                                    <th>Created By</th>
                                    <th>Date</th>
                                    <th>Last Updated Log</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count = $offset + 1;
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>
                                            <td class='ps-4'>" . $count++ . "</td>
                                            <td class='fw-bold'>" . e($row["instruction_text"] ?? "N/A") . "</td>
                                            <td>
                                                <div class='small text-muted mb-1'>
                                                    <i class='fa-solid fa-user text-primary me-1 scale-80'></i> <b>" . (!empty($row['created_by']) ? e($row['created_by']) : 'System') . "</b>
                                                </div>
                                            </td>
                                            <td>
                                                <span class='ms-1 opacity-75' style='font-size: 0.85rem;'>" . (!empty($row['created_at']) ? date('d M, Y - h:i A', strtotime($row['created_at'])) : '-') . "</span>
                                            </td>
                                            <td>
                                                " . (!empty($row['updated_by']) ? "
                                                <div class='small text-muted mb-1'>
                                                    <i class='fa-solid fa-user-pen text-info me-1 scale-80'></i> <b>" . e($row['updated_by']) . "</b>
                                                </div>
                                                <span class='ms-1 opacity-75' style='font-size: 0.85rem;'>" . (!empty($row['updated_at']) ? date('d M, Y - h:i A', strtotime($row['updated_at'])) : '-') . "</span>
                                                " : "<span class='text-muted small fst-italic'>Not updated yet</span>") . "
                                            </td>
                                            <td class='text-end pe-4'>
                                                <a href='update.php?id=" . (int)$row['id'] . "' class='btn btn-white btn-sm text-primary shadow-sm border me-1' title='Edit Record'><i class='fa-solid fa-pen'></i></a>
                                                <form action='delete' method='POST' style='display:inline;'>
                                                    " . getCsrfField() . "
                                                    <input type='hidden' name='id' value='" . (int)$row['id'] . "'>
                                                    <button type='submit' class='btn btn-white btn-sm text-danger shadow-sm border' title='Delete Record' onclick='return confirm(\"Are you sure you want to delete this instruction?\")'><i class='fa-solid fa-trash-can'></i></button>
                                                </form>
                                            </td>
                                        </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php } else { ?>
                <div class="glass-card p-5 text-center my-5">
                    <i class="fa-solid fa-bullhorn fa-3x text-muted opacity-25 mb-3"></i>
                    <h4 class="text-muted fw-bold">No Instructions Found</h4>
                    <p class="text-muted mb-0">Try adding a new instruction to see it on the marquee.</p>
                </div>
            <?php } ?>

            <?php
            // Pagination links
            if ($total_pages > 1) {
                $params = $_GET;
                unset($params['page']);
                $query_string = !empty($params) ? '&' . http_build_query($params) : '';

                echo '<nav aria-label="Page navigation" class="mt-4 pb-5">
                        <ul class="pagination pagination-sm justify-content-center">';

                $prev_disabled = ($page <= 1) ? 'disabled' : '';
                $prev_url = ($page <= 1) ? '#' : '?page=' . ($page - 1) . $query_string;
                echo '<li class="page-item ' . $prev_disabled . '">
                        <a class="page-link border-0 shadow-sm mx-1 rounded-3" href="' . $prev_url . '"><i class="fa-solid fa-chevron-left small"></i></a>
                      </li>';

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

                $next_disabled = ($page >= $total_pages) ? 'disabled' : '';
                $next_url = ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) . $query_string;
                echo '<li class="page-item ' . $next_disabled . '">
                        <a class="page-link border-0 shadow-sm mx-1 rounded-3" href="' . $next_url . '"><i class="fa-solid fa-chevron-right small"></i></a>
                      </li>';

                echo '</ul></nav>';
            }

            ?>
            <?php include INCLUDES_PATH . '/modals.php'; ?>
        </div> <!-- End main-content -->
    </div> <!-- End dashboard-container -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
