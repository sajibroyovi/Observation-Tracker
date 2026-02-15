<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch existing record using prepared statement
    $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM ssl_certificate WHERE serial_no = ?");
    mysqli_stmt_bind_param($stmt_fetch, "i", $id);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);
    
    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
    } else {
        echo "Record not found";
        exit;
    }
    mysqli_stmt_close($stmt_fetch);
} else {
    echo "No ID provided";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for update attempt on ssl_certificate", ['id' => $_POST['id'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $certificate_name = cleanInput($_POST['certificate_name']);
    $expiration_date = cleanInput($_POST['expiration_date']);
    $renewal_status = cleanInput($_POST['renewal_status']);
    $issues = cleanInput($_POST['issues']);
    $edited_by = $_SESSION['username'];

    // Update record using prepared statement
    $sql = "UPDATE ssl_certificate SET 
            certificate_name = ?, 
            expiration_date = ?, 
            renewal_status = ?, 
            issues = ?,
            edited_by = ?,
            edited_at = NOW() 
            WHERE serial_no = ?";

    $stmt_update = mysqli_prepare($conn, $sql);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "sssssi", $certificate_name, $expiration_date, $renewal_status, $issues, $edited_by, $id);
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            header("Location: view.php?msg=updated");
            exit;
        } else {
            log_error("Update Error for ssl_certificate", ['id' => $id, 'error' => mysqli_stmt_error($stmt_update)]);
            $error = "An error occurred while updating the record. Please try again.";
        }
        mysqli_stmt_close($stmt_update);
    } else {
        log_error("Prepare Error for ssl_certificate update", ['error' => mysqli_error($conn)]);
        $error = "Critical Error: Internal Server Error.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update SSL Certificate | Shift Handover</title>
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-file-shield text-purple me-2"></i> Update SSL Certificate</h1>
                        <p class="text-muted small mb-0">Modify certificate properties, expiration dates, and tracking status.</p>
                    </div>
                    <a href="view.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="glass-card p-5 shadow-lg border-0">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <form method="POST" action="">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $row['serial_no']; ?>">

                                <div class="mb-4">
                                    <label for="certificate_name" class="form-label small fw-bold text-muted text-uppercase">Certificate Name / Domain</label>
                                    <input type="text" class="form-control bg-light border-0 shadow-sm p-3" name="certificate_name" id="certificate_name"
                                        value="<?php echo htmlspecialchars($row['certificate_name']); ?>" required placeholder="e.g. *.example.com">
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label for="expiration_date" class="form-label small fw-bold text-muted text-uppercase">Expiration Date</label>
                                        <input type="date" class="form-control bg-light border-0 shadow-sm p-3" name="expiration_date" id="expiration_date"
                                            value="<?php echo $row['expiration_date']; ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="renewal_status" class="form-label small fw-bold text-muted text-uppercase">Renewal Status</label>
                                        <select class="form-select bg-light border-0 shadow-sm p-3" name="renewal_status" id="renewal_status" required>
                                            <option value="renewed" <?php if ($row['renewal_status'] == 'renewed') echo 'selected'; ?>>Renewed</option>
                                            <option value="pending" <?php if ($row['renewal_status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                            <option value="failed" <?php if ($row['renewal_status'] == 'failed') echo 'selected'; ?>>Failed</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="issues" class="form-label small fw-bold text-muted text-uppercase">Known Certificate Issues</label>
                                    <textarea class="form-control bg-light border-0 shadow-sm p-3" name="issues" id="issues" rows="3" placeholder="Describe any technical issues..."><?php echo htmlspecialchars($row['issues']); ?></textarea>
                                </div>

                                <div class="alert alert-primary border-0 rounded-3 small mb-4 bg-purple bg-opacity-10 text-purple">
                                    <i class="fa-solid fa-shield-halved me-2"></i> Security Audit: Last modified by <b><?php echo htmlspecialchars($row['edited_by'] ?? 'System'); ?></b> on <?php echo date('d M, Y H:i', strtotime($row['edited_at'] ?? $row['created_at'])); ?>
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-purple py-3 fw-bold rounded-pill shadow-lg text-white" style="background: #7c3aed;">
                                        <i class="fa-solid fa-lock-open me-2"></i> Update Security Certificate
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- End container-fluid -->
        </div> <!-- End main-content -->
    </div> <!-- End dashboard-container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>



