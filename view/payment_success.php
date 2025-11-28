<?php
/**
 * Payment Success Page
 * CampusDigs Kenya - Displays payment confirmation details
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/payment_controller.php';
require_once '../controllers/booking_controller.php';
require_once '../controllers/property_controller.php';

// Check if user is logged in
requireLogin();

// Get payment reference from URL
$reference = isset($_GET['reference']) ? sanitizeInput($_GET['reference']) : '';

if (!$reference) {
    redirectWithMessage('student_bookings.php', 'Invalid payment reference', 'error');
}

// Get payment details
$payment = getPaymentByReference($reference);

if (!$payment) {
    redirectWithMessage('student_bookings.php', 'Payment not found', 'error');
}

// Verify payment belongs to logged-in user
if ($payment['student_id'] != $_SESSION['user_id']) {
    redirectWithMessage('student_bookings.php', 'Unauthorized access', 'error');
}

// Get booking and property details
$booking = $payment['booking_id'] ? getBookingById($payment['booking_id']) : null;
$property = getPropertyById($payment['property_id']);

$page_title = 'Payment Successful';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CampusDigs Kenya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #059669;
            --primary-green-dark: #047857;
            --primary-green-light: #10b981;
            --secondary-gold: #d97706;
        }

        body {
            background: linear-gradient(135deg, #f0fdf4 0%, #fef3c7 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .success-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .success-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .success-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-light) 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        .success-body {
            padding: 40px;
        }
        .receipt-section {
            background: #f0fdf4;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #d1fae5;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #d1fae5;
        }
        .receipt-row:last-child {
            border-bottom: none;
        }
        .receipt-label {
            color: #6c757d;
            font-weight: 500;
        }
        .receipt-value {
            font-weight: 600;
            text-align: right;
            color: var(--primary-green-dark);
        }
        .amount-highlight {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-light) 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 25px 0;
        }
        .amount-highlight .amount {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 30px;
        }
        .btn-custom {
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 10px;
        }
        .btn-primary {
            background: var(--primary-green);
            border-color: var(--primary-green);
        }
        .btn-primary:hover {
            background: var(--primary-green-dark);
            border-color: var(--primary-green-dark);
        }
        .btn-outline-primary {
            color: var(--primary-green);
            border-color: var(--primary-green);
        }
        .btn-outline-primary:hover {
            background: var(--primary-green);
            border-color: var(--primary-green);
            color: white;
        }
        .badge.bg-success {
            background: var(--primary-green-light) !important;
        }
        .print-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #d1fae5;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: white;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-header">
                <i class="fas fa-check-circle success-icon"></i>
                <h1>Payment Successful!</h1>
                <p class="mb-0">Your booking has been confirmed</p>
            </div>

            <div class="success-body">
                <div class="receipt-section">
                    <h5 class="mb-4">
                        <i class="fas fa-receipt"></i> Payment Receipt
                    </h5>

                    <div class="receipt-row">
                        <span class="receipt-label">Receipt Number</span>
                        <span class="receipt-value">#<?php echo $payment['id']; ?></span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Payment Reference</span>
                        <span class="receipt-value">
                            <code><?php echo htmlspecialchars($payment['payment_reference']); ?></code>
                        </span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Date & Time</span>
                        <span class="receipt-value">
                            <?php echo date('F j, Y - g:i A', strtotime($payment['paid_at'] ?? $payment['created_at'])); ?>
                        </span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Payment Method</span>
                        <span class="receipt-value">
                            <?php echo ucfirst($payment['payment_method']); ?>
                        </span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Payment Status</span>
                        <span class="receipt-value">
                            <span class="badge bg-success">
                                <?php echo ucfirst($payment['payment_status']); ?>
                            </span>
                        </span>
                    </div>
                </div>

                <div class="amount-highlight">
                    <div class="text-uppercase mb-2" style="font-size: 0.9rem;">Amount Paid</div>
                    <div class="amount">
                        KES <?php echo number_format($payment['amount'], 2); ?>
                    </div>
                </div>

                <?php if ($property): ?>
                <div class="receipt-section">
                    <h5 class="mb-4">
                        <i class="fas fa-home"></i> Property Details
                    </h5>

                    <div class="receipt-row">
                        <span class="receipt-label">Property Name</span>
                        <span class="receipt-value">
                            <?php echo htmlspecialchars($property['title']); ?>
                        </span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Location</span>
                        <span class="receipt-value">
                            <?php echo htmlspecialchars($property['location']); ?>
                        </span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Property Type</span>
                        <span class="receipt-value">
                            <?php echo htmlspecialchars($property['property_type']); ?>
                        </span>
                    </div>

                    <?php if ($booking): ?>
                    <div class="receipt-row">
                        <span class="receipt-label">Check-in Date</span>
                        <span class="receipt-value">
                            <?php echo date('F j, Y', strtotime($booking['check_in_date'])); ?>
                        </span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Check-out Date</span>
                        <span class="receipt-value">
                            <?php echo date('F j, Y', strtotime($booking['check_out_date'])); ?>
                        </span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Booking Status</span>
                        <span class="receipt-value">
                            <span class="badge bg-info">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="receipt-section">
                    <h5 class="mb-4">
                        <i class="fas fa-user"></i> Student Information
                    </h5>

                    <div class="receipt-row">
                        <span class="receipt-label">Full Name</span>
                        <span class="receipt-value">
                            <?php echo htmlspecialchars($payment['student_name']); ?>
                        </span>
                    </div>

                    <div class="receipt-row">
                        <span class="receipt-label">Email</span>
                        <span class="receipt-value">
                            <?php echo htmlspecialchars($payment['student_email']); ?>
                        </span>
                    </div>
                </div>

                <div class="action-buttons no-print">
                    <a href="student_bookings.php" class="btn btn-primary btn-custom">
                        <i class="fas fa-calendar-check"></i> My Bookings
                    </a>
                    <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-outline-primary btn-custom">
                        <i class="fas fa-home"></i> View Property
                    </a>
                </div>

                <div class="print-section no-print">
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                    <button onclick="downloadReceipt()" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-download"></i> Download PDF
                    </button>
                </div>

                <div class="text-center mt-4 text-muted">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        A confirmation email has been sent to <?php echo htmlspecialchars($payment['student_email']); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function downloadReceipt() {
            // In a real implementation, this would generate a PDF
            alert('PDF download functionality will be implemented soon.');
        }

        // Auto-scroll to top on page load
        window.scrollTo(0, 0);
    </script>
</body>
</html>
