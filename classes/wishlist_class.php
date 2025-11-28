<?php
/**
 * CampusDigs Kenya - Wishlist Class
 * Handles all database operations for wishlist
 * MVC Architecture - Model Layer
 */

class Wishlist {
    
    private $conn;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->conn = $GLOBALS['conn'];
    }
    
    // WISHLIST MANAGEMENT    
    /**
     * Add property to wishlist
     * @param int $studentId Student ID
     * @param int $propertyId Property ID
     * @return bool True if successful
     */
    public function addToWishlist($studentId, $propertyId) {
        $stmt = $this->conn->prepare("
            INSERT INTO wishlist (student_id, property_id) 
            VALUES (?, ?)
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("ii", $studentId, $propertyId);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update property wishlist count
            $this->updatePropertyWishlistCount($propertyId);
            
            return true;
        }
        
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    /**
     * Remove property from wishlist
     * @param int $studentId Student ID
     * @param int $propertyId Property ID
     * @return bool True if successful
     */
    public function removeFromWishlist($studentId, $propertyId) {
        $stmt = $this->conn->prepare("
            DELETE FROM wishlist 
            WHERE student_id = ? AND property_id = ?
        ");
        
        $stmt->bind_param("ii", $studentId, $propertyId);
        $success = $stmt->execute();
        $stmt->close();
        
        if ($success) {
            // Update property wishlist count
            $this->updatePropertyWishlistCount($propertyId);
        }
        
        return $success;
    }
    
    /**
     * Check if property is in wishlist
     * @param int $studentId Student ID
     * @param int $propertyId Property ID
     * @return bool True if in wishlist
     */
    public function isInWishlist($studentId, $propertyId) {
        $stmt = $this->conn->prepare("
            SELECT id FROM wishlist 
            WHERE student_id = ? AND property_id = ? 
            LIMIT 1
        ");
        
        $stmt->bind_param("ii", $studentId, $propertyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Get student's wishlist with property details
     * @param int $studentId Student ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array ['properties' => array, 'total' => int, 'pages' => int]
     */
    public function getStudentWishlist($studentId, $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->conn->prepare("
            SELECT COUNT(*) as total 
            FROM wishlist 
            WHERE student_id = ?
        ");
        $countStmt->bind_param("i", $studentId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get wishlist items with property details
        $stmt = $this->conn->prepare("
            SELECT w.id as wishlist_id, w.added_on,
                   p.*, 
                   u.full_name as landlord_name, u.account_verified as landlord_verified
            FROM wishlist w
            LEFT JOIN properties p ON w.property_id = p.id
            LEFT JOIN users u ON p.landlord_id = u.id
            WHERE w.student_id = ?
            ORDER BY w.added_on DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bind_param("iii", $studentId, $perPage, $offset);
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
     * Get wishlist count for student
     * @param int $studentId Student ID
     * @return int Number of items in wishlist
     */
    public function getWishlistCount($studentId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM wishlist 
            WHERE student_id = ?
        ");
        
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
        
        return $count;
    }
    
    /**
     * Clear entire wishlist
     * @param int $studentId Student ID
     * @return bool True if successful
     */
    public function clearWishlist($studentId) {
        // Get all property IDs first to update counts
        $stmt = $this->conn->prepare("SELECT property_id FROM wishlist WHERE student_id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $propertyIds = [];
        
        while ($row = $result->fetch_assoc()) {
            $propertyIds[] = $row['property_id'];
        }
        $stmt->close();
        
        // Delete all wishlist items
        $deleteStmt = $this->conn->prepare("DELETE FROM wishlist WHERE student_id = ?");
        $deleteStmt->bind_param("i", $studentId);
        $success = $deleteStmt->execute();
        $deleteStmt->close();
        
        // Update property wishlist counts
        if ($success) {
            foreach ($propertyIds as $propertyId) {
                $this->updatePropertyWishlistCount($propertyId);
            }
        }
        
        return $success;
    }
    
    /**
     * Get wishlist property IDs only (for quick checking)
     * @param int $studentId Student ID
     * @return array Array of property IDs
     */
    public function getWishlistPropertyIds($studentId) {
        $stmt = $this->conn->prepare("
            SELECT property_id 
            FROM wishlist 
            WHERE student_id = ?
        ");
        
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $propertyIds = [];
        
        while ($row = $result->fetch_assoc()) {
            $propertyIds[] = $row['property_id'];
        }
        
        $stmt->close();
        return $propertyIds;
    }
    
    // HELPER METHODS    
    /**
     * Update property wishlist count
     * @param int $propertyId Property ID
     * @return bool True if successful
     */
    private function updatePropertyWishlistCount($propertyId) {
        $stmt = $this->conn->prepare("
            UPDATE properties 
            SET wishlist_count = (
                SELECT COUNT(*) 
                FROM wishlist 
                WHERE property_id = ?
            )
            WHERE id = ?
        ");
        
        $stmt->bind_param("ii", $propertyId, $propertyId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get most wishlisted properties
     * @param int $limit Number of properties
     * @return array Properties array
     */
    public function getMostWishlistedProperties($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT p.*, 
                   COUNT(w.id) as wishlist_count,
                   u.full_name as landlord_name
            FROM properties p
            LEFT JOIN wishlist w ON p.id = w.property_id
            LEFT JOIN users u ON p.landlord_id = u.id
            WHERE p.status = 'active'
            GROUP BY p.id
            ORDER BY wishlist_count DESC
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
}

?>