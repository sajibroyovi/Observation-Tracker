<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if (!isSuperAdmin()) {
    header("Location: " . BASE_URL . "/index.php");
    exit();
}

// Fetch all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Shift Handover</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        <div class="main-content">
            <div class="container-fluid ps-0 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-users-gear text-primary me-2"></i> User Management</h1>
                        <p class="text-muted small mb-0">Control system access, define specialized roles, and audit account activities.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="add_user.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                            <i class="fa-solid fa-user-plus me-2"></i> Provision New User
                        </a>
                    </div>
                </div>

                <div class="glass-card shadow-lg border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 dashboard-table">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3">User Profile</th>
                                    <th class="py-3 text-center">Security Role</th>
                                    <th class="py-3">Created By</th>
                                    <th class="py-3">Audit Log (Created)</th>
                                    <th class="py-3">Last Adjusted By</th>
                                    <th class="py-3 text-end pe-4">Control Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while ($result && $row = mysqli_fetch_assoc($result)) {
                                    $role_badge = '';
                                    switch ($row['role']) {
                                        case 'super_admin':
                                            $role_badge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2">Super Admin</span>';
                                            break;
                                        case 'admin':
                                            $role_badge = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">Administrator</span>';
                                            break;
                                        case 'l2':
                                            $role_badge = '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-2">L2 Analyst</span>';
                                            break;
                                        case 'l1':
                                            $role_badge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-2">L1 Operator</span>';
                                            break;
                                        default:
                                            $role_badge = '<span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-3 py-2">' . strtoupper($row['role']) . '</span>';
                                    }

                                    $created_by = !empty($row['created_by']) ? e($row['created_by']) : '<span class="text-muted italic small">System-Init</span>';
                                    $updated_by = !empty($row['edited_by']) ? e($row['edited_by']) : '<span class="text-muted italic small">-</span>';
                                    
                                    echo "<tr>
                                            <td class='ps-4'>
                                                <div class='d-flex align-items-center'>
                                                    <div class='avatar-circle me-3' style='background: var(--primary-light); color: var(--primary-blue);'>
                                                        <i class='fa-solid fa-user-tie'></i>
                                                    </div>
                                                    <div>
                                                        <div class='fw-bold text-dark'>" . e($row['username']) . "</div>
                                                        <div class='text-muted' style='font-size: 11px;'>ID: #USR-" . str_pad((int)$row['id'], 3, '0', STR_PAD_LEFT) . "</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class='text-center'>{$role_badge}</td>
                                            <td>
                                                <div class='d-flex align-items-center'>
                                                    <div class='text-dark fw-medium small'>{$created_by}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class='small text-dark'>" . date('d M, Y', strtotime($row['created_at'])) . "</div>
                                                <div class='text-muted' style='font-size: 11px;'><i class='fa-regular fa-clock me-1'></i>" . date('H:i', strtotime($row['created_at'])) . "</div>
                                            </td>
                                            <td>
                                                <div class='text-dark small'>{$updated_by}</div>
                                                <div class='text-muted small' style='font-size: 10px;'>" . (!empty($row['edited_at']) ? date('d M, H:i', strtotime($row['edited_at'])) : '-') . "</div>
                                            </td>
                                            <td class='text-end pe-4'>
                                                <div class='btn-group shadow-sm rounded-pill overflow-hidden'>
                                                    <a href='edit_user.php?id=" . (int)$row['id'] . "' class='btn btn-light btn-sm border-end' title='Edit User Profile'>
                                                        <i class='fa-solid fa-user-pen text-info'></i>
                                                    </a>
                                                    <a href='delete_user.php?id=" . (int)$row['id'] . "&csrf_token=" . urlencode($_SESSION['csrf_token']) . "' class='btn btn-light btn-sm' onclick='return confirm(\"Security Alert: Proceed with account termination?\")' title='Revoke Access'>
                                                        <i class='fa-solid fa-user-slash text-danger'></i>
                                                    </a>
                                                </div>
                                            </td>
                                          </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= ASSETS_URL ?>/js/script.js" defer></script>
</body>

</html>
