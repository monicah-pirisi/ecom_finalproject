<?php
/**
 * CampusDigs Kenya - User Controller
 * Handles all user-related business logic
 * MVC Architecture - Controller Layer
 */

require_once dirname(__DIR__) . '/classes/user_class.php';

// USER REGISTRATION
/**
 * Register a new user
 * @param array $userData User data array
 * @return int|false User ID if successful, false otherwise
 */
function registerUser($userData) {
    $userClass = new User();
    
    // Extract data
    $userType = $userData['user_type'];
    $fullName = $userData['full_name'];
    $email = $userData['email'];
    $phone = $userData['phone'];
    $password = hashPassword($userData['password']);
    $university = isset($userData['university']) ? $userData['university'] : null;
    $studentId = isset($userData['student_id']) ? $userData['student_id'] : null;
    
    // Register user
    $userId = $userClass->registerUser(
        $userType,
        $fullName,
        $email,
        $phone,
        $password,
        $university,
        $studentId
    );
    
    return $userId;
}

/**
 * Check if email already exists
 * @param string $email Email to check
 * @return bool True if email exists
 */
function emailExists($email) {
    $userClass = new User();
    return $userClass->emailExists($email);
}

/**
 * Check if phone already exists
 * @param string $phone Phone number to check
 * @return bool True if phone exists
 */
function phoneExists($phone) {
    $userClass = new User();
    return $userClass->phoneExists($phone);
}

// USER AUTHENTICATION
/**
 * Authenticate user with credentials
 * @param string $loginValue Email or phone
 * @param string $password Plain text password
 * @param string $loginField 'email' or 'phone'
 * @return array|false User data if authenticated, false otherwise
 */
