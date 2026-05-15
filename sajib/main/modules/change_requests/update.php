<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch existing record using prepared statement
    $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM cr_list WHERE serial_no = ?");
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
        log_error("CSRF token validation failed for update attempt on cr_list", ['id' => $_POST['id'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $cr_subject = cleanInput($_POST['cr_subject']);
    $impacted_area = cleanInput($_POST['impacted_area']);
    $cr_start_time = cleanInput($_POST['cr_start_time']);
    $cr_end_time = cleanInput($_POST['cr_end_time']);
    $downtime = cleanInput($_POST['downtime']);
    $cr_meeting_attended = cleanInput($_POST['cr_meeting_attended']);
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);
    $edited_by = $_SESSION['username'];

    $sql = "UPDATE cr_list SET 
            cr_subject = ?, 
            impacted_area = ?, 
            cr_start_time = ?, 
            cr_end_time = ?, 
            downtime = ?, 
            cr_meeting_attended = ?,
            handed_over_to = ?,
            handover_date = ?,
            edited_by = ?,
            edited_at = NOW() 
            WHERE serial_no = ?";

    $stmt_update = mysqli_prepare($conn, $sql);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "sssssssssi", $cr_subject, $impacted_area, $cr_start_time, $cr_end_time, $downtime, $cr_meeting_attended, $handed_over_to, $handover_date, $edited_by, $id);
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            showSuccess('Record updated successfully');
            redirectTo(BASE_URL . '/modules/change_requests/view');
        } else {
            log_error("Update Error for cr_list", ['id' => $id, 'error' => mysqli_stmt_error($stmt_update)]);
            showError('An error occurred while updating the record. Please try again.');
            redirectTo(BASE_URL . '/modules/change_requests/view');
        }
        mysqli_stmt_close($stmt_update);
    } else {
        log_error("Prepare Error for cr_list update", ['error' => mysqli_error($conn)]);
        echo "Critical Error: Internal Server Error.";
    }

    mysqli_close($conn);
    exit;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Change Request | Shift Handover</title>
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-file-pen text-info me-2"></i> Update Change Request</h1>
                        <p class="text-muted small mb-0">Modify CR details, impact analysis, and implementation timeline.</p>
                    </div>
                    <a href="view" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-9 col-lg-8">
                        <div class="glass-card p-5 shadow-lg border-0">
                            <form method="POST" action="">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $row['serial_no']; ?>">

                                <div class="mb-4">
                                    <label for="cr_subject" class="form-label small fw-bold text-muted text-uppercase">CR Subject / ID / Details</label>
                                    <textarea class="form-control bg-light border-0 shadow-sm p-3" name="cr_subject" id="cr_subject" rows="2" required placeholder="e.g. CR-2023-045 - Kernel Upgrade"><?php echo htmlspecialchars($row['cr_subject']); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label for="impacted_area" class="form-label small fw-bold text-muted text-uppercase">Impacted Area & Services</label>
                                    <textarea class="form-control bg-light border-0 shadow-sm p-3" name="impacted_area" id="impacted_area" rows="3" required placeholder="List all services affected by this change..."><?php echo htmlspecialchars($row['impacted_area']); ?></textarea>
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label for="cr_start_time" class="form-label small fw-bold text-muted text-uppercase">Execution Start</label>
                                        <input type="datetime-local" class="form-control bg-light border-0 shadow-sm p-3" name="cr_start_time" id="cr_start_time"
                                            value="<?php echo date('Y-m-d\TH:i', strtotime($row['cr_start_time'])); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="cr_end_time" class="form-label small fw-bold text-muted text-uppercase">Execution End</label>
                                        <input type="datetime-local" class="form-control bg-light border-0 shadow-sm p-3" name="cr_end_time" id="cr_end_time"
                                            value="<?php echo date('Y-m-d\TH:i', strtotime($row['cr_end_time'])); ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="downtime" class="form-label small fw-bold text-muted text-uppercase">Downtime Nature</label>
                                        <select class="form-select bg-light border-0 shadow-sm p-3" name="downtime" id="downtime" required>
                                            <option value="1" <?php if ($row['downtime'] == '1') echo 'selected'; ?>>No Downtime</option>
                                            <option value="0" <?php if ($row['downtime'] == '0') echo 'selected'; ?>>Service Downtime</option>
                                            <option value="2" <?php if ($row['downtime'] == '2') echo 'selected'; ?>>Service Fluctuation</option>
                                            <option value="3" <?php if ($row['downtime'] == '3') echo 'selected'; ?>>Not Applicable</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="cr_meeting_attended" class="form-label small fw-bold text-muted text-uppercase">Stakeholder Attendee</label>
                                        <input type="text" class="form-control bg-light border-0 shadow-sm p-3" name="cr_meeting_attended" id="cr_meeting_attended"
                                            value="<?php echo htmlspecialchars($row['cr_meeting_attended']); ?>" placeholder="Name of attendee">
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
                                    <i class="fa-solid fa-history me-2"></i> Audit Record: Modified by <b><?php echo htmlspecialchars($row['edited_by'] ?? 'Initial Creator'); ?></b> on <?php echo date('d M, Y H:i', strtotime($row['edited_at'] ?? $row['created_at'])); ?>
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-info py-3 fw-bold rounded-pill shadow-lg text-white">
                                        <i class="fa-solid fa-save me-2"></i> Update Change Request
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



