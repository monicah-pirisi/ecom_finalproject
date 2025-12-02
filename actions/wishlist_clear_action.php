<?php
/**
 * CampusDigs Kenya - Clear Wishlist Action
 * Clears all properties from student's wishlist
 * MVC Architecture - Controller Layer
 */

// Suppress errors and warnings to prevent HTML output before JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Start output buffering to catch any stray output
ob_start();

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/wishlist_controller.php';

// Clean output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to access wishlist'
    ]);
    exit();
}

// Check if student
if (!isStudent()) {
    echo json_encode([
        'success' => false,
        'message' => 'Only students can use wishlist'
    ]);
    exit();
}

// Validate request method (should be POST for destructive actions)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get student ID
$studentId = $_SESSION['user_id'];

// Clear entire wishlist (confirmation is handled by JavaScript confirm dialog)
try {
    $success = clearWishlist($studentId);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Your wishlist has been cleared successfully',
            'wishlist_count' => 0
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to clear wishlist. Please try again.'
        ]);
    }

} catch (Exception $e) {
    error_log("Wishlist clear error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

exit();
?>
