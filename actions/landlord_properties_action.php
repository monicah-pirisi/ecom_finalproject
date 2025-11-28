<?php
/**
 * CampusDigs Kenya - Landlord Properties Action
 * Handles add, edit, and delete property operations
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/property_controller.php';

// Check if user is logged in and is a landlord
if (!isLoggedIn() || !isLandlord()) {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: ../index.php');
    exit();
}

$landlordId = $_SESSION['user_id'];

// Verify CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken()) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: ../view/landlord/my_properties.php');
    exit();
}

// Get action
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';

try {
    switch ($action) {
        case 'add':
            handleAddProperty();
            break;

        case 'edit':
            handleEditProperty();
            break;

        case 'delete':
            handleDeleteProperty();
            break;

        case 'delete_image':
            handleDeleteImage();
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    error_log("Property action error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../view/landlord/my_properties.php');
    exit();
}

/**
 * Handle add property request
 */
function handleAddProperty() {
    global $landlordId;

    // Validate required fields
    $requiredFields = ['title', 'description', 'location', 'price_monthly', 'security_deposit', 'room_type'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }

    // Validate property images
    if (empty($_FILES['property_images']['name'][0])) {
        throw new Exception('At least one property image is required');
    }

    // Upload images
    $uploadedImages = uploadPropertyImages($_FILES['property_images']);

    if (empty($uploadedImages)) {
        throw new Exception('Failed to upload images. Please try again.');
    }

    // Prepare property data
    $propertyData = [
        'landlord_id' => $landlordId,
        'title' => sanitizeInput($_POST['title']),
        'description' => sanitizeInput($_POST['description']),
        'location' => sanitizeInput($_POST['location']),
        'price_monthly' => (float)$_POST['price_monthly'],
        'security_deposit' => (float)$_POST['security_deposit'],
        'room_type' => sanitizeInput($_POST['room_type']),
        'capacity' => isset($_POST['capacity']) ? (int)$_POST['capacity'] : 1,
        'distance_from_campus' => isset($_POST['distance_from_campus']) ? (float)$_POST['distance_from_campus'] : null,
        'university_nearby' => isset($_POST['university_nearby']) ? sanitizeInput($_POST['university_nearby']) : null,
        'min_lease_months' => isset($_POST['min_lease_months']) ? (int)$_POST['min_lease_months'] : 4,
        'max_lease_months' => isset($_POST['max_lease_months']) ? (int)$_POST['max_lease_months'] : 12,
        'has_cctv' => isset($_POST['has_cctv']) ? 1 : 0,
        'has_security_guard' => isset($_POST['has_security_guard']) ? 1 : 0,
        'has_secure_entry' => isset($_POST['has_secure_entry']) ? 1 : 0,
        'amenities' => isset($_POST['amenities']) ? $_POST['amenities'] : []
    ];

    // Add property
    $propertyId = addProperty($propertyData);

    if (!$propertyId) {
        // Clean up uploaded images
        foreach ($uploadedImages as $image) {
            @unlink('../' . $image);
        }
        throw new Exception('Failed to add property. Please try again.');
    }

    // Add property images
    addPropertyImages($propertyId, $uploadedImages, 0);

    $_SESSION['success'] = 'Property added successfully! It will be reviewed by our team before being published.';
    header('Location: ../view/landlord/my_properties.php');
    exit();
}

/**
 * Handle edit property request
 */
