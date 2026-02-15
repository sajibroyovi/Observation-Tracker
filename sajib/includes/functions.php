<?php
/**
 * ============================================================================
 * SHIFT HANDOVER APPLICATION - FUNCTION LIBRARY
 * ============================================================================
 * 
 * Centralized library containing all reusable PHP functions for the
 * Shift Handover Application.
 * 
 * Sections:
 * 1. Database Functions
 * 2. Authentication & Session Functions
 * 3. Permission & Role Functions
 * 4. Utility Functions
 * 
 * @author Shift Handover Development Team
 * @version 1.0
 * @date 2026-02-08
 */

// ============================================================================
// 1. DATABASE FUNCTIONS
// ============================================================================

/**
 * Get database connection
 * 
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getConnection() {
    static $conn = null;
    if ($conn === null) {
        $servername = "localhost";
        $username   = "root";
        $password   = "";
        $dbname     = "shift_hand_over";
        
        // Create connection
        $conn = mysqli_connect($servername, $username, $password, $dbname);
        
        // Check connection
        if (!$conn) {
            log_error("Critical Security Failure: Database connection failed.", ["context" => "Internal"]);
            die("Service Temporarily Unavailable.");
        }
    }
    return $conn;
}

/**
 * Close database connection
 * 
 * @param mysqli $conn Database connection to close
 * @return void
 */
function closeConnection($conn) {
    if ($conn) {
        mysqli_close($conn);
    }
}

/**
 * Basic data cleaning (trim, stripslashes)
 * Note: Use prepared statements for SQL and e() for HTML output instead of this
 * 
 * @param string $data Data to clean
 * @return string Cleaned data
 */
function cleanInput($data) {
    if ($data === null) return '';
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Sanitize user input - DEPRECATED: Use prepared statements instead
 * 
 * @param mysqli $conn Database connection
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($conn, $data) {
    if ($data === null) return '';
    $data = cleanInput($data);
    return mysqli_real_escape_string($conn, $data);
}

/**
 * XSS Mitigation: Escape text for safe HTML output
 * 
 * @param string $text Text to escape
 * @return string Escaped text
 */
function e($text) {
    if ($text === null) return '';
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Log error to a file
 * 
 * @param string $message Error message
 * @param array $context Additional context for the log
 * @return void
 */
function log_error($message, $context = []) {
    $log_file = ROOT_PATH . '/logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'System';
    $context_json = !empty($context) ? json_encode($context) : '';
    
    $log_message = "[$timestamp] [User: $user_id] $message $context_json" . PHP_EOL;
    error_log($log_message, 3, $log_file);
}

/**
 * Execute a safe query (For internal/static use only)
 * WARNING: Do NOT use this with dynamic parameters. Use executePreparedStatement instead.
 * 
 * @param mysqli $conn Database connection
 * @param string $query SQL query to execute
 * @return mysqli_result|bool Query result or false on failure
 */
function executeQuery($conn, $query) {
    if (!$conn) {
        log_error("Database connection missing for query.");
        return false;
    }
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        log_error("Database Query Error", ["query_summary" => substr($query, 0, 50)]);
        return false;
    }
    return $result;
}

/**
 * Execute a prepared statement safely
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query with placeholders (?)
 * @param string $types String representing parameter types (e.g., "ssi")
 * @param array $params Array of parameters to bind
 * @return mysqli_stmt|bool Prepared statement object or false on failure
 */
function executePreparedStatement($conn, $sql, $types = "", $params = []) {
    if (!$conn) {
        log_error("Database connection missing for prepared statement.");
        return false;
    }

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        log_error("Failed to prepare statement", ["sql" => substr($sql, 0, 50), "error" => mysqli_error($conn)]);
        return false;
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        log_error("Failed to execute prepared statement", ["sql" => substr($sql, 0, 50), "error" => mysqli_stmt_error($stmt)]);
        mysqli_stmt_close($stmt);
        return false;
    }

    return $stmt;
}

// ============================================================================
// 2. AUTHENTICATION & SESSION FUNCTIONS
// ============================================================================

/**
 * Initialize session with timezone
 * 
 * @return void
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        date_default_timezone_set('Asia/Dhaka');
        session_start();
    }
}

/**
 * Check if user is authenticated, redirect to login if not
 * 
 * @param string $redirect_url URL to redirect to if not authenticated
 * @return void
 */
function requireAuth($redirect_url = 'login.php') {
    initSession();
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $redirect_url);
        exit();
    }
}

/**
 * Get current user information
 * 
 * @return array User information (id, username, role)
 */
function getUserInfo() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Logout user and redirect
 * 
 * @param string $redirect_url URL to redirect after logout
 * @return void
 */
function logout($redirect_url = 'login.php') {
    initSession();
    session_destroy();
    header('Location: ' . $redirect_url);
    exit();
}

/**
 * Generate and store CSRF token if not exists
 * 
 * @return string CSRF token
 */
