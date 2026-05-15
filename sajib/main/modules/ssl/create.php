<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for SSL certificate create attempt");
        showError("Security Validation Failed: CSRF Token Mismatch.");
        redirectTo(BASE_URL . '/');
    }

    // Get form data
    $certificate_name = cleanInput($_POST['certificate_name']);
    $expiration_date = cleanInput($_POST['expiration_date']);
    $renewal_status = cleanInput($_POST['renewal_status']);
    $issues = cleanInput($_POST['issues']);
    $created_by = $_SESSION['username'];
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);

    // Insert into ssl_certificate table using prepared statements
    $sql = "INSERT INTO ssl_certificate (certificate_name, expiration_date, renewal_status, issues, created_by, handed_over_to, handover_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssss", $certificate_name, $expiration_date, $renewal_status, $issues, $created_by, $handed_over_to, $handover_date);
        if (mysqli_stmt_execute($stmt)) {
            $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
            showSuccess('SSL Certificate record inserted successfully');
            redirectTo($redirect);
        } else {
            log_error("Failed to insert SSL certificate record", ['error' => mysqli_stmt_error($stmt)]);
            $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
            showError("Critical Error: Failed to save record.");
            redirectTo($redirect);
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare statement for SSL certificate", ['error' => mysqli_error($conn)]);
        showError("Critical Error: Internal Server Error.");
        redirectTo(BASE_URL . '/');
    }

    mysqli_close($conn);
    exit();
}
?>
