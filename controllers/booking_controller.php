<?php
/**
 * CampusDigs Kenya - Booking Controller
 * Handles all booking-related business logic
 * MVC Architecture - Controller Layer
 */

require_once dirname(__DIR__) . '/classes/booking_class.php';

// STUDENT BOOKING FUNCTIONS
/**
 * Create new booking
 * @param array $data Booking data
 * @return int|false Booking ID if successful, false otherwise
 */
function createBooking($data) {
    $bookingClass = new Booking();
    
    // Extract data
    $studentId = $data['student_id'];
    $propertyId = $data['property_id'];
    $landlordId = $data['landlord_id'];
    $moveInDate = $data['move_in_date'];
    $leaseDurationMonths = $data['lease_duration_months'];
    $monthlyRent = $data['monthly_rent'];
    $securityDeposit = $data['security_deposit'];
    $message = $data['message'] ?? null;
    
    // Calculate totals
    $totalAmount = ($monthlyRent * $leaseDurationMonths) + $securityDeposit;
    $commissionAmount = $totalAmount * COMMISSION_RATE;
    $landlordPayout = $totalAmount - $commissionAmount;
    
    $bookingId = $bookingClass->createBooking(
        $studentId,
        $propertyId,
        $landlordId,
        $moveInDate,
        $leaseDurationMonths,
        $monthlyRent,
        $securityDeposit,
        $totalAmount,
        $commissionAmount,
        $landlordPayout,
        $message
    );
    
    if ($bookingId) {
        logActivity($studentId, 'booking_created', "Created booking #$bookingId");
        
        // TODO: Send notification to landlord
    }
    
    return $bookingId;
}

/**
 * Get student's active bookings
 * @param int $studentId Student ID
 * @param int $limit Number of bookings to retrieve
 * @return array Bookings array
 */
function getStudentActiveBookings($studentId, $limit = 5) {
    $bookingClass = new Booking();
    return $bookingClass->getStudentActiveBookings($studentId, $limit);
}

/**
 * Get student's all bookings with pagination
 * @param int $studentId Student ID
 * @param int $page Page number
 * @param int $perPage Items per page
 * @return array ['bookings' => array, 'total' => int, 'pages' => int]
 */
function getStudentBookings($studentId, $page = 1, $perPage = 10) {
    $bookingClass = new Booking();
    return $bookingClass->getStudentBookings($studentId, $page, $perPage);
}

/**
 * Get student dashboard statistics
 * @param int $studentId Student ID
 * @return array Statistics array
 */
function getStudentDashboardStats($studentId) {
    $bookingClass = new Booking();
    return $bookingClass->getStudentDashboardStats($studentId);
}

/**
 * Get booking by ID (no verification)
 * @param int $bookingId Booking ID
 * @return array|false Booking data if found, false otherwise
 */
function getBookingById($bookingId) {
    $bookingClass = new Booking();
    return $bookingClass->getBookingById($bookingId);
}

/**
 * Get booking by ID
 * @param int $bookingId Booking ID
 * @param int $studentId Student ID (for verification)
 * @return array|false Booking data if found, false otherwise
 */
function getStudentBookingById($bookingId, $studentId) {
    $bookingClass = new Booking();
    $booking = $bookingClass->getBookingById($bookingId);

    // Verify ownership
    if (!$booking || $booking['student_id'] != $studentId) {
        return false;
    }

    return $booking;
}

/**
 * Cancel booking (student action)
 * @param int $bookingId Booking ID
 * @param int $studentId Student ID
 * @param string $reason Cancellation reason
 * @return array ['success' => bool, 'message' => string, 'refund_amount' => float]
 */
