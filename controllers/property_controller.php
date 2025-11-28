<?php
/**
 * CampusDigs Kenya - Property Controller
 * Handles all property-related business logic
 * MVC Architecture - Controller Layer
 */

require_once dirname(__DIR__) . '/classes/property_class.php';
require_once dirname(__DIR__) . '/controllers/user_controller.php';

// PROPERTY LISTING & RETRIEVAL
/**
 * Get recent/featured properties
 * @param int $limit Number of properties to retrieve
 * @return array Properties array
 */
function getRecentProperties($limit = 10) {
    $propertyClass = new Property();
    return $propertyClass->getRecentProperties($limit);
}

/**
 * Get all active properties with pagination
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param array $filters Filter parameters
 * @return array ['properties' => array, 'total' => int, 'pages' => int]
 */
function getAllProperties($page = 1, $perPage = 12, $filters = []) {
    $propertyClass = new Property();
    return $propertyClass->getAllProperties($page, $perPage, $filters);
}

/**
 * Get single property by ID
 * @param int $propertyId Property ID
 * @return array|false Property data if found, false otherwise
 */
function getPropertyById($propertyId) {
    $propertyClass = new Property();
    return $propertyClass->getPropertyById($propertyId);
}

/**
 * Get properties by landlord
 * @param int $landlordId Landlord ID
 * @param int $page Page number
 * @param int $perPage Items per page
 * @return array ['properties' => array, 'total' => int, 'pages' => int]
 */
function getLandlordProperties($landlordId, $page = 1, $perPage = 10) {
    $propertyClass = new Property();
    return $propertyClass->getLandlordProperties($landlordId, $page, $perPage);
}

/**
 * Search properties by keyword
 * @param string $keyword Search keyword
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param array $filters Additional filters
 * @return array ['properties' => array, 'total' => int, 'pages' => int]
 */
function searchProperties($keyword, $page = 1, $perPage = 12, $filters = []) {
    $propertyClass = new Property();
    return $propertyClass->searchProperties($keyword, $page, $perPage, $filters);
}

// PROPERTY MANAGEMENT (LANDLORD)

/**
 * Add new property
 * @param array $data Property data
 * @return int|false Property ID if successful, false otherwise
 */
function addProperty($data) {
    $propertyClass = new Property();
    
    // Extract data
    $landlordId = $data['landlord_id'];
    $title = $data['title'];
    $description = $data['description'];
    $location = $data['location'];
    $priceMonthly = $data['price_monthly'];
    $securityDeposit = $data['security_deposit'];
    $roomType = $data['room_type'];
    $amenities = isset($data['amenities']) ? json_encode($data['amenities']) : null;
    
    // Optional fields
    $address = $data['address'] ?? null;
    $capacity = $data['capacity'] ?? 1;
    $distanceFromCampus = $data['distance_from_campus'] ?? null;
    $universityNearby = $data['university_nearby'] ?? null;
    $hasCctv = isset($data['has_cctv']) ? 1 : 0;
    $hasSecurityGuard = isset($data['has_security_guard']) ? 1 : 0;
    $hasSecureEntry = isset($data['has_secure_entry']) ? 1 : 0;
    $keywords = $data['keywords'] ?? null;
    $availableFrom = $data['available_from'] ?? null;
    $minLeaseMonths = $data['min_lease_months'] ?? 4;
    $maxLeaseMonths = $data['max_lease_months'] ?? 12;
    
    $propertyId = $propertyClass->addProperty(
        $landlordId,
        $title,
        $description,
        $location,
        $priceMonthly,
        $securityDeposit,
        $roomType,
        $amenities,
        $address,
        $capacity,
        $distanceFromCampus,
        $universityNearby,
        $hasCctv,
        $hasSecurityGuard,
        $hasSecureEntry,
        $keywords,
        $availableFrom,
        $minLeaseMonths,
        $maxLeaseMonths
    );
    
    if ($propertyId) {
        logActivity($landlordId, 'property_added', "Added property: $title");
    }
    
    return $propertyId;
}

/**
 * Update property
 * @param int $propertyId Property ID
 * @param array $data Updated data
 * @return bool True if successful
 */
