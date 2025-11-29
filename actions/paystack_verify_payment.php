<?php
/**
 * Paystack Payment Verification & Callback Handler
 * CampusDigs Kenya - Verify payment and create/update booking
 */

// Suppress errors and warnings to prevent HTML output before JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Start output buffering to catch any stray output
ob_start();

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../includes/paystack_config.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/booking_controller.php';
require_once '../controllers/user_controller.php';

// Clean output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Session expired. Please login again.'
    ]);
    exit();
}

// Get verification reference from POST data
$input = json_decode(file_get_contents('php://input'), true);
$reference = isset($input['reference']) ? trim($input['reference']) : null;

if (!$reference) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No payment reference provided'
    ]);
    exit();
}

// Optional: Verify reference matches session
$session_payment = isset($_SESSION['paystack_payment']) ? $_SESSION['paystack_payment'] : null;

try {
    global $conn;

    // Verify transaction with Paystack
    $verification_response = paystack_verify_transaction($reference);

    if (!$verification_response) {
        throw new Exception("No response from Paystack verification API");
    }

    // Check if verification was successful
    if (!isset($verification_response['status']) || $verification_response['status'] !== true) {
        $error_msg = $verification_response['message'] ?? 'Payment verification failed';

        echo json_encode([
            'status' => 'error',
            'message' => $error_msg,
            'verified' => false
        ]);
        exit();
    }

    // Extract transaction data
    $transaction_data = $verification_response['data'] ?? [];
    $payment_status = $transaction_data['status'] ?? null;
    $amount_paid = isset($transaction_data['amount']) ? $transaction_data['amount'] / 100 : 0; // Convert from cents
    $customer_email = $transaction_data['customer']['email'] ?? '';
    $authorization = $transaction_data['authorization'] ?? [];
    $authorization_code = $authorization['authorization_code'] ?? '';
    $payment_method = $authorization['channel'] ?? 'card';

    // Convert Paystack ISO 8601 datetime to MySQL format
    $paid_at_raw = $transaction_data['paid_at'] ?? null;
    if ($paid_at_raw) {
        try {
            $datetime_obj = new DateTime($paid_at_raw);
            $paid_at = $datetime_obj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $paid_at = date('Y-m-d H:i:s');
        }
    } else {
        $paid_at = date('Y-m-d H:i:s');
    }

    // Extract metadata
    $metadata = $transaction_data['metadata'] ?? [];
    $booking_id = $metadata['booking_id'] ?? ($session_payment['booking_id'] ?? 0);
    $property_id = $metadata['property_id'] ?? ($session_payment['property_id'] ?? 0);
    $payment_type = $metadata['payment_type'] ?? ($session_payment['payment_type'] ?? 'full');
    $student_id = $_SESSION['user_id'];

    // Validate payment status
    if ($payment_status !== 'success') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Payment was not successful. Status: ' . ucfirst($payment_status),
            'verified' => false,
            'payment_status' => $payment_status
        ]);
        exit();
    }

    // Get expected amount from session
    $expected_amount = $session_payment['amount'] ?? $amount_paid;

    // Verify amount matches (with KES 1 tolerance for rounding)
    if (abs($amount_paid - $expected_amount) > 1) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Payment amount does not match expected amount',
            'verified' => false,
            'expected' => number_format($expected_amount, 2),
            'paid' => number_format($amount_paid, 2)
        ]);
        exit();
    }

    // Get property details (optional)
    $property = getPropertyById($property_id);
    $property_title = $property ? $property['title'] : 'Property #' . $property_id;

    // Begin database transaction
    $conn->begin_transaction();

    try {
        // Record payment in payments table
        $stmt = $conn->prepare("
            INSERT INTO payments
            (booking_id, student_id, property_id, amount, payment_method, payment_reference,
             authorization_code, payment_status, paid_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $payment_status_db = 'completed';
        $stmt->bind_param("iiidsssss",
            $booking_id, $student_id, $property_id, $amount_paid,
            $payment_method, $reference, $authorization_code, $payment_status_db, $paid_at
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to record payment: " . $stmt->error);
        }

        $payment_id = $stmt->insert_id;
        $stmt->close();

        // If booking exists, update it
        if ($booking_id > 0) {
            $stmt = $conn->prepare("
                UPDATE bookings
                SET payment_status = 'paid', payment_reference = ?, updated_at = NOW()
                WHERE id = ? AND student_id = ?
            ");
            $stmt->bind_param("sii", $reference, $booking_id, $student_id);
            $stmt->execute();
            $stmt->close();
        }

        // Commit database transaction
        $conn->commit();

        // Clear session payment data
        unset($_SESSION['paystack_payment']);

        // Log user activity (function not yet implemented)
        // logActivity($student_id, 'payment_completed',
        //     "Completed payment via Paystack - Property: {$property_title}, Amount: KES $amount_paid, Reference: $reference");

        // Return success response
        echo json_encode([
            'status' => 'success',
            'verified' => true,
            'message' => 'Payment successful! Booking confirmed.',
            'payment_id' => $payment_id,
            'booking_id' => $booking_id,
            'property_id' => $property_id,
            'property_title' => $property_title,
            'amount_paid' => number_format($amount_paid, 2),
            'currency' => 'KES',
            'payment_date' => date('F j, Y', strtotime($paid_at)),
            'payment_reference' => $reference,
            'payment_method' => ucfirst($payment_method),
            'customer_email' => $customer_email
        ]);

    } catch (Exception $e) {
        // Rollback database transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'verified' => false,
        'message' => 'Payment processing error: ' . $e->getMessage()
    ]);
}
?>
