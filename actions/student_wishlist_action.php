<?php
/**
 * CampusDigs Kenya - Wishlist Action
 * AJAX handler for adding/removing properties from wishlist
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
        'message' => 'Please login to use wishlist'
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

// Get student ID
$studentId = $_SESSION['user_id'];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get action and property ID
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

// Validate inputs
if (!in_array($action, ['add', 'remove'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
    exit();
}

if (!$propertyId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid property ID'
    ]);
    exit();
}

// Perform action
try {
    if ($action === 'add') {
        // Add to wishlist
        $success = addToWishlist($studentId, $propertyId);
        
        if ($success) {
            $wishlistCount = getWishlistCount($studentId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Property added to wishlist',
                'wishlist_count' => $wishlistCount
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Property already in wishlist or failed to add'
            ]);
        }
        
    } else {
        // Remove from wishlist
        $success = removeFromWishlist($studentId, $propertyId);
        
        if ($success) {
            $wishlistCount = getWishlistCount($studentId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Property removed from wishlist',
                'wishlist_count' => $wishlistCount
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to remove property from wishlist'
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("Wishlist action error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

exit();
?>