<?php
/**
 * CampusDigs Kenya - Clear Wishlist Action
 * Clears all properties from student's wishlist
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

// Validate request method (should be POST for destructive actions)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: ../view/student_wishlist.php');
    exit();
}

// Get student ID
$studentId = $_SESSION['user_id'];

// Confirm action (check for confirmation parameter)
$confirm = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';

if (!$confirm) {
    $_SESSION['error'] = 'Please confirm that you want to clear your wishlist';
    header('Location: ../view/student_wishlist.php');
    exit();
}

// Clear entire wishlist
try {
    $success = clearWishlist($studentId);

    if ($success) {
        $_SESSION['success'] = 'Your wishlist has been cleared successfully';
    } else {
        $_SESSION['error'] = 'Failed to clear wishlist. Please try again.';
    }

} catch (Exception $e) {
    error_log("Wishlist clear error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

// Redirect back to wishlist
header('Location: ../view/student_wishlist.php');
exit();
?>
