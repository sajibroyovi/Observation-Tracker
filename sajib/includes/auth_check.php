<?php
/**
 * Authentication Check - Include at the top of protected pages
 * 
 * This file initializes the session, checks authentication,
 * and loads the centralized function library.
 */

// Load centralized function library
require_once __DIR__ . '/functions.php';

// Initialize session and check authentication
requireAuth();

// Global CSRF Protection for state-changing requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        // Log CSRF failure
        error_log("CSRF validation failed for user_id: " . ($_SESSION['user_id'] ?? 'unknown') . " on page: " . $_SERVER['REQUEST_URI']);
        
        // Show error and redirect back if possible, or to dashboard
        showError("Invalid security token. Please try again.");
        $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php');
        header('Location: ' . $referer);
        exit();
    }
}

// Establish global database connection for backward compatibility
$conn = getConnection();

// Store user info for easy access (backward compatibility)
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'];
$user_role = $_SESSION['role'];

// All functions are now available from functions.php:
// - Database: getConnection(), closeConnection(), sanitizeInput(), executeQuery()
// - Auth: requireAuth(), getUserInfo(), isLoggedIn(), logout()
// - Permissions: hasPermission(), canViewModule(), isSuperAdmin(), isAdmin()
//               canEditL1(), canEditL2(), canAddObservation(), canEditGlobal(), canDispatchHandover()
// - Utility: getCurrentShift(), formatDate(), getTimestamp(), redirectTo()
//           showSuccess(), showError(), getSuccessMessage(), getErrorMessage()
//           validateFileUpload(), generateUniqueFilename()
?>
