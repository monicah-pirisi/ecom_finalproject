<?php
/**
 * CampusDigs Kenya - Login Action
 * Handles user authentication and login
 * MVC Architecture - Controller Layer
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// VALIDATE REQUEST METHOD

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('../login/login.php', 'Invalid request method', 'error');
}


// VALIDATE CSRF TOKEN

if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    // Check if this is admin login for proper redirect
    $loginRedirect = (isset($_POST['admin_login']) && $_POST['admin_login'] === '1') ? '../admin/login.php' : '../login/login.php';
    redirectWithMessage($loginRedirect, 'Invalid security token. Please try again.', 'error');
}

// SANITIZE AND VALIDATE INPUT

// Get and sanitize login credentials
$loginValue = isset($_POST['login_value']) ? trim($_POST['login_value']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$rememberMe = isset($_POST['remember_me']) ? true : false;
$isAdminLogin = isset($_POST['admin_login']) && $_POST['admin_login'] === '1';

// Set the redirect page based on login type
$loginPage = $isAdminLogin ? '../admin/login.php' : '../login/login.php';

// VALIDATION CHECKS

// Check if all required fields are filled
if (empty($loginValue) || empty($password)) {
    redirectWithMessage($loginPage, 'Email/Phone and password are required', 'error');
}

// Determine if login value is email or phone
$isEmail = strpos($loginValue, '@') !== false;
$loginField = $isEmail ? 'email' : 'phone';

// If phone, format it
if (!$isEmail) {
    // Validate phone format
    if (!validateKenyanPhone($loginValue)) {
        redirectWithMessage($loginPage, 'Invalid phone number format', 'error');
    }
    $loginValue = formatKenyanPhone($loginValue);
} else {
    // Validate email format
    if (!validateEmail($loginValue)) {
        redirectWithMessage($loginPage, 'Invalid email format', 'error');
    }
    $loginValue = strtolower($loginValue);
}

// CHECK ACCOUNT LOCKOUT

$lockoutStatus = checkAccountLockout($loginValue, $loginField);

if ($lockoutStatus['locked']) {
    $remainingMinutes = ceil($lockoutStatus['remaining_time'] / 60);
    redirectWithMessage(
        $loginPage,
        "Account is temporarily locked due to multiple failed login attempts. Please try again in $remainingMinutes minutes.",
        'error'
    );
}

// AUTHENTICATE USER

$user = authenticateUser($loginValue, $password, $loginField);

if ($user) {
    // Login successful

    // Check if account is active
    if (isset($user['account_status']) && $user['account_status'] !== 'active') {
        $status = ucfirst($user['account_status']);
        redirectWithMessage($loginPage, "Your account is $status. Please contact support.", 'error');
    }

    // Reset login attempts on successful login
    resetLoginAttempts($user['id']);

    // Update last login time
    updateLastLogin($user['id']);

    // Log the activity
    logActivity($user['id'], 'user_login', 'User logged in successfully');

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['email_verified'] = $user['email_verified'];
    $_SESSION['phone_verified'] = $user['phone_verified'];
    $_SESSION['account_verified'] = $user['account_verified'];
    $_SESSION['session_start_time'] = time();
    $_SESSION['last_activity_time'] = time();

    // Regenerate session ID for security
    regenerateSession();

    // Handle "Remember Me" functionality
    if ($rememberMe) {
        // Set cookie for 30 days
        $token = generateRememberToken($user['id']);
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }

    // Redirect to appropriate dashboard based on user type
    if ($user['user_type'] === 'admin') {
        redirectWithMessage('../dashboard_admin.php', 'Welcome back, ' . $user['full_name'] . '!', 'success');
    } elseif ($user['user_type'] === 'landlord') {
        redirectWithMessage('../dashboard_landlord.php', 'Welcome back, ' . $user['full_name'] . '!', 'success');
    } elseif ($user['user_type'] === 'student') {
        redirectWithMessage('../dashboard_student.php', 'Welcome back, ' . $user['full_name'] . '!', 'success');
    } else {
        redirectWithMessage('../index.php', 'Welcome back!', 'success');
    }

} else {
    // Login failed

    // Increment login attempts
    incrementLoginAttempts($loginValue, $loginField);

    // Get remaining attempts
    $remainingAttempts = getRemainingLoginAttempts($loginValue, $loginField);

    // Log failed login attempt
    logActivity(null, 'login_failed', "Failed login attempt for: $loginValue");

    // Prepare error message
    if ($remainingAttempts > 0) {
        $message = "Invalid credentials. You have $remainingAttempts attempt(s) remaining before your account is locked.";
    } else {
        $message = "Invalid credentials. Your account has been locked for " . (LOGIN_LOCKOUT_DURATION / 60) . " minutes.";
    }

    redirectWithMessage($loginPage, $message, 'error');
}

?>
