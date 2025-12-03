<?php
/**
 * CampusDigs Kenya - Remove from Wishlist Action
 * Removes a property from wishlist (supports both logged-in users and guests)
 * MVC Architecture - Controller Layer
 */

// Include required files FIRST (they handle sessions)
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';
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

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get property ID from POST data
$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

// Validate property ID
if (!$propertyId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid property ID'
    ]);
    exit();
}

// Remove from wishlist
try {
    // GUEST USER: Remove from session wishlist
    if ($isGuest) {
        $success = removeFromGuestWishlist($propertyId);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Property removed from wishlist',
                'wishlist_count' => getGuestWishlistCount(),
                'is_guest' => true
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to remove property from wishlist'
            ]);
        }
    }
    // LOGGED-IN USER: Remove from database
    else {
        $studentId = $_SESSION['user_id'];
        $success = removeFromWishlist($studentId, $propertyId);

        if ($success) {
            $wishlistCount = getWishlistCount($studentId);

            echo json_encode([
                'success' => true,
                'message' => 'Property removed from wishlist',
                'wishlist_count' => $wishlistCount,
                'is_guest' => false
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to remove property from wishlist'
            ]);
        }
    }

} catch (Exception $e) {
    error_log("Wishlist remove error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

exit();
?>
