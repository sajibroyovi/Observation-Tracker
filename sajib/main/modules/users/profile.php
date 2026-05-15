<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    redirectTo(BASE_URL . "/login");
}

$stmt_fetch = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
mysqli_stmt_execute($stmt_fetch);
$result = mysqli_stmt_get_result($stmt_fetch);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_fetch);

if (!$user) {
    die("User session error.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password)) {
        $error = "Password cannot be empty.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_update = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, "si", $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $success = "Password updated successfully.";
        } else {
            $error = "Failed to update password. Please try again.";
        }
        mysqli_stmt_close($stmt_update);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Shift Handover</title>
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/uploads/bkash_logo.png">
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-user-circle text-primary me-2"></i> My Profile</h1>
                        <p class="text-muted small mb-0">Manage your account information and security settings.</p>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="glass-card p-5 shadow-lg border-0">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger border-0 rounded-3 mb-4 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($success)): ?>
                                <div class="alert alert-success border-0 rounded-3 mb-4 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mb-5 border-bottom pb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-3 d-block">Account Information</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 rounded bg-light bg-opacity-50 border border-white border-opacity-20 shadow-sm">
                                            <small class="text-muted d-block mb-1">Username</small>
                                            <span class="fw-bold"><?php echo htmlspecialchars($user['username']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 rounded bg-light bg-opacity-50 border border-white border-opacity-20 shadow-sm">
                                            <small class="text-muted d-block mb-1">Security Role</small>
                                            <span class="fw-bold text-uppercase"><?php echo htmlspecialchars($user['role']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form method="POST">
                                <?php echo getCsrfField(); ?>
                                <label class="form-label small fw-bold text-muted text-uppercase mb-3 d-block">Change Password</label>
                                <div class="mb-4">
                                    <div class="input-group-modern">
                                        <input type="password" name="new_password" class="form-control bg-light border-0 shadow-sm p-3" 
                                            placeholder="Enter new password" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="input-group-modern">
                                        <input type="password" name="confirm_password" class="form-control bg-light border-0 shadow-sm p-3" 
                                            placeholder="Confirm new password" required>
                                    </div>
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-primary py-3 fw-bold rounded-pill shadow-lg text-white">
                                        <i class="fa-solid fa-key me-2"></i> Update Security Password
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