function handleEditProperty() {
    global $landlordId;

    $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

    if (!$propertyId) {
        throw new Exception('Invalid property ID');
    }

    // Verify ownership
    $property = getPropertyById($propertyId);
    if (!$property || $property['landlord_id'] != $landlordId) {
        throw new Exception('Property not found or access denied');
    }

    // Prepare update data
    $updateData = [
        'title' => sanitizeInput($_POST['title']),
        'description' => sanitizeInput($_POST['description']),
        'location' => sanitizeInput($_POST['location']),
        'price_monthly' => (float)$_POST['price_monthly'],
        'security_deposit' => (float)$_POST['security_deposit'],
        'room_type' => sanitizeInput($_POST['room_type']),
        'capacity' => isset($_POST['capacity']) ? (int)$_POST['capacity'] : 1,
        'distance_from_campus' => isset($_POST['distance_from_campus']) ? (float)$_POST['distance_from_campus'] : null,
        'university_nearby' => isset($_POST['university_nearby']) ? sanitizeInput($_POST['university_nearby']) : null,
        'min_lease_months' => isset($_POST['min_lease_months']) ? (int)$_POST['min_lease_months'] : 4,
        'max_lease_months' => isset($_POST['max_lease_months']) ? (int)$_POST['max_lease_months'] : 12,
        'has_cctv' => isset($_POST['has_cctv']) ? 1 : 0,
        'has_security_guard' => isset($_POST['has_security_guard']) ? 1 : 0,
        'has_secure_entry' => isset($_POST['has_secure_entry']) ? 1 : 0,
        'amenities' => isset($_POST['amenities']) ? json_encode($_POST['amenities']) : null
    ];

    // Update property
    $success = updateProperty($propertyId, $updateData);

    if (!$success) {
        throw new Exception('Failed to update property. Please try again.');
    }

    // Handle new images if uploaded
    if (!empty($_FILES['property_images']['name'][0])) {
        $uploadedImages = uploadPropertyImages($_FILES['property_images']);

        if (!empty($uploadedImages)) {
            addPropertyImages($propertyId, $uploadedImages);
        }
    }

    $_SESSION['success'] = 'Property updated successfully!';
    header('Location: ../view/landlord/edit_property.php?id=' . $propertyId);
    exit();
}

/**
 * Handle delete property request
 */
function handleDeleteProperty() {
    global $landlordId, $conn;

    $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

    if (!$propertyId) {
        throw new Exception('Invalid property ID');
    }

    // Verify ownership
    $property = getPropertyById($propertyId);
    if (!$property || $property['landlord_id'] != $landlordId) {
        throw new Exception('You do not have permission to delete this property');
    }

    // Check if property has active bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE property_id = ? AND status IN ('pending', 'approved')");
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result['count'] > 0) {
        throw new Exception('Cannot delete property with active bookings. Please complete or cancel all bookings first.');
    }

    // Start transaction for complete deletion
    $conn->begin_transaction();

    try {
        // 1. Delete property images from database
        $stmt = $conn->prepare("DELETE FROM property_images WHERE property_id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $stmt->close();

        // 2. Delete from wishlist
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE property_id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $stmt->close();

        // 3. Delete reviews
        $stmt = $conn->prepare("DELETE FROM reviews WHERE property_id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $stmt->close();

        // 4. Delete the property itself
        $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();

        // Log activity
        logActivity($landlordId, 'property_deleted', "Deleted property: {$property['title']}");

        redirectWithMessage('../../view/landlord/my_properties.php', 'Property deleted successfully', 'success');

    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception('Failed to delete property: ' . $e->getMessage());
    }
}

/**
 * Handle delete image request
 */
function handleDeleteImage() {
    global $landlordId;

    $imageId = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
    $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;

    if (!$imageId || !$propertyId) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit();
    }

    // Verify property ownership
    $property = getPropertyById($propertyId);
    if (!$property || $property['landlord_id'] != $landlordId) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    // Delete image
    $success = deletePropertyImage($imageId, $propertyId);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete image']);
    }
    exit();
}

/**
 * Upload property images
 * @param array $files Files array from $_FILES
 * @return array Array of uploaded file paths
 */
function uploadPropertyImages($files) {
    $uploadedImages = [];
    $uploadDir = '../uploads/properties/';

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validate and upload each image
    $totalFiles = count($files['name']);

    for ($i = 0; $i < $totalFiles; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $fileType = $files['type'][$i];

        if (!in_array($fileType, $allowedTypes)) {
            continue;
        }

        // Validate file size (max 5MB)
        if ($files['size'][$i] > 5 * 1024 * 1024) {
            continue;
        }

        // Generate unique filename
        $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $filename = uniqid('property_') . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($files['tmp_name'][$i], $filepath)) {
            $uploadedImages[] = 'uploads/properties/' . $filename;
        }
    }

    return $uploadedImages;
}

?>