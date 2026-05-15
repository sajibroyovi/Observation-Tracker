<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if (!isSuperAdmin()) {
    header('Location: ' . BASE_URL . '/');
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt_fetch = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_fetch, "i", $id);
mysqli_stmt_execute($stmt_fetch);
$result = mysqli_stmt_get_result($stmt_fetch);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_fetch);

if (!$user) {
    echo "User not found.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for update attempt on user", ['id' => $id]);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $phone_number = cleanInput($_POST['phone_number'] ?? '');
    $role = cleanInput($_POST['role']);
    $allowed_modules = isset($_POST['modules']) ? implode(',', $_POST['modules']) : '';
    $password = $_POST['password'];
    $edited_by = $_SESSION['username'];

    // Check if username already exists for another user
    $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
    mysqli_stmt_bind_param($stmt_check, "si", $username, $id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        $error = "Username already exists. Please choose another one.";
        mysqli_stmt_close($stmt_check);
    } else {
        mysqli_stmt_close($stmt_check);
        $fields = ["username = ?", "email = ?", "phone_number = ?", "role = ?", "allowed_modules = ?", "edited_by = ?", "edited_at = NOW()"];
        $params = [$username, $email, $phone_number, $role, $allowed_modules, $edited_by];
        $types = "ssssss";

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $fields[] = "password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
    $params[] = $id;
    $types .= "i";

        $stmt_update = mysqli_prepare($conn, $sql);
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, $types, ...$params);
            if (mysqli_stmt_execute($stmt_update)) {
                mysqli_stmt_close($stmt_update);
                showSuccess('User updated successfully');
                redirectTo('manage');
            } else {
                log_error("Update Error for user", ['id' => $id, 'error' => mysqli_stmt_error($stmt_update)]);
                $error = "Error updating user. Please check logs.";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            log_error("Prepare Error for user update", ['error' => mysqli_error($conn)]);
            $error = "Critical Error: Internal server error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Permissions | Shift Handover</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <script src="<?= ASSETS_URL ?>/js/script.js" defer></script>
</head>

<body>
    <div class="dashboard-container">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        <div class="main-content">
            <div class="container-fluid ps-0 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-user-pen text-success me-2"></i> Authority Adjustment</h1>
                        <p class="text-muted small mb-0">Modify security roles and refresh authentication credentials for <b><?php echo htmlspecialchars($user['username']); ?></b>.</p>
                    </div>
                    <a href="manage" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Users
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="glass-card p-5 shadow-lg border-0">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger border-0 rounded-3 mb-4 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <?php echo getCsrfField(); ?>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">System Username</label>
                                    <div class="input-group-modern">
                                        <input type="text" name="username" class="form-control bg-light border-0 shadow-sm p-3" 
                                            value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Organization Email</label>
                                    <div class="input-group-modern">
                                        <input type="email" name="email" class="form-control bg-light border-0 shadow-sm p-3" 
                                            value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="e.g. j.doe@bkash.com" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">WhatsApp Number</label>
                                    <div class="input-group-modern">
                                        <input type="text" name="phone_number" class="form-control bg-light border-0 shadow-sm p-3" 
                                            value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="88017...">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Assigned Access Level</label>
                                    <select name="role" class="form-select bg-light border-0 shadow-sm p-3">
                                        <option value="l1" <?php if ($user['role'] == 'l1') echo 'selected'; ?>>Level 1 Operator (L1)</option>
                                        <option value="l2" <?php if ($user['role'] == 'l2') echo 'selected'; ?>>Level 2 Analyst (L2)</option>
                                        <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Administrator</option>
                                        <option value="super_admin" <?php if ($user['role'] == 'super_admin') echo 'selected'; ?>>Super Administrator</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-3 d-block">Module Access Control</label>
                                    <div class="row g-3">
                                        <?php
                                        $available_modules = [
                                            'Enable/Disable' => 'fa-toggle-on',
                                            'Pending Mail' => 'fa-envelope',
                                            'Security Mail' => 'fa-shield-halved',
                                            'CR List' => 'fa-file-invoice',
                                            'Promo Banner' => 'fa-image',
                                            'Service Outage' => 'fa-triangle-exclamation',
                                            'SSL Certificate' => 'fa-lock',
                                            'Campaign' => 'fa-bullhorn',
                                            'Observations' => 'fa-clipboard-check'
                                        ];
                                        $current_modules = !empty($user['allowed_modules']) ? explode(',', $user['allowed_modules']) : [];
                                        
                                        foreach ($available_modules as $title => $icon): 
                                            $mod_id = str_replace([' ', '/', '&'], '_', strtolower($title));
                                            ?>
                                            <div class="col-6">
                                                <div class="form-check p-2 rounded bg-light bg-opacity-50 border border-white border-opacity-20 shadow-sm">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="modules[]" value="<?php echo $title; ?>" id="mod_<?php echo $mod_id; ?>" <?php echo in_array($title, $current_modules) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small fw-medium" style="cursor: pointer;" for="mod_<?php echo $mod_id; ?>">
                                                        <i class="fa-solid <?php echo $icon; ?> me-1 opacity-75"></i> <?php echo $title; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted mt-2 d-block">Super Admins inherently have access to all modules regardless of the selection above.</small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Reset Security Password</label>
                                    <div class="input-group-modern">
                                        <input type="password" name="password" class="form-control bg-light border-0 shadow-sm p-3" 
                                            placeholder="Leave blank to maintain current credentials">
                                    </div>
                                    <small class="text-muted italic mt-2 d-block">System policy: Passwords must be obfuscated and encrypted.</small>
                                </div>

                                <div class="alert alert-success border-0 rounded-3 small mb-4 bg-success bg-opacity-5">
                                    <i class="fa-solid fa-clock-rotate-left me-2 text-success"></i> <b>Traceability Log:</b> Last administrative action recorded on <?php echo !empty($user['edited_at']) ? date('d M, Y H:i', strtotime($user['edited_at'])) : date('d M, Y H:i', strtotime($user['created_at'])); ?> by <b><?php echo htmlspecialchars($user['edited_by'] ?? $user['created_by'] ?? 'System'); ?></b>.
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-success py-3 fw-bold rounded-pill shadow-lg text-white">
                                        <i class="fa-solid fa-shield-check me-2"></i> Commit Permission Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
