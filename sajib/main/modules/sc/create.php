<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for security mail create attempt");
        showError("Security Validation Failed: CSRF Token Mismatch.");
        redirectTo(BASE_URL . '/');
    }

    // Get form data
    $subject_line = cleanInput($_POST['subject_line']);
    $priority = cleanInput($_POST['priority']);
    $status = cleanInput($_POST['status']);
    $created_by = $_SESSION['username'];
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);

    // Insert into security_mail using prepared statements
    $sql = "INSERT INTO security_mail (subject_line, priority, status, created_by, handed_over_to, handover_date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssss", $subject_line, $priority, $status, $created_by, $handed_over_to, $handover_date);
        if (mysqli_stmt_execute($stmt)) { 
            $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
            showSuccess('Security Record inserted successfully');
            redirectTo($redirect);
        } else { 
            log_error("Failed to insert security mail record", ['error' => mysqli_stmt_error($stmt)]);
            $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
            showError("Critical Error: Failed to save record.");
            redirectTo($redirect);
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare statement for security mail", ['error' => mysqli_error($conn)]);
        showError("Critical Error: Internal Server Error.");
        redirectTo(BASE_URL . '/');
    }
    mysqli_close($conn);
    exit();
}
?>