function updateProperty($propertyId, $data) {
    $propertyClass = new Property();
    $success = $propertyClass->updateProperty($propertyId, $data);
    
    if ($success) {
        $property = getPropertyById($propertyId);
        logActivity($property['landlord_id'], 'property_updated', "Updated property: " . $property['title']);
    }
    
    return $success;
}

/**
 * Delete property (set to inactive)
 * @param int $propertyId Property ID
 * @param int $landlordId Landlord ID (for verification)
 * @return bool True if successful
 */
function deleteProperty($propertyId, $landlordId) {
    $propertyClass = new Property();
    
    // Verify ownership
    $property = getPropertyById($propertyId);
    if (!$property || $property['landlord_id'] != $landlordId) {
        return false;
    }
    
    $success = $propertyClass->updatePropertyStatus($propertyId, 'inactive');
    
    if ($success) {
        logActivity($landlordId, 'property_deleted', "Deleted property: " . $property['title']);
    }
    
    return $success;
}

// PROPERTY IMAGES
/**
 * Add property images
 * @param int $propertyId Property ID
 * @param array $images Array of image paths
 * @param int $mainImageIndex Index of main image
 * @return bool True if successful
 */
function addPropertyImages($propertyId, $images, $mainImageIndex = 0) {
    $propertyClass = new Property();
    return $propertyClass->addPropertyImages($propertyId, $images, $mainImageIndex);
}

/**
 * Get property images
 * @param int $propertyId Property ID
 * @return array Images array
 */
function getPropertyImages($propertyId) {
    $propertyClass = new Property();
    return $propertyClass->getPropertyImages($propertyId);
}

/**
 * Delete property image
 * @param int $imageId Image ID
 * @param int $propertyId Property ID (for verification)
 * @return bool True if successful
 */
function deletePropertyImage($imageId, $propertyId) {
    $propertyClass = new Property();
    return $propertyClass->deletePropertyImage($imageId, $propertyId);
}

// PROPERTY STATISTICS

/**
 * Increment property view count
 * @param int $propertyId Property ID
 * @return bool True if successful
 */
function incrementPropertyViews($propertyId) {
    $propertyClass = new Property();
    return $propertyClass->incrementPropertyViews($propertyId);
}

/**
 * Get property statistics
 * @param int $propertyId Property ID
 * @return array Statistics array
 */
function getPropertyStatistics($propertyId) {
    $propertyClass = new Property();
    return $propertyClass->getPropertyStatistics($propertyId);
}

// ADMIN FUNCTIONS
/**
 * Get all properties for admin dashboard (all statuses)
 * @param int $page Page number
 * @param int $perPage Items per page
 * @return array ['properties' => array, 'total' => int, 'pages' => int]
 */
function getAllPropertiesAdmin($page = 1, $perPage = 20) {
    global $conn;

    $offset = ($page - 1) * $perPage;

    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM properties";
    $result = $conn->query($countQuery);
    $total = $result->fetch_assoc()['total'];

    // Get properties with landlord info
    $query = "SELECT p.*,
              u.full_name as landlord_name,
              u.email as landlord_email,
              u.phone as landlord_phone,
              u.account_verified as landlord_verified,
              (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) as booking_count
              FROM properties p
              LEFT JOIN users u ON p.landlord_id = u.id
              ORDER BY p.created_at DESC
              LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $properties = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['amenities'])) {
            $row['amenities'] = json_decode($row['amenities'], true);
        }
        $properties[] = $row;
    }
    $stmt->close();

    return [
        'properties' => $properties,
        'total' => $total,
        'pages' => ceil($total / $perPage)
    ];
}

/**
 * Get pending properties for admin approval
 * @param int $page Page number
 * @param int $perPage Items per page
 * @return array ['properties' => array, 'total' => int, 'pages' => int]
 */
function getPendingProperties($page = 1, $perPage = 20) {
    $propertyClass = new Property();
    return $propertyClass->getPropertiesByStatus('pending', $page, $perPage);
}

/**
 * Get rejected properties
 * @param int $page Page number
 * @param int $perPage Items per page
 * @return array ['properties' => array, 'total' => int, 'pages' => int]
 */
