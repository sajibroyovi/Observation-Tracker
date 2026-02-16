<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch existing record using prepared statement
    $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM service_outage WHERE serial_no = ?");
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
        log_error("CSRF token validation failed for update attempt on service_outage", ['id' => $_POST['id'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $details = cleanInput($_POST['details']);
    $incident_id = cleanInput($_POST['incident_id']);
    $problem_ticket = cleanInput($_POST['problem_ticket']);
    $status = cleanInput($_POST['status']);
    $technician = cleanInput($_POST['technician']);
    $edited_by = $_SESSION['username'];

    $sql = "UPDATE service_outage SET 
            details = ?, 
            incident_id = ?, 
            problem_ticket = ?, 
            status = ?, 
            technician = ?,
            edited_by = ?,
            edited_at = NOW() 
            WHERE serial_no = ?";

    $stmt_update = mysqli_prepare($conn, $sql);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "ssssssi", $details, $incident_id, $problem_ticket, $status, $technician, $edited_by, $id);
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            header('Location: view.php?msg=updated');
            exit;
        } else {
            log_error("Update Error for service_outage", ['id' => $id, 'error' => mysqli_stmt_error($stmt_update)]);
            echo "An error occurred while updating the record. Please try again.";
        }
        mysqli_stmt_close($stmt_update);
    } else {
        log_error("Prepare Error for service_outage update", ['error' => mysqli_error($conn)]);
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
    <title>Update Service Outage | Shift Handover</title>
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Update Service Outage</h1>
                        <p class="text-muted small mb-0">Modify incident details, technical assignments, and resolution progress.</p>
                    </div>
                    <a href="view.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
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
                                    <label for="details" class="form-label small fw-bold text-muted text-uppercase">Incident Details</label>
                                    <textarea class="form-control bg-light border-0 shadow-sm p-3" name="details" id="details" rows="4" required placeholder="Provide a detailed description of the outage..."><?php echo htmlspecialchars($row['details']); ?></textarea>
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label for="incident_id" class="form-label small fw-bold text-muted text-uppercase">Incident ID</label>
                                        <input type="text" class="form-control bg-light border-0 shadow-sm p-3" name="incident_id" id="incident_id"
                                            value="<?php echo htmlspecialchars($row['incident_id']); ?>" required placeholder="INC0000123">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="problem_ticket" class="form-label small fw-bold text-muted text-uppercase">Problem Ticket</label>
                                        <input type="text" class="form-control bg-light border-0 shadow-sm p-3" name="problem_ticket" id="problem_ticket"
                                            value="<?php echo htmlspecialchars($row['problem_ticket']); ?>" placeholder="PRB0000456">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="technician" class="form-label small fw-bold text-muted text-uppercase">Assigned Technician</label>
                                        <input type="text" class="form-control bg-light border-0 shadow-sm p-3" name="technician" id="technician"
                                            value="<?php echo htmlspecialchars($row['technician']); ?>" required placeholder="Name of technician">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="status" class="form-label small fw-bold text-muted text-uppercase">Resolution Status</label>
                                        <select class="form-select bg-light border-0 shadow-sm p-3" name="status" id="status" required>
                                            <option value="resolved" <?php if ($row['status'] == 'resolved') echo 'selected'; ?>>Resolved</option>
                                            <option value="in_progress" <?php if ($row['status'] == 'in_progress') echo 'selected'; ?>>In Progress</option>
                                            <option value="pending" <?php if ($row['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="alert alert-danger border-0 rounded-3 small mb-4 bg-danger bg-opacity-10 text-danger">
                                    <i class="fa-solid fa-user-pen me-2"></i> Last update by <b><?php echo htmlspecialchars($row['edited_by'] ?? 'Initial Reporter'); ?></b> on <?php echo date('d M, Y H:i', strtotime($row['edited_at'] ?? $row['created_at'])); ?>
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-danger py-3 fw-bold rounded-pill shadow-lg">
                                        <i class="fa-solid fa-check-circle me-2"></i> Confirm Incident Update
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