function cancelBooking($bookingId, $studentId, $reason) {
    $bookingClass = new Booking();
    $booking = $bookingClass->getBookingById($bookingId);
    
    // Verify ownership
    if (!$booking || $booking['student_id'] != $studentId) {
        return ['success' => false, 'message' => 'Booking not found', 'refund_amount' => 0];
    }
    
    // Check if booking can be cancelled
    if ($booking['status'] === 'cancelled') {
        return ['success' => false, 'message' => 'Booking already cancelled', 'refund_amount' => 0];
    }
    
    if ($booking['status'] === 'completed') {
        return ['success' => false, 'message' => 'Cannot cancel completed booking', 'refund_amount' => 0];
    }
    
    // Calculate refund based on policy
    $refundAmount = 0;
    if ($booking['approved_at']) {
        $daysSinceApproval = (time() - strtotime($booking['approved_at'])) / 86400;
        
        if ($daysSinceApproval <= REFUND_TIER_1_DAYS) {
            $refundAmount = $booking['total_amount'] * REFUND_TIER_1_PERCENT;
        } elseif ($daysSinceApproval <= REFUND_TIER_2_DAYS) {
            $refundAmount = $booking['total_amount'] * REFUND_TIER_2_PERCENT;
        } else {
            $refundAmount = 0;
        }
    } else {
        // Not yet approved, full refund minus processing fee
        $refundAmount = $booking['total_amount'] - PROCESSING_FEE;
    }
    
    // Cancel booking
    $success = $bookingClass->cancelBooking($bookingId, $reason);
    
    if ($success) {
        logActivity($studentId, 'booking_cancelled', "Cancelled booking #$bookingId");
        
        // TODO: Process refund
        // TODO: Send notifications
        
        return [
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'refund_amount' => $refundAmount
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to cancel booking', 'refund_amount' => 0];
}

// LANDLORD BOOKING FUNCTIONS
/**
 * Get landlord's bookings
 * @param int $landlordId Landlord ID
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param string $status Filter by status
 * @return array ['bookings' => array, 'total' => int, 'pages' => int]
 */
function getLandlordBookings($landlordId, $page = 1, $perPage = 10, $status = null) {
    $bookingClass = new Booking();
    return $bookingClass->getLandlordBookings($landlordId, $page, $perPage, $status);
}

/**
 * Get pending bookings for landlord
 * @param int $landlordId Landlord ID
 * @return array Bookings array
 */
function getLandlordPendingBookings($landlordId) {
    $bookingClass = new Booking();
    return $bookingClass->getLandlordPendingBookings($landlordId);
}

/**
 * Approve booking (landlord action)
 * @param int $bookingId Booking ID
 * @param int $landlordId Landlord ID
 * @return bool True if successful
 */
function approveBooking($bookingId, $landlordId) {
    $bookingClass = new Booking();
    $booking = $bookingClass->getBookingById($bookingId);
    
    // Verify ownership
    if (!$booking || $booking['landlord_id'] != $landlordId) {
        return false;
    }
    
    $success = $bookingClass->approveBooking($bookingId);
    
    if ($success) {
        logActivity($landlordId, 'booking_approved', "Approved booking #$bookingId");
        
        // TODO: Send notification to student
        // TODO: Send payment request
    }
    
    return $success;
}

/**
 * Reject booking (landlord action)
 * @param int $bookingId Booking ID
 * @param int $landlordId Landlord ID
 * @param string $reason Rejection reason
 * @return bool True if successful
 */
function rejectBooking($bookingId, $landlordId, $reason) {
    $bookingClass = new Booking();
    $booking = $bookingClass->getBookingById($bookingId);
    
    // Verify ownership
    if (!$booking || $booking['landlord_id'] != $landlordId) {
        return false;
    }
    
    $success = $bookingClass->rejectBooking($bookingId, $reason);
    
    if ($success) {
        logActivity($landlordId, 'booking_rejected', "Rejected booking #$bookingId");
        
        // TODO: Send notification to student
        // TODO: Process refund
    }
    
    return $success;
}

/**
 * Mark booking as completed
 * @param int $bookingId Booking ID
 * @param int $landlordId Landlord ID
 * @return bool True if successful
 */
function completeBooking($bookingId, $landlordId) {
    $bookingClass = new Booking();
    $booking = $bookingClass->getBookingById($bookingId);
    
    // Verify ownership
    if (!$booking || $booking['landlord_id'] != $landlordId) {
        return false;
    }
    
    $success = $bookingClass->completeBooking($bookingId);
    
    if ($success) {
        logActivity($landlordId, 'booking_completed', "Completed booking #$bookingId");
        
        // TODO: Request review from student
    }
    
    return $success;
}

// ADMIN BOOKING FUNCTIONS
/**
 * Get all bookings with pagination
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param array $filters Filters
 * @return array ['bookings' => array, 'total' => int, 'pages' => int]
 */
function getAllBookings($page = 1, $perPage = 20, $filters = []) {
    $bookingClass = new Booking();
    return $bookingClass->getAllBookings($page, $perPage, $filters);
}

/**
 * Get all bookings with advanced filtering (for admin dashboard)
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param array $filters Filter parameters (status, student_search, landlord_search, property_search, date_from, date_to)
 * @return array ['bookings' => array, 'total' => int, 'pages' => int]
 */
function getAllBookingsFiltered($page = 1, $perPage = 20, $filters = []) {
    global $conn;

    $offset = ($page - 1) * $perPage;

    // Base query
    $whereConditions = ["1=1"];
    $params = [];
    $types = "";

    // Status filter
    if (!empty($filters['status'])) {
        $whereConditions[] = "b.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }

    // Student search (name, email)
    if (!empty($filters['student_search'])) {
        $searchTerm = '%' . $filters['student_search'] . '%';
        $whereConditions[] = "(s.full_name LIKE ? OR s.email LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    // Landlord search (name, email)
    if (!empty($filters['landlord_search'])) {
        $searchTerm = '%' . $filters['landlord_search'] . '%';
        $whereConditions[] = "(l.full_name LIKE ? OR l.email LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    // Property search (title)
    if (!empty($filters['property_search'])) {
        $searchTerm = '%' . $filters['property_search'] . '%';
        $whereConditions[] = "p.title LIKE ?";
        $params[] = $searchTerm;
        $types .= "s";
    }

    // Date range filter
    if (!empty($filters['date_from'])) {
        $whereConditions[] = "b.created_at >= ?";
        $params[] = $filters['date_from'] . ' 00:00:00';
        $types .= "s";
    }

    if (!empty($filters['date_to'])) {
        $whereConditions[] = "b.created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
        $types .= "s";
    }

    $whereClause = implode(" AND ", $whereConditions);

    // Count total
    $countQuery = "SELECT COUNT(*) as total
                   FROM bookings b
                   LEFT JOIN users s ON b.student_id = s.id
                   LEFT JOIN properties p ON b.property_id = p.id
                   LEFT JOIN users l ON p.landlord_id = l.id
                   WHERE $whereClause";

    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();

    // Get bookings
    $query = "SELECT b.*,
              s.full_name as student_name,
              s.email as student_email,
              s.phone as student_phone,
              p.title as property_title,
              p.location as property_location,
              p.price_monthly as property_price,
              l.full_name as landlord_name,
              l.email as landlord_email,
              l.phone as landlord_phone
              FROM bookings b
              LEFT JOIN users s ON b.student_id = s.id
              LEFT JOIN properties p ON b.property_id = p.id
              LEFT JOIN users l ON p.landlord_id = l.id
              WHERE $whereClause
              ORDER BY b.created_at DESC
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
 * Get booking statistics
 * @return array Statistics array
 */
function getBookingStatistics() {
    $bookingClass = new Booking();
    return $bookingClass->getBookingStatistics();
}

/**
 * Get student bookings for admin view
 * @param int $studentId Student ID
 * @return array Array of bookings
 */
function getStudentBookingsAdmin($studentId) {
    global $conn;

    $query = "SELECT b.*,
              p.title as property_title,
              p.location as property_location,
              p.price_monthly as property_price,
              u.full_name as landlord_name,
              u.phone as landlord_phone,
              u.email as landlord_email
              FROM bookings b
              LEFT JOIN properties p ON b.property_id = p.id
              LEFT JOIN users u ON p.landlord_id = u.id
              WHERE b.student_id = ?
              ORDER BY b.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $studentId);
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
 * Get landlord bookings for admin view
 * @param int $landlordId Landlord ID
 * @return array Array of bookings
 */
function getLandlordBookingsAdmin($landlordId) {
    global $conn;

    $query = "SELECT b.*,
              p.title as property_title,
              p.location as property_location,
              s.full_name as student_name,
              s.phone as student_phone,
              s.email as student_email
              FROM bookings b
              LEFT JOIN properties p ON b.property_id = p.id
              LEFT JOIN users s ON b.student_id = s.id
              WHERE p.landlord_id = ?
              ORDER BY b.created_at DESC";

    $stmt = $conn->prepare($query);
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
 * Get landlord total revenue
 * @param int $landlordId Landlord ID
 * @return float Total revenue
 */
function getLandlordTotalRevenue($landlordId) {
    global $conn;

    $query = "SELECT SUM(p.price_monthly) as total_revenue
              FROM bookings b
              INNER JOIN properties p ON b.property_id = p.id
              WHERE p.landlord_id = ?
              AND b.status IN ('confirmed', 'active', 'completed')";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (float)($row['total_revenue'] ?? 0);
}

// PAYMENT INTEGRATION HELPERS
/**
 * Generate payment link for booking
 * @param int $bookingId Booking ID
 * @return string|false Payment link if successful, false otherwise
 */
function generateBookingPaymentLink($bookingId) {
    $bookingClass = new Booking();
    $booking = $bookingClass->getBookingById($bookingId);
    
    if (!$booking) {
        return false;
    }
    
    // TODO: Integrate with Paystack API
    // For now, return placeholder
    return BASE_URL . "/view/payment.php?booking_id=" . $bookingId;
}

/**
 * Check if booking has been paid
 * @param int $bookingId Booking ID
 * @return bool True if paid
 */
function isBookingPaid($bookingId) {
    $bookingClass = new Booking();
    return $bookingClass->isBookingPaid($bookingId);
}

/**
 * Get booking status counts for admin dashboard
 * @return array Counts by status
 */
function getBookingStatusCounts() {
    global $conn;

    $counts = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'cancelled' => 0,
        'completed' => 0
    ];

    // Total bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings");
    if ($result) {
        $counts['total'] = $result->fetch_assoc()['total'];
    }

    // Pending bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
    if ($result) {
        $counts['pending'] = $result->fetch_assoc()['total'];
    }

    // Approved bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'approved'");
    if ($result) {
        $counts['approved'] = $result->fetch_assoc()['total'];
    }

    // Rejected bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'rejected'");
    if ($result) {
        $counts['rejected'] = $result->fetch_assoc()['total'];
    }

    // Cancelled bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'cancelled'");
    if ($result) {
        $counts['cancelled'] = $result->fetch_assoc()['total'];
    }

    // Completed bookings
    $result = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'completed'");
    if ($result) {
        $counts['completed'] = $result->fetch_assoc()['total'];
    }

    return $counts;
}

?>