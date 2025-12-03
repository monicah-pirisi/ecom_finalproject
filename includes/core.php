<?php
/**
 * CampusDigs Core Functions
 * Handles session management, security, and authentication
 */

// Start output buffering to prevent header issues
ob_start();


// SESSION SECURITY CONFIGURATION

// Detect HTTPS connection
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$httpOnly = true;

// Set secure session cookie parameters (PHP 7.3+)
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,                    // Session expires when browser closes
        'path' => '/',                      // Cookie available across entire domain
        'domain' => '',                     // Current domain
        'secure' => $isHttps,               // HTTPS only (production)
        'httponly' => $httpOnly,            // Prevent JavaScript access
        'samesite' => 'Lax'                 // CSRF protection
    ]);
} else {
    // Fallback for older PHP versions
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
}

// Enable strict session mode (reject uninitialized session IDs)
ini_set('session.use_strict_mode', 1);

// Start session if not already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// AUTHENTICATION FUNCTIONS

/**
 * Check if user is logged in
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is a student
 * @return bool True if user is a student
 */
function isStudent() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

/**
 * Check if user is a landlord
 * @return bool True if user is a landlord
 */
function isLandlord() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'landlord';
}

/**
 * Check if user is an admin
 * @return bool True if user is an admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Require user to be logged in, redirect if not
 * @param string $redirectUrl URL to redirect to if not logged in (null = auto-detect)
 */
