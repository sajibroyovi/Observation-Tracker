<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch existing record using prepared statement
    $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM promo_banner WHERE serial_no = ?");
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
        log_error("CSRF token validation failed for update attempt on promo_banner", ['id' => $_POST['id'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $subject_line = cleanInput($_POST['subject_line']);
    $status = cleanInput($_POST['status']);
    $start_date = cleanInput($_POST['start_date']);
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);
    $edited_by = $_SESSION['username'];

    $sql = "UPDATE promo_banner SET 
            subject_line = ?, 
            status = ?, 
            start_time = ?,
            handed_over_to = ?,
            handover_date = ?,
            edited_by = ?,
            edited_at = NOW() 
            WHERE serial_no = ?";

    $stmt_update = mysqli_prepare($conn, $sql);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "ssssssi", $subject_line, $status, $start_date, $handed_over_to, $handover_date, $edited_by, $id);
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            showSuccess('Record updated successfully');
            redirectTo(BASE_URL . '/modules/banners/view');
        } else {
            log_error("Update Error for promo_banner", ['id' => $id, 'error' => mysqli_stmt_error($stmt_update)]);
            showError('An error occurred while updating the record. Please try again.');
            redirectTo(BASE_URL . '/modules/banners/view');
        }
        mysqli_stmt_close($stmt_update);
    } else {
        log_error("Prepare Error for promo_banner update", ['error' => mysqli_error($conn)]);
        echo "Critical Error: Internal Server Error.";
    }

    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Promo Banner | Shift Handover</title>
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Update Promo Banner</h1>
                        <p class="text-muted small mb-0">Modify banner details, status, and scheduling information.</p>
                    </div>
                    <a href="view" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="glass-card p-5 shadow-lg border-0">
                            <form method="POST" action="">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $row['serial_no']; ?>">

                                <div class="mb-4">
                                    <label for="subject_line" class="form-label small fw-bold text-muted text-uppercase">Subject / Campaign Title</label>
                                    <textarea class="form-control bg-light border-0 shadow-sm p-3" name="subject_line" id="subject_line" rows="3" required placeholder="Describe the banner purpose..."><?php echo htmlspecialchars($row['subject_line']); ?></textarea>
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label small fw-bold text-muted text-uppercase">Start Date & Time</label>
                                        <input type="datetime-local" class="form-control bg-light border-0 shadow-sm p-3" name="start_date" id="start_date"
                                            value="<?php echo date('Y-m-d\TH:i', strtotime($row['start_time'])); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="status" class="form-label small fw-bold text-muted text-uppercase">Banner Status</label>
                                        <select class="form-select bg-light border-0 shadow-sm p-3" name="status" id="status" required>
                                            <option value="live" <?php if ($row['status'] == 'live') echo 'selected'; ?>>Live</option>
                                            <option value="scheduled" <?php if ($row['status'] == 'scheduled') echo 'selected'; ?>>Scheduled</option>
                                            <option value="draft" <?php if ($row['status'] == 'draft') echo 'selected'; ?>>Draft</option>
                                            <option value="inactive" <?php if ($row['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label for="handed_over_to" class="form-label small fw-bold text-muted text-uppercase">Handed over to</label>
                                        <select class="form-select bg-light border-0 shadow-sm p-3" name="handed_over_to" id="handed_over_to" required>
                                            <option value="Morning" <?php if ($row['handed_over_to'] == 'Morning') echo 'selected'; ?>>Morning</option>
                                            <option value="Evening" <?php if ($row['handed_over_to'] == 'Evening') echo 'selected'; ?>>Evening</option>
                                            <option value="Night" <?php if ($row['handed_over_to'] == 'Night') echo 'selected'; ?>>Night</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="handover_date" class="form-label small fw-bold text-muted text-uppercase">Handover Date</label>
                                        <input type="date" class="form-control bg-light border-0 shadow-sm p-3" name="handover_date" id="handover_date"
                                            value="<?php echo htmlspecialchars($row['handover_date'] ?? date('Y-m-d')); ?>" required>
                                    </div>
                                </div>

                                <div class="alert alert-info border-0 rounded-3 small mb-4">
                                    <i class="fa-solid fa-circle-info me-2"></i> Last edited by <b><?php echo htmlspecialchars($row['edited_by'] ?? 'System'); ?></b> on <?php echo date('d M, Y H:i', strtotime($row['edited_at'] ?? $row['created_at'])); ?>
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-primary py-3 fw-bold rounded-pill shadow-lg">
                                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> Update Record
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