function getRejectedProperties($page = 1, $perPage = 20) {
    global $conn;

    $offset = ($page - 1) * $perPage;

    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM properties WHERE status = 'rejected'";
    $result = $conn->query($countQuery);
    $total = $result->fetch_assoc()['total'];

    // Get rejected properties with landlord info
    $query = "SELECT p.*,
              u.full_name as landlord_name,
              u.email as landlord_email,
              u.phone as landlord_phone,
              u.account_verified as landlord_verified
              FROM properties p
              LEFT JOIN users u ON p.landlord_id = u.id
              WHERE p.status = 'rejected'
              ORDER BY p.updated_at DESC
              LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $properties = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['amenities'])) {
            $row['amenities'] = json_decode($row['amenities'], true);
        }
        $properties[] = $row;
    }
    $stmt->close();

    return [
        'properties' => $properties,
        'total' => $total,
        'pages' => ceil($total / $perPage)
    ];
}

/**
 * Approve property (admin action)
 * @param int $propertyId Property ID
 * @param int $adminId Admin ID
 * @return bool True if successful
 */
function approveProperty($propertyId, $adminId) {
    $propertyClass = new Property();
    $success = $propertyClass->updatePropertyStatus($propertyId, 'active');
    
    if ($success) {
        // Mark as verified
        $propertyClass->markPropertyVerified($propertyId);
        
        $property = getPropertyById($propertyId);
        logActivity($adminId, 'property_approved', "Approved property: " . $property['title']);
        
        // TODO: Send notification to landlord
    }
    
    return $success;
}

/**
 * Reject property (admin action)
 * @param int $propertyId Property ID
 * @param int $adminId Admin ID
 * @param string $reason Rejection reason
 * @return bool True if successful
 */
function rejectProperty($propertyId, $adminId, $reason) {
    $propertyClass = new Property();
    $success = $propertyClass->rejectProperty($propertyId, $reason);
    
    if ($success) {
        $property = getPropertyById($propertyId);
        logActivity($adminId, 'property_rejected', "Rejected property: " . $property['title']);
        
        // TODO: Send notification to landlord
    }
    
    return $success;
}

/**
 * Get all property statistics
 * @return array Statistics array
 */
function getAllPropertyStatistics() {
    $propertyClass = new Property();
    return $propertyClass->getAllPropertyStatistics();
}

// FILTERING & SORTING
/**
 * Build filter query from form data
 * @param array $formData Form data
 * @return array Filters array
 */
function buildPropertyFilters($formData) {
    $filters = [];
    
    if (!empty($formData['min_price'])) {
        $filters['min_price'] = (float)$formData['min_price'];
    }
    
    if (!empty($formData['max_price'])) {
        $filters['max_price'] = (float)$formData['max_price'];
    }
    
    if (!empty($formData['room_type'])) {
        $filters['room_type'] = $formData['room_type'];
    }
    
    if (!empty($formData['location'])) {
        $filters['location'] = $formData['location'];
    }
    
    if (!empty($formData['university'])) {
        $filters['university'] = $formData['university'];
    }
    
    if (!empty($formData['min_safety_score'])) {
        $filters['min_safety_score'] = (float)$formData['min_safety_score'];
    }
    
    if (isset($formData['has_cctv']) && $formData['has_cctv'] == '1') {
        $filters['has_cctv'] = 1;
    }
    
    if (isset($formData['has_security_guard']) && $formData['has_security_guard'] == '1') {
        $filters['has_security_guard'] = 1;
    }
    
    if (!empty($formData['max_distance'])) {
        $filters['max_distance'] = (float)$formData['max_distance'];
    }
    
    if (!empty($formData['sort_by'])) {
        $filters['sort_by'] = $formData['sort_by'];
    }
    
    return $filters;
}

// PUBLIC STOREFRONT FUNCTIONS
/**
 * Get public properties with advanced filtering for storefront
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param array $filters Filter parameters (search, location, room_type, min_price, max_price, amenities, max_distance, sort)
 * @return array ['properties' => array, 'total' => int, 'pages' => int]
 */
