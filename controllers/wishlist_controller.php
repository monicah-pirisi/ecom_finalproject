<?php
/**
 * CampusDigs Kenya - Wishlist Controller
 * Handles all wishlist-related business logic
 * MVC Architecture - Controller Layer
 */

require_once dirname(__DIR__) . '/classes/wishlist_class.php';

// WISHLIST MANAGEMENT
/**
 * Add property to wishlist
 * @param int $studentId Student ID
 * @param int $propertyId Property ID
 * @return bool True if successful
 */
function addToWishlist($studentId, $propertyId) {
    $wishlistClass = new Wishlist();
    
    // Check if already in wishlist
    if ($wishlistClass->isInWishlist($studentId, $propertyId)) {
        return false; // Already in wishlist
    }
    
    $success = $wishlistClass->addToWishlist($studentId, $propertyId);
    
    if ($success) {
        logActivity($studentId, 'wishlist_added', "Added property #$propertyId to wishlist");
    }
    
    return $success;
}

/**
 * Remove property from wishlist
 * @param int $studentId Student ID
 * @param int $propertyId Property ID
 * @return bool True if successful
 */
function removeFromWishlist($studentId, $propertyId) {
    $wishlistClass = new Wishlist();
    $success = $wishlistClass->removeFromWishlist($studentId, $propertyId);
    
    if ($success) {
        logActivity($studentId, 'wishlist_removed', "Removed property #$propertyId from wishlist");
    }
    
    return $success;
}

/**
 * Check if property is in student's wishlist
 * @param int $studentId Student ID
 * @param int $propertyId Property ID
 * @return bool True if in wishlist
 */
function isInWishlist($studentId, $propertyId) {
    $wishlistClass = new Wishlist();
    return $wishlistClass->isInWishlist($studentId, $propertyId);
}

/**
 * Get student's wishlist
 * @param int $studentId Student ID
 * @param int $page Page number
 * @param int $perPage Items per page
 * @return array ['properties' => array, 'total' => int, 'pages' => int]
 */
function getStudentWishlist($studentId, $page = 1, $perPage = 12) {
    $wishlistClass = new Wishlist();
    return $wishlistClass->getStudentWishlist($studentId, $page, $perPage);
}

/**
 * Get wishlist count for student
 * @param int $studentId Student ID
 * @return int Number of items in wishlist
 */
function getWishlistCount($studentId) {
    $wishlistClass = new Wishlist();
    return $wishlistClass->getWishlistCount($studentId);
}

/**
 * Clear entire wishlist
 * @param int $studentId Student ID
 * @return bool True if successful
 */
function clearWishlist($studentId) {
    $wishlistClass = new Wishlist();
    $success = $wishlistClass->clearWishlist($studentId);
    
    if ($success) {
        logActivity($studentId, 'wishlist_cleared', "Cleared entire wishlist");
    }
    
    return $success;
}

/**
 * Get wishlist property IDs (for quick checking)
 * @param int $studentId Student ID
 * @return array Array of property IDs
 */
function getWishlistPropertyIds($studentId) {
    $wishlistClass = new Wishlist();
    return $wishlistClass->getWishlistPropertyIds($studentId);
}

?>