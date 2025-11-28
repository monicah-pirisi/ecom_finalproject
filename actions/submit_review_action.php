<?php
/**
 * CampusDigs Kenya - Submit Review Action
 * Handles student review submission for completed bookings
 */

// Suppress errors and warnings to prevent HTML output before JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Start output buffering to catch any stray output
ob_start();

session_start();

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';
require_once '../controllers/review_controller.php';

// Clean output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json');

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please log in as a student.'
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

try {
    // Get and validate inputs
    $bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Validate booking ID
    if (!$bookingId) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid booking ID'
        ]);
        exit();
    }

    // Validate property ID
    if (!$propertyId) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid property ID'
        ]);
        exit();
    }

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Rating must be between 1 and 5 stars'
        ]);
        exit();
    }

    // Sanitize comment
    if (!empty($comment)) {
        $comment = sanitizeInput($comment);

        // Limit comment length
        if (strlen($comment) > 1000) {
            echo json_encode([
                'success' => false,
                'message' => 'Review comment is too long (maximum 1000 characters)'
            ]);
            exit();
        }
    }

    // Create review
    $result = createReview($studentId, $propertyId, $bookingId, $rating, $comment);

    // Return result
    echo json_encode($result);
    exit();

} catch (Exception $e) {
    error_log("Submit review error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
    exit();
}
?>