function requireLogin($redirectUrl = null) {
    if (!isLoggedIn()) {
        if ($redirectUrl === null) {
            $redirectUrl = (defined('BASE_URL') ? BASE_URL : '/campus_digs') . '/login/login.php';
        }
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Require user to be a student, redirect if not
 * @param string $redirectUrl URL to redirect to if not student (null = auto-detect)
 */
function requireStudent($redirectUrl = null) {
    requireLogin();
    if (!isStudent()) {
        if ($redirectUrl === null) {
            $redirectUrl = (defined('BASE_URL') ? BASE_URL : '/campus_digs') . '/index.php';
        }
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Require user to be a landlord, redirect if not
 * @param string $redirectUrl URL to redirect to if not landlord (null = auto-detect)
 */
function requireLandlord($redirectUrl = null) {
    requireLogin();
    if (!isLandlord()) {
        if ($redirectUrl === null) {
            $redirectUrl = (defined('BASE_URL') ? BASE_URL : '/campus_digs') . '/index.php';
        }
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Require user to be an admin, redirect if not
 * @param string $redirectUrl URL to redirect to if not admin (null = auto-detect)
 */
function requireAdmin($redirectUrl = null) {
    requireLogin();
    if (!isAdmin()) {
        if ($redirectUrl === null) {
            $redirectUrl = (defined('BASE_URL') ? BASE_URL : '/campus_digs') . '/index.php';
        }
        header('Location: ' . $redirectUrl);
        exit();
    }
}

// SESSION MANAGEMENT FUNCTIONS

/**
 * Regenerate session ID to prevent session fixation attacks
 * @return bool True if successful
 */
function regenerateSession() {
    try {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Session regeneration failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Check session timeout and handle idle/absolute timeouts
 * @param int $idleTimeout Maximum idle time in seconds (default: 30 minutes)
 * @param int $absoluteTimeout Maximum session lifetime in seconds (default: 8 hours)
 * @return bool True if session is valid
 */
function checkSessionTimeout($idleTimeout = 1800, $absoluteTimeout = 28800) {
    try {
        $currentTime = time();

        // Initialize session timestamps
        if (!isset($_SESSION['session_start_time'])) {
            $_SESSION['session_start_time'] = $currentTime;
        }

        if (!isset($_SESSION['last_activity_time'])) {
            $_SESSION['last_activity_time'] = $currentTime;
        }

        // Check idle timeout (30 minutes of inactivity)
        if (($currentTime - $_SESSION['last_activity_time']) > $idleTimeout) {
            session_unset();
            session_destroy();
            return false;
        }

        // Check absolute timeout (8 hours maximum session)
        if (($currentTime - $_SESSION['session_start_time']) > $absoluteTimeout) {
            session_unset();
            session_destroy();
            return false;
        }

        // Update last activity time
        $_SESSION['last_activity_time'] = $currentTime;

        // Regenerate session ID every 30 minutes
        if (!isset($_SESSION['last_regeneration']) ||
            ($currentTime - $_SESSION['last_regeneration']) > 1800) {
            regenerateSession();
        }

        return true;
    } catch (Exception $e) {
        error_log("Session timeout check failed: " . $e->getMessage());
        return false;
    }
}

// CSRF PROTECTION FUNCTIONS

/**
 * Generate a new CSRF token
 * @return string The generated CSRF token
 */
function generateCSRFToken() {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Use cryptographically secure random bytes
        if (function_exists('random_bytes')) {
            $token = bin2hex(random_bytes(32));
        } else {
            $token = bin2hex(openssl_random_pseudo_bytes(32));
        }

        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    } catch (Exception $e) {
        error_log("CSRF token generation failed: " . $e->getMessage());
        // Fallback to less secure method
        $token = md5(uniqid(mt_rand(), true));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }
}

/**
 * Validate a CSRF token
 * @param string $token Token to validate
 * @param int $tokenLifetime Maximum token age in seconds (default: 1 hour)
 * @return bool True if token is valid
 */
function validateCSRFToken($token, $tokenLifetime = 3600) {
    try {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }

        // Check token expiration
        if ((time() - $_SESSION['csrf_token_time']) > $tokenLifetime) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }

        // Use timing-safe comparison to prevent timing attacks
        if (function_exists('hash_equals')) {
            return hash_equals($_SESSION['csrf_token'], $token);
        } else {
            return $_SESSION['csrf_token'] === $token;
        }
    } catch (Exception $e) {
        error_log("CSRF token validation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current CSRF token or generate new one
 * @return string CSRF token
 */
function getCSRFToken() {
    if (!isset($_SESSION['csrf_token']) ||
        !isset($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > 3600) {
        return generateCSRFToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output CSRF token as hidden form field
 */
function csrfTokenField() {
    $token = getCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from POST request
 * @return bool True if token is valid
 */
function verifyCsrfToken() {
    if (!isset($_POST['csrf_token'])) {
        return false;
    }
    return validateCSRFToken($_POST['csrf_token']);
}

// SECURITY HELPER FUNCTIONS

/**
 * Sanitize input to prevent XSS attacks
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool True if valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate university email (must end with .ac.ke or .edu)
 * @param string $email Email to validate
 * @return bool True if valid university email
 */
function validateUniversityEmail($email) {
    if (!validateEmail($email)) {
        return false;
    }
    // Check if email ends with .ac.ke or .edu
    return preg_match('/@[a-zA-Z0-9-]+\.(ac\.ke|edu)$/i', $email);
}

/**
 * Validate phone number (Kenyan format)
 * @param string $phone Phone number to validate
 * @return bool True if valid
 */
function validateKenyanPhone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check formats: +254XXXXXXXXX, 254XXXXXXXXX, 07XXXXXXXX, 01XXXXXXXX
    return preg_match('/^(\+?254|0)[17]\d{8}$/', $phone);
}

/**
 * Format Kenyan phone number to standard format (+254XXXXXXXXX)
 * @param string $phone Phone number to format
 * @return string Formatted phone number
 */
function formatKenyanPhone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Convert to +254 format
    if (substr($phone, 0, 1) === '0') {
        $phone = '+254' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) === '254') {
        $phone = '+' . $phone;
    }
    
    return $phone;
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'message' => string]
 */
function validatePassword($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters long'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number'];
    }
    return ['valid' => true, 'message' => 'Password is valid'];
}

/**
 * Hash password securely
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// REDIRECT HELPER FUNCTIONS

/**
 * Redirect to appropriate dashboard based on user type
 */
function redirectToDashboard() {
    $baseUrl = defined('BASE_URL') ? BASE_URL : '/campus_digs';

    if (isAdmin()) {
        header('Location: ' . $baseUrl . '/dashboard_admin.php');
    } elseif (isLandlord()) {
        header('Location: ' . $baseUrl . '/dashboard_landlord.php');
    } elseif (isStudent()) {
        header('Location: ' . $baseUrl . '/dashboard_student.php');
    } else {
        header('Location: ' . $baseUrl . '/index.php');
    }
    exit();
}

/**
 * Redirect with message
 * @param string $url Destination URL
 * @param string $message Message to display
 * @param string $type Message type (success, error, warning, info)
 */
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $url);
    exit();
}

/**
 * Get and clear flash message
 * @return array ['message' => string, 'type' => string] or null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])) {
        $flash = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type']
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $flash;
    }
    return null;
}

// UTILITY FUNCTIONS

/**
 * Format currency (Kenyan Shillings)
 * @param float $amount Amount to format
 * @return string Formatted currency string
 */
function formatCurrency($amount) {
    return 'KSh ' . number_format($amount, 2);
}

/**
 * Format date to human-readable format
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Format datetime to human-readable format
 * @param string $datetime Datetime string
 * @return string Formatted datetime
 */
function formatDateTime($datetime) {
    return date('d M Y, g:i A', strtotime($datetime));
}

/**
 * Calculate time ago from timestamp
 * @param string $datetime Datetime string
 * @return string Time ago string (e.g., "2 hours ago")
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($datetime);
    }
}

// AUTO-RUN SESSION CHECKS

// Automatically check session timeout for logged-in users
if (isLoggedIn()) {
    if (!checkSessionTimeout()) {
        // Session expired, redirect to login with absolute URL
        if (defined('BASE_URL')) {
            header('Location: ' . BASE_URL . '/login/login.php?timeout=1');
        } else {
            header('Location: /campus_digs/login/login.php?timeout=1');
        }
        exit();
    }
}

// Automatically generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    generateCSRFToken();
}

/**
 * Get system settings from database
 * @return array Settings key-value pairs
 */
function getSystemSettings() {
    global $conn;

    $settings = [];

    try {
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Error getting system settings: " . $e->getMessage());
    }

    return $settings;
}

/**
 * Get a single system setting value
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function getSystemSetting($key, $default = null) {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? $row['setting_value'] : $default;

    } catch (Exception $e) {
        error_log("Error getting setting $key: " . $e->getMessage());
        return $default;
    }
}

// GUEST WISHLIST FUNCTIONS
// These functions allow non-logged-in users to browse and save properties to wishlist

/**
 * Get guest wishlist from session
 * Guest wishlist is stored in session as array of property IDs
 * @return array Array of property IDs
 */
function getGuestWishlist() {
    if (!isset($_SESSION['guest_wishlist'])) {
        $_SESSION['guest_wishlist'] = [];
    }
    return $_SESSION['guest_wishlist'];
}

/**
 * Add property to guest wishlist
 * @param int $propertyId Property ID to add
 * @return bool True if added successfully
 */
function addToGuestWishlist($propertyId) {
    $propertyId = (int)$propertyId;

    if ($propertyId <= 0) {
        return false;
    }

    $wishlist = getGuestWishlist();

    // Check if already in wishlist
    if (in_array($propertyId, $wishlist)) {
        return false; // Already exists
    }

    // Add to wishlist
    $wishlist[] = $propertyId;
    $_SESSION['guest_wishlist'] = $wishlist;

    return true;
}

/**
 * Remove property from guest wishlist
 * @param int $propertyId Property ID to remove
 * @return bool True if removed successfully
 */
function removeFromGuestWishlist($propertyId) {
    $propertyId = (int)$propertyId;
    $wishlist = getGuestWishlist();

    // Find and remove the property ID
    $key = array_search($propertyId, $wishlist);

    if ($key !== false) {
        unset($wishlist[$key]);
        // Reindex array to remove gaps
        $_SESSION['guest_wishlist'] = array_values($wishlist);
        return true;
    }

    return false;
}

/**
 * Clear guest wishlist
 * @return bool True always
 */
function clearGuestWishlist() {
    $_SESSION['guest_wishlist'] = [];
    return true;
}

/**
 * Get guest wishlist count
 * @return int Number of items in guest wishlist
 */
function getGuestWishlistCount() {
    return count(getGuestWishlist());
}

/**
 * Check if property is in guest wishlist
 * @param int $propertyId Property ID to check
 * @return bool True if in wishlist
 */
function isInGuestWishlist($propertyId) {
    $propertyId = (int)$propertyId;
    $wishlist = getGuestWishlist();
    return in_array($propertyId, $wishlist);
}

/**
 * Merge guest wishlist with user wishlist on login
 * This function is called when a guest user logs in or registers
 * @param int $userId User ID
 * @return bool True if merge successful
 */
function mergeGuestWishlistWithUser($userId) {
    $guestWishlist = getGuestWishlist();

    // If guest has no wishlist items, nothing to merge
    if (empty($guestWishlist)) {
        return true;
    }

    // Import wishlist controller to use its functions
    require_once dirname(__DIR__) . '/controllers/wishlist_controller.php';

    $successCount = 0;

    // Add each guest wishlist item to user's database wishlist
    foreach ($guestWishlist as $propertyId) {
        $result = addToWishlist($userId, $propertyId);
        if ($result) {
            $successCount++;
        }
    }

    // Clear guest wishlist after merging
    clearGuestWishlist();

    return $successCount > 0;
}

/**
 * Check if user is a guest (not logged in)
 * @return bool True if user is guest
 */
function isGuest() {
    return !isLoggedIn();
}

?>