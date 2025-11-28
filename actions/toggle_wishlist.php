<?php
/**
 * CampusDigs Kenya - Toggle Wishlist Action
 * Adds or removes property from student's wishlist
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
require_once '../controllers/user_controller.php';

// Clean output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json');

// SECURITY CHECKS

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to use wishlist',
        'login_required' => true
    ]);
    exit;
}

// Check if user is a student
if ($_SESSION['user_type'] !== 'student') {
    echo json_encode([
        'success' => false,
        'message' => 'Only students can add properties to wishlist'
    ]);
    exit;
}

// VALIDATE INPUT

if (!isset($_GET['property_id']) || empty($_GET['property_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Property ID is required'
    ]);
    exit;
}

$propertyId = (int)$_GET['property_id'];
$studentId = $_SESSION['user_id'];

// Verify database connection
if (!$conn) {
    error_log("Wishlist error: Database connection not available");
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error. Please try again.'
    ]);
    exit;
}

// Verify property exists and is active
$stmt = $conn->prepare("SELECT id, title FROM properties WHERE id = ? AND status = 'active'");
if (!$stmt) {
    error_log("Wishlist error: Failed to prepare property query - " . $conn->error);
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again.'
    ]);
    exit;
}

$stmt->bind_param("i", $propertyId);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    echo json_encode([
        'success' => false,
        'message' => 'Property not found or not available'
    ]);
    exit;
}

// TOGGLE WISHLIST

try {
    // Check if already in wishlist
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE student_id = ? AND property_id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare wishlist check query: " . $conn->error);
    }
    $stmt->bind_param("ii", $studentId, $propertyId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute wishlist check: " . $stmt->error);
    }
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Remove from wishlist
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE student_id = ? AND property_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare delete query: " . $conn->error);
        }
        $stmt->bind_param("ii", $studentId, $propertyId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to remove from wishlist: " . $stmt->error);
        }
        $stmt->close();

        // Log activity
        logActivity($studentId, 'wishlist_removed', "Removed property from wishlist: " . $property['title']);

        // Get updated wishlist count
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE student_id = ?");
        $countStmt->bind_param("i", $studentId);
        $countStmt->execute();
        $wishlistCount = $countStmt->get_result()->fetch_assoc()['count'];
        $countStmt->close();

        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'Property removed from wishlist',
            'in_wishlist' => false,
            'wishlist_count' => $wishlistCount
        ]);
    } else {
        // Add to wishlist
        $stmt = $conn->prepare("INSERT INTO wishlist (student_id, property_id) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare insert query: " . $conn->error);
        }
        $stmt->bind_param("ii", $studentId, $propertyId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to add to wishlist: " . $stmt->error);
        }
        $stmt->close();

        // Log activity
        logActivity($studentId, 'wishlist_added', "Added property to wishlist: " . $property['title']);

        // Get updated wishlist count
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE student_id = ?");
        $countStmt->bind_param("i", $studentId);
        $countStmt->execute();
        $wishlistCount = $countStmt->get_result()->fetch_assoc()['count'];
        $countStmt->close();

        echo json_encode([
            'success' => true,
            'action' => 'added',
            'message' => 'Property added to wishlist',
            'in_wishlist' => true,
            'wishlist_count' => $wishlistCount
        ]);
    }
} catch (Exception $e) {
    error_log("Wishlist toggle error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>
