<?php
require_once __DIR__ . '/../../../config/app.php';
require_once INCLUDES_PATH . '/auth_check.php';
require_once INCLUDES_PATH . '/functions.php';

// Check permissions
if (!in_array($_SESSION['role'] ?? '', ['l1', 'super_admin'])) {
    showError("You don't have permission to perform this action.");
    redirectTo(BASE_URL . '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        showError("Security validation failed. Please try again.");
        redirectTo(BASE_URL . '/');
    }

    $conn = getConnection();
    
    // Sanitize input
    $instruction_text = cleanInput($_POST['instruction_text'] ?? '');
    $created_by = $_SESSION['username'] ?? 'Unknown';

    if (empty($instruction_text)) {
        showError("Instruction text is required.");
        redirectTo(BASE_URL . '/');
    }

    // Insert record
    $sql = "INSERT INTO l1_instructions (instruction_text, created_by) VALUES (?, ?)";
    $stmt = executePreparedStatement($conn, $sql, "ss", [$instruction_text, $created_by]);

    if ($stmt) {
        showSuccess("L1 Instruction added successfully.");
        mysqli_stmt_close($stmt);
    } else {
        showError("Error adding instruction.");
    }
    
    closeConnection($conn);
    redirectTo(BASE_URL . '/modules/l1_instructions/view.php');
} else {
    redirectTo(BASE_URL . '/');
}
