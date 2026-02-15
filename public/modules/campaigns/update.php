<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch existing record using prepared statement
    $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM campaign WHERE serial_no = ?");
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
        log_error("CSRF token validation failed for update attempt on campaign", ['id' => $_POST['id'] ?? 'unknown']);
        die("Security Validation Failed: CSRF Token Mismatch.");
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $campaign_name = cleanInput($_POST['campaign_name']);
    $start_date = cleanInput($_POST['start_date']);
    $status = cleanInput($_POST['status']);
    $description = cleanInput($_POST['description']);
    $edited_by = $_SESSION['username'];

    $sql = "UPDATE campaign SET
            campaign_name = ?,
            start_date = ?,
            status = ?,
            description = ?,
            edited_by = ?,
            edited_at = NOW()
            WHERE serial_no = ?";

    $stmt_update = mysqli_prepare($conn, $sql);
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, "sssssi", $campaign_name, $start_date, $status, $description, $edited_by, $id);
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            header('Location: view.php?msg=updated');
            exit;
        } else {
            log_error("Update Error for campaign", ['id' => $id, 'error' => mysqli_stmt_error($stmt_update)]);
            echo "An error occurred while updating the record. Please try again.";
        }
        mysqli_stmt_close($stmt_update);
    } else {
        log_error("Prepare Error for campaign update", ['error' => mysqli_error($conn)]);
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
    <title>Update Campaign | Shift Handover</title>
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-accent-blue me-2"></i> Update Campaign</h1>
                        <p class="text-muted small mb-0">Modify campaign details, scheduling, and lifecycle status.</p>
                    </div>
                    <a href="view.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-7">
                        <div class="glass-card p-5 shadow-lg border-0">
                            <form method="POST" action="">
                                <?php echo getCsrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $row['serial_no']; ?>">

                                <div class="mb-4">
                                    <label for="campaign_name" class="form-label small fw-bold text-muted text-uppercase">Campaign Name</label>
                                    <input type="text" class="form-control bg-light border-0 shadow-sm p-3" name="campaign_name" id="campaign_name"
                                        value="<?php echo htmlspecialchars($row['campaign_name']); ?>" required placeholder="e.g. Winter Sale 2023">
                                </div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label small fw-bold text-muted text-uppercase">Start Date</label>
                                        <input type="date" class="form-control bg-light border-0 shadow-sm p-3" name="start_date" id="start_date"
                                            value="<?php echo $row['start_date']; ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="status" class="form-label small fw-bold text-muted text-uppercase">Lifecycle Status</label>
                                        <select class="form-select bg-light border-0 shadow-sm p-3" name="status" id="status" required>
                                            <option value="active" <?php if ($row['status'] == 'active') echo 'selected'; ?>>Active</option>
                                            <option value="inactive" <?php if ($row['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
                                            <option value="completed" <?php if ($row['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="description" class="form-label small fw-bold text-muted text-uppercase">Campaign Description</label>
                                    <textarea class="form-control bg-light border-0 shadow-sm p-3" name="description" id="description" rows="4" required placeholder="Outline the campaign objectives..."><?php echo htmlspecialchars($row['description']); ?></textarea>
                                </div>

                                <div class="alert alert-info border-0 rounded-3 small mb-4">
                                    <i class="fa-solid fa-clock-rotate-left me-2"></i> Last audit update: <b><?php echo htmlspecialchars($row['edited_by'] ?? 'System'); ?></b> on <?php echo date('d M, Y H:i', strtotime($row['edited_at'] ?? $row['created_at'])); ?>
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-primary py-3 fw-bold rounded-pill shadow-lg">
                                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> Commit Changes
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



