<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';

if (!isSuperAdmin()) {
    header("Location: ../../index");
    exit;
}

$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total records
$count_sql = "SELECT COUNT(*) as total FROM recycle_bin";
$count_result = mysqli_query($conn, $count_sql);
$total_records = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
$total_pages = ceil($total_records / $limit);

// Fetch paginated data
$sql = "SELECT * FROM recycle_bin ORDER BY deleted_at DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Bin | Shift Handover</title>
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
            <div style="display: none;"><input type="checkbox" id="night-mode-toggle"></div>

            <div class="container-fluid ps-0 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-trash-can-arrow-up text-danger me-2"></i>Recycle Bin</h1>
                        <p class="text-muted small mb-0">View, restore, or permanently delete backed up records.</p>
                    </div>
                </div>

                <?php if ($total_records > 0): ?>
                    <div class="glass-card mb-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 dashboard-table">
                                <thead class="bg-light bg-opacity-50">
                                    <tr>
                                        <th class="ps-4">Original ID</th>
                                        <th>Module</th>
                                        <th>Table Source</th>
                                        <th>Deleted By</th>
                                        <th>Deleted At</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $modals = [];
                                    while ($row = mysqli_fetch_assoc($result)): 
                                        $modals[] = $row;
                                    ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">#<?= htmlspecialchars($row['original_id']) ?></td>
                                            <td><span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1"><?= htmlspecialchars($row['module_name']) ?></span></td>
                                            <td class="small text-muted font-monospace"><?= htmlspecialchars($row['table_name']) ?></td>
                                            <td><i class="fa-solid fa-user-xmark me-1 text-muted"></i> <?= htmlspecialchars($row['deleted_by']) ?></td>
                                            <td class="small text-muted"><i class="fa-regular fa-clock me-1"></i> <?= date('d M, Y h:i A', strtotime($row['deleted_at'])) ?></td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group shadow-sm rounded">
                                                    <button class="btn btn-white btn-sm border-end text-success" title="View Details" data-bs-toggle="modal" data-bs-target="#viewModal_<?= $row['id'] ?>"><i class="fa-solid fa-eye"></i></button>
                                                    <a href="restore?id=<?= $row['id'] ?>&csrf_token=<?= urlencode($_SESSION['csrf_token']) ?>" class="btn btn-white btn-sm border-end text-info" title="Restore Record" onclick="return confirm('Restore this record to its original module?');"><i class="fa-solid fa-rotate-left"></i></a>
                                                    <a href="delete?id=<?= $row['id'] ?>&csrf_token=<?= urlencode($_SESSION['csrf_token']) ?>" class="btn btn-white btn-sm text-danger" title="Permanently Delete" onclick="return confirm('WARNING: This will permanently eradicate the record. Continue?');"><i class="fa-solid fa-eraser"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="glass-card p-5 text-center my-5">
                        <i class="fa-solid fa-box-open fa-3x text-muted opacity-25 mb-3"></i>
                        <h4 class="text-muted fw-bold">Recycle Bin is Empty</h4>
                        <p class="text-muted mb-0">No deleted records found.</p>
                    </div>
                <?php endif; ?>

                <?php
                if ($total_pages > 1) {
                    echo '<nav aria-label="Page navigation" class="mt-4 pb-5"><ul class="pagination pagination-sm justify-content-center">';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active = ($page == $i) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link border-0 shadow-sm mx-1 rounded-3 ' . ($active ? 'bg-primary text-white' : '') . '" href="?page=' . $i . '">' . $i . '</a></li>';
                    }
                    echo '</ul></nav>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modals defined at end to avoid backdrop issues -->
    <?php foreach ($modals as $row): ?>
        <div class="modal fade" id="viewModal_<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-white border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-database text-primary me-2"></i> Record Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="ps-4 py-2 small text-uppercase opacity-50" style="width: 30%">Field</th>
                                        <th class="py-2 small text-uppercase opacity-50">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $payload = json_decode($row['data_payload'], true) ?: [];
                                        foreach ($payload as $key => $val):
                                            $displayVal = $val;
                                            // Format dates if key contains 'date' or 'at'
                                            if ($val && (strpos($key, 'date') !== false || strpos($key, 'at') !== false) && strtotime($val)) {
                                                $displayVal = '<span class="text-primary fw-bold">' . date('d M, Y h:i A', strtotime($val)) . '</span>';
                                            }
                                            // Truncate long text
                                            if (is_string($val) && strlen($val) > 100) {
                                                $displayVal = '<div class="small" style="max-height: 100px; overflow-y: auto;">' . htmlspecialchars($val) . '</div>';
                                            }
                                    ?>
                                        <tr>
                                            <td class="ps-4 py-2 fw-bold text-muted small"><?= htmlspecialchars(str_replace('_', ' ', strtoupper($key))) ?></td>
                                            <td class="py-2 small"><?= is_array($val) ? '<pre class="m-0">'.json_encode($val, JSON_PRETTY_PRINT).'</pre>' : (strpos($displayVal, '<') !== false ? $displayVal : htmlspecialchars($val)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php mysqli_close($conn); ?>
</body>
</html>