function authenticateUser($loginValue, $password, $loginField = 'email') {
    $userClass = new User();
    
    // Get user by email or phone
    $user = $userClass->getUserByEmailOrPhone($loginValue, $loginField);
    
    if (!$user) {
        return false;
    }
    
    // Verify password
    if (verifyPassword($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

/**
 * Get user by ID
 * @param int $userId User ID
 * @return array|false User data if found, false otherwise
 */
function getUserById($userId) {
    $userClass = new User();
    return $userClass->getUserById($userId);
}

// LOGIN ATTEMPTS & LOCKOUT

/**
 * Check if account is locked out
 * @param string $identifier Email or phone
 * @param string $field 'email' or 'phone'
 * @return array ['locked' => bool, 'remaining_time' => int]
 */
function checkAccountLockout($identifier, $field = 'email') {
    $userClass = new User();
    $user = $userClass->getUserByEmailOrPhone($identifier, $field);
    
    if (!$user) {
        return ['locked' => false, 'remaining_time' => 0];
    }
    
    // Check if account is locked
    if ($user['lockout_until']) {
        $lockoutTime = strtotime($user['lockout_until']);
        $currentTime = time();
        
        if ($currentTime < $lockoutTime) {
            // Account is still locked
            return [
                'locked' => true, 
                'remaining_time' => $lockoutTime - $currentTime
            ];
        } else {
            // Lockout expired, reset attempts
            $userClass->resetLoginAttempts($user['id']);
            return ['locked' => false, 'remaining_time' => 0];
        }
    }
    
    return ['locked' => false, 'remaining_time' => 0];
}

/**
 * Increment login attempts
 * @param string $identifier Email or phone
 * @param string $field 'email' or 'phone'
 * @return bool True if successful
 */
function incrementLoginAttempts($identifier, $field = 'email') {
    $userClass = new User();
    $user = $userClass->getUserByEmailOrPhone($identifier, $field);
    
    if (!$user) {
        return false;
    }
    
    $attempts = $user['login_attempts'] + 1;
    
    // Check if max attempts reached
    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        // Lock account
        $lockoutUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_DURATION);
        return $userClass->lockAccount($user['id'], $lockoutUntil);
    } else {
        // Just increment attempts
        return $userClass->incrementLoginAttempts($user['id'], $attempts);
    }
}

/**
 * Reset login attempts
 * @param int $userId User ID
 * @return bool True if successful
 */
function resetLoginAttempts($userId) {
    $userClass = new User();
    return $userClass->resetLoginAttempts($userId);
}

/**
 * Get remaining login attempts
 * @param string $identifier Email or phone
 * @param string $field 'email' or 'phone'
 * @return int Remaining attempts
 */
function getRemainingLoginAttempts($identifier, $field = 'email') {
    $userClass = new User();
    $user = $userClass->getUserByEmailOrPhone($identifier, $field);
    
    if (!$user) {
        return MAX_LOGIN_ATTEMPTS;
    }
    
    return MAX_LOGIN_ATTEMPTS - $user['login_attempts'];
}

/**
 * Update last login time
 * @param int $userId User ID
 * @return bool True if successful
 */
function updateLastLogin($userId) {
    $userClass = new User();
    return $userClass->updateLastLogin($userId);
}

// REMEMBER ME FUNCTIONALITY

/**
 * Generate remember me token
 * @param int $userId User ID
 * @return string Token
 */
function generateRememberToken($userId) {
    $token = bin2hex(random_bytes(32));
    // Store token in database (implement in user_class.php)
    // For now, just return encoded userId
    return base64_encode($userId . ':' . $token);
}


// USER PROFILE MANAGEMENT
/**
 * Update user profile
 * @param int $userId User ID
 * @param array $data Profile data
 * @return bool True if successful
 */
function updateUserProfile($userId, $data) {
    $userClass = new User();
    return $userClass->updateUserProfile($userId, $data);
}

/**
 * Change user password
 * @param int $userId User ID
 * @param string $oldPassword Old password
 * @param string $newPassword New password
 * @return array ['success' => bool, 'message' => string]
 */
function changePassword($userId, $oldPassword, $newPassword) {
    $userClass = new User();
    $user = $userClass->getUserById($userId);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Verify old password
    if (!verifyPassword($oldPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Validate new password
    $validation = validatePassword($newPassword);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }
    
    // Hash and update password
    $hashedPassword = hashPassword($newPassword);
    $success = $userClass->updatePassword($userId, $hashedPassword);
    
    if ($success) {
        logActivity($userId, 'password_changed', 'User changed password');
        return ['success' => true, 'message' => 'Password changed successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update password'];
}

// EMAIL & PHONE VERIFICATION
/**
 * Mark email as verified
 * @param int $userId User ID
 * @return bool True if successful
 */
function verifyEmail($userId) {
    $userClass = new User();
    $success = $userClass->markEmailVerified($userId);
    
    if ($success) {
        logActivity($userId, 'email_verified', 'Email address verified');
    }
    
    return $success;
}

/**
 * Mark phone as verified
 * @param int $userId User ID
 * @return bool True if successful
 */
function verifyPhone($userId) {
    $userClass = new User();
    $success = $userClass->markPhoneVerified($userId);
    
    if ($success) {
        logActivity($userId, 'phone_verified', 'Phone number verified');
    }
    
    return $success;
}

// ADMIN FUNCTIONS
/**
 * Get all users with pagination
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param string $userType Filter by user type
 * @return array ['users' => array, 'total' => int, 'pages' => int]
 */
function getAllUsers($page = 1, $perPage = 20, $userType = null) {
    $userClass = new User();
    return $userClass->getAllUsers($page, $perPage, $userType);
}

/**
 * Get all users with advanced filtering (for admin dashboard)
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param array $filters Filter parameters (user_type, status, search, verified)
 * @return array ['users' => array, 'total' => int, 'pages' => int]
 */
function getAllUsersFiltered($page = 1, $perPage = 20, $filters = []) {
    global $conn;

    $offset = ($page - 1) * $perPage;

    // Base query
    $whereConditions = ["account_status != 'deleted'"];
    $params = [];
    $types = "";

    // User type filter
    if (!empty($filters['user_type']) && in_array($filters['user_type'], ['student', 'landlord', 'admin'])) {
        $whereConditions[] = "user_type = ?";
        $params[] = $filters['user_type'];
        $types .= "s";
    }

    // Account status filter
    if (!empty($filters['status'])) {
        if ($filters['status'] === 'unverified') {
            // Special case: unverified users
            $whereConditions[] = "account_verified = 0";
        } else {
            // Regular status filter
            $whereConditions[] = "account_status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
    }

    // Search filter (name, email, phone)
    if (!empty($filters['search'])) {
        $searchTerm = '%' . $filters['search'] . '%';
        $whereConditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }

    // Verified filter
    if (isset($filters['verified'])) {
        if ($filters['verified'] === 'yes') {
            $whereConditions[] = "account_verified = 1";
        } elseif ($filters['verified'] === 'no') {
            $whereConditions[] = "account_verified = 0";
        }
    }

    $whereClause = implode(" AND ", $whereConditions);

    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE $whereClause";
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();

    // Get users
    $query = "SELECT * FROM users WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    return [
        'users' => $users,
        'total' => $total,
        'pages' => ceil($total / $perPage)
    ];
}

/**
 * Get user status counts for admin dashboard
 * @return array Counts by status and type
 */
function getUserStatusCounts() {
    global $conn;

    $counts = [
        'all' => 0,
        'active' => 0,
        'suspended' => 0,
        'unverified' => 0,
        'students' => 0,
        'landlords' => 0,
        'admins' => 0
    ];

    // Total users (excluding deleted)
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE account_status != 'deleted'");
    $counts['all'] = $result->fetch_assoc()['total'];

    // Active users
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE account_status = 'active'");
    $counts['active'] = $result->fetch_assoc()['total'];

    // Suspended users
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE account_status = 'suspended'");
    $counts['suspended'] = $result->fetch_assoc()['total'];

    // Unverified users (not yet verified)
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE account_verified = 0 AND account_status != 'deleted'");
    $counts['unverified'] = $result->fetch_assoc()['total'];

    // Students count
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'student' AND account_status != 'deleted'");
    $counts['students'] = $result->fetch_assoc()['total'];

    // Landlords count
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'landlord' AND account_status != 'deleted'");
    $counts['landlords'] = $result->fetch_assoc()['total'];

    // Admins count
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'admin' AND account_status != 'deleted'");
    $counts['admins'] = $result->fetch_assoc()['total'];

    return $counts;
}

/**
 * Verify user account (admin action)
 * @param int $userId User ID
 * @return bool True if successful
 */
function verifyUserAccount($userId) {
    $userClass = new User();
    $success = $userClass->markAccountVerified($userId);
    
    if ($success) {
        logActivity($userId, 'account_verified', 'Account verified by admin');
    }
    
    return $success;
}

/**
 * Suspend user account
 * @param int $userId User ID
 * @param string $reason Suspension reason
 * @return bool True if successful
 */
function suspendUser($userId, $reason = '') {
    $userClass = new User();
    $success = $userClass->updateAccountStatus($userId, 'suspended');
    
    if ($success) {
        logActivity($userId, 'account_suspended', 'Account suspended: ' . $reason);
    }
    
    return $success;
}

/**
 * Activate user account
 * @param int $userId User ID
 * @return bool True if successful
 */
function activateUser($userId) {
    $userClass = new User();
    $success = $userClass->updateAccountStatus($userId, 'active');
    
    if ($success) {
        logActivity($userId, 'account_activated', 'Account activated');
    }
    
    return $success;
}

/**
 * Delete user account
 * @param int $userId User ID
 * @return bool True if successful
 */
function deleteUser($userId) {
    $userClass = new User();
    $success = $userClass->updateAccountStatus($userId, 'deleted');
    
    if ($success) {
        logActivity($userId, 'account_deleted', 'Account deleted');
    }
    
    return $success;
}

// ACTIVITY LOGGING
/**
 * Log user activity
 * @param int $userId User ID (null for anonymous)
 * @param string $action Action type
 * @param string $description Action description
 * @return bool True if successful
 */
function logActivity($userId = null, $action = '', $description = '') {
    $userClass = new User();

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    return $userClass->logActivity($userId, $action, $description, $ipAddress, $userAgent);
}

// USER STATISTIC
/**
 * Get user statistics
 * @return array Statistics array
 */
function getUserStatistics() {
    $userClass = new User();
    return $userClass->getUserStatistics();
}

/**
 * Get user activity history
 * @param int $userId User ID
 * @param int $limit Number of records
 * @return array Activity logs
 */
function getUserActivityHistory($userId, $limit = 10) {
    $userClass = new User();
    return $userClass->getUserActivityHistory($userId, $limit);
}

/**
 * Get user activity log (alias for getUserActivityHistory for admin views)
 * @param int $userId User ID
 * @param int $limit Number of records
 * @return array Activity logs
 */
function getUserActivityLog($userId, $limit = 10) {
    return getUserActivityHistory($userId, $limit);
}

?>