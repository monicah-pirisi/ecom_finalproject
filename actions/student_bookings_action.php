<?php
/**
 * CampusDigs Kenya - Student Bookings Action
 * Handles student booking operations (cancel, payment, etc.)
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

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    sendJSON([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
}

$studentId = $_SESSION['user_id'];

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
        case 'cancel':
            handleCancelBooking($bookingId, $studentId);
            break;

        case 'payment_confirm':
            handlePaymentConfirmation($bookingId, $studentId);
            break;

        default:
            sendJSON([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    error_log("Student booking action error: " . $e->getMessage());

    sendJSON([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Handle cancel booking request
 * @param int $bookingId Booking ID
 * @param int $studentId Student ID
 */
function handleCancelBooking($bookingId, $studentId) {
    // Get cancellation reason
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    if (empty($reason)) {
        sendJSON([
            'success' => false,
            'message' => 'Please provide a reason for cancellation'
        ]);
    }

    // Cancel booking
    $result = cancelBooking($bookingId, $studentId, $reason);

    if ($result['success']) {
        // Format refund amount
        $refundFormatted = formatCurrency($result['refund_amount']);

        sendJSON([
            'success' => true,
            'message' => $result['message'],
            'refund_amount' => $result['refund_amount'],
            'refund_formatted' => $refundFormatted,
            'details' => "You will receive a refund of {$refundFormatted} within 5-7 business days."
        ]);
    } else {
        sendJSON([
            'success' => false,
            'message' => $result['message'],
            'refund_amount' => 0
        ]);
    }
}

/**
 * Handle payment confirmation (placeholder for Paystack integration)
 * @param int $bookingId Booking ID
 * @param int $studentId Student ID
 */
function handlePaymentConfirmation($bookingId, $studentId) {
    // Get payment reference
    $paymentReference = isset($_POST['reference']) ? sanitizeInput($_POST['reference']) : '';

    if (empty($paymentReference)) {
        sendJSON([
            'success' => false,
            'message' => 'Invalid payment reference'
        ]);
    }

    // Verify booking ownership
    $booking = getStudentBookingById($bookingId, $studentId);

    if (!$booking) {
        sendJSON([
            'success' => false,
            'message' => 'Booking not found or access denied'
        ]);
    }

    // TODO: Integrate with Paystack API to verify payment
    // For now, return placeholder response

    sendJSON([
        'success' => true,
        'message' => 'Payment verification is being processed. You will receive confirmation shortly.',
        'reference' => $paymentReference
    ]);
}

?>
