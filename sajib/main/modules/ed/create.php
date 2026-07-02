<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for service enable/disable create attempt");
        showError("Security Validation Failed: CSRF Token Mismatch.");
        redirectTo(BASE_URL . '/');
    }

    // Get form data
    $service_name = cleanInput($_POST['service_name']); 
    $action_date = cleanInput($_POST['action_date']);
    $action_taken = cleanInput($_POST['action_taken']);
    $action_taken_by = cleanInput($_POST['action_taken_by']); 
    $reference = cleanInput($_POST['reference']);
    $created_by = $_SESSION['username'];
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);

    // Insert into enable_disable table using prepared statement
    $sql = "INSERT INTO enable_disable (service_name, action_date, action_taken, action_taken_by, reference, created_by, handed_over_to, handover_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; 
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssss", $service_name, $action_date, $action_taken, $action_taken_by, $reference, $created_by, $handed_over_to, $handover_date);
        if (mysqli_stmt_execute($stmt)) { 
            $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
            showSuccess('Service Enable/Disable record inserted successfully');
            redirectTo($redirect);
        } else {
            log_error("Failed to insert service enable/disable record", ['error' => mysqli_stmt_error($stmt)]);
            $redirect = getSafeRedirectUrl($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? null, BASE_URL . '/');
            showError("Critical Error: Failed to save record.");
            redirectTo($redirect);
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare statement for service enable/disable", ['error' => mysqli_error($conn)]);
        showError("Critical Error: Internal Server Error.");
        redirectTo(BASE_URL . '/');
    }

    exit();
}
?>
