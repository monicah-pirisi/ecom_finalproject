<?php
/**
 * CampusDigs Kenya - Remove from Wishlist Action
 * Removes a property from student's wishlist (with redirect)
 * MVC Architecture - Controller Layer
 */

// Start session
session_start();

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/wishlist_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please login to access wishlist';
    header('Location: ../view/login.php');
    exit();
}

// Check if student
if (!isStudent()) {
    $_SESSION['error'] = 'Only students can use wishlist';
    header('Location: ../index.php');
    exit();
}

// Get student ID
$studentId = $_SESSION['user_id'];

// Get property ID from URL
$propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

// Validate property ID
if (!$propertyId) {
    $_SESSION['error'] = 'Invalid property ID';
    header('Location: ../view/student_wishlist.php');
    exit();
}

// Remove from wishlist
try {
    $success = removeFromWishlist($studentId, $propertyId);

    if ($success) {
        $_SESSION['success'] = 'Property removed from your wishlist';
    } else {
        $_SESSION['error'] = 'Failed to remove property from wishlist';
    }

} catch (Exception $e) {
    error_log("Wishlist remove error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

// Redirect back to wishlist
header('Location: ../view/student_wishlist.php');
exit();
?>
