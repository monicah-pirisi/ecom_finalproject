<?php
/**
 * CampusDigs Kenya - Landlord Bookings Action
 * Handles approve and reject booking operations
 */

// Suppress errors and warnings to prevent HTML output before JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Start output buffering to catch any stray output
ob_start();

session_start();

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/booking_controller.php';

// Clean output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json');

// Check if user is logged in and is a landlord
if (!isLoggedIn() || !isLandlord()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$landlordId = $_SESSION['user_id'];

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
$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;

// Validate booking ID
if (!$bookingId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid booking ID'
    ]);
    exit();
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
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            exit();
    }
} catch (Exception $e) {
    error_log("Booking action error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
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
        echo json_encode([
            'success' => true,
            'message' => 'Booking approved successfully! The student will be notified.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to approve booking. Please try again or contact support.'
        ]);
    }
    exit();
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
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason for rejection'
        ]);
        exit();
    }

    // Reject booking
    $success = rejectBooking($bookingId, $landlordId, $reason);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking rejected successfully. The student will be notified.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reject booking. Please try again or contact support.'
        ]);
    }
    exit();
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
        echo json_encode([
            'success' => true,
            'message' => 'Booking marked as completed successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to complete booking. Please try again or contact support.'
        ]);
    }
    exit();
}

?>