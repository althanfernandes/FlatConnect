<?php
/**
 * FlatConnect - Authentication & Security Helper Functions
 * 
 * Provides session handling, role enforcement, CSRF protection, and XSS sanitization.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user details from session
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'     => $_SESSION['user_id'],
        'name'   => $_SESSION['user_name'] ?? 'User',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['user_role'] ?? 'seeker',
        'mobile' => $_SESSION['user_mobile'] ?? ''
    ];
}

/**
 * Ensure user is logged in; otherwise redirect to login page
 */
function require_login(string $redirect_to = 'index.php'): void {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to access this page.');
        header("Location: $redirect_to");
        exit;
    }
}

/**
 * Ensure the logged-in user possesses one of the allowed roles
 */
function require_role($allowed_roles, string $redirect_to = 'home.php'): void {
    require_login();
    $roles = (array) $allowed_roles;
    $current_role = $_SESSION['user_role'] ?? '';
    
    if (empty($current_role)) {
        header("Location: choose-role.php");
        exit;
    }

    if (!in_array($current_role, $roles, true)) {
        set_flash('error', 'Please switch to the required role to access this section.');
        header("Location: choose-role.php");
        exit;
    }
}

/**
 * Return dashboard URL according to user role
 */
function get_dashboard_url(?string $role = null): string {
    if ($role === null && is_logged_in()) {
        $role = $_SESSION['user_role'] ?? '';
    }
    switch ($role) {
        case 'owner':
            return 'owner-dashboard.php';
        case 'tenant':
            return 'tenant-dashboard.php';
        case 'seeker':
            return 'seeker-dashboard.php';
        default:
            return 'choose-role.php';
    }
}

/**
 * Generate or retrieve CSRF token for forms
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify submitted CSRF token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set a one-time flash notification (e.g. success or error)
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'error', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash notification
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Safe output escaping for HTML to prevent Cross-Site Scripting (XSS)
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Helper to format Indian Rupees
 */
function format_inr(int|float $amount): string {
    return '₹' . number_format($amount);
}
