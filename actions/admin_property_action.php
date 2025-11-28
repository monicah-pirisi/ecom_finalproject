<?php
/**
 * Admin Property Actions
 * Handle admin-level property operations (delete, etc.)
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/user_controller.php';

// Require admin authentication
requireAdmin();

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('../admin/manage_properties.php', 'Invalid request method', 'error');
}

// Verify CSRF token
if (!verifyCsrfToken()) {
    redirectWithMessage('../admin/manage_properties.php', 'Invalid security token. Please try again.', 'error');
}

// Get action
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';

try {
    switch ($action) {
        case 'deactivate':
            handleDeactivateProperty();
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    error_log("Admin Property Action Error: " . $e->getMessage());
    redirectWithMessage('../admin/manage_properties.php', $e->getMessage(), 'error');
}

/**
 * Handle property deactivation (admin only - with reason)
 */
function handleDeactivateProperty() {
    global $conn;

    // Get property ID and reason
    $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    if (!$propertyId) {
        throw new Exception('Invalid property ID');
    }

    if (empty($reason)) {
        throw new Exception('Deactivation reason is required');
    }

    // Get property details for logging
    $property = getPropertyById($propertyId);
    if (!$property) {
        throw new Exception('Property not found');
    }

    if ($property['status'] !== 'active') {
        throw new Exception('Only active properties can be deactivated');
    }

    // Update property status to inactive
    $stmt = $conn->prepare("UPDATE properties SET status = 'inactive', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $propertyId);
    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        throw new Exception('Failed to deactivate property');
    }

    // Log admin action with reason
    logActivity($_SESSION['user_id'], 'admin_deactivate_property',
        "Admin deactivated property: {$property['title']} (ID: {$propertyId}). Reason: {$reason}");

    // TODO: Optionally notify landlord about deactivation
    // Could send email or create notification

    redirectWithMessage('../admin/manage_properties.php',
        'Property deactivated successfully', 'success');
}
?>