function getPublicPropertiesFiltered($page = 1, $perPage = 12, $filters = []) {
    global $conn;

    $offset = ($page - 1) * $perPage;

    // Base query - only active and approved properties
    $whereConditions = ["p.status = 'active'"];
    $params = [];
    $types = "";

    // Text search filter (title, description, location)
    if (!empty($filters['search'])) {
        $searchTerm = '%' . $filters['search'] . '%';
        $whereConditions[] = "(p.title LIKE ? OR p.description LIKE ? OR p.location LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }

    // Location filter
    if (!empty($filters['location'])) {
        $whereConditions[] = "p.location = ?";
        $params[] = $filters['location'];
        $types .= "s";
    }

    // Room type filter
    if (!empty($filters['room_type'])) {
        $whereConditions[] = "p.room_type = ?";
        $params[] = $filters['room_type'];
        $types .= "s";
    }

    // Price range filter
    if (!empty($filters['min_price'])) {
        $whereConditions[] = "p.price_monthly >= ?";
        $params[] = (float)$filters['min_price'];
        $types .= "d";
    }

    if (!empty($filters['max_price'])) {
        $whereConditions[] = "p.price_monthly <= ?";
        $params[] = (float)$filters['max_price'];
        $types .= "d";
    }

    // Distance from campus filter
    if (!empty($filters['max_distance']) && $filters['max_distance'] != 999) {
        $whereConditions[] = "p.distance_from_campus <= ?";
        $params[] = (float)$filters['max_distance'];
        $types .= "d";
    }

    // Amenities filter (JSON search)
    if (!empty($filters['amenities']) && is_array($filters['amenities'])) {
        foreach ($filters['amenities'] as $amenity) {
            $whereConditions[] = "JSON_CONTAINS(p.amenities, ?, '$')";
            $params[] = '"' . $amenity . '"';
            $types .= "s";
        }
    }

    // Build WHERE clause
    $whereClause = implode(" AND ", $whereConditions);

    // Sorting
    $orderBy = "p.created_at DESC"; // Default sort
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'featured':
                $orderBy = "p.is_premium DESC, p.created_at DESC";
                break;
            case 'price_low':
                $orderBy = "p.price_monthly ASC";
                break;
            case 'price_high':
                $orderBy = "p.price_monthly DESC";
                break;
            case 'newest':
                $orderBy = "p.created_at DESC";
                break;
            case 'popular':
                $orderBy = "p.view_count DESC, p.created_at DESC";
                break;
        }
    }

    // Count total results
    $countQuery = "SELECT COUNT(*) as total
                   FROM properties p
                   WHERE $whereClause";

    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();

    // Get properties with pagination - optimized with LEFT JOIN
    $query = "SELECT p.*,
              u.full_name as landlord_name,
              u.phone as landlord_phone,
              u.email as landlord_email,
              COALESCE(AVG(r.rating), 0) as average_rating,
              COUNT(r.id) as review_count
              FROM properties p
              LEFT JOIN users u ON p.landlord_id = u.id
              LEFT JOIN reviews r ON r.property_id = p.id AND r.is_approved = 1
              WHERE $whereClause
              GROUP BY p.id
              ORDER BY $orderBy
              LIMIT ? OFFSET ?";

    $params[] = $perPage;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $properties = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON amenities
        if (!empty($row['amenities'])) {
            $row['amenities'] = json_decode($row['amenities'], true);
        }
        $properties[] = $row;
    }
    $stmt->close();

    $totalPages = ceil($total / $perPage);

    return [
        'properties' => $properties,
        'total' => $total,
        'pages' => $totalPages
    ];
}

/**
 * Get distinct property locations for filter dropdown
 * @return array Array of location strings
 */
function getPropertyLocations() {
    // Include locations configuration
    require_once dirname(__DIR__) . '/includes/locations.php';

    // Return all predefined locations
    return locations_getAllLocations();
}

/**
 * Get locations grouped by university for organized display
 * @return array Associative array [university => [locations]]
 */
function getLocationsByUniversity() {
    // Include locations configuration
    require_once dirname(__DIR__) . '/includes/locations.php';

    // Return grouped locations
    return locations_getGroupedLocations();
}

