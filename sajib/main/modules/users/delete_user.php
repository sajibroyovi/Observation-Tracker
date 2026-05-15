<?php
require_once __DIR__ . '/../../../config/app.php'; 
include_once INCLUDES_PATH . '/auth_check.php';

if (!isSuperAdmin()) {
    header('Location: ' . BASE_URL . '/');
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // CSRF check
    if (isset($_GET['csrf_token']) && !validateCsrfToken($_GET['csrf_token'])) {
        log_error("CSRF token validation failed for delete attempt on user", ['id' => $id]);
        header("Location: manage?error=csrf");
        exit;
    }

    // Prevent self-deletion
    $stmt_check = mysqli_prepare($conn, "SELECT username FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt_check, "i", $id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $user_to_delete = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if ($user_to_delete && $user_to_delete['username'] === $_SESSION['username']) {
        showError('You cannot delete your own account.');
        redirectTo('manage');
    }

    // Move to Recycle Bin before deleting
    moveToRecycleBin($conn, 'users', 'id', $id, 'System Users');

    $stmt_delete = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt_delete, "i", $id);

    if (mysqli_stmt_execute($stmt_delete)) {
        mysqli_stmt_close($stmt_delete);
        $redirect = $_GET['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'manage';
        showSuccess('User deleted successfully');
        redirectTo($redirect);
    } else {
        log_error("Failed to delete user", ['id' => $id, 'error' => mysqli_stmt_error($stmt_delete)]);
        mysqli_stmt_close($stmt_delete);
        echo "Error deleting record. Please check logs.";
    }
} else {
    header("Location: manage");
    exit();
}
?>
