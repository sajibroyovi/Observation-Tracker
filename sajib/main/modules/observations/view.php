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
    $where .= " AND date(start_date) >= ?";
    $filter_params[] = $_GET['start_date'];
    $filter_types .= "s";
}
if (!empty($_GET['end_date'])) {
    $where .= " AND date(start_date) <= ?";
    $filter_params[] = $_GET['end_date'];
    $filter_types .= "s";
}

if (!empty($_GET['status'])) {
    if ($_GET['status'] === 'Complete') {
        $where .= " AND l2_observation IS NOT NULL AND l2_observation != ''";
    } elseif ($_GET['status'] === 'Pending') {
        $where .= " AND (l2_observation IS NULL OR l2_observation = '')";
    }
}

if (!empty($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $where .= " AND (observation_names LIKE ? OR team_name LIKE ? OR technician_name LIKE ? OR l1_observation LIKE ? OR l2_observation LIKE ? OR l1_observations_by LIKE ? OR l2_observations_by LIKE ?)";
    $filter_params[] = $searchTerm;
    $filter_params[] = $searchTerm;
    $filter_params[] = $searchTerm;
    $filter_params[] = $searchTerm;
    $filter_params[] = $searchTerm;
    $filter_params[] = $searchTerm;
    $filter_params[] = $searchTerm;
    $filter_types .= "sssssss";
}

// Count total records with filters
$count_sql = "SELECT COUNT(*) as total FROM observations" . $where;
$stmt_count = mysqli_prepare($conn, $count_sql);
if ($stmt_count === false) {
    log_error("Failed to prepare count query for observations", ["error" => mysqli_error($conn)]);
    die("Critical Error: Internal Server Error. Please contact support.");
}
if (!empty($filter_params)) {
    mysqli_stmt_bind_param($stmt_count, $filter_types, ...$filter_params);
}
mysqli_stmt_execute($stmt_count);
$count_result = mysqli_stmt_get_result($stmt_count);
if ($count_result === false) {
    log_error("Failed to get count result for observations", ["error" => mysqli_error($conn)]);
    die("Critical Error: Internal Server Error.");
}
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Fetch data from observations table with filters and limit
$sql = "SELECT * FROM observations " . $where . " ORDER BY serial_no DESC LIMIT $limit OFFSET $offset";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    log_error("Failed to prepare observations query", ["error" => mysqli_error($conn)]);
    die("Critical Error: Internal Server Error.");
}
if (!empty($filter_params)) {
    mysqli_stmt_bind_param($stmt, $filter_types, ...$filter_params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($result === false) {
    log_error("Failed to get observations result", ["error" => mysqli_error($conn)]);
    die("Critical Error: Internal Server Error.");
}

// Module Stats
$_stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, SUM(l2_observation IS NULL OR l2_observation = '') as pending, SUM(l2_observation IS NOT NULL AND l2_observation != '') as complete FROM observations")) ?: [];
$_progress = ($_stats['total'] > 0) ? round(($_stats['complete'] / $_stats['total']) * 100) : 0;

// Truncate words function
function truncate_words($text, $limit = 100) {
    if (empty($text)) return "No notes";
    $words = explode(' ', $text);
    if (count($words) > $limit) {
        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }
    return $text;
}
// Truncate characters function
function truncate_chars($text, $limit = 50, $default = "") {
    if (empty($text)) return $default;
    return mb_strimwidth($text, 0, $limit, "...");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Observations</title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/uploads/bkash_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <script src="<?= ASSETS_URL ?>/js/script.js" defer></script>
    <script src="<?= ASSETS_URL ?>/js/toast.js" defer></script>

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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-clipboard-check text-teal me-2"></i>Observations Records</h1>
                        <p class="text-muted small mb-0">Monitor L1/L2 operational checks and photographic evidence.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if (canAddObservation()): ?>
                        <button class="btn btn-primary btn-sm rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#staticBackdrop_observations">
                            <i class="fa-solid fa-plus me-1"></i> Add New
                        </button>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/modules/export/export?module=observations&<?= e(http_build_query($_GET)) ?>" class="btn btn-outline-success btn-sm rounded-pill shadow-sm px-3 bg-white border-0 btn-export-view"><i class="fa-solid fa-file-export me-1"></i> Export</a>
                        <a href="<?= BASE_URL ?>/" class="btn btn-outline-primary btn-sm rounded-pill shadow-sm px-3 border-0 bg-white btn-dashboard-view"><i class="fa-solid fa-house me-1"></i> Dashboard</a>
                    </div>
                </div>

                <!-- Stats Strip -->
                <div class="glass-card mb-4 p-3">
                    <div class="d-flex align-items-center gap-4 flex-wrap">
                        <i class="fa-solid fa-magnifying-glass-chart fa-lg text-primary opacity-50"></i>
                        <div class="text-center border-end pe-4">
                            <div class="text-muted fw-bold text-uppercase" style="font-size:.6rem;letter-spacing:1.5px">PENDING</div>
                            <div class="fw-bold text-danger" style="font-size:1.3rem;line-height:1.2"><?= (int)($_stats['pending'] ?? 0) ?></div>
                        </div>
                        <div class="text-center border-end pe-4">
                            <div class="text-muted fw-bold text-uppercase" style="font-size:.6rem;letter-spacing:1.5px">COMPLETE</div>
                            <div class="fw-bold text-success" style="font-size:1.3rem;line-height:1.2"><?= (int)($_stats['complete'] ?? 0) ?></div>
                        </div>
                        <div class="text-center border-end pe-4">
                            <div class="text-muted fw-bold text-uppercase" style="font-size:.6rem;letter-spacing:1.5px">TOTAL</div>
                            <div class="fw-bold" style="font-size:1.3rem;line-height:1.2"><?= (int)($_stats['total'] ?? 0) ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-muted fw-bold text-uppercase" style="font-size:.6rem;letter-spacing:1.5px">PROG</div>
                            <div class="fw-bold text-primary" style="font-size:1.3rem;line-height:1.2"><?= $_progress ?>%</div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="glass-card mb-4 p-3 py-1">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">START DATE</label>
                            <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-light" value="<?php echo htmlspecialchars($_GET['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">END DATE</label>
                            <input type="date" name="end_date" class="form-control form-control-sm border-0 bg-light" value="<?php echo htmlspecialchars($_GET['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted fw-bold">SEARCH</label>
                            <input type="text" name="search" class="form-control form-control-sm border-0 bg-light" placeholder="Notes..." value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="small text-muted fw-bold">STATUS</label>
                            <select name="status" class="form-select form-select-sm border-0 bg-light">
                                <option value="">All Status</option>
                                <option value="Complete" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Complete') ? 'selected' : ''; ?>>Complete</option>
                                <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
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
                                    <th style="width: 200px;">Observation</th>
                                    <th>Technician</th>
                                    <th>Team</th>
                                    <th>L1 Observation</th>
                                    <th>L2 Observation</th>
                                    <th>Status</th>
                                    <th>Evidence</th>
                                    <th style="width: 100px;">Audit History</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count = $offset + 1;
                                 while ($row = mysqli_fetch_assoc($result)) {
                                    // Build team badges HTML
                                    $team_badges_html = '';
                                    $team_str = $row['team_name'] ?? 'Standard';
                                    $teams = explode(', ', $team_str);
                                     foreach ($teams as $t) {
                                        $t = trim($t);
                                        $badge_class = 'bg-secondary';
                                        switch($t) {
                                            case 'Tech Service Operations': $badge_class = 'bg-primary'; break;
                                            case 'Tech Service Delivery': $badge_class = 'bg-success'; break;
                                            case 'Central Monitoring Center': $badge_class = 'bg-info'; break;
                                            case 'Network Operations': $badge_class = 'bg-warning text-dark'; break;
                                            case 'Data Center Operations': $badge_class = 'bg-teal'; break;
                                            case 'Server Storage & Backup Management': $badge_class = 'bg-purple'; break;
                                            case 'Incident & Performance Management': $badge_class = 'bg-pink'; break;
                                            case 'Database Management': $badge_class = 'bg-indigo'; break;
                                            default: $badge_class = 'bg-secondary';
                                        }
                                        $color_only = str_replace(['bg-', ' text-dark'], '', $badge_class);
                                        $team_badges_html .= "<span class='badge $badge_class bg-opacity-10 text-$color_only border border-$color_only border-opacity-25 px-2 py-1' style='font-size: 0.65rem; letter-spacing: 0.3px;'>".e($t)."</span>";
                                    }

                                    echo "<tr>
                                            <td class='ps-4'>" . $count++ . "</td>
                                            <td>
                                                <div class='fw-bold' title='" . e($row["observation_names"] ?? "") . "'>" . e(truncate_chars($row["observation_names"] ?? "", 30, "N/A")) . "</div>
                                                <div class='text-muted' style='font-size: 0.7rem;'><i class='fa-solid fa-calendar-days me-1 scale-80'></i> " . ($row["start_date"] ? date('d M, Y h:i A', strtotime($row['start_date'])) : 'N/A') . "</div>
                                            </td>
                                            <td>
                                                <div class='text-dark fw-medium small'><i class='fa-solid fa-user-gear me-1 text-muted'></i> " . e($row['technician_name'] ?: 'N/A') . "</div>
                                            </td>
                                            <td>
                                                <div class='d-flex flex-column gap-1'>
                                                    " . $team_badges_html . "
                                                </div>
                                            </td>
                                            <td style='max-width: 250px;'>
                                                <div class='small fw-bold text-primary mb-1' title='" . e($row["l1_observations_by"] ?? "") . "'><i class='fa-solid fa-user-tag me-1 scale-80'></i> " . e(truncate_chars($row["l1_observations_by"] ?? "", 15, "N/A")) . "</div>
                                                <div class='text-muted small' title='" . e($row["l1_observation"] ?? "") . "'>" . nl2br(e(truncate_chars($row["l1_observation"] ?? "", 60, "No notes"))) . "</div>
                                            </td>
                                            <td style='max-width: 250px;'>
                                                <div class='small fw-bold text-info mb-1' title='" . e($row["l2_observations_by"] ?? "") . "'><i class='fa-solid fa-user-check me-1 scale-80'></i> " . e(truncate_chars($row["l2_observations_by"] ?? "", 15, "N/A")) . "</div>
                                                <div class='text-muted small' title='" . e($row["l2_observation"] ?? "") . "'>" . nl2br(e(truncate_chars($row["l2_observation"] ?? "", 60, "No notes"))) . "</div>
                                            </td>
                                            <td>" . ($row['l2_observation'] ? "<span class='badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1' style='font-size: 0.65rem;'>COMPLETE</span>" : "<span class='badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1' style='font-size: 0.65rem;'>PENDING</span>") . "</td>
                                            <td>
                                                <div class='d-flex gap-2'>";
                                    
                                    if (!empty($row['l1_image'])) {
                                        $l1_image_path = ASSETS_URL . '/' . ltrim($row['l1_image'], '/');
                                        echo "<img src='" . e($l1_image_path) . "' class='img-thumbnail rounded-3 shadow-sm' style='width: 35px; height: 35px; object-fit: cover; cursor: pointer;' data-bs-toggle='modal' data-bs-target='#floatModal1_" . (int)$row['serial_no'] . "'>";
                                    }
                                    
                                    if (!empty($row['l1_image_2'])) {
                                        $l1_image_2_path = ASSETS_URL . '/' . ltrim($row['l1_image_2'], '/');
                                        echo "<img src='" . e($l1_image_2_path) . "' class='img-thumbnail rounded-3 shadow-sm' style='width: 35px; height: 35px; object-fit: cover; cursor: pointer;' data-bs-toggle='modal' data-bs-target='#floatModal2_" . (int)$row['serial_no'] . "'>";
                                    }
                                    
                                    if (empty($row['l1_image']) && empty($row['l1_image_2'])) {
                                        echo "<span class='text-muted small'><i class='fa-solid fa-image-slash me-1 opacity-50'></i> None</span>";
                                    }

                                    echo "</div></td>
                                          <td>
                                              <div class='audit-stack'>";
                                    
                                    if (!empty($row['edited_by'])) {
                                        echo "<div class='small text-muted font-italic' title='Last Edited'>
                                                <i class='fa-solid fa-user-pen text-info me-1 scale-80'></i> <b>" . e($row['edited_by']) . "</b>
                                                <span class='ms-1 opacity-75' style='font-size: 0.7rem;'>" . date('d/m H:i', strtotime($row['edited_at'])) . "</span>
                                              </div>";
                                    } else {
                                        echo "<span class='text-muted small opacity-50'>-</span>";
                                    }
                                    echo "</div></td>";

                                        echo "<td class='text-end pe-4'>
                                                <div class='btn-group shadow-sm rounded'>
                                                    <button class='btn btn-white btn-sm border-end text-success' title='View Full Details' data-bs-toggle='modal' data-bs-target='#viewModal_" . (int)$row['serial_no'] . "'><i class='fa-solid fa-eye'></i></button>";
                                        
                                        if (canEditL1() || canEditL2()) {
                                            echo "<a href='update?id=" . (int)$row['serial_no'] . "' class='btn btn-white btn-sm border-end text-primary' title='Edit Record'><i class='fa-solid fa-pen-to-square'></i></a>";
                                        }
                                        if (isSuperAdmin()) {
                                            echo "<a href='delete?id=" . (int)$row['serial_no'] . "&csrf_token=" . urlencode($_SESSION['csrf_token']) . "' class='btn btn-white btn-sm text-danger border-0' title='Delete Record' onclick='return confirm(\"Are you sure you want to delete this observation?\")'>
<i class='fa-solid fa-trash-can'></i></a>";
                                        }
                                        echo "</div>
                                            </td>";
                                    
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
            ?>
            </div>
        </div>
    </div>

    <!-- Modals moved to bottom to prevent backdrop issues -->
    <?php
    if (mysqli_num_rows($result) > 0) {
        mysqli_data_seek($result, 0); // Reset result pointer
        while ($row = mysqli_fetch_assoc($result)) {
            // Rebuild team badges for the modal
            $team_badges_html = '';
            $team_str = $row['team_name'] ?? 'Standard';
            $teams = explode(', ', $team_str);
            foreach ($teams as $t) {
                $t = trim($t);
                $badge_class = 'bg-secondary';
                switch($t) {
                    case 'Tech Service Operations': $badge_class = 'bg-primary'; break;
                    case 'Tech Service Delivery': $badge_class = 'bg-success'; break;
                    case 'Central Monitoring Center': $badge_class = 'bg-info'; break;
                    case 'Network Operations': $badge_class = 'bg-warning text-dark'; break;
                    case 'Data Center Operations': $badge_class = 'bg-teal'; break;
                    case 'Server Storage & Backup Management': $badge_class = 'bg-purple'; break;
                    case 'Incident & Performance Management': $badge_class = 'bg-pink'; break;
                    case 'Database Management': $badge_class = 'bg-indigo'; break;
                    default: $badge_class = 'bg-secondary';
                }
                $color_only = str_replace(['bg-', ' text-dark'], '', $badge_class);
                $team_badges_html .= "<span class='badge $badge_class bg-opacity-10 text-$color_only border border-$color_only border-opacity-25 px-2 py-1' style='font-size: 0.65rem; letter-spacing: 0.3px;'>".htmlspecialchars($t)."</span>";
            }

            // View Details Modal
            echo "
              <div class='modal fade' id='viewModal_" . (int)$row['serial_no'] . "' tabindex='-1' aria-labelledby='viewModalLabel_" . (int)$row['serial_no'] . "' aria-hidden='true'>
                <div class='modal-dialog modal-lg modal-dialog-centered'>
                  <div class='modal-content border-0 shadow-lg'>
                    <div class='modal-header bg-primary text-white py-3'>
                      <h5 class='modal-title fw-bold' id='viewModalLabel_" . (int)$row['serial_no'] . "'><i class='fa-solid fa-magnifying-glass me-2'></i> Observation Details #" . (int)$row['serial_no'] . "</h5>
                      <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
                    </div>
                    <div class='modal-body p-4'>
                      <div class='row g-4'>
                        <div class='col-md-7'>
                          <div class='mb-4'>
                            <label class='small text-muted fw-bold text-uppercase d-block mb-1'>Observation Name</label>
                            <div class='h5 fw-bold text-primary'>" . e($row['observation_names'] ?? 'N/A') . "</div>
                            <div class='text-muted small'><i class='fa-solid fa-calendar me-1'></i> " . ($row['start_date'] ? date('d M, Y h:i A', strtotime($row['start_date'])) : 'N/A') . "</div>
                          </div>
                          <div class='mb-4'>
                            <label class='small text-muted fw-bold text-uppercase d-block mb-1'>Assigned Details</label>
                            <div class='d-flex flex-wrap gap-2 mt-1'>
                                <span class='badge bg-light text-dark border px-3 py-2 rounded-pill'><i class='fa-solid fa-user-gear me-1 text-primary'></i> Technician: " . e($row['technician_name'] ?: 'None') . "</span>
                                " . $team_badges_html . "
                            </div>
                          </div>
                          <div class='mb-4'>
                            <div class='p-3 rounded bg-light border-start border-4 border-primary'>
                              <label class='small text-primary fw-bold text-uppercase d-block mb-1'><i class='fa-solid fa-user-tag me-1'></i> L1 Observation (" . e($row['l1_observations_by'] ?? 'N/A') . ")</label>
                              <div class='text-dark'>" . nl2br(e($row['l1_observation'] ?? 'No notes provided.')) . "</div>
                            </div>
                          </div>
                          <div class='mb-0'>
                            <div class='p-3 rounded bg-light border-start border-4 border-info'>
                              <label class='small text-info fw-bold text-uppercase d-block mb-1'><i class='fa-solid fa-user-check me-1'></i> L2 Observation (" . e($row['l2_observations_by'] ?? 'N/A') . ")</label>
                              <div class='text-dark'>" . nl2br(e($row['l2_observation'] ?? 'Awaiting L2 input.')) . "</div>
                            </div>
                          </div>
                        </div>
                        <div class='col-md-5'>
                          <label class='small text-muted fw-bold text-uppercase d-block mb-3'>Evidence Images</label>
                          <div class='row g-2'>";
            if (!empty($row['l1_image'])) {
                $l1_img = ASSETS_URL . '/' . ltrim($row['l1_image'], '/');
                echo "<div class='col-6'><img src='" . e($l1_img) . "' class='img-fluid rounded border shadow-sm' style='cursor: pointer;' onclick='window.open(\"" . e($l1_img) . "\", \"_blank\")'></div>";
            }
            if (!empty($row['l1_image_2'])) {
                $l1_img2 = ASSETS_URL . '/' . ltrim($row['l1_image_2'], '/');
                echo "<div class='col-6'><img src='" . e($l1_img2) . "' class='img-fluid rounded border shadow-sm' style='cursor: pointer;' onclick='window.open(\"" . e($l1_img2) . "\", \"_blank\")'></div>";
            }
            if (empty($row['l1_image']) && empty($row['l1_image_2'])) {
                echo "<div class='col-12 text-center py-4 bg-light rounded text-muted'><i class='fa-solid fa-image-slash d-block h3 mb-2 opacity-25'></i>No Evidence Provided</div>";
            }
            echo "          </div>
                          <div class='mt-4 p-3 rounded " . ($row['l2_observation'] ? 'bg-success bg-opacity-10' : 'bg-warning bg-opacity-10') . " text-center'>
                            <div class='small fw-bold text-uppercase mb-1'>Current Status</div>
                            <div class='h6 mb-0 fw-bold " . ($row['l2_observation'] ? 'text-success' : 'text-warning') . "'>" . ($row['l2_observation'] ? 'COMPLETE' : 'PENDING') . "</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>";

            // Float modals for standard evidence check
            if (!empty($row['l1_image'])) {
                echo "
                <div class='modal modal-non-blocking fade' id='floatModal1_" . (int)$row['serial_no'] . "' tabindex='-1' data-bs-backdrop='false' data-bs-scroll='true' aria-hidden='true'>
                   <div class='modal-dialog modal-dialog-scrollable modal-lg'>
                       <div class='modal-content floating-viewer border-0'>
                           <div class='modal-header border-bottom'>
                               <h6 class='modal-title fw-bold'><i class='fa-solid fa-image me-2'></i> Evidence Review #1</h6>
                               <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                           </div>
                           <div class='modal-body text-center p-3'>
                               <img src='" . e(ASSETS_URL . "/" . ltrim($row['l1_image'], "/")) . "' class='img-fluid rounded shadow-lg'>
                           </div>
                       </div>
                   </div>
                </div>";
            }
            if (!empty($row['l1_image_2'])) {
                echo "
                <div class='modal modal-non-blocking fade' id='floatModal2_" . (int)$row['serial_no'] . "' tabindex='-1' data-bs-backdrop='false' data-bs-scroll='true' aria-hidden='true'>
                   <div class='modal-dialog modal-dialog-scrollable modal-lg'>
                       <div class='modal-content floating-viewer border-0'>
                           <div class='modal-header border-bottom'>
                               <h6 class='modal-title fw-bold'><i class='fa-solid fa-image me-2'></i> Evidence Review #2</h6>
                               <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                           </div>
                           <div class='modal-body text-center p-3'>
                               <img src='" . e(ASSETS_URL . "/" . ltrim($row['l1_image_2'], "/")) . "' class='img-fluid rounded shadow-lg'>
                           </div>
                       </div>
                   </div>
                </div>";
            }
        }
    }
    ?>
    <?php include INCLUDES_PATH . '/modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>



