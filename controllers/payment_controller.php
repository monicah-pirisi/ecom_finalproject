<?php
/**
 * Payment Controller - Wrapper functions for payment operations
 * CampusDigs Kenya - Payment management
 */

require_once dirname(__FILE__) . '/../classes/payment_class.php';

/**
 * Create a new payment record
 * @param array $payment_data Payment details
 * @return int|false Payment ID on success
 */
function createPayment($payment_data) {
    global $conn;
    $payment = new Payment($conn);
    return $payment->createPayment($payment_data);
}

/**
 * Get payment by reference
 * @param string $reference Payment reference
 * @return array|null Payment data
 */
function getPaymentByReference($reference) {
    global $conn;
    $payment = new Payment($conn);
    return $payment->getPaymentByReference($reference);
}

/**
 * Get payment by ID
 * @param int $payment_id Payment ID
 * @return array|null Payment data
 */
function getPaymentById($payment_id) {
    global $conn;
    $payment = new Payment($conn);
    return $payment->getPaymentById($payment_id);
}

/**
 * Get all payments for a student
 * @param int $student_id Student ID
 * @return array List of payments
 */
function getStudentPayments($student_id) {
    global $conn;
    $payment = new Payment($conn);
    return $payment->getStudentPayments($student_id);
}

/**
 * Get all payments for a property
 * @param int $property_id Property ID
 * @return array List of payments
 */
function getPropertyPayments($property_id) {
    global $conn;
    $payment = new Payment($conn);
    return $payment->getPropertyPayments($property_id);
}

/**
 * Update payment status
 * @param int $payment_id Payment ID
 * @param string $status New status (pending, completed, failed, refunded)
 * @return bool Success status
 */
function updatePaymentStatus($payment_id, $status) {
    global $conn;
    $payment = new Payment($conn);
    return $payment->updatePaymentStatus($payment_id, $status);
}

/**
 * Check if payment reference exists
 * @param string $reference Payment reference
 * @return bool True if exists
 */
function paymentReferenceExists($reference) {
    global $conn;
    $payment = new Payment($conn);
    return $payment->paymentReferenceExists($reference);
}

/**
 * Get total payments for a student
 * @param int $student_id Student ID
 * @return float Total amount paid
 */
function getStudentTotalPayments($student_id) {
    global $conn;
    $payment = new Payment($conn);
    return $payment->getStudentTotalPayments($student_id);
}

/**
 * Get recent payments (for admin dashboard)
 * @param int $limit Number of payments to return
 * @return array List of recent payments
 */
function getRecentPayments($limit = 10) {
    global $conn;
    $payment = new Payment($conn);
    return $payment->getRecentPayments($limit);
}

/**
 * Get payment statistics
 * @return array Payment statistics
 */
function getPaymentStatistics() {
    global $conn;
    $payment = new Payment($conn);
    return $payment->getPaymentStatistics();
}

/**
 * Process refund for a payment
 * @param int $payment_id Payment ID
 * @param string $reason Refund reason
 * @return bool Success status
 */
function processRefund($payment_id, $reason = '') {
    global $conn;

    try {
        // Get payment details
        $payment_data = getPaymentById($payment_id);
        if (!$payment_data) {
            return false;
        }

        // Update payment status to refunded
        $payment = new Payment($conn);
        $success = $payment->updatePaymentStatus($payment_id, 'refunded');

        if ($success) {
            // Log the refund
            error_log("Payment refunded - ID: $payment_id, Amount: KES {$payment_data['amount']}, Reason: $reason");

            // Update related booking status if exists
            if ($payment_data['booking_id']) {
                $stmt = $conn->prepare("
                    UPDATE bookings
                    SET payment_status = 'refunded', status = 'cancelled'
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $payment_data['booking_id']);
                $stmt->execute();
                $stmt->close();
            }

            return true;
        }

        return false;

    } catch (Exception $e) {
        error_log("Error processing refund: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify payment amount matches expected amount
 * @param float $paid_amount Amount paid
 * @param float $expected_amount Expected amount
 * @param float $tolerance Tolerance for rounding (default 1 KES)
 * @return bool True if amounts match
 */
function verifyPaymentAmount($paid_amount, $expected_amount, $tolerance = 1) {
    return abs($paid_amount - $expected_amount) <= $tolerance;
}

/**
 * Get landlord earnings from a property
 * @param int $landlord_id Landlord ID
 * @param int|null $property_id Property ID (optional)
 * @return float Total earnings
 */
function getLandlordEarnings($landlord_id, $property_id = null) {
    global $conn;

    try {
        if ($property_id) {
            // Earnings from specific property
            $stmt = $conn->prepare("
                SELECT SUM(p.amount) as total
                FROM payments p
                JOIN properties pr ON p.property_id = pr.id
                WHERE pr.landlord_id = ? AND p.property_id = ?
                AND p.payment_status = 'completed'
            ");
            $stmt->bind_param("ii", $landlord_id, $property_id);
        } else {
            // Total earnings from all properties
            $stmt = $conn->prepare("
                SELECT SUM(p.amount) as total
                FROM payments p
                JOIN properties pr ON p.property_id = pr.id
                WHERE pr.landlord_id = ?
                AND p.payment_status = 'completed'
            ");
            $stmt->bind_param("i", $landlord_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['total'] ?? 0;

    } catch (Exception $e) {
        error_log("Error getting landlord earnings: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get payment history with filters
 * @param array $filters Filter options (student_id, property_id, status, date_from, date_to)
 * @param int $limit Results limit
 * @param int $offset Results offset
 * @return array List of payments
 */
function getPaymentHistory($filters = [], $limit = 20, $offset = 0) {
    global $conn;

    try {
        $where_clauses = [];
        $params = [];
        $types = '';

        if (isset($filters['student_id'])) {
            $where_clauses[] = "p.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= 'i';
        }

        if (isset($filters['property_id'])) {
            $where_clauses[] = "p.property_id = ?";
            $params[] = $filters['property_id'];
            $types .= 'i';
        }

        if (isset($filters['status'])) {
            $where_clauses[] = "p.payment_status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (isset($filters['date_from'])) {
            $where_clauses[] = "p.created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }

        if (isset($filters['date_to'])) {
            $where_clauses[] = "p.created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        $sql = "
            SELECT p.*, u.full_name as student_name, u.email as student_email,
                   pr.title as property_title, pr.location as property_location
            FROM payments p
            LEFT JOIN users u ON p.student_id = u.id
            LEFT JOIN properties pr ON p.property_id = pr.id
            $where_sql
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);

        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }

        $stmt->close();
        return $payments;

    } catch (Exception $e) {
        error_log("Error getting payment history: " . $e->getMessage());
        return [];
    }
}
?>
