<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for change request create attempt");
        header('Location: ' . BASE_URL . '/index.php?status=error&msg=' . urlencode("Security Validation Failed: CSRF Token Mismatch."));
        exit;
    }

    // Get form data
    $cr_subject = cleanInput($_POST['cr_subject']);
    $impacted_area = cleanInput($_POST['impacted_area']);
    $cr_start_time = cleanInput($_POST['cr_start_time']);
    $cr_end_time = cleanInput($_POST['cr_end_time']);
    $downtime = cleanInput($_POST['downtime']);
    $cr_meeting_attended = cleanInput($_POST['cr_meeting_attended']);
    $created_by = $_SESSION['username'];

    // Insert into cr_list table using prepared statement
    $sql = "INSERT INTO cr_list (cr_subject, impacted_area, cr_start_time, cr_end_time, downtime, cr_meeting_attended, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssss", $cr_subject, $impacted_area, $cr_start_time, $cr_end_time, $downtime, $cr_meeting_attended, $created_by);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: ' . BASE_URL . '/index.php?status=success&msg=' . urlencode('Change Request record inserted successfully'));
        } else {
            log_error("Failed to insert change request record", ['error' => mysqli_stmt_error($stmt)]);
            header('Location: ' . BASE_URL . '/index.php?status=error&msg=' . urlencode("Critical Error: Failed to save record."));
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare statement for change request", ['error' => mysqli_error($conn)]);
        header('Location: ' . BASE_URL . '/index.php?status=error&msg=' . urlencode("Critical Error: Internal Server Error."));
    }

    mysqli_close($conn);
    exit();
}
?>
