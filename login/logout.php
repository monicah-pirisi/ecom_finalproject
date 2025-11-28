<?php
/**
 * CampusDigs Kenya - Logout Handler
 * Securely logs out user and destroys session
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// LOG LOGOUT ACTIVITY
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'user_logout', 'User logged out');
}

// DESTROY SESSION

// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params['path'], 
        $params['domain'],
        $params['secure'], 
        $params['httponly']
    );
}

// Delete remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Destroy the session
session_destroy();

// REDIRECT TO LOGIN PAGE
header('Location: login.php?logout=1');
exit();

?>