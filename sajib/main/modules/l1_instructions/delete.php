<?php
require_once __DIR__ . '/../../../config/app.php';
require_once INCLUDES_PATH . '/auth_check.php';
require_once INCLUDES_PATH . '/functions.php';

// Check permissions
if (!in_array($_SESSION['role'] ?? '', ['l1', 'super_admin'])) {
    showError("You don't have permission to perform this action.");
    redirectTo(BASE_URL . '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        showError("Security validation failed. Please try again.");
        redirectTo(BASE_URL . '/modules/l1_instructions/view.php');
    }

    $conn = getConnection();
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        showError("Invalid record ID.");
        redirectTo(BASE_URL . '/modules/l1_instructions/view.php');
    }

    // Move to recycle bin
    if (moveToRecycleBin($conn, 'l1_instructions', 'id', $id, 'L1 Instructions')) {
        $sql = "DELETE FROM l1_instructions WHERE id = ?";
        $stmt = executePreparedStatement($conn, $sql, "i", [$id]);

        if ($stmt) {
            showSuccess("Instruction deleted and moved to recycle bin.");
            mysqli_stmt_close($stmt);
        } else {
            showError("Failed to delete record.");
        }
    } else {
        showError("Failed to move record to recycle bin.");
    }

    closeConnection($conn);
    redirectTo(BASE_URL . '/modules/l1_instructions/view.php');
} else {
    redirectTo(BASE_URL . '/modules/l1_instructions/view.php');
}
