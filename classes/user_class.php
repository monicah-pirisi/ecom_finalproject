<?php
/**
 * CampusDigs Kenya - User Class
 * Handles all database operations for users
 * MVC Architecture - Model Layer
 */

class User {
    
    private $conn;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->conn = $GLOBALS['conn'];
    }
    
    // USER REGISTRATION
    
    /**
     * Register a new user
     * @param string $userType 'student' or 'landlord'
     * @param string $fullName Full name
     * @param string $email Email address
     * @param string $phone Phone number
     * @param string $password Hashed password
     * @param string $university University name (for students)
     * @param string $studentId Student ID (for students)
     * @return int|false User ID if successful, false otherwise
     */
    public function registerUser($userType, $fullName, $email, $phone, $password, $university = null, $studentId = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO users (user_type, full_name, email, phone, password, university, student_id, account_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("sssssss", $userType, $fullName, $email, $phone, $password, $university, $studentId);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $stmt->close();
            return $userId;
        } else {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
    
    /**
     * Check if email already exists
     * @param string $email Email to check
     * @return bool True if email exists
     */
    public function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND account_status != 'deleted' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    /**
     * Check if phone already exists
     * @param string $phone Phone to check
     * @return bool True if phone exists
     */
    public function phoneExists($phone) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE phone = ? AND account_status != 'deleted' LIMIT 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    // USER RETRIEVAL    
    /**
     * Get user by ID
     * @param int $userId User ID
     * @return array|false User data if found, false otherwise
     */
    public function getUserById($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Get user by email or phone
     * @param string $identifier Email or phone
     * @param string $field 'email' or 'phone'
     * @return array|false User data if found, false otherwise
     */
    public function getUserByEmailOrPhone($identifier, $field = 'email') {
        $allowedFields = ['email', 'phone'];
        if (!in_array($field, $allowedFields)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE $field = ? AND account_status != 'deleted' LIMIT 1");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Get all users with pagination
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param string $userType Filter by user type
     * @return array ['users' => array, 'total' => int, 'pages' => int]
     */
    public function getAllUsers($page = 1, $perPage = 20, $userType = null) {
        $offset = ($page - 1) * $perPage;
        
        // Build query based on filter
        if ($userType && in_array($userType, ['student', 'landlord', 'admin'])) {
            $sql = "SELECT * FROM users WHERE user_type = ? AND account_status != 'deleted' ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $countSql = "SELECT COUNT(*) as total FROM users WHERE user_type = ? AND account_status != 'deleted'";
        } else {
            $sql = "SELECT * FROM users WHERE account_status != 'deleted' ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $countSql = "SELECT COUNT(*) as total FROM users WHERE account_status != 'deleted'";
        }
        
        // Get total count
        if ($userType) {
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->bind_param("s", $userType);
        } else {
            $countStmt = $this->conn->prepare($countSql);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get users
        if ($userType) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sii", $userType, $perPage, $offset);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $perPage, $offset);
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
    
    // LOGIN ATTEMPTS & LOCKOUT    
    /**
     * Increment login attempts
     * @param int $userId User ID
     * @param int $attempts New attempt count
     * @return bool True if successful
     */
    public function incrementLoginAttempts($userId, $attempts) {
        $stmt = $this->conn->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
        $stmt->bind_param("ii", $attempts, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Lock user account
     * @param int $userId User ID
     * @param string $lockoutUntil Lockout expiry datetime
     * @return bool True if successful
     */
    public function lockAccount($userId, $lockoutUntil) {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET login_attempts = ?, lockout_until = ? 
            WHERE id = ?
        ");
        $maxAttempts = MAX_LOGIN_ATTEMPTS;
        $stmt->bind_param("isi", $maxAttempts, $lockoutUntil, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Reset login attempts
     * @param int $userId User ID
     * @return bool True if successful
     */
    public function resetLoginAttempts($userId) {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET login_attempts = 0, lockout_until = NULL 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Update last login time
     * @param int $userId User ID
     * @return bool True if successful
     */
    public function updateLastLogin($userId) {
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    // PROFILE MANAGEMENT
    
    /**
     * Update user profile
     * @param int $userId User ID
     * @param array $data Profile data
     * @return bool True if successful
     */
    public function updateUserProfile($userId, $data) {
        $allowedFields = ['full_name', 'phone', 'university', 'student_id'];
        $updates = [];
        $types = '';
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = ?";
                $types .= 's';
                $values[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $userId;
        $types .= 'i';
        
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Update user password
     * @param int $userId User ID
     * @param string $hashedPassword Hashed password
     * @return bool True if successful
     */
    public function updatePassword($userId, $hashedPassword) {
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    // VERIFICATION    
    /**
     * Mark email as verified
     * @param int $userId User ID
     * @return bool True if successful
     */
    public function markEmailVerified($userId) {
        $stmt = $this->conn->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Mark phone as verified
     * @param int $userId User ID
     * @return bool True if successful
     */
    public function markPhoneVerified($userId) {
        $stmt = $this->conn->prepare("UPDATE users SET phone_verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Mark account as verified (admin action)
     * @param int $userId User ID
     * @return bool True if successful
     */
    public function markAccountVerified($userId) {
        $stmt = $this->conn->prepare("UPDATE users SET account_verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    // ACCOUNT STATUS    
    /**
     * Update account status
     * @param int $userId User ID
     * @param string $status 'active', 'suspended', or 'deleted'
     * @return bool True if successful
     */
    public function updateAccountStatus($userId, $status) {
        $allowedStatuses = ['active', 'suspended', 'deleted'];
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("UPDATE users SET account_status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    // ACTIVITY LOGGING    
    /**
     * Log user activity
     * @param int $userId User ID (null for anonymous)
     * @param string $action Action type
     * @param string $description Action description
     * @param string $ipAddress IP address
     * @param string $userAgent User agent
     * @return bool True if successful
     */
    public function logActivity($userId, $action, $description, $ipAddress, $userAgent) {
        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issss", $userId, $action, $description, $ipAddress, $userAgent);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Get user activity history
     * @param int $userId User ID
     * @param int $limit Number of records
     * @return array Activity logs
     */
    public function getUserActivityHistory($userId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM activity_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $activities = [];
        
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        
        $stmt->close();
        return $activities;
    }
    
    // STATISTICS
    
    /**
     * Get user statistics
     * @return array Statistics array
     */
    public function getUserStatistics() {
        $stats = [];
        
        // Total users
        $result = $this->conn->query("SELECT COUNT(*) as total FROM users WHERE account_status != 'deleted'");
        $stats['total_users'] = $result->fetch_assoc()['total'];
        
        // Students
        $result = $this->conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'student' AND account_status != 'deleted'");
        $stats['total_students'] = $result->fetch_assoc()['total'];
        
        // Landlords
        $result = $this->conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'landlord' AND account_status != 'deleted'");
        $stats['total_landlords'] = $result->fetch_assoc()['total'];
        
        // Verified accounts
        $result = $this->conn->query("SELECT COUNT(*) as total FROM users WHERE account_verified = 1 AND account_status != 'deleted'");
        $stats['verified_accounts'] = $result->fetch_assoc()['total'];
        
        // Active today
        $result = $this->conn->query("SELECT COUNT(*) as total FROM users WHERE DATE(last_login) = CURDATE() AND account_status != 'deleted'");
        $stats['active_today'] = $result->fetch_assoc()['total'];
        
        return $stats;
    }
}

?>