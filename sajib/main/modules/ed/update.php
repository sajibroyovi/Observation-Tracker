<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch existing record using prepared statement
    $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM enable_disable WHERE serial_no = ?");
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
        log_error("CSRF token validation failed for update attempt on enable_disable", ['id' => $_POST['id'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $service_name = cleanInput($_POST['service_name']);
    $action_date = cleanInput($_POST['action_date']);
    $action_taken = cleanInput($_POST['action_taken']);
    $action_taken_by = cleanInput($_POST['action_taken_by']);
    $reference = cleanInput($_POST['reference']);
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);
    $edited_by = $_SESSION['username'];

    $sql = "UPDATE enable_disable SET 
            service_name = ?, 
            action_date = ?, 
            action_taken = ?, 
            action_taken_by = ?, 
            reference = ?,
            handed_over_to = ?,
            handover_date = ?,
            edited_by = ?,
            edited_at = NOW() 
            WHERE serial_no = ?";

    $stmt_update = mysqli_prepare($conn, $sql);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "ssssssssi", $service_name, $action_date, $action_taken, $action_taken_by, $reference, $handed_over_to, $handover_date, $edited_by, $id);
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            $redirect = $_SERVER['HTTP_REFERER'] ?? 'view?msg=updated';
            if (strpos($redirect, 'update') !== false) $redirect = 'view?msg=updated';
            header("Location: $redirect");
            exit;
        } else {
            log_error("Update Error for enable_disable", ['id' => $id, 'error' => mysqli_stmt_error($stmt_update)]);
            echo "An error occurred while updating the record. Please try again.";
        }
        mysqli_stmt_close($stmt_update);
    } else {
        log_error("Prepare Error for enable_disable update", ['error' => mysqli_error($conn)]);
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
    <title>Update Service Status | Shift Handover</title>
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-toggle-on text-primary me-2"></i> Update Service Status</h1>
                        <p class="text-muted small mb-0">Modify service reachability toggles and update reference documentation.</p>
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
                                    <label for="service_name" class="form-label small fw-bold text-muted text-uppercase">Service Name</label>
                                    <textarea class="form-control bg-light border-0 shadow-sm p-3" name="service_name" id="service_name" rows="2" required placeholder="e.g. Mobile App API Gateway"><?php echo htmlspecialchars($row['service_name']); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label for="action_date" class="form-label small fw-bold text-muted text-uppercase">Action Date & Time</label>
                                    <input type="datetime-local" class="form-control bg-light border-0 shadow-sm p-3" name="action_date" id="action_date"
                                        value="<?php echo date('Y-m-d\TH:i', strtotime($row['action_date'])); ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label for="action_taken" class="form-label small fw-bold text-muted text-uppercase">Current Status Action</label>
                                    <select class="form-select bg-light border-0 shadow-sm p-3" name="action_taken" id="action_taken" required>
                                        <option value="0" <?php if ($row['action_taken'] == '0') echo 'selected'; ?>>Enable Service</option>
                                        <option value="1" <?php if ($row['action_taken'] == '1') echo 'selected'; ?>>Disable Service</option>
                                        <option value="2" <?php if ($row['action_taken'] == '2') echo 'selected'; ?>>Hide Service</option>
                                        <option value="3" <?php if ($row['action_taken'] == '3') echo 'selected'; ?>>Unhide Service</option>
                                    </select>
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label for="action_taken_by" class="form-label small fw-bold text-muted text-uppercase">Executed By</label>
                                        <input type="text" class="form-control bg-light border-0 shadow-sm p-3" name="action_taken_by" id="action_taken_by"
                                            value="<?php echo htmlspecialchars($row['action_taken_by']); ?>" required placeholder="Operator Name">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="reference" class="form-label small fw-bold text-muted text-uppercase">Reference Ticket</label>
                                        <input type="text" class="form-control bg-light border-0 shadow-sm p-3" name="reference" id="reference"
                                            value="<?php echo htmlspecialchars($row['reference']); ?>" required placeholder="REF-ID-123">
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
                                    <i class="fa-solid fa-user-check me-2"></i> Audit Trail: Last verified by <b><?php echo htmlspecialchars($row['edited_by'] ?? 'System'); ?></b> on <?php echo date('d M, Y H:i', strtotime($row['edited_at'] ?? $row['created_at'])); ?>
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-primary py-3 fw-bold rounded-pill shadow-lg">
                                        <i class="fa-solid fa-sync me-2"></i> Update Service Status
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



