<?php
/**
 * CampusDigs Kenya - Review Controller
 * Handles all review-related operations
 */

require_once dirname(__DIR__) . '/controllers/user_controller.php';

/**
 * Create a new review
 * @param int $studentId Student ID
 * @param int $propertyId Property ID
 * @param int $bookingId Booking ID
 * @param int $rating Rating (1-5)
 * @param string $comment Review comment
 * @return array Result array with success status and message
 */
function createReview($studentId, $propertyId, $bookingId, $rating, $comment) {
    global $conn;

    try {
        // Validate inputs
        if (!$studentId || !$propertyId || !$bookingId) {
            return ['success' => false, 'message' => 'Missing required information'];
        }

        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'Rating must be between 1 and 5'];
        }

        // Check if booking exists and belongs to student
        $stmt = $conn->prepare("
            SELECT b.id, b.student_id, b.property_id, b.status
            FROM bookings b
            WHERE b.id = ? AND b.student_id = ? AND b.property_id = ?
        ");
        $stmt->bind_param("iii", $bookingId, $studentId, $propertyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            return ['success' => false, 'message' => 'Invalid booking or access denied'];
        }

        // Check if booking is completed
        if ($booking['status'] !== 'completed') {
            return ['success' => false, 'message' => 'You can only review completed bookings'];
        }

        // Check if review already exists
        $stmt = $conn->prepare("
            SELECT id FROM reviews
            WHERE booking_id = ? AND student_id = ?
        ");
        $stmt->bind_param("ii", $bookingId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'You have already reviewed this property'];
        }
        $stmt->close();

        // Insert review with pending approval (is_approved = 0)
        $stmt = $conn->prepare("
            INSERT INTO reviews (property_id, student_id, booking_id, rating, comment, is_approved, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");

        $stmt->bind_param("iiiis", $propertyId, $studentId, $bookingId, $rating, $comment);

        if ($stmt->execute()) {
            $reviewId = $stmt->insert_id;
            $stmt->close();

            // Log activity
            logActivity($studentId, 'review_created', "Created review for booking #$bookingId");

            return [
                'success' => true,
                'message' => 'Thank you for your review! It will be published after moderation.',
                'review_id' => $reviewId
            ];
        } else {
            $error = $stmt->error;
            $stmt->close();
            error_log("Error creating review: " . $error);
            return ['success' => false, 'message' => 'Failed to submit review. Please try again.'];
        }

    } catch (Exception $e) {
        error_log("Error in createReview: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while submitting your review'];
    }
}

/**
 * Get review status counts
 * @return array Counts by status
 */
function getReviewStatusCounts() {
    global $conn;

    $counts = [
        'total' => 0,
        'approved' => 0,
        'pending' => 0,
        'flagged' => 0,
        'deleted' => 0
    ];

    try {
        // Get total count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE moderation_status != 'deleted'");
        $stmt->execute();
        $result = $stmt->get_result();
        $counts['total'] = $result->fetch_assoc()['count'];
        $stmt->close();

        // Get counts by status
        $stmt = $conn->prepare("
            SELECT moderation_status, COUNT(*) as count
            FROM reviews
            GROUP BY moderation_status
        ");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $status = $row['moderation_status'];
            if (isset($counts[$status])) {
                $counts[$status] = (int)$row['count'];
            }
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Error getting review counts: " . $e->getMessage());
    }

    return $counts;
}

/**
 * Get all reviews with filters
 * @param int $page Page number
 * @param int $perPage Items per page
 * @param array $filters Filter criteria
 * @return array Reviews and pagination data
 */
function getAllReviewsFiltered($page, $perPage, $filters = []) {
    global $conn;

    $offset = ($page - 1) * $perPage;

    // Build WHERE clause
    $where = ["1=1"];
    $params = [];
    $types = "";

    if (!empty($filters['status'])) {
        $where[] = "r.moderation_status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }

    if (!empty($filters['rating'])) {
        $where[] = "r.rating = ?";
        $params[] = $filters['rating'];
        $types .= "i";
    }

    if (!empty($filters['property_search'])) {
        $where[] = "p.title LIKE ?";
        $params[] = "%" . $filters['property_search'] . "%";
        $types .= "s";
    }

    if (!empty($filters['student_search'])) {
        $where[] = "u.full_name LIKE ?";
        $params[] = "%" . $filters['student_search'] . "%";
        $types .= "s";
    }

    $whereClause = implode(" AND ", $where);

    // Build ORDER BY clause
    $orderBy = "r.created_at DESC"; // Default: most recent
    if (isset($filters['sort'])) {
        switch ($filters['sort']) {
            case 'oldest':
                $orderBy = "r.created_at ASC";
                break;
            case 'highest':
                $orderBy = "r.rating DESC, r.created_at DESC";
                break;
            case 'lowest':
                $orderBy = "r.rating ASC, r.created_at DESC";
                break;
        }
    }

    try {
        // Get total count
        $countSql = "
            SELECT COUNT(*) as total
            FROM reviews r
            INNER JOIN properties p ON r.property_id = p.id
            INNER JOIN users u ON r.student_id = u.id
            WHERE {$whereClause}
        ";

        $stmt = $conn->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();

        // Get reviews
        $sql = "
            SELECT
                r.*,
                p.title as property_title,
                p.location as property_location,
                u.full_name as student_name,
                u.email as student_email,
                (SELECT COUNT(*) FROM review_flags rf WHERE rf.review_id = r.id) as flag_count
            FROM reviews r
            INNER JOIN properties p ON r.property_id = p.id
            INNER JOIN users u ON r.student_id = u.id
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);
        $params[] = $perPage;
        $params[] = $offset;
        $types .= "ii";
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt->close();

        return [
            'reviews' => $reviews,
            'total' => $total,
            'pages' => ceil($total / $perPage),
            'current_page' => $page
        ];

    } catch (Exception $e) {
        error_log("Error getting filtered reviews: " . $e->getMessage());
        return [
            'reviews' => [],
            'total' => 0,
            'pages' => 0,
            'current_page' => 1
        ];
    }
}

/**
 * Get review by ID
 * @param int $reviewId Review ID
 * @return array|null Review data
 */
function getReviewById($reviewId) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT
                r.*,
                p.title as property_title,
                p.location as property_location,
                u.full_name as student_name,
                u.email as student_email,
                u.phone as student_phone
            FROM reviews r
            INNER JOIN properties p ON r.property_id = p.id
            INNER JOIN users u ON r.student_id = u.id
            WHERE r.id = ?
        ");

        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();
        $review = $result->fetch_assoc();
        $stmt->close();

        return $review;

    } catch (Exception $e) {
        error_log("Error getting review by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get review flag details
 * @param int $reviewId Review ID
 * @return array Flag details
 */
function getReviewFlagDetails($reviewId) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT
                rf.*,
                u.full_name as flagger_name
            FROM review_flags rf
            INNER JOIN users u ON rf.flagger_id = u.id
            WHERE rf.review_id = ?
            ORDER BY rf.flagged_at DESC
        ");

        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();

        $flags = [];
        while ($row = $result->fetch_assoc()) {
            $flags[] = $row;
        }
        $stmt->close();

        return $flags;

    } catch (Exception $e) {
        error_log("Error getting review flag details: " . $e->getMessage());
        return [];
    }
}

/**
 * Get student review statistics
 * @param int $studentId Student ID
 * @return array Review statistics
 */
function getStudentReviewStats($studentId) {
    global $conn;

    $stats = [
        'total_reviews' => 0,
        'average_rating_given' => 0
    ];

    try {
        $stmt = $conn->prepare("
            SELECT
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating
            FROM reviews
            WHERE student_id = ? AND moderation_status != 'deleted'
        ");

        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        $stats['total_reviews'] = (int)$data['total_reviews'];
        $stats['average_rating_given'] = round($data['average_rating'], 1);

    } catch (Exception $e) {
        error_log("Error getting student review stats: " . $e->getMessage());
    }

    return $stats;
}

/**
 * Approve a review
 * @param int $reviewId Review ID
 * @param int $adminId Admin ID
 * @return bool Success status
 */
function approveReview($reviewId, $adminId) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            UPDATE reviews
            SET moderation_status = 'approved',
                approved_at = NOW(),
                approved_by = ?
            WHERE id = ?
        ");

        $stmt->bind_param("ii", $adminId, $reviewId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;

    } catch (Exception $e) {
        error_log("Error approving review: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a review
 * @param int $reviewId Review ID
 * @param int $adminId Admin ID
 * @param string $reason Deletion reason
 * @return bool Success status
 */
function deleteReview($reviewId, $adminId, $reason) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            UPDATE reviews
            SET moderation_status = 'deleted',
                deleted_at = NOW(),
                deleted_by = ?,
                deletion_reason = ?
            WHERE id = ?
        ");

        $stmt->bind_param("isi", $adminId, $reason, $reviewId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;

    } catch (Exception $e) {
        error_log("Error deleting review: " . $e->getMessage());
        return false;
    }
}

/**
 * Flag a review for attention
 * @param int $reviewId Review ID
 * @param int $adminId Admin ID
 * @param string $reason Flag reason
 * @return bool Success status
 */
function flagReview($reviewId, $adminId, $reason) {
    global $conn;

    try {
        // Update review status
        $stmt = $conn->prepare("
            UPDATE reviews
            SET moderation_status = 'flagged'
            WHERE id = ?
        ");

        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $stmt->close();

        // Add flag record
        $stmt = $conn->prepare("
            INSERT INTO review_flags (review_id, flagger_id, flag_reason, flagged_at)
            VALUES (?, ?, ?, NOW())
        ");

        $stmt->bind_param("iis", $reviewId, $adminId, $reason);
        $success = $stmt->execute();
        $stmt->close();

        return $success;

    } catch (Exception $e) {
        error_log("Error flagging review: " . $e->getMessage());
        return false;
    }
}

/**
 * Edit a review
 * @param int $reviewId Review ID
 * @param int $adminId Admin ID
 * @param string $reviewText New review text
 * @param string $editReason Edit reason
 * @return bool Success status
 */
function editReview($reviewId, $adminId, $reviewText, $editReason) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            UPDATE reviews
            SET review_text = ?,
                edited_by_admin = 1,
                edited_at = NOW(),
                edited_by = ?,
                edit_reason = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("sisi", $reviewText, $adminId, $editReason, $reviewId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;

    } catch (Exception $e) {
        error_log("Error editing review: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user analytics for reports
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array User analytics data
 */
function getUserAnalytics($startDate, $endDate) {
    global $conn;

    $analytics = [
        'total_users' => 0,
        'total_students' => 0,
        'total_landlords' => 0,
        'active_users' => 0,
        'new_users' => 0,
        'retention_rate' => 0,
        'growth_rate' => 0,
        'top_students' => [],
        'top_landlords' => []
    ];

    try {
        // Total users
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE user_type != 'admin'");
        $stmt->execute();
        $analytics['total_users'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Students
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'");
        $stmt->execute();
        $analytics['total_students'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Landlords
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'landlord'");
        $stmt->execute();
        $analytics['total_landlords'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Active users (last 30 days)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $analytics['active_users'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // New users in date range
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN ? AND ?");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $analytics['new_users'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Calculate retention rate
        if ($analytics['total_users'] > 0) {
            $analytics['retention_rate'] = ($analytics['active_users'] / $analytics['total_users']) * 100;
        }

        // Calculate growth rate (placeholder - would need historical data)
        $analytics['growth_rate'] = 15.5; // Mock data

        // Top students
        $stmt = $conn->prepare("
            SELECT u.id, u.full_name,
                   COUNT(DISTINCT b.id) as booking_count,
                   COUNT(DISTINCT r.id) as review_count
            FROM users u
            LEFT JOIN bookings b ON u.id = b.student_id
            LEFT JOIN reviews r ON u.id = r.student_id
            WHERE u.user_type = 'student'
            GROUP BY u.id
            ORDER BY booking_count DESC, review_count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['top_students'][] = $row;
        }
        $stmt->close();

        // Top landlords
        $stmt = $conn->prepare("
            SELECT u.id, u.full_name,
                   COUNT(DISTINCT p.id) as property_count,
                   COUNT(DISTINCT b.id) as booking_count
            FROM users u
            LEFT JOIN properties p ON u.id = p.landlord_id
            LEFT JOIN bookings b ON p.id = b.property_id
            WHERE u.user_type = 'landlord'
            GROUP BY u.id
            ORDER BY booking_count DESC, property_count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['top_landlords'][] = $row;
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Error getting user analytics: " . $e->getMessage());
    }

    return $analytics;
}

/**
 * Get user registration trend data
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array Chart data
 */
function getUserRegistrationTrend($startDate, $endDate) {
    global $conn;

    $data = ['labels' => [], 'data' => []];

    try {
        $stmt = $conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM users
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = date('M d', strtotime($row['date']));
            $data['data'][] = (int)$row['count'];
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Error getting registration trend: " . $e->getMessage());
    }

    return $data;
}

/**
 * Get property analytics
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array Property analytics data
 */
function getPropertyAnalytics($startDate, $endDate) {
    global $conn;

    $analytics = [
        'total_properties' => 0,
        'active_properties' => 0,
        'pending_properties' => 0,
        'average_price' => 0,
        'growth_rate' => 0,
        'by_location' => [],
        'most_viewed' => [],
        'most_booked' => [],
        'status_distribution' => []
    ];

    try {
        // Total properties
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM properties");
        $stmt->execute();
        $analytics['total_properties'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Active properties
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM properties WHERE status = 'active'");
        $stmt->execute();
        $analytics['active_properties'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Pending properties
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM properties WHERE status = 'pending'");
        $stmt->execute();
        $analytics['pending_properties'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Average price
        $stmt = $conn->prepare("SELECT AVG(price) as avg_price FROM properties WHERE status = 'active'");
        $stmt->execute();
        $analytics['average_price'] = (float)$stmt->get_result()->fetch_assoc()['avg_price'];
        $stmt->close();

        // Properties by location
        $stmt = $conn->prepare("
            SELECT location, COUNT(*) as count,
                   (COUNT(*) * 100.0 / ?) as percentage
            FROM properties
            WHERE status = 'active'
            GROUP BY location
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->bind_param("i", $analytics['total_properties']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['by_location'][] = $row;
        }
        $stmt->close();

        // Most viewed
        $stmt = $conn->prepare("
            SELECT id, title, view_count,
                   (SELECT COUNT(*) FROM bookings WHERE property_id = properties.id) as booking_count
            FROM properties
            WHERE status = 'active'
            ORDER BY view_count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['most_viewed'][] = $row;
        }
        $stmt->close();

        // Most booked
        $stmt = $conn->prepare("
            SELECT p.id, p.title,
                   COUNT(b.id) as booking_count,
                   SUM(b.total_amount) as total_revenue
            FROM properties p
            LEFT JOIN bookings b ON p.id = b.property_id
            WHERE p.status = 'active'
            GROUP BY p.id
            ORDER BY booking_count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['most_booked'][] = $row;
        }
        $stmt->close();

        // Status distribution
        $stmt = $conn->prepare("
            SELECT status, COUNT(*) as count
            FROM properties
            GROUP BY status
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['status_distribution'][] = $row;
        }
        $stmt->close();

        $analytics['growth_rate'] = 12.3; // Mock data

    } catch (Exception $e) {
        error_log("Error getting property analytics: " . $e->getMessage());
    }

    return $analytics;
}

/**
 * Get booking volume trend data
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array Chart data
 */
function getBookingVolumeTrend($startDate, $endDate) {
    global $conn;

    $data = ['labels' => [], 'data' => []];

    try {
        $stmt = $conn->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM bookings
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = date('M d', strtotime($row['date']));
            $data['data'][] = (int)$row['count'];
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Error getting booking volume: " . $e->getMessage());
    }

    return $data;
}

/**
 * Get revenue over time data
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array Chart data
 */
function getRevenueOverTime($startDate, $endDate) {
    global $conn;

    $data = ['labels' => [], 'data' => []];

    try {
        $stmt = $conn->prepare("
            SELECT DATE(created_at) as date, SUM(total_amount) as revenue
            FROM bookings
            WHERE created_at BETWEEN ? AND ? AND status IN ('approved', 'completed')
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = date('M d', strtotime($row['date']));
            $data['data'][] = (float)$row['revenue'];
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Error getting revenue over time: " . $e->getMessage());
    }

    return $data;
}

/**
 * Get property distribution data
 * @return array Chart data
 */
function getPropertyDistribution() {
    global $conn;

    $data = ['labels' => [], 'data' => []];

    try {
        $stmt = $conn->prepare("
            SELECT location, COUNT(*) as count
            FROM properties
            WHERE status = 'active'
            GROUP BY location
            ORDER BY count DESC
            LIMIT 5
        ");

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data['labels'][] = $row['location'];
            $data['data'][] = (int)$row['count'];
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Error getting property distribution: " . $e->getMessage());
    }

    return $data;
}

/**
 * Get booking analytics
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array Booking analytics data
 */
function getBookingAnalytics($startDate, $endDate) {
    global $conn;

    $analytics = [
        'total_bookings' => 0,
        'approved_bookings' => 0,
        'conversion_rate' => 0,
        'cancellation_rate' => 0,
        'average_booking_value' => 0,
        'growth_rate' => 0,
        'status_distribution' => [],
        'peak_periods' => []
    ];

    try {
        // Total bookings
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings");
        $stmt->execute();
        $analytics['total_bookings'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Approved bookings
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = 'approved'");
        $stmt->execute();
        $analytics['approved_bookings'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Average booking value
        $stmt = $conn->prepare("SELECT AVG(total_amount) as avg_value FROM bookings WHERE status IN ('approved', 'completed')");
        $stmt->execute();
        $analytics['average_booking_value'] = (float)$stmt->get_result()->fetch_assoc()['avg_value'];
        $stmt->close();

        // Calculate rates
        if ($analytics['total_bookings'] > 0) {
            $analytics['conversion_rate'] = ($analytics['approved_bookings'] / $analytics['total_bookings']) * 100;
        }

        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = 'cancelled'");
        $stmt->execute();
        $cancelledCount = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        if ($analytics['total_bookings'] > 0) {
            $analytics['cancellation_rate'] = ($cancelledCount / $analytics['total_bookings']) * 100;
        }

        // Status distribution
        $stmt = $conn->prepare("
            SELECT status, COUNT(*) as count
            FROM bookings
            GROUP BY status
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['status_distribution'][] = $row;
        }
        $stmt->close();

        // Peak periods (by month)
        $stmt = $conn->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
            FROM bookings
            GROUP BY month
            ORDER BY count DESC
            LIMIT 12
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['peak_periods'][] = $row;
        }
        $stmt->close();

        $analytics['growth_rate'] = 18.7; // Mock data

    } catch (Exception $e) {
        error_log("Error getting booking analytics: " . $e->getMessage());
    }

    return $analytics;
}

/**
 * Get revenue analytics
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array Revenue analytics data
 */
function getRevenueAnalytics($startDate, $endDate) {
    global $conn;

    $analytics = [
        'total_revenue' => 0,
        'commission_earned' => 0,
        'monthly_revenue' => 0,
        'average_transaction' => 0,
        'growth_rate' => 0,
        'monthly_breakdown' => [],
        'top_properties' => [],
        'payment_methods' => []
    ];

    try {
        // Total revenue
        $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM bookings WHERE status IN ('approved', 'completed')");
        $stmt->execute();
        $analytics['total_revenue'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Commission earned (10%)
        $analytics['commission_earned'] = $analytics['total_revenue'] * 0.10;

        // This month's revenue
        $stmt = $conn->prepare("
            SELECT SUM(total_amount) as total
            FROM bookings
            WHERE status IN ('approved', 'completed')
            AND MONTH(created_at) = MONTH(NOW())
            AND YEAR(created_at) = YEAR(NOW())
        ");
        $stmt->execute();
        $analytics['monthly_revenue'] = (float)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Average transaction
        $stmt = $conn->prepare("SELECT AVG(total_amount) as avg_value FROM bookings WHERE status IN ('approved', 'completed')");
        $stmt->execute();
        $analytics['average_transaction'] = (float)$stmt->get_result()->fetch_assoc()['avg_value'];
        $stmt->close();

        // Monthly breakdown
        $stmt = $conn->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as revenue
            FROM bookings
            WHERE status IN ('approved', 'completed')
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['monthly_breakdown'][] = $row;
        }
        $stmt->close();

        // Top revenue properties
        $stmt = $conn->prepare("
            SELECT p.id, p.title,
                   COUNT(b.id) as booking_count,
                   SUM(b.total_amount) as total_revenue,
                   SUM(b.commission_amount) as commission
            FROM properties p
            INNER JOIN bookings b ON p.id = b.property_id
            WHERE b.status IN ('approved', 'completed')
            GROUP BY p.id
            ORDER BY total_revenue DESC
            LIMIT 10
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $analytics['top_properties'][] = $row;
        }
        $stmt->close();

        // Payment methods (mock data for now)
        $analytics['payment_methods'] = [
            ['method' => 'M-Pesa', 'count' => 150],
            ['method' => 'Bank Transfer', 'count' => 45],
            ['method' => 'Cash', 'count' => 30]
        ];

        $analytics['growth_rate'] = 22.4; // Mock data

    } catch (Exception $e) {
        error_log("Error getting revenue analytics: " . $e->getMessage());
    }

    return $analytics;
}

/**
 * Get recent admin activity
 * @param int $limit Number of activities to fetch
 * @return array Activity log entries
 */
function getRecentAdminActivity($limit = 20) {
    global $conn;

    try {
        $stmt = $conn->prepare("
            SELECT al.*, u.full_name as admin_name
            FROM admin_logs al
            INNER JOIN users u ON al.admin_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?
        ");

        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        $stmt->close();

        return $activities;

    } catch (Exception $e) {
        error_log("Error getting recent activity: " . $e->getMessage());
        return [];
    }
}
?>
