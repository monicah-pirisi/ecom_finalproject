<?php
/**
 * CampusDigs Kenya - Booking Class
 * Handles all database operations for bookings
 * MVC Architecture - Model Layer
 */

class Booking {
    
    private $conn;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->conn = $GLOBALS['conn'];
    }
    
    // BOOKING CREATION & MANAGEMENT
    
    /**
     * Create new booking
     */
    public function createBooking($studentId, $propertyId, $landlordId, $moveInDate, $leaseDurationMonths,
                                  $monthlyRent, $securityDeposit, $totalAmount, $commissionAmount,
                                  $landlordPayout, $message = null) {
        
        // Generate booking reference
        $bookingRef = $this->generateBookingReference();
        
        $stmt = $this->conn->prepare("
            INSERT INTO bookings (
                booking_reference, student_id, property_id, landlord_id, move_in_date,
                lease_duration_months, monthly_rent, security_deposit, total_amount,
                commission_amount, landlord_payout, message, status, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param(
            "siiisiddddds",
            $bookingRef, $studentId, $propertyId, $landlordId, $moveInDate,
            $leaseDurationMonths, $monthlyRent, $securityDeposit, $totalAmount,
            $commissionAmount, $landlordPayout, $message
        );
        
        if ($stmt->execute()) {
            $bookingId = $stmt->insert_id;
            $stmt->close();
            return $bookingId;
        }
        
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    /**
     * Generate unique booking reference
     */
    private function generateBookingReference() {
        $year = date('Y');
        $month = date('m');
        
        // Get last booking ID
        $result = $this->conn->query("SELECT MAX(id) as max_id FROM bookings");
        $row = $result->fetch_assoc();
        $nextId = ($row['max_id'] ?? 0) + 1;
        
        return 'CD' . $year . $month . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
    
    // BOOKING RETRIEVAL
    
    /**
     * Get booking by ID
     */
    public function getBookingById($bookingId) {
        $stmt = $this->conn->prepare("
            SELECT b.*,
                   p.title as property_title, p.location as property_location,
                   p.main_image as property_image, p.price_monthly as property_price,
                   s.full_name as student_name, s.email as student_email, s.phone as student_phone,
                   l.full_name as landlord_name, l.email as landlord_email, l.phone as landlord_phone,
                   pay.paid_at as payment_completed_at
            FROM bookings b
            LEFT JOIN properties p ON b.property_id = p.id
            LEFT JOIN users s ON b.student_id = s.id
            LEFT JOIN users l ON b.landlord_id = l.id
            LEFT JOIN payments pay ON pay.booking_id = b.id AND pay.payment_status = 'completed'
            WHERE b.id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
            $stmt->close();
            return $booking;
        }

        $stmt->close();
        return false;
    }
    
    /**
     * Get student's active bookings
     */
    public function getStudentActiveBookings($studentId, $limit = 5) {
        $stmt = $this->conn->prepare("
            SELECT b.*, 
                   p.title as property_title, p.location as property_location
            FROM bookings b
            LEFT JOIN properties p ON b.property_id = p.id
            WHERE b.student_id = ? AND b.status IN ('pending', 'approved')
            ORDER BY b.created_at DESC
            LIMIT ?
        ");
        
        $stmt->bind_param("ii", $studentId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = [];
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        $stmt->close();
        return $bookings;
    }
    
    /**
     * Get student's all bookings with pagination
     */
    public function getStudentBookings($studentId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countStmt = $this->conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE student_id = ?");
        $countStmt->bind_param("i", $studentId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();

        // Get bookings with review status
        $stmt = $this->conn->prepare("
            SELECT b.*,
                   p.title as property_title, p.location as property_location, p.main_image as property_image,
                   r.id as review_id,
                   IF(r.id IS NOT NULL, 1, 0) as has_review
            FROM bookings b
            LEFT JOIN properties p ON b.property_id = p.id
            LEFT JOIN reviews r ON r.booking_id = b.id AND r.student_id = b.student_id
            WHERE b.student_id = ?
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->bind_param("iii", $studentId, $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = [];

        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }

        $stmt->close();

        return [
            'bookings' => $bookings,
            'total' => $total,
            'pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Get landlord's bookings
     */
    public function getLandlordBookings($landlordId, $page = 1, $perPage = 10, $status = null) {
        $offset = ($page - 1) * $perPage;
        
        // Build WHERE clause
        if ($status) {
            $whereClause = "b.landlord_id = ? AND b.status = ?";
        } else {
            $whereClause = "b.landlord_id = ?";
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM bookings b WHERE $whereClause";
        $countStmt = $this->conn->prepare($countSql);
        
        if ($status) {
            $countStmt->bind_param("is", $landlordId, $status);
        } else {
            $countStmt->bind_param("i", $landlordId);
        }
        
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
        
        // Get bookings
        $sql = "
            SELECT b.*, 
                   p.title as property_title, p.location as property_location,
                   s.full_name as student_name, s.email as student_email, s.phone as student_phone
            FROM bookings b
            LEFT JOIN properties p ON b.property_id = p.id
            LEFT JOIN users s ON b.student_id = s.id
            WHERE $whereClause
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($status) {
            $stmt->bind_param("isii", $landlordId, $status, $perPage, $offset);
        } else {
            $stmt->bind_param("iii", $landlordId, $perPage, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = [];
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        $stmt->close();
        
        return [
            'bookings' => $bookings,
            'total' => $total,
            'pages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Get landlord's pending bookings
     */
    public function getLandlordPendingBookings($landlordId) {
        $stmt = $this->conn->prepare("
            SELECT b.*, 
                   p.title as property_title, p.location as property_location,
                   s.full_name as student_name, s.email as student_email, s.phone as student_phone
            FROM bookings b
            LEFT JOIN properties p ON b.property_id = p.id
            LEFT JOIN users s ON b.student_id = s.id
            WHERE b.landlord_id = ? AND b.status = 'pending'
            ORDER BY b.created_at DESC
        ");
        
        $stmt->bind_param("i", $landlordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookings = [];
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        $stmt->close();
        return $bookings;
    }
    
    /**
     * Get all bookings (admin)
     */
    public function getAllBookings($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        
        // Build WHERE clause
        $whereConditions = ["1=1"];
        $params = [];
        $types = '';
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "b.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        
        if (!empty($filters['student_id'])) {
            $whereConditions[] = "b.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['landlord_id'])) {
            $whereConditions[] = "b.landlord_id = ?";
            $params[] = $filters['landlord_id'];
            $types .= 'i';
        }
        
        $whereClause = implode(" AND ", $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM bookings b WHERE $whereClause";
        
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
        
        // Get bookings
        $sql = "
            SELECT b.*, 
                   p.title as property_title,
                   s.full_name as student_name,
                   l.full_name as landlord_name
            FROM bookings b
            LEFT JOIN properties p ON b.property_id = p.id
            LEFT JOIN users s ON b.student_id = s.id
            LEFT JOIN users l ON b.landlord_id = l.id
            WHERE $whereClause
            ORDER BY b.created_at DESC
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
        $bookings = [];
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        $stmt->close();
        
        return [
            'bookings' => $bookings,
            'total' => $total,
            'pages' => ceil($total / $perPage)
        ];
    }
    
    // BOOKING STATUS UPDATES
    
    /**
     * Approve booking
     */
    public function approveBooking($bookingId) {
        $stmt = $this->conn->prepare("
            UPDATE bookings 
            SET status = 'approved', approved_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Reject booking
     */
    public function rejectBooking($bookingId, $reason) {
        $stmt = $this->conn->prepare("
            UPDATE bookings 
            SET status = 'rejected', rejection_reason = ?, rejected_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $reason, $bookingId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Cancel booking
     */
    public function cancelBooking($bookingId, $reason) {
        $stmt = $this->conn->prepare("
            UPDATE bookings 
            SET status = 'cancelled', cancellation_reason = ?, cancelled_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $reason, $bookingId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    /**
     * Complete booking
     */
    public function completeBooking($bookingId) {
        $stmt = $this->conn->prepare("
            UPDATE bookings 
            SET status = 'completed', completed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $bookingId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    // PAYMENT TRACKING
    
    /**
     * Check if booking is paid
     */
    public function isBookingPaid($bookingId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as paid_count
            FROM payments
            WHERE booking_id = ? AND status = 'successful'
        ");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['paid_count'] > 0;
    }
    
    // STATISTICS
    
    /**
     * Get student dashboard statistics
     */
    public function getStudentDashboardStats($studentId) {
        $stats = [];
        
        // Active bookings
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE student_id = ? AND status IN ('pending', 'approved')
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['active_bookings'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        // Wishlist items
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE student_id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['wishlist_items'] = $result->fetch_assoc()['count'];
        $stmt->close();
        
        // Total spent
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM bookings
            WHERE student_id = ? AND status IN ('approved', 'completed')
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_spent'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Pending payments
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM bookings
            WHERE student_id = ? AND status = 'approved'
              AND id NOT IN (SELECT booking_id FROM payments WHERE status = 'successful')
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['pending_payments'] = $result->fetch_assoc()['total'];
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Get booking statistics
     */
    public function getBookingStatistics() {
        $stats = [];
        
        // Total bookings
        $result = $this->conn->query("SELECT COUNT(*) as total FROM bookings");
        $stats['total_bookings'] = $result->fetch_assoc()['total'];
        
        // Pending bookings
        $result = $this->conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
        $stats['pending_bookings'] = $result->fetch_assoc()['total'];
        
        // Approved bookings
        $result = $this->conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'approved'");
        $stats['approved_bookings'] = $result->fetch_assoc()['total'];
        
        // Total revenue
        $result = $this->conn->query("
            SELECT COALESCE(SUM(total_amount), 0) as total 
            FROM bookings 
            WHERE status IN ('approved', 'completed')
        ");
        $stats['total_revenue'] = $result->fetch_assoc()['total'];
        
        // Commission earned
        $result = $this->conn->query("
            SELECT COALESCE(SUM(commission_amount), 0) as total 
            FROM bookings 
            WHERE status IN ('approved', 'completed')
        ");
        $stats['commission_earned'] = $result->fetch_assoc()['total'];
        
        return $stats;
    }
}

?>