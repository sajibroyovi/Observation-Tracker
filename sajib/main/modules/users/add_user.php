<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if (!isSuperAdmin()) {
    redirectTo(BASE_URL . '/');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for add user attempt", ['username' => $_POST['username'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $allowed_modules = isset($_POST['modules']) ? implode(',', $_POST['modules']) : '';

    // Validations
    if (empty($username) || empty($password) || empty($email)) {
        $error = "Username, Email, and Password are required.";
    } else {
        // Check if username exists using prepared statement
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            $error = "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $created_by = $_SESSION['username'];
            
            $stmt_insert = mysqli_prepare($conn, "INSERT INTO users (username, email, phone_number, password, role, allowed_modules, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "sssssss", $username, $email, $phone_number, $hashed_password, $role, $allowed_modules, $created_by);

            if (mysqli_stmt_execute($stmt_insert)) {
                mysqli_stmt_close($stmt_insert);
                showSuccess('User created successfully');
                redirectTo(BASE_URL . '/modules/users/manage');
            } else {
                log_error("Failed to insert new user", ['username' => $username, 'error' => mysqli_stmt_error($stmt_insert)]);
                $error = "Error adding user. Please check logs.";
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt_check);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provision New User | Shift Handover</title>
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-user-shield text-primary me-2"></i> Account Provisioning</h1>
                        <p class="text-muted small mb-0">Create new system identities and assign security clearance roles.</p>
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
                                            placeholder="e.g. j.doe" required autofocus>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Organization Email</label>
                                    <div class="input-group-modern">
                                        <input type="email" name="email" class="form-control bg-light border-0 shadow-sm p-3" 
                                            placeholder="e.g. j.doe@bkash.com" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">WhatsApp Number</label>
                                    <div class="input-group-modern">
                                        <input type="text" name="phone_number" class="form-control bg-light border-0 shadow-sm p-3" 
                                            placeholder="e.g. 88017..." title="International format, e.g. 8801700000000">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Security Password</label>
                                    <div class="input-group-modern">
                                        <input type="password" name="password" class="form-control bg-light border-0 shadow-sm p-3" 
                                            placeholder="Enter strong password" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Assigned Access Level</label>
                                    <select name="role" class="form-select bg-light border-0 shadow-sm p-3">
                                        <option value="l1">Level 1 Operator (L1)</option>
                                        <option value="l2">Level 2 Analyst (L2)</option>
                                        <option value="admin">Administrator</option>
                                        <option value="super_admin">Super Administrator</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-3 d-block">Module Access Control</label>
                                    <div class="d-flex flex-wrap gap-2">
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
                                        foreach ($available_modules as $title => $icon): 
                                            $mod_id = str_replace([' ', '/', '&'], '_', strtolower($title));
                                            ?>
                                            <div class="form-check p-2 px-3 rounded bg-light bg-opacity-50 border border-white border-opacity-20 shadow-sm d-flex align-items-center">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="modules[]" value="<?php echo $title; ?>" id="mod_<?php echo $mod_id; ?>">
                                                <label class="form-check-label small fw-medium text-nowrap" style="cursor: pointer;" for="mod_<?php echo $mod_id; ?>">
                                                    <i class="fa-solid <?php echo $icon; ?> me-1 opacity-75"></i> <?php echo $title; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted mt-2 d-block">Super Admins inherently have access to all modules regardless of the selection above.</small>
                                </div>

                                <div class="alert alert-info border-0 rounded-3 small mb-4 bg-primary bg-opacity-5">
                                    <i class="fa-solid fa-circle-info me-2 text-primary"></i> <b>Account Governance:</b> The creator of this account (<b><?php echo $_SESSION['username'] ?? 'Unknown'; ?></b>) will be logged as the issuing authority.
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-primary py-3 fw-bold rounded-pill shadow-lg">
                                        <i class="fa-solid fa-user-check me-2"></i> Authorize & Create Account
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
