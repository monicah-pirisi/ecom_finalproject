<?php
/**
 * CampusDigs Kenya - Booking Process
 * Handles booking form submission
 * MVC Architecture - Controller Layer
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/booking_controller.php';
require_once '../controllers/property_controller.php';

// Require student authentication
requireStudent();

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('all_properties.php', 'Invalid request method', 'error');
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    redirectWithMessage('all_properties.php', 'Invalid security token. Please try again.', 'error');
}

// Get student ID
$studentId = $_SESSION['user_id'];

// Get and validate form data
$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
$landlordId = isset($_POST['landlord_id']) ? (int)$_POST['landlord_id'] : 0;
$leaseDuration = isset($_POST['lease_duration']) ? (int)$_POST['lease_duration'] : 0;
$moveInDate = isset($_POST['move_in_date']) ? sanitizeInput($_POST['move_in_date']) : '';
$message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';

// Validate required fields
if (!$propertyId || !$landlordId || !$leaseDuration || !$moveInDate) {
    redirectWithMessage('single_property.php?id=' . $propertyId, 'All fields are required', 'error');
}

// Validate lease duration
if ($leaseDuration < MIN_LEASE_DURATION || $leaseDuration > MAX_LEASE_DURATION) {
    redirectWithMessage('single_property.php?id=' . $propertyId, 'Invalid lease duration', 'error');
}

// Validate move-in date (must be in future)
$moveInTimestamp = strtotime($moveInDate);
if ($moveInTimestamp < time()) {
    redirectWithMessage('single_property.php?id=' . $propertyId, 'Move-in date must be in the future', 'error');
}

// Get property details
$property = getPropertyById($propertyId);

if (!$property) {
    redirectWithMessage('all_properties.php', 'Property not found', 'error');
}

// Check if property is active
if ($property['status'] !== 'active') {
    redirectWithMessage('all_properties.php', 'This property is no longer available', 'error');
}

// Verify landlord ID matches
if ($property['landlord_id'] != $landlordId) {
    redirectWithMessage('single_property.php?id=' . $propertyId, 'Invalid landlord information', 'error');
}

// Prepare booking data
$bookingData = [
    'student_id' => $studentId,
    'property_id' => $propertyId,
    'landlord_id' => $landlordId,
    'move_in_date' => $moveInDate,
    'lease_duration_months' => $leaseDuration,
    'monthly_rent' => $property['price_monthly'],
    'security_deposit' => $property['security_deposit'],
    'message' => $message
];

// Create booking
try {
    $bookingId = createBooking($bookingData);
    
    if ($bookingId) {
        // Booking created successfully
        redirectWithMessage(
            'student_bookings.php', 
            'Booking request submitted successfully! The landlord will review your request.', 
            'success'
        );
    } else {
        // Booking creation failed
        redirectWithMessage(
            'single_property.php?id=' . $propertyId, 
            'Failed to create booking. Please try again.', 
            'error'
        );
    }
    
} catch (Exception $e) {
    error_log("Booking creation error: " . $e->getMessage());
    redirectWithMessage(
        'single_property.php?id=' . $propertyId, 
        'An error occurred. Please try again.', 
        'error'
    );
}
?>