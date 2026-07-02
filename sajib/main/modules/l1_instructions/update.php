<?php
require_once __DIR__ . '/../../../config/app.php'; 
require_once INCLUDES_PATH . '/auth_check.php';
require_once INCLUDES_PATH . '/functions.php';

// Check permissions
if (!in_array($_SESSION['role'] ?? '', ['l1', 'super_admin'])) {
    showError("You don't have permission to perform this action.");
    redirectTo(BASE_URL . '/');
}

$conn = getConnection();

if (isset($_GET['id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch existing record using prepared statement
    $stmt_fetch = mysqli_prepare($conn, "SELECT * FROM l1_instructions WHERE id = ?");
    mysqli_stmt_bind_param($stmt_fetch, "i", $id);
    mysqli_stmt_execute($stmt_fetch);
    $result = mysqli_stmt_get_result($stmt_fetch);

    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
    } else {
        showError("Record not found");
        redirectTo(BASE_URL . '/modules/l1_instructions/view');
        exit;
    }
    mysqli_stmt_close($stmt_fetch);
} else {
    showError("No ID provided");
    redirectTo(BASE_URL . '/modules/l1_instructions/view');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        showError("Security Validation Failed: CSRF Token Mismatch.");
        redirectTo(BASE_URL . '/modules/l1_instructions/view');
        exit;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $instruction_text = cleanInput($_POST['instruction_text'] ?? '');
    
    if (empty($instruction_text)) {
        showError("Instruction text is required.");
        redirectTo(BASE_URL . '/modules/l1_instructions/update?id=' . $id);
        exit;
    }

    $sql = "UPDATE l1_instructions SET 
            instruction_text = ?,
            updated_by = ?,
            updated_at = NOW()
            WHERE id = ?";

    $stmt_update = mysqli_prepare($conn, $sql);
    if ($stmt_update) {
        $updated_by = $_SESSION['username'] ?? 'System';
        mysqli_stmt_bind_param($stmt_update, "ssi", $instruction_text, $updated_by, $id);
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            showSuccess('Record updated successfully');
            redirectTo(BASE_URL . '/modules/l1_instructions/view');
            exit;
        } else {
            showError('An error occurred while updating the record. Please try again.');
            redirectTo(BASE_URL . '/modules/l1_instructions/update?id=' . $id);
            exit;
        }
    } else {
        showError('Internal Server Error.');
        redirectTo(BASE_URL . '/modules/l1_instructions/view');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update L1 Instruction | Shift Handover</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        <h1 class="view-header fw-bold mb-1"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Update L1 Instruction</h1>
                        <p class="text-muted small mb-0">Modify instruction details.</p>
                    </div>
                    <a href="view.php" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>

                <div class="glass-card mb-4">
                    <div class="card-body p-4">
                        <form action="" method="POST" class="row g-4 needs-validation" novalidate>
                            <?= getCsrfField() ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">

                            <!-- Main Input Area -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark small mb-2">Instruction Text <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge shadow-sm rounded-3">
                                    <span class="input-group-text bg-white border-end-0 text-primary px-3">
                                        <i class="fa-solid fa-bullhorn"></i>
                                    </span>
                                    <textarea name="instruction_text" class="form-control border-start-0 ps-0" rows="4" placeholder="Enter instruction details here..." required><?= htmlspecialchars($row['instruction_text']) ?></textarea>
                                </div>
                                <div class="invalid-feedback">Please enter the instruction text.</div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm rounded-pill">
                                    <i class="fa-solid fa-save me-2"></i> Save Changes
                                </button>
                                <a href="view.php" class="btn btn-light border px-4 py-2 fw-bold text-muted rounded-pill hover-bg-light">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form Validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
<?php  ?>
