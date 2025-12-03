<?php
/**
 * CampusDigs Kenya - Clear Wishlist Action
 * Clears all properties from wishlist (supports both logged-in users and guests)
 * MVC Architecture - Controller Layer
 */

// Include required files FIRST (they handle sessions)
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/wishlist_controller.php';

// NOW suppress errors and set JSON header
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Check if user is logged in or guest
$isLoggedIn = isLoggedIn();
$isGuest = !$isLoggedIn;

// If logged in, must be a student
if ($isLoggedIn && !isStudent()) {
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

// Clear entire wishlist (confirmation is handled by JavaScript confirm dialog)
try {
    // GUEST USER: Clear session wishlist
    if ($isGuest) {
        $success = clearGuestWishlist();

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Your wishlist has been cleared successfully',
                'wishlist_count' => 0,
                'is_guest' => true
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to clear wishlist. Please try again.'
            ]);
        }
    }
    // LOGGED-IN USER: Clear database wishlist
    else {
        $studentId = $_SESSION['user_id'];
        $success = clearWishlist($studentId);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Your wishlist has been cleared successfully',
                'wishlist_count' => 0,
                'is_guest' => false
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to clear wishlist. Please try again.'
            ]);
        }
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
