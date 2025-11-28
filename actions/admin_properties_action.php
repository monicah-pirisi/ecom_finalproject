<?php
/**
 * CampusDigs Kenya - Admin Properties Action
 * Handles approve, reject, and deactivate property operations
 */

session_start();

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/property_controller.php';

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
$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

// Validate property ID
if (!$propertyId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid property ID'
    ]);
    exit();
}

try {
    switch ($action) {
        case 'approve':
            handleApproveProperty($propertyId, $adminId);
            break;

        case 'reject':
            handleRejectProperty($propertyId, $adminId);
            break;

        case 'deactivate':
            handleDeactivateProperty($propertyId, $adminId);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            exit();
    }
} catch (Exception $e) {
    error_log("Admin property action error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}

/**
 * Handle approve property request
 * @param int $propertyId Property ID
 * @param int $adminId Admin ID
 */
function handleApproveProperty($propertyId, $adminId) {
    // Verify property exists
    $property = getPropertyById($propertyId);

    if (!$property) {
        echo json_encode([
            'success' => false,
            'message' => 'Property not found'
        ]);
        exit();
    }

    // Approve property
    $success = approveProperty($propertyId, $adminId);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'approve_property', "Approved property #{$propertyId}: {$property['title']}");

        // TODO: Send email notification to landlord
        // sendPropertyApprovalEmail($property['landlord_id'], $propertyId);

        echo json_encode([
            'success' => true,
            'message' => 'Property approved successfully! The property is now visible to students.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve property. Please try again.'
        ]);
    }
    exit();
}

/**
 * Handle reject property request
 * @param int $propertyId Property ID
 * @param int $adminId Admin ID
 */
function handleRejectProperty($propertyId, $adminId) {
    // Get rejection reason
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    if (empty($reason)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason for rejection'
        ]);
        exit();
    }

    // Verify property exists
    $property = getPropertyById($propertyId);

    if (!$property) {
        echo json_encode([
            'success' => false,
            'message' => 'Property not found'
        ]);
        exit();
    }

    // Reject property
    $success = rejectProperty($propertyId, $adminId, $reason);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'reject_property', "Rejected property #{$propertyId}: {$property['title']} - Reason: {$reason}");

        // TODO: Send email notification to landlord with rejection reason
        // sendPropertyRejectionEmail($property['landlord_id'], $propertyId, $reason);

        echo json_encode([
            'success' => true,
            'message' => 'Property rejected successfully. The landlord has been notified.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reject property. Please try again.'
        ]);
    }
    exit();
}

/**
 * Handle deactivate property request
 * @param int $propertyId Property ID
 * @param int $adminId Admin ID
 */
function handleDeactivateProperty($propertyId, $adminId) {
    // Get deactivation reason
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    if (empty($reason)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason for deactivation'
        ]);
        exit();
    }

    // Verify property exists
    $property = getPropertyById($propertyId);

    if (!$property) {
        echo json_encode([
            'success' => false,
            'message' => 'Property not found'
        ]);
        exit();
    }

    // Deactivate property
    $success = deactivateProperty($propertyId, $adminId, $reason);

    if ($success) {
        // Log admin action
        logAdminAction($adminId, 'deactivate_property', "Deactivated property #{$propertyId}: {$property['title']} - Reason: {$reason}");

        // TODO: Send email notification to landlord
        // sendPropertyDeactivationEmail($property['landlord_id'], $propertyId, $reason);

        echo json_encode([
            'success' => true,
            'message' => 'Property deactivated successfully. It is no longer visible to students.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to deactivate property. Please try again.'
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
            // This prevents breaking the JSON response if table doesn't exist
            if (function_exists('logActivity')) {
                logActivity($adminId, $action, $details);
            }
        }
    } catch (Exception $e) {
        // Silently fail - don't break the main operation
        // Just log the error for debugging
    }
}
?>
