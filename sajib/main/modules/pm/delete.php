<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (!isSuperAdmin()) {
    header("Location: view?error=unauthorized");
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // CSRF check (Recommended to use POST for deletes, but adding token to GET for now if present)
    if (isset($_GET['csrf_token']) && !validateCsrfToken($_GET['csrf_token'])) {
        log_error("CSRF token validation failed for delete attempt on pending_mail", ['id' => $id]);
        header("Location: view?error=csrf");
        exit;
    }

    $sql = "DELETE FROM pending_mail WHERE serial_no = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: view?msg=deleted");
        } else {
            log_error("Failed to delete record from pending_mail", ['id' => $id, 'error' => mysqli_stmt_error($stmt)]);
            header("Location: view?msg=error");
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare delete statement for pending_mail", ['error' => mysqli_error($conn)]);
        header("Location: view?msg=error");
    }
} else {
    header("Location: view?msg=noid");
}

mysqli_close($conn);
exit;


