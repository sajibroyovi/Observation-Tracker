<?php
require_once __DIR__ . '/../../../config/app.php'; include_once INCLUDES_PATH . '/auth_check.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        log_error("CSRF token validation failed for service outage create attempt");
        header('Location: ' . BASE_URL . '/?status=error&msg=' . urlencode("Security Validation Failed: CSRF Token Mismatch."));
        exit;
    }

    // Get form data
    $details = cleanInput($_POST['details']);
    $incident_id = cleanInput($_POST['incident_id']);
    $problem_ticket = cleanInput($_POST['problem_ticket']);
    $status = cleanInput($_POST['status']);
    $technician = cleanInput($_POST['technician']);
    $created_by = $_SESSION['username'];
    $handed_over_to = cleanInput($_POST['handed_over_to'] ?? '');
    $handover_date = cleanInput($_POST['handover_date'] ?? null);

    // Insert into service_outage table using prepared statement
    $sql = "INSERT INTO service_outage (details, incident_id, problem_ticket, status, technician, created_by, handed_over_to, handover_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssss", $details, $incident_id, $problem_ticket, $status, $technician, $created_by, $handed_over_to, $handover_date);
        if (mysqli_stmt_execute($stmt)) {
            $redirect = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/');
            $separator = (strpos($redirect, '?') !== false) ? '&' : '?';
            header('Location: ' . $redirect . $separator . 'status=success&msg=' . urlencode('Service Outage record inserted successfully'));
        } else {
            log_error("Failed to insert service outage record", ['error' => mysqli_stmt_error($stmt)]);
            $redirect = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/');
            $separator = (strpos($redirect, '?') !== false) ? '&' : '?';
            header('Location: ' . $redirect . $separator . 'status=error&msg=' . urlencode("Critical Error: Failed to save record."));
        }
        mysqli_stmt_close($stmt);
    } else {
        log_error("Failed to prepare statement for service outage", ['error' => mysqli_error($conn)]);
        header('Location: ' . BASE_URL . '/?status=error&msg=' . urlencode("Critical Error: Internal Server Error."));
    }

    mysqli_close($conn);
    exit();
}
?>
