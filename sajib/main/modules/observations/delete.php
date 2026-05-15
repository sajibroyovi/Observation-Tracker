<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if (!isSuperAdmin()) {
    $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
    showError('Unauthorized access.');
    redirectTo($redirect);
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // CSRF check
    if (isset($_GET['csrf_token']) && !validateCsrfToken($_GET['csrf_token'])) {
        log_error("CSRF token validation failed for delete attempt on observations", ['id' => $id]);
        $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
        showError('Security Validation Failed: CSRF Token Mismatch.');
        redirectTo($redirect);
        exit;
    }

    // Move to Recycle Bin before deleting
    moveToRecycleBin($conn, 'observations', 'serial_no', $id, 'Observations');

    $sql = "DELETE FROM observations WHERE serial_no = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
            showSuccess('Record deleted successfully');
            redirectTo($redirect);
        } else {
            log_error("Failed to delete record from observations", ['id' => $id, 'error' => mysqli_stmt_error($stmt)]);
            $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
            showError('Critical Error: Failed to process request.');
            redirectTo($redirect);
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare delete statement for observations", ['error' => mysqli_error($conn)]);
        $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
            showError('Critical Error: Failed to process request.');
            redirectTo($redirect);
    }
} else {
    $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'view';
    showError('Error: Invalid record ID.');
    redirectTo($redirect);
}

mysqli_close($conn);
exit;
