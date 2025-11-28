<?php
/**
 * CampusDigs Kenya - Student Bookings Action
 * Handles student booking operations (cancel, payment, etc.)
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

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

$studentId = $_SESSION['user_id'];

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
        case 'cancel':
            handleCancelBooking($bookingId, $studentId);
            break;

        case 'payment_confirm':
            handlePaymentConfirmation($bookingId, $studentId);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            exit();
    }
} catch (Exception $e) {
    error_log("Student booking action error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
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
        echo json_encode([
            'success' => false,
            'message' => 'Please provide a reason for cancellation'
        ]);
        exit();
    }

    // Cancel booking
    $result = cancelBooking($bookingId, $studentId, $reason);

    if ($result['success']) {
        // Format refund amount
        $refundFormatted = formatCurrency($result['refund_amount']);

        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'refund_amount' => $result['refund_amount'],
            'refund_formatted' => $refundFormatted,
            'details' => "You will receive a refund of {$refundFormatted} within 5-7 business days."
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'refund_amount' => 0
        ]);
    }
    exit();
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
        echo json_encode([
            'success' => false,
            'message' => 'Invalid payment reference'
        ]);
        exit();
    }

    // Verify booking ownership
    $booking = getStudentBookingById($bookingId, $studentId);

    if (!$booking) {
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found or access denied'
        ]);
        exit();
    }

    // TODO: Integrate with Paystack API to verify payment
    // For now, return placeholder response

    echo json_encode([
        'success' => true,
        'message' => 'Payment verification is being processed. You will receive confirmation shortly.',
        'reference' => $paymentReference
    ]);

    exit();
}

?>
