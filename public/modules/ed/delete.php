<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (!isSuperAdmin()) {
    header("Location: ../viewdata/view_ed.php?error=unauthorized");
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // CSRF check
    if (isset($_GET['csrf_token']) && !validateCsrfToken($_GET['csrf_token'])) {
        log_error("CSRF token validation failed for delete attempt on enable_disable", ['id' => $id]);
        header("Location: view.php?error=csrf");
        exit;
    }

    $sql = "DELETE FROM enable_disable WHERE serial_no = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: view.php?msg=deleted");
        } else {
            log_error("Failed to delete record from enable_disable", ['id' => $id, 'error' => mysqli_stmt_error($stmt)]);
            header("Location: view.php?msg=error");
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare delete statement for enable_disable", ['error' => mysqli_error($conn)]);
        header("Location: view.php?msg=error");
    }
} else {
    header("Location: view.php?msg=noid");
}

mysqli_close($conn);
exit;


