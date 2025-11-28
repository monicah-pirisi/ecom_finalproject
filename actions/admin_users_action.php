<?php
/**
 * CampusDigs Kenya - Admin Users Action
 * Handles verify, suspend, reactivate, and delete user operations
 */

// Suppress warnings and notices to ensure clean JSON output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Start output buffering to catch any stray output
ob_start();

session_start();

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';

// Clear any buffered output before sending JSON
ob_end_clean();

// Set JSON header for AJAX responses
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$adminId = $_SESSION['user_id'];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get action
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// Validate user ID
if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit();
}

// Prevent admin from modifying themselves
if ($userId === $adminId) {
    echo json_encode([
        'success' => false,
        'message' => 'You cannot modify your own account through this interface'
    ]);
    exit();
}

try {
    switch ($action) {
        case 'verify':
            handleVerifyUser($userId, $adminId);
            break;

        case 'suspend':
            handleSuspendUser($userId, $adminId);
            break;

        case 'reactivate':
            handleReactivateUser($userId, $adminId);
            break;

        case 'delete':
            handleDeleteUser($userId, $adminId);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            exit();
    }
} catch (Exception $e) {
    error_log("Admin user action error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}

/**
 * Handle verify user request
 * @param int $userId User ID
 * @param int $adminId Admin ID
 */
function handleVerifyUser($userId, $adminId) {
    // Get user details
    $user = getUserById($userId);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }

    // Check if already verified
    if ($user['account_verified']) {
        echo json_encode([
            'success' => false,
            'message' => 'User account is already verified'
        ]);
        exit();
    }

    // Verify user
    $success = verifyUserAccount($userId, $adminId);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'verify_user', "Verified user account #{$userId}: {$user['full_name']} ({$user['user_type']})");

        // TODO: Send email notification to user
        // sendAccountVerificationEmail($userId);

        echo json_encode([
            'success' => true,
            'message' => 'User account verified successfully! The user now has full platform access.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to verify user account. Please try again.'
        ]);
    }
    exit();
}

/**
 * Handle suspend user request
 * @param int $userId User ID
 * @param int $adminId Admin ID
 */
function handleSuspendUser($userId, $adminId) {
    // Get suspension reason
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    if (empty($reason)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason for suspension'
        ]);
        exit();
    }

    // Get user details
    $user = getUserById($userId);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }

    // Prevent suspending admins
    if ($user['user_type'] === 'admin') {
        echo json_encode([
            'success' => false,
            'message' => 'Admin accounts cannot be suspended'
        ]);
        exit();
    }

    // Check if already suspended
    if ($user['account_status'] === 'suspended') {
        echo json_encode([
            'success' => false,
            'message' => 'User account is already suspended'
        ]);
        exit();
    }

    // Suspend user
    $success = suspendUserAccount($userId, $adminId, $reason);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'suspend_user', "Suspended user account #{$userId}: {$user['full_name']} - Reason: {$reason}");

        // TODO: Send email notification to user
        // sendAccountSuspensionEmail($userId, $reason);

        echo json_encode([
            'success' => true,
            'message' => 'User account suspended successfully. The user can no longer access the platform.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to suspend user account. Please try again.'
        ]);
    }
    exit();
}

/**
 * Handle reactivate user request
 * @param int $userId User ID
 * @param int $adminId Admin ID
 */
function handleReactivateUser($userId, $adminId) {
    // Get user details
    $user = getUserById($userId);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }

    // Check if suspended
    if ($user['account_status'] !== 'suspended') {
        echo json_encode([
            'success' => false,
            'message' => 'User account is not suspended'
        ]);
        exit();
    }

    // Reactivate user
    $success = reactivateUserAccount($userId, $adminId);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'reactivate_user', "Reactivated suspended user account #{$userId}: {$user['full_name']}");

        // TODO: Send email notification to user
        // sendAccountReactivationEmail($userId);

        echo json_encode([
            'success' => true,
            'message' => 'User account reactivated successfully. The user can now access the platform again.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reactivate user account. Please try again.'
        ]);
    }
    exit();
}

/**
 * Handle delete user request
 * @param int $userId User ID
 * @param int $adminId Admin ID
 */
function handleDeleteUser($userId, $adminId) {
    // Get deletion reason
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    if (empty($reason)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason for deletion (for audit purposes)'
        ]);
        exit();
    }

    // Get user details
    $user = getUserById($userId);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit();
    }

    // Prevent deleting admins
    if ($user['user_type'] === 'admin') {
        echo json_encode([
            'success' => false,
            'message' => 'Admin accounts cannot be deleted through this interface'
        ]);
        exit();
    }

    // Delete user (soft delete - mark as deleted)
    $success = deleteUserAccount($userId, $adminId, $reason);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'delete_user', "Deleted user account #{$userId}: {$user['full_name']} ({$user['user_type']}) - Reason: {$reason}");

        // TODO: Send final email notification to user
        // sendAccountDeletionEmail($user['email'], $user['full_name']);

        echo json_encode([
            'success' => true,
            'message' => 'User account deleted successfully. All associated data has been marked for deletion.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete user account. Please try again.'
        ]);
    }
    exit();
}

/**
 * Log admin action to database
 * @param int $adminId Admin ID
 * @param string $action Action type
 * @param string $details Action details
 */
function logAdminAction($adminId, $action, $details) {
    global $conn;

    try {
        // Check if admin_logs table exists, if not use activity_logs
        $tableExists = $conn->query("SHOW TABLES LIKE 'admin_logs'")->num_rows > 0;

        if ($tableExists) {
            $stmt = $conn->prepare("
                INSERT INTO admin_logs (admin_id, action, details, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("iss", $adminId, $action, $details);
            $stmt->execute();
            $stmt->close();
        } else {
            // Fallback to activity_logs or just log to error_log
            if (function_exists('logActivity')) {
                logActivity($adminId, $action, $details);
            }
        }
    } catch (Exception $e) {
        // Silently fail - don't break the main operation
    }
}
?>
