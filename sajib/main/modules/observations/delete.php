<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if (!isSuperAdmin()) {
    $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
    showError('Unauthorized access.');
    redirectTo($redirect);
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // CSRF check
    if (isset($_GET['csrf_token']) && !validateCsrfToken($_GET['csrf_token'])) {
        log_error("CSRF token validation failed for delete attempt on observations", ['id' => $id]);
        $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
        showError('Security Validation Failed: CSRF Token Mismatch.');
        redirectTo($redirect);
    }

    // Move to Recycle Bin before deleting
    moveToRecycleBin($conn, 'observations', 'serial_no', $id, 'Observations');

    $sql = "DELETE FROM observations WHERE serial_no = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
            showSuccess('Record deleted successfully');
            redirectTo($redirect);
        } else {
            log_error("Failed to delete record from observations", ['id' => $id, 'error' => mysqli_stmt_error($stmt)]);
            $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
            showError('Critical Error: Failed to process request.');
            redirectTo($redirect);
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare delete statement for observations", ['error' => mysqli_error($conn)]);
        $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
        showError('Critical Error: Failed to process request.');
        redirectTo($redirect);
    }
} else {
    $redirect = getSafeRedirectUrl($_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, 'view');
    showError('Error: Invalid record ID.');
    redirectTo($redirect);
}

mysqli_close($conn);
exit;
?>