/**
 * Get all universities
 * @return array Array of university names
 */
function getAllUniversities() {
    // Include locations configuration
    require_once dirname(__DIR__) . '/includes/locations.php';

    // Return all universities
    return locations_getAllUniversities();
}

/**
 * Get featured properties for hero section
 * @param int $limit Number of properties to retrieve
 * @return array Array of property objects
 */
function getFeaturedProperties($limit = 6) {
    global $conn;

    // Optimized query with LEFT JOIN instead of subqueries
    $query = "SELECT p.*,
              u.full_name as landlord_name,
              COALESCE(AVG(r.rating), 0) as average_rating,
              COUNT(r.id) as review_count
              FROM properties p
              LEFT JOIN users u ON p.landlord_id = u.id
              LEFT JOIN reviews r ON r.property_id = p.id AND r.is_approved = 1
              WHERE p.status = 'active' AND p.is_premium = 1
              GROUP BY p.id
              ORDER BY p.created_at DESC
              LIMIT ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $properties = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['amenities'])) {
            $row['amenities'] = json_decode($row['amenities'], true);
        }
        $properties[] = $row;
    }
    $stmt->close();

    return $properties;
}

/**
 * Get total count of active properties for trust badge
 * @return int Count of active properties
 */
function getTotalActiveProperties() {
    global $conn;

    $query = "SELECT COUNT(*) as total FROM properties WHERE status = 'active'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    return (int)$row['total'];
}

/**
 * Get total count of registered students for trust badge
 * @return int Count of students
 */
function getTotalStudents() {
    global $conn;

    $query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'student' AND account_status = 'active'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    return (int)$row['total'];
}

/**
 * Get total count of successful bookings for trust badge
 * @return int Count of bookings
 */
function getTotalBookings() {
    global $conn;

    $query = "SELECT COUNT(*) as total FROM bookings WHERE status IN ('confirmed', 'active', 'completed')";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();

    return (int)$row['total'];
}

/**
 * Get landlord properties for admin view
 * @param int $landlordId Landlord ID
 * @return array Array of properties
 */
function getLandlordPropertiesAdmin($landlordId) {
    global $conn;

    $query = "SELECT p.*,
              (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) as booking_count,
              (SELECT COUNT(*) FROM bookings WHERE property_id = p.id AND status = 'active') as active_bookings
              FROM properties p
              WHERE p.landlord_id = ?
              ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();

    $properties = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['amenities'])) {
            $row['amenities'] = json_decode($row['amenities'], true);
        }
        $properties[] = $row;
    }
    $stmt->close();

    return $properties;
}

/**
 * Get active properties for admin view
 * @param int $page Page number
 * @param int $perPage Items per page
 * @return array ['properties' => array, 'total' => int, 'pages' => int]
 */
function getActiveProperties($page = 1, $perPage = 20) {
    $propertyClass = new Property();
    return $propertyClass->getPropertiesByStatus('active', $page, $perPage);
}

/**
 * Get property status counts for admin dashboard
 * @return array Counts by status
 */
function getPropertyStatusCounts() {
    global $conn;

    $counts = [
        'total' => 0,
        'active' => 0,
        'pending' => 0,
        'rejected' => 0
    ];

    // Total properties (excluding deleted)
    $result = $conn->query("SELECT COUNT(*) as total FROM properties WHERE status != 'deleted'");
    if ($result) {
        $counts['total'] = $result->fetch_assoc()['total'];
    }

    // Active properties
    $result = $conn->query("SELECT COUNT(*) as total FROM properties WHERE status = 'active'");
    if ($result) {
        $counts['active'] = $result->fetch_assoc()['total'];
    }

    // Pending properties
    $result = $conn->query("SELECT COUNT(*) as total FROM properties WHERE status = 'pending'");
    if ($result) {
        $counts['pending'] = $result->fetch_assoc()['total'];
    }

    // Rejected properties
    $result = $conn->query("SELECT COUNT(*) as total FROM properties WHERE status = 'rejected'");
    if ($result) {
        $counts['rejected'] = $result->fetch_assoc()['total'];
    }

    return $counts;
}

?>