<?php
/**
 * Payment Class - Database operations for payments
 * CampusDigs Kenya - Handles payment-related database queries
 */

class Payment {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Create a new payment record
     * @param array $payment_data Payment details
     * @return int|false Payment ID on success, false on failure
     */
    public function createPayment($payment_data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO payments
                (booking_id, student_id, property_id, amount, currency, payment_method,
                 payment_reference, authorization_code, payment_status, paid_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param("iiidssssss",
                $payment_data['booking_id'],
                $payment_data['student_id'],
                $payment_data['property_id'],
                $payment_data['amount'],
                $payment_data['currency'],
                $payment_data['payment_method'],
                $payment_data['payment_reference'],
                $payment_data['authorization_code'],
                $payment_data['payment_status'],
                $payment_data['paid_at']
            );

            if ($stmt->execute()) {
                $payment_id = $stmt->insert_id;
                $stmt->close();
                return $payment_id;
            }

            $stmt->close();
            return false;

        } catch (Exception $e) {
            error_log("Error creating payment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment by reference
     * @param string $reference Payment reference
     * @return array|null Payment data
     */
    public function getPaymentByReference($reference) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, u.full_name as student_name, u.email as student_email,
                       pr.title as property_title, pr.price as property_price
                FROM payments p
                LEFT JOIN users u ON p.student_id = u.id
                LEFT JOIN properties pr ON p.property_id = pr.id
                WHERE p.payment_reference = ?
            ");

            $stmt->bind_param("s", $reference);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment = $result->fetch_assoc();
            $stmt->close();

            return $payment;

        } catch (Exception $e) {
            error_log("Error getting payment by reference: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get payment by ID
     * @param int $payment_id Payment ID
     * @return array|null Payment data
     */
    public function getPaymentById($payment_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, u.full_name as student_name, u.email as student_email,
                       pr.title as property_title, pr.price as property_price,
                       b.check_in_date, b.check_out_date
                FROM payments p
                LEFT JOIN users u ON p.student_id = u.id
                LEFT JOIN properties pr ON p.property_id = pr.id
                LEFT JOIN bookings b ON p.booking_id = b.id
                WHERE p.id = ?
            ");

            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment = $result->fetch_assoc();
            $stmt->close();

            return $payment;

        } catch (Exception $e) {
            error_log("Error getting payment by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all payments for a student
     * @param int $student_id Student ID
     * @return array List of payments
     */
    public function getStudentPayments($student_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, pr.title as property_title, pr.location as property_location,
                       b.check_in_date, b.check_out_date
                FROM payments p
                LEFT JOIN properties pr ON p.property_id = pr.id
                LEFT JOIN bookings b ON p.booking_id = b.id
                WHERE p.student_id = ?
                ORDER BY p.created_at DESC
            ");

            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }

            $stmt->close();
            return $payments;

        } catch (Exception $e) {
            error_log("Error getting student payments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all payments for a property
     * @param int $property_id Property ID
     * @return array List of payments
     */
    public function getPropertyPayments($property_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, u.full_name as student_name, u.email as student_email,
                       b.check_in_date, b.check_out_date, b.status as booking_status
                FROM payments p
                LEFT JOIN users u ON p.student_id = u.id
                LEFT JOIN bookings b ON p.booking_id = b.id
                WHERE p.property_id = ?
                ORDER BY p.created_at DESC
            ");

            $stmt->bind_param("i", $property_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }

            $stmt->close();
            return $payments;

        } catch (Exception $e) {
            error_log("Error getting property payments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update payment status
     * @param int $payment_id Payment ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updatePaymentStatus($payment_id, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE payments
                SET payment_status = ?, updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->bind_param("si", $status, $payment_id);
            $success = $stmt->execute();
            $stmt->close();

            return $success;

        } catch (Exception $e) {
            error_log("Error updating payment status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if payment reference exists
     * @param string $reference Payment reference
     * @return bool True if exists
     */
    public function paymentReferenceExists($reference) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM payments WHERE payment_reference = ?
            ");

            $stmt->bind_param("s", $reference);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return $row['count'] > 0;

        } catch (Exception $e) {
            error_log("Error checking payment reference: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total payments for a student
     * @param int $student_id Student ID
     * @return float Total amount paid
     */
    public function getStudentTotalPayments($student_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(amount) as total
                FROM payments
                WHERE student_id = ? AND payment_status = 'completed'
            ");

            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            return $row['total'] ?? 0;

        } catch (Exception $e) {
            error_log("Error getting student total payments: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent payments (for admin dashboard)
     * @param int $limit Number of payments to return
     * @return array List of recent payments
     */
    public function getRecentPayments($limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, u.full_name as student_name, pr.title as property_title
                FROM payments p
                LEFT JOIN users u ON p.student_id = u.id
                LEFT JOIN properties pr ON p.property_id = pr.id
                ORDER BY p.created_at DESC
                LIMIT ?
            ");

            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $payments = [];
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }

            $stmt->close();
            return $payments;

        } catch (Exception $e) {
            error_log("Error getting recent payments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get payment statistics
     * @return array Payment statistics
     */
    public function getPaymentStatistics() {
        try {
            $stats = [];

            // Total revenue
            $stmt = $this->conn->prepare("
                SELECT SUM(amount) as total_revenue, COUNT(*) as total_payments
                FROM payments
                WHERE payment_status = 'completed'
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['total_revenue'] = $row['total_revenue'] ?? 0;
            $stats['total_payments'] = $row['total_payments'] ?? 0;
            $stmt->close();

            // Pending payments
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as pending_payments
                FROM payments
                WHERE payment_status = 'pending'
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['pending_payments'] = $row['pending_payments'] ?? 0;
            $stmt->close();

            // Revenue this month
            $stmt = $this->conn->prepare("
                SELECT SUM(amount) as monthly_revenue
                FROM payments
                WHERE payment_status = 'completed'
                AND MONTH(created_at) = MONTH(CURRENT_DATE())
                AND YEAR(created_at) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['monthly_revenue'] = $row['monthly_revenue'] ?? 0;
            $stmt->close();

            return $stats;

        } catch (Exception $e) {
            error_log("Error getting payment statistics: " . $e->getMessage());
            return [];
        }
    }
}
?>
