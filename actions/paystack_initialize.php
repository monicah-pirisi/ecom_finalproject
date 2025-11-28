<?php
/**
 * Paystack Payment Initialization
 * CampusDigs Kenya - Initialize booking payment
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

// Clean output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json');

// Check if user is logged in as student
if (!isLoggedIn() || $_SESSION['user_type'] !== 'student') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login as a student to make payment'
    ]);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = isset($input['booking_id']) ? (int)$input['booking_id'] : 0;
$property_id = isset($input['property_id']) ? (int)$input['property_id'] : 0;
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$payment_type = isset($input['payment_type']) ? $input['payment_type'] : 'full'; // 'full', 'deposit', 'rent'

// Validate inputs
if (!$property_id || !$amount) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid payment details'
    ]);
    exit();
}

if ($amount <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Amount must be greater than 0'
    ]);
    exit();
}

try {
    $student_id = $_SESSION['user_id'];
    $student_email = $_SESSION['email'];
    $student_name = $_SESSION['full_name'];

    // Get property details for metadata
    $property = getPropertyById($property_id);
    $property_title = $property ? $property['title'] : 'Property #' . $property_id;

    // Generate unique reference
    $reference = 'CDIGS-P' . $property_id . '-U' . $student_id . '-' . time();

    // Prepare metadata
    $metadata = [
        'booking_id' => $booking_id,
        'property_id' => $property_id,
        'property_title' => $property_title,
        'student_id' => $student_id,
        'student_name' => $student_name,
        'payment_type' => $payment_type,
        'custom_fields' => [
            [
                'display_name' => 'Property',
                'variable_name' => 'property_title',
                'value' => $property_title
            ],
            [
                'display_name' => 'Student',
                'variable_name' => 'student_name',
                'value' => $student_name
            ],
            [
                'display_name' => 'Payment Type',
                'variable_name' => 'payment_type',
                'value' => ucfirst($payment_type)
            ]
        ]
    ];

    // Initialize Paystack transaction
    $paystack_response = paystack_initialize_transaction($amount, $student_email, $reference, $metadata);

    if (!$paystack_response) {
        throw new Exception("No response from Paystack API");
    }

    if (isset($paystack_response['status']) && $paystack_response['status'] === true) {
        // Store transaction details in session for verification later
        $_SESSION['paystack_payment'] = [
            'reference' => $reference,
            'booking_id' => $booking_id,
            'property_id' => $property_id,
            'amount' => $amount,
            'payment_type' => $payment_type,
            'timestamp' => time()
        ];

        echo json_encode([
            'status' => 'success',
            'authorization_url' => $paystack_response['data']['authorization_url'],
            'reference' => $reference,
            'access_code' => $paystack_response['data']['access_code'],
            'message' => 'Redirecting to payment gateway...'
        ]);
    } else {
        $error_message = $paystack_response['message'] ?? 'Payment gateway error';
        throw new Exception($error_message);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to initialize payment: ' . $e->getMessage()
    ]);
}
?>
