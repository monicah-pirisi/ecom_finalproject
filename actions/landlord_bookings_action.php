<?php
/**
 * CampusDigs Kenya - Landlord Bookings Action
 * Handles approve and reject booking operations
 */

// Start session first if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include JSON handler
require_once '../includes/json_handler.php';

try {
    require_once '../includes/config.php';
    require_once '../includes/core.php';
    require_once '../controllers/booking_controller.php';
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Configuration error: ' . $e->getMessage()
    ]);
}

// Check if user is logged in and is a landlord
if (!isLoggedIn() || !isLandlord()) {
    sendJSON([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
}

$landlordId = $_SESSION['user_id'];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

// Get action
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;

// Validate booking ID
if (!$bookingId) {
    sendJSON([
        'success' => false,
        'message' => 'Invalid booking ID'
    ]);
}

try {
    switch ($action) {
        case 'approve':
            handleApproveBooking($bookingId, $landlordId);
            break;

        case 'reject':
            handleRejectBooking($bookingId, $landlordId);
            break;

        case 'complete':
            handleCompleteBooking($bookingId, $landlordId);
            break;

        default:
            sendJSON([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    error_log("Booking action error: " . $e->getMessage());

    sendJSON([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle approve booking request
 * @param int $bookingId Booking ID
 * @param int $landlordId Landlord ID
 */
function handleApproveBooking($bookingId, $landlordId) {
    // Approve booking
    $success = approveBooking($bookingId, $landlordId);

    if ($success) {
        sendJSON([
            'success' => true,
            'message' => 'Booking approved successfully! The student will be notified.'
        ]);
    } else {
        sendJSON([
            'success' => false,
            'message' => 'Failed to approve booking. Please try again or contact support.'
        ]);
    }
}

/**
 * Handle reject booking request
 * @param int $bookingId Booking ID
 * @param int $landlordId Landlord ID
 */
function handleRejectBooking($bookingId, $landlordId) {
    // Get rejection reason
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    if (empty($reason)) {
        sendJSON([
            'success' => false,
            'message' => 'Please provide a reason for rejection'
        ]);
    }

    // Reject booking
    $success = rejectBooking($bookingId, $landlordId, $reason);

    if ($success) {
        sendJSON([
            'success' => true,
            'message' => 'Booking rejected successfully. The student will be notified.'
        ]);
    } else {
        sendJSON([
            'success' => false,
            'message' => 'Failed to reject booking. Please try again or contact support.'
        ]);
    }
}

/**
 * Handle complete booking request
 * @param int $bookingId Booking ID
 * @param int $landlordId Landlord ID
 */
function handleCompleteBooking($bookingId, $landlordId) {
    // Complete booking
    $success = completeBooking($bookingId, $landlordId);

    if ($success) {
        sendJSON([
            'success' => true,
            'message' => 'Booking marked as completed successfully!'
        ]);
    } else {
        sendJSON([
            'success' => false,
            'message' => 'Failed to complete booking. Please try again or contact support.'
        ]);
    }
}

?>