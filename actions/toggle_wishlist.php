<?php
/**
 * CampusDigs Kenya - Toggle Wishlist Action
 * Adds or removes property from wishlist (supports both logged-in users and guests)
 * MVC Architecture - Controller Layer
 *
 * GUEST USERS: Can add/remove from wishlist stored in session
 * LOGGED-IN USERS: Wishlist saved to database
 */

// Include required files FIRST (they handle sessions)
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';

// NOW suppress errors and set JSON header
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Check if user is logged in or guest
$isLoggedIn = isLoggedIn();
$isGuest = !$isLoggedIn;

// If logged in, must be a student
if ($isLoggedIn && $_SESSION['user_type'] !== 'student') {
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
    // GUEST USER: Use session-based wishlist
    if ($isGuest) {
        // Check if property is already in guest wishlist
        $inWishlist = isInGuestWishlist($propertyId);

        if ($inWishlist) {
            // Remove from guest wishlist
            $success = removeFromGuestWishlist($propertyId);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'action' => 'removed',
                    'message' => 'Property removed from wishlist',
                    'in_wishlist' => false,
                    'wishlist_count' => getGuestWishlistCount(),
                    'is_guest' => true
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to remove property from wishlist'
                ]);
            }
        } else {
            // Add to guest wishlist
            $success = addToGuestWishlist($propertyId);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'action' => 'added',
                    'message' => 'Property added to wishlist',
                    'in_wishlist' => true,
                    'wishlist_count' => getGuestWishlistCount(),
                    'is_guest' => true
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Property already in wishlist or failed to add'
                ]);
            }
        }
    }
    // LOGGED-IN USER: Use database wishlist
    else {
        $studentId = $_SESSION['user_id'];

        // Check if already in database wishlist
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
            // Remove from database wishlist
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
                'wishlist_count' => $wishlistCount,
                'is_guest' => false
            ]);
        } else {
            // Add to database wishlist
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
                'wishlist_count' => $wishlistCount,
                'is_guest' => false
            ]);
        }
    }
} catch (Exception $e) {
    error_log("Wishlist toggle error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>
