<?php
/**
 * Get Student Recommendation Profile
 * Returns the student's preference profile used for recommendations
 */

// Suppress ALL errors and warnings to prevent HTML output before JSON
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Start output buffering to catch any stray output
ob_start();

session_start();

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/recommendation_controller.php';

// Clean ALL output buffer content
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh buffer
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in as student
if (!isLoggedIn() || !isStudent()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in as a student'
    ]);
    exit();
}

try {
    $studentId = $_SESSION['user_id'];

    $profile = getStudentProfile($studentId);

    echo json_encode([
        'success' => true,
        'profile' => $profile
    ]);

} catch (Exception $e) {
    error_log("Get student profile error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load profile'
    ]);
}
?>
