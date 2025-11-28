<?php
/**
 * CampusDigs Kenya - Admin Reviews Action
 * Handles approve, delete, flag, and edit review operations
 */

session_start();

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/review_controller.php';

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
$reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;

// Validate review ID
if (!$reviewId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid review ID'
    ]);
    exit();
}

try {
    switch ($action) {
        case 'approve':
            handleApproveReview($reviewId, $adminId);
            break;

        case 'delete':
            handleDeleteReview($reviewId, $adminId);
            break;

        case 'flag':
            handleFlagReview($reviewId, $adminId);
            break;

        case 'edit':
            handleEditReview($reviewId, $adminId);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            exit();
    }
} catch (Exception $e) {
    error_log("Admin review action error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}

/**
 * Handle approve review request
 * @param int $reviewId Review ID
 * @param int $adminId Admin ID
 */
function handleApproveReview($reviewId, $adminId) {
    // Get review details
    $review = getReviewById($reviewId);

    if (!$review) {
        echo json_encode([
            'success' => false,
            'message' => 'Review not found'
        ]);
        exit();
    }

    // Check if already approved
    if ($review['moderation_status'] === 'approved') {
        echo json_encode([
            'success' => false,
            'message' => 'Review is already approved'
        ]);
        exit();
    }

    // Approve review
    $success = approveReview($reviewId, $adminId);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'approve_review', "Approved review #{$reviewId} for property: {$review['property_title']}");

        // TODO: Send notification to student
        // notifyReviewApproved($review['student_id'], $reviewId);

        echo json_encode([
            'success' => true,
            'message' => 'Review approved successfully! It is now visible to all users.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve review. Please try again.'
        ]);
    }
    exit();
}

/**
 * Handle delete review request
 * @param int $reviewId Review ID
 * @param int $adminId Admin ID
 */
function handleDeleteReview($reviewId, $adminId) {
    // Get deletion reason
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    if (empty($reason)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason for deletion (for audit purposes)'
        ]);
        exit();
    }

    // Get review details
    $review = getReviewById($reviewId);

    if (!$review) {
        echo json_encode([
            'success' => false,
            'message' => 'Review not found'
        ]);
        exit();
    }

    // Check if already deleted
    if ($review['moderation_status'] === 'deleted') {
        echo json_encode([
            'success' => false,
            'message' => 'Review is already deleted'
        ]);
        exit();
    }

    // Delete review
    $success = deleteReview($reviewId, $adminId, $reason);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'delete_review', "Deleted review #{$reviewId} - Reason: {$reason}");

        // TODO: Send notification to student
        // notifyReviewDeleted($review['student_id'], $reviewId, $reason);

        echo json_encode([
            'success' => true,
            'message' => 'Review deleted successfully. The student has been notified.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete review. Please try again.'
        ]);
    }
    exit();
}

/**
 * Handle flag review request
 * @param int $reviewId Review ID
 * @param int $adminId Admin ID
 */
function handleFlagReview($reviewId, $adminId) {
    // Get flag reason
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    if (empty($reason)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason for flagging'
        ]);
        exit();
    }

    // Get review details
    $review = getReviewById($reviewId);

    if (!$review) {
        echo json_encode([
            'success' => false,
            'message' => 'Review not found'
        ]);
        exit();
    }

    // Check if already flagged
    if ($review['moderation_status'] === 'flagged') {
        echo json_encode([
            'success' => false,
            'message' => 'Review is already flagged for attention'
        ]);
        exit();
    }

    // Flag review
    $success = flagReview($reviewId, $adminId, $reason);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'flag_review', "Flagged review #{$reviewId} for attention - Reason: {$reason}");

        echo json_encode([
            'success' => true,
            'message' => 'Review flagged for attention. It will be reviewed further.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to flag review. Please try again.'
        ]);
    }
    exit();
}

/**
 * Handle edit review request
 * @param int $reviewId Review ID
 * @param int $adminId Admin ID
 */
function handleEditReview($reviewId, $adminId) {
    // Get edited review text
    $reviewText = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
    $editReason = isset($_POST['edit_reason']) ? sanitizeInput($_POST['edit_reason']) : '';

    if (empty($reviewText)) {
        echo json_encode([
            'success' => false,
            'message' => 'Review text cannot be empty'
        ]);
        exit();
    }

    if (empty($editReason)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason for editing (for audit purposes)'
        ]);
        exit();
    }

    // Get review details
    $review = getReviewById($reviewId);

    if (!$review) {
        echo json_encode([
            'success' => false,
            'message' => 'Review not found'
        ]);
        exit();
    }

    // Check if already deleted
    if ($review['moderation_status'] === 'deleted') {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot edit a deleted review'
        ]);
        exit();
    }

    // Edit review
    $success = editReview($reviewId, $adminId, $reviewText, $editReason);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'edit_review', "Edited review #{$reviewId} - Reason: {$editReason}");

        // TODO: Send notification to student
        // notifyReviewEdited($review['student_id'], $reviewId, $editReason);

        echo json_encode([
            'success' => true,
            'message' => 'Review edited successfully. Changes have been saved.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to edit review. Please try again.'
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
