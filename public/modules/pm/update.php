<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch existing record using prepared statement
    $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM pending_mail WHERE serial_no = ?");
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
        log_error("CSRF token validation failed for update attempt on pending_mail", ['id' => $_POST['id'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $subject_line = cleanInput($_POST['subject_line']);
    $priority = cleanInput($_POST['priority']);
    $status = cleanInput($_POST['status']);
    $edited_by = $_SESSION['username'];

    $sql = "UPDATE pending_mail SET 
            subject_line = ?, 
            priority = ?, 
            status = ?,
            edited_by = ?,
            edited_at = NOW() 
            WHERE serial_no = ?";

    $stmt_update = mysqli_prepare($conn, $sql);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "ssssi", $subject_line, $priority, $status, $edited_by, $id);
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            header('Location: view.php?msg=updated');
            exit;
        } else {
            log_error("Update Error for pending_mail", ['id' => $id, 'error' => mysqli_stmt_error($stmt_update)]);
            echo "An error occurred while updating the record. Please try again.";
        }
        mysqli_stmt_close($stmt_update);
    } else {
        log_error("Prepare Error for pending_mail update", ['error' => mysqli_error($conn)]);
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
    <title>Update Pending Mail | Shift Handover</title>
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-envelope-open-text text-accent-pink me-2"></i> Update Pending Mail</h1>
                        <p class="text-muted small mb-0">Modify communication threads, priority levels, and follow-up status.</p>
                    </div>
                    <a href="view.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
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
                                    <label for="subject_line" class="form-label small fw-bold text-muted text-uppercase">Email Subject Line</label>
                                    <input type="text" class="form-control bg-light border-0 shadow-sm p-3" name="subject_line" id="subject_line"
                                        value="<?php echo htmlspecialchars($row['subject_line']); ?>" required placeholder="e.g. Server Migration Follow-up">
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label for="priority" class="form-label small fw-bold text-muted text-uppercase">Urgency Level</label>
                                        <select class="form-select bg-light border-0 shadow-sm p-3" name="priority" id="priority" required>
                                            <option value="high" <?php if ($row['priority'] == 'high') echo 'selected'; ?>>High Priority</option>
                                            <option value="medium" <?php if ($row['priority'] == 'medium') echo 'selected'; ?>>Medium Priority</option>
                                            <option value="low" <?php if ($row['priority'] == 'low') echo 'selected'; ?>>Low Priority</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="status" class="form-label small fw-bold text-muted text-uppercase">Thread Status</label>
                                        <select class="form-select bg-light border-0 shadow-sm p-3" name="status" id="status" required>
                                            <option value="pending" <?php if ($row['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                            <option value="follow_up" <?php if ($row['status'] == 'follow_up') echo 'selected'; ?>>Follow Up</option>
                                            <option value="answered" <?php if ($row['status'] == 'answered') echo 'selected'; ?>>Answered</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="alert alert-info border-0 rounded-3 small mb-4 bg-pink bg-opacity-10 text-pink">
                                    <i class="fa-solid fa-user-edit me-2"></i> Mail Record Audit: Updated by <b><?php echo htmlspecialchars($row['edited_by'] ?? 'System'); ?></b> on <?php echo date('d M, Y H:i', strtotime($row['edited_at'] ?? $row['created_at'])); ?>
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn py-3 fw-bold rounded-pill shadow-lg" style="background: var(--accent-pink); border: none; color: white;">
                                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> Update Communication Status
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



