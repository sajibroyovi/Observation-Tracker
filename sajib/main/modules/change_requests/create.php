<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for change request create attempt");
        showError("Security Validation Failed: CSRF Token Mismatch.");
        redirectTo(BASE_URL . '/');
    }

    // Get form data
    $cr_subject = cleanInput($_POST['cr_subject']);
    $impacted_area = cleanInput($_POST['impacted_area']);
    $cr_start_time = cleanInput($_POST['cr_start_time']);
    $cr_end_time = cleanInput($_POST['cr_end_time']);
    $downtime = cleanInput($_POST['downtime']);
    $cr_meeting_attended = cleanInput($_POST['cr_meeting_attended']);
    $created_by = $_SESSION['username'];
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);

    // Insert into cr_list table using prepared statement
    $sql = "INSERT INTO cr_list (cr_subject, impacted_area, cr_start_time, cr_end_time, downtime, cr_meeting_attended, created_by, handed_over_to, handover_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssssss", $cr_subject, $impacted_area, $cr_start_time, $cr_end_time, $downtime, $cr_meeting_attended, $created_by, $handed_over_to, $handover_date);
        if (mysqli_stmt_execute($stmt)) {
            $redirect = $_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/');
                showSuccess('Change Request record inserted successfully');
                redirectTo($redirect);
        } else {
            log_error("Failed to insert change request record", ['error' => mysqli_stmt_error($stmt)]);
            $redirect = $_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/');
                showError("Critical Error: Failed to save record.");
                redirectTo($redirect);
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare statement for change request", ['error' => mysqli_error($conn)]);
        showError("Critical Error: Internal Server Error.");
        redirectTo(BASE_URL . '/');
    }

    mysqli_close($conn);
    exit();
}
?>
