<?php
/**
 * FlatConnect - Logout Handler
 * 
 * Safely destroys PHP session and redirects user back to the login page.
 */

require_once __DIR__ . '/includes/auth.php';

// Unset all session values
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a fresh session only to pass flash notification
session_start();
set_flash('info', 'You have been successfully logged out.');

header('Location: index.php');
exit;