function generateCsrfToken() {
    initSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token
 * 
 * @return string CSRF token
 */
function getCsrfToken() {
    return generateCsrfToken();
}

/**
 * Get HTML hidden input field for CSRF
 * 
 * @return string HTML input for CSRF
 */
function getCsrfField() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate CSRF token from request
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token) {
    initSession();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================================
// 3. PERMISSION & ROLE FUNCTIONS
// ============================================================================

/**
 * Check if user has a specific permission/role
 * 
 * @param string $required_role Required role to check
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($required_role) {
    if ($_SESSION['role'] === 'super_admin') {
        return true;
    }
    return $_SESSION['role'] === $required_role;
}

/**
 * Check if user can view a specific module
 * 
 * @param string $module_title Module title to check access for
 * @return bool True if user can view module, false otherwise
 */
function canViewModule($module_title) {
    if ($_SESSION['role'] === 'super_admin') {
        return true;
    }
    
    $allowed = $_SESSION['allowed_modules'] ?? [];
    if (!is_array($allowed)) {
        $allowed = explode(',', $allowed);
    }
    
    return in_array($module_title, $allowed);
}

/**
 * Check if user is a super admin
 * 
 * @return bool True if super admin, false otherwise
 */
function isSuperAdmin() {
    return $_SESSION['role'] === 'super_admin';
}

/**
 * Check if user is an admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return $_SESSION['role'] === 'admin';
}

/**
 * Check if user can edit L1 observations
 * Super Admin and L1 users can edit L1
 * 
 * @return bool True if can edit L1, false otherwise
 */
function canEditL1() {
    return in_array($_SESSION['role'], ['super_admin', 'l1']);
}

/**
 * Check if user can edit L2 observations
 * Super Admin, Admin, and L2 users can edit L2
 * 
 * @return bool True if can edit L2, false otherwise
 */
function canEditL2() {
    return in_array($_SESSION['role'], ['super_admin', 'admin', 'l2']);
}

/**
 * Check if user can add observations
 * Super Admin and L1 users can add observations
 * 
 * @return bool True if can add observation, false otherwise
 */
function canAddObservation() {
    return in_array($_SESSION['role'], ['super_admin', 'l1']);
}

/**
 * Check if user can edit global modules
 * Everyone except L2 can edit global modules
 * L2 can only edit observations
 * 
 * @return bool True if can edit global, false otherwise
 */
function canEditGlobal() {
    return $_SESSION['role'] !== 'l2';
}

/**
 * Check if user can dispatch shift handover
 * Super Admin and Admin can dispatch handover
 * 
 * @return bool True if can dispatch, false otherwise
 */
function canDispatchHandover() {
    return in_array($_SESSION['role'], ['super_admin', 'admin']);
}

// ============================================================================
// 4. UTILITY FUNCTIONS
// ============================================================================

/**
 * Get current shift based on time
 * Morning: 6 AM to 2 PM (06:00 - 13:59)
 * Evening: 2 PM to 10 PM (14:00 - 21:59)
 * Night: 10 PM to 6 AM (22:00 - 05:59)
 * 
 * @return string Current shift (Morning, Evening, or Night)
 */
function getCurrentShift() {
    $hour = (int)date('H');
    
    // Morning: 6 AM to 2 PM (06:00 - 13:59)
    if ($hour >= 6 && $hour < 14) {
        return 'Morning';
    }
    
    // Evening: 2 PM to 10 PM (14:00 - 21:59)
    if ($hour >= 14 && $hour < 22) {
        return 'Evening';
    }
    
    // Night: 10 PM to 6 AM (22:00 - 05:59)
    return 'Night';
}

/**
 * Format date with specified format
 * 
 * @param string $date Date to format
 * @param string $format Format string (default: 'Y-m-d H:i:s')
 * @return string Formatted date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date; // Return original if parsing fails
    }
    
    return date($format, $timestamp);
}

/**
 * Get current timestamp in standard format
 * 
 * @return string Current timestamp
 */
function getTimestamp() {
    return date('Y-m-d H:i:s');
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirectTo($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Display success message
 * 
 * @param string $message Success message to display
 * @return void
 */
function showSuccess($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Display error message
 * 
 * @param string $message Error message to display
 * @return void
 */
function showError($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message
 * 
 * @return string|null Success message or null
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Get and clear error message
 * 
 * @return string|null Error message or null
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

/**
 * Validate file upload
 * 
 * @param array $file File from $_FILES
 * @param array $allowed_types Allowed MIME types
 * @param int $max_size Maximum file size in bytes
 * @return array ['success' => bool, 'message' => string]
 */
function validateFileUpload($file, $allowed_types = [], $max_size = 5242880) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $max_mb = $max_size / 1048576;
        return ['success' => false, 'message' => "File size exceeds {$max_mb}MB limit"];
    }
    
    // Check file type if specified
    if (!empty($allowed_types)) {
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
    }
    
    return ['success' => true, 'message' => 'File validation passed'];
}

/**
 * Generate a unique filename
 * 
 * @param string $original_name Original filename
 * @return string Unique filename
 */
function generateUniqueFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $filename = pathinfo($original_name, PATHINFO_FILENAME);
    $unique = uniqid() . '_' . time();
    return $filename . '_' . $unique . '.' . $extension;
}
