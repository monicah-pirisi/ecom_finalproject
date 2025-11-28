<?php
/**
 * CampusDigs Kenya - Property Class
 * Handles all database operations for properties
 * MVC Architecture - Model Layer
 */

class Property {
    
    private $conn;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->conn = $GLOBALS['conn'];
    }
    
    // PROPERTY CREATION & MANAGEMENT
    
    /**
     * Add new property
     */
    public function addProperty($landlordId, $title, $description, $location, $priceMonthly, 
                                $securityDeposit, $roomType, $amenities = null, $address = null, 
                                $capacity = 1, $distanceFromCampus = null, $universityNearby = null,
                                $hasCctv = 0, $hasSecurityGuard = 0, $hasSecureEntry = 0, 
                                $keywords = null, $availableFrom = null, $minLeaseMonths = 4, 
                                $maxLeaseMonths = 12) {
        
        $stmt = $this->conn->prepare("
            INSERT INTO properties (
                landlord_id, title, description, location, address, price_monthly, 
                security_deposit, room_type, capacity, distance_from_campus, university_nearby,
                has_cctv, has_security_guard, has_secure_entry, amenities, keywords,
                available_from, min_lease_months, max_lease_months, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param(
            "issssddsiidsiiissii",
            $landlordId, $title, $description, $location, $address,
            $priceMonthly, $securityDeposit, $roomType, $capacity,
            $distanceFromCampus, $universityNearby, $hasCctv,
            $hasSecurityGuard, $hasSecureEntry, $amenities,
            $keywords, $availableFrom, $minLeaseMonths, $maxLeaseMonths
        );
        
        if ($stmt->execute()) {
            $propertyId = $stmt->insert_id;
            $stmt->close();
            return $propertyId;
        }
        
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    /**
     * Update property
     */
    public function updateProperty($propertyId, $data) {
        $allowedFields = [
            'title', 'description', 'location', 'address', 'price_monthly',
            'security_deposit', 'room_type', 'capacity', 'distance_from_campus',
            'university_nearby', 'has_cctv', 'has_security_guard', 'has_secure_entry',
            'amenities', 'keywords', 'available_from', 'min_lease_months', 'max_lease_months'
        ];
        
        $updates = [];
        $types = '';
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = ?";
                
                // Determine type
                if (in_array($field, ['price_monthly', 'security_deposit', 'distance_from_campus'])) {
                    $types .= 'd';
                } elseif (in_array($field, ['capacity', 'has_cctv', 'has_security_guard', 'has_secure_entry', 'min_lease_months', 'max_lease_months'])) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
                
                $values[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $propertyId;
        $types .= 'i';
        
        $sql = "UPDATE properties SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Update property status
     */
    public function updatePropertyStatus($propertyId, $status) {
        $allowedStatuses = ['active', 'inactive', 'pending', 'rejected', 'expired'];
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("UPDATE properties SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $propertyId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    // PROPERTY RETRIEVAL
    
    /**
     * Get property by ID
     */
    public function getPropertyById($propertyId) {
        $stmt = $this->conn->prepare("
            SELECT p.*, u.full_name as landlord_name, u.email as landlord_email, 
                   u.phone as landlord_phone, u.account_verified as landlord_verified
            FROM properties p
            LEFT JOIN users u ON p.landlord_id = u.id
            WHERE p.id = ?
            LIMIT 1
        ");
        
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $property = $result->fetch_assoc();
            $stmt->close();
            return $property;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Get recent properties
     */
    public function getRecentProperties($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT p.*, u.full_name as landlord_name
            FROM properties p
            LEFT JOIN users u ON p.landlord_id = u.id
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $properties = [];
        
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
        
        $stmt->close();
        return $properties;
    }
    
    /**
     * Get all properties with pagination and filters
     */
    public function getAllProperties($page = 1, $perPage = 12, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        // Build WHERE clause
        $whereConditions = ["p.status = 'active'"];
        $params = [];
        $types = '';
        
        if (!empty($filters['min_price'])) {
            $whereConditions[] = "p.price_monthly >= ?";
            $params[] = $filters['min_price'];
            $types .= 'd';
        }
        
        if (!empty($filters['max_price'])) {
            $whereConditions[] = "p.price_monthly <= ?";
            $params[] = $filters['max_price'];
            $types .= 'd';
        }
        
        if (!empty($filters['room_type'])) {
            $whereConditions[] = "p.room_type = ?";
            $params[] = $filters['room_type'];
            $types .= 's';
        }
        
        if (!empty($filters['location'])) {
            $whereConditions[] = "p.location LIKE ?";
            $params[] = '%' . $filters['location'] . '%';
            $types .= 's';
        }
        
        if (!empty($filters['university'])) {
            $whereConditions[] = "p.university_nearby = ?";
            $params[] = $filters['university'];
            $types .= 's';
        }
        
        if (!empty($filters['min_safety_score'])) {
            $whereConditions[] = "p.safety_score >= ?";
            $params[] = $filters['min_safety_score'];
            $types .= 'd';
        }
        
        if (isset($filters['has_cctv']) && $filters['has_cctv'] == 1) {
            $whereConditions[] = "p.has_cctv = 1";
        }
        
        if (isset($filters['has_security_guard']) && $filters['has_security_guard'] == 1) {
            $whereConditions[] = "p.has_security_guard = 1";
        }
        
        if (!empty($filters['max_distance'])) {
            $whereConditions[] = "p.distance_from_campus <= ?";
            $params[] = $filters['max_distance'];
            $types .= 'd';
        }
        
        $whereClause = implode(" AND ", $whereConditions);
        
        // Determine sort order
        $orderBy = "p.created_at DESC";
        if (!empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'price_asc':
                    $orderBy = "p.price_monthly ASC";
                    break;
                case 'price_desc':
                    $orderBy = "p.price_monthly DESC";
                    break;
                case 'safety_desc':
                    $orderBy = "p.safety_score DESC";
                    break;
                case 'distance_asc':
                    $orderBy = "p.distance_from_campus ASC";
                    break;
            }
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM properties p WHERE $whereClause";
        
        if (!empty($params)) {
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->bind_param($types, ...$params);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $total = $countResult->fetch_assoc()['total'];
            $countStmt->close();
        } else {
            $countResult = $this->conn->query($countSql);
            $total = $countResult->fetch_assoc()['total'];
        }
        
        // Get properties
        $sql = "
            SELECT p.*, u.full_name as landlord_name, u.account_verified as landlord_verified
            FROM properties p
            LEFT JOIN users u ON p.landlord_id = u.id
            WHERE $whereClause
            ORDER BY $orderBy
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $properties = [];
        
        while ($row = $result->fetch_assoc()) {
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
     * Search properties by keyword
     */
    public function searchProperties($keyword, $page = 1, $perPage = 12, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        // Build search condition
        $searchCondition = "(p.title LIKE ? OR p.description LIKE ? OR p.location LIKE ? OR p.keywords LIKE ?)";
        $searchParam = '%' . $keyword . '%';
        
        // Build WHERE clause
        $whereConditions = ["p.status = 'active'", $searchCondition];
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        $types = 'ssss';
        
        // Add additional filters (same as getAllProperties)
        if (!empty($filters['min_price'])) {
            $whereConditions[] = "p.price_monthly >= ?";
            $params[] = $filters['min_price'];
            $types .= 'd';
        }
        
        if (!empty($filters['max_price'])) {
            $whereConditions[] = "p.price_monthly <= ?";
            $params[] = $filters['max_price'];
            $types .= 'd';
        }
        
        $whereClause = implode(" AND ", $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM properties p WHERE $whereClause";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get properties
        $sql = "
            SELECT p.*, u.full_name as landlord_name, u.account_verified as landlord_verified
            FROM properties p
            LEFT JOIN users u ON p.landlord_id = u.id
            WHERE $whereClause
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $properties = [];
        
        while ($row = $result->fetch_assoc()) {
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
     * Get landlord's properties
     */
    public function getLandlordProperties($landlordId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->conn->prepare("
            SELECT COUNT(*) as total 
            FROM properties 
            WHERE landlord_id = ? AND status != 'deleted'
        ");
        $countStmt->bind_param("i", $landlordId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get properties
        $stmt = $this->conn->prepare("
            SELECT * FROM properties
            WHERE landlord_id = ? AND status != 'deleted'
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bind_param("iii", $landlordId, $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $properties = [];
        
        while ($row = $result->fetch_assoc()) {
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
     * Get properties by status
     */
    public function getPropertiesByStatus($status, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->conn->prepare("SELECT COUNT(*) as total FROM properties WHERE status = ?");
        $countStmt->bind_param("s", $status);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get properties
        $stmt = $this->conn->prepare("
            SELECT p.*, u.full_name as landlord_name, u.email as landlord_email, u.phone as landlord_phone, u.account_verified as landlord_verified
            FROM properties p
            LEFT JOIN users u ON p.landlord_id = u.id
            WHERE p.status = ?
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bind_param("sii", $status, $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $properties = [];
        
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
        
        $stmt->close();
        
        return [
            'properties' => $properties,
            'total' => $total,
            'pages' => ceil($total / $perPage)
        ];
    }
    
    // PROPERTY IMAGES
    
    /**
     * Add property images
     */
    public function addPropertyImages($propertyId, $images, $mainImageIndex = 0) {
        $success = true;
        
        foreach ($images as $index => $imagePath) {
            $isMain = ($index == $mainImageIndex) ? 1 : 0;
            
            $stmt = $this->conn->prepare("
                INSERT INTO property_images (property_id, image_path, is_main, display_order)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->bind_param("isii", $propertyId, $imagePath, $isMain, $index);
            
            if (!$stmt->execute()) {
                $success = false;
                error_log("Failed to add image: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Update main image in properties table
            if ($isMain) {
                $updateStmt = $this->conn->prepare("UPDATE properties SET main_image = ? WHERE id = ?");
                $updateStmt->bind_param("si", $imagePath, $propertyId);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
        
        return $success;
    }
    
    /**
     * Get property images
     */
    public function getPropertyImages($propertyId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM property_images
            WHERE property_id = ?
            ORDER BY is_main DESC, display_order ASC
        ");
        
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        
        $stmt->close();
        return $images;
    }
    
    /**
     * Delete property image
     */
    public function deletePropertyImage($imageId, $propertyId) {
        $stmt = $this->conn->prepare("DELETE FROM property_images WHERE id = ? AND property_id = ?");
        $stmt->bind_param("ii", $imageId, $propertyId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    // PROPERTY VERIFICATION & APPROVAL
    
    /**
     * Mark property as verified
     */
    public function markPropertyVerified($propertyId) {
        $stmt = $this->conn->prepare("UPDATE properties SET is_verified = 1 WHERE id = ?");
        $stmt->bind_param("i", $propertyId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Reject property
     */
    public function rejectProperty($propertyId, $reason) {
        $stmt = $this->conn->prepare("
            UPDATE properties 
            SET status = 'rejected', rejection_reason = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $reason, $propertyId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    // STATISTICS

    /**
     * Increment property view count
     */
    public function incrementPropertyViews($propertyId) {
        $stmt = $this->conn->prepare("UPDATE properties SET view_count = view_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $propertyId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Get property statistics
     */
    public function getPropertyStatistics($propertyId) {
        $stmt = $this->conn->prepare("
            SELECT 
                view_count,
                booking_count,
                wishlist_count,
                (SELECT COUNT(*) FROM bookings WHERE property_id = ?) as total_bookings,
                (SELECT COUNT(*) FROM wishlist WHERE property_id = ?) as total_wishlist
            FROM properties
            WHERE id = ?
        ");
        
        $stmt->bind_param("iii", $propertyId, $propertyId, $propertyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        return $stats;
    }
    
    /**
     * Get all property statistics
     */
    public function getAllPropertyStatistics() {
        $stats = [];
        
        // Total properties
        $result = $this->conn->query("SELECT COUNT(*) as total FROM properties WHERE status != 'deleted'");
        $stats['total_properties'] = $result->fetch_assoc()['total'];
        
        // Active properties
        $result = $this->conn->query("SELECT COUNT(*) as total FROM properties WHERE status = 'active'");
        $stats['active_properties'] = $result->fetch_assoc()['total'];
        
        // Pending approval
        $result = $this->conn->query("SELECT COUNT(*) as total FROM properties WHERE status = 'pending'");
        $stats['pending_properties'] = $result->fetch_assoc()['total'];
        
        // Verified properties
        $result = $this->conn->query("SELECT COUNT(*) as total FROM properties WHERE is_verified = 1");
        $stats['verified_properties'] = $result->fetch_assoc()['total'];
        
        return $stats;
    }
}

?>