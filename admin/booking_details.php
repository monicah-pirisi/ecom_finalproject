<?php
/**
 * CampusDigs Kenya - Admin Booking Details
 * Detailed booking view with complete transaction information
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/booking_controller.php';
require_once '../controllers/property_controller.php';
require_once '../classes/booking_class.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get booking ID
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$bookingId) {
    $_SESSION['error'] = 'Invalid booking ID';
    header('Location: manage_bookings.php');
    exit();
}

// Get booking details
$bookingClass = new Booking();
$booking = $bookingClass->getBookingById($bookingId);

if (!$booking) {
    $_SESSION['error'] = 'Booking not found';
    header('Location: manage_bookings.php');
    exit();
}

// Get property details
$property = getPropertyById($booking['property_id']);
$propertyImages = getPropertyImages($booking['property_id']);

// Calculate days until move-in
$moveInTimestamp = strtotime($booking['move_in_date']);
$today = time();
$daysUntilMoveIn = ceil(($moveInTimestamp - $today) / 86400);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking #<?php echo $booking['booking_reference']; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <style>
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.875rem;
        }
        .info-value {
            font-size: 1rem;
            font-weight: 500;
        }
        .timeline-item {
            border-left: 3px solid #dee2e6;
            padding-left: 20px;
            padding-bottom: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            width: 14px;
            height: 14px;
            background: #17a2b8;
            border: 3px solid white;
            border-radius: 50%;
            position: absolute;
            left: -9px;
            top: 0;
            box-shadow: 0 0 0 2px #17a2b8;
        }
        .timeline-item:last-child {
            border-left-color: transparent;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-4">
        <!-- Back Button -->
        <a href="manage_bookings.php" class="btn btn-outline-secondary mb-3 no-print">
            <i class="fas fa-arrow-left"></i> Back to Bookings
        </a>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show no-print">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Booking Header -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice"></i>
                        Booking #<?php echo $booking['booking_reference']; ?>
                    </h4>
                    <?php
                    $statusClass = [
                        'pending' => 'bg-warning text-dark',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'completed' => 'bg-primary',
                        'cancelled' => 'bg-secondary'
                    ][$booking['status']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $statusClass; ?> fs-6">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="info-label mb-1">Booking Date</p>
                        <p class="info-value"><?php echo formatDateTime($booking['created_at']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="info-label mb-1">Move-in Date</p>
                        <p class="info-value">
                            <?php echo formatDate($booking['move_in_date']); ?>
                            <?php if ($daysUntilMoveIn > 0 && $booking['status'] === 'approved'): ?>
                                <small class="text-muted">(in <?php echo $daysUntilMoveIn; ?> days)</small>
                            <?php elseif ($daysUntilMoveIn < 0): ?>
                                <small class="text-danger">(<?php echo abs($daysUntilMoveIn); ?> days ago)</small>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Student Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user-graduate text-primary"></i> Student Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="info-label mb-1">Full Name</p>
                                <p class="info-value"><?php echo htmlspecialchars($booking['student_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Student ID</p>
                                <p class="info-value">#<?php echo $booking['student_id']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Email Address</p>
                                <p class="info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($booking['student_email']); ?>">
                                        <?php echo htmlspecialchars($booking['student_email']); ?>
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Phone Number</p>
                                <p class="info-value">
                                    <a href="tel:<?php echo htmlspecialchars($booking['student_phone']); ?>">
                                        <?php echo htmlspecialchars($booking['student_phone']); ?>
                                    </a>
                                </p>
                            </div>
                            <?php if (!empty($booking['message'])): ?>
                                <div class="col-12">
                                    <p class="info-label mb-1">Message from Student</p>
                                    <div class="alert alert-light">
                                        <?php echo nl2br(htmlspecialchars($booking['message'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="col-12 no-print">
                                <div class="btn-group" role="group">
                                    <a href="user_details.php?id=<?php echo $booking['student_id']; ?>"
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-user"></i> View Profile
                                    </a>
                                    <a href="mailto:<?php echo htmlspecialchars($booking['student_email']); ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-envelope"></i> Email
                                    </a>
                                    <a href="tel:<?php echo htmlspecialchars($booking['student_phone']); ?>"
                                       class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-phone"></i> Call
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Landlord Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user-tie text-success"></i> Landlord Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="info-label mb-1">Full Name</p>
                                <p class="info-value"><?php echo htmlspecialchars($booking['landlord_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Landlord ID</p>
                                <p class="info-value">#<?php echo $booking['landlord_id']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Email Address</p>
                                <p class="info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($booking['landlord_email']); ?>">
                                        <?php echo htmlspecialchars($booking['landlord_email']); ?>
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Phone Number</p>
                                <p class="info-value">
                                    <a href="tel:<?php echo htmlspecialchars($booking['landlord_phone']); ?>">
                                        <?php echo htmlspecialchars($booking['landlord_phone']); ?>
                                    </a>
                                </p>
                            </div>
                            <div class="col-12 no-print">
                                <div class="btn-group" role="group">
                                    <a href="user_details.php?id=<?php echo $booking['landlord_id']; ?>"
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-user"></i> View Profile
                                    </a>
                                    <a href="mailto:<?php echo htmlspecialchars($booking['landlord_email']); ?>"
                                       class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-envelope"></i> Email
                                    </a>
                                    <a href="tel:<?php echo htmlspecialchars($booking['landlord_phone']); ?>"
                                       class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-phone"></i> Call
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Property Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-building text-warning"></i> Property Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($propertyImages)): ?>
                            <img src="../<?php echo htmlspecialchars($propertyImages[0]['image_path']); ?>"
                                 class="img-fluid rounded mb-3"
                                 alt="Property"
                                 style="max-height: 200px; width: 100%; object-fit: cover;">
                        <?php endif; ?>

                        <h6><?php echo htmlspecialchars($property['title']); ?></h6>
                        <p class="text-muted mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($property['location']); ?>
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="info-label mb-1">Property ID</p>
                                <p class="info-value">#<?php echo $property['id']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Room Type</p>
                                <p class="info-value">
                                    <?php echo htmlspecialchars(ROOM_TYPES[$property['room_type']] ?? $property['room_type']); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Capacity</p>
                                <p class="info-value"><?php echo $property['capacity']; ?> person(s)</p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Distance from Campus</p>
                                <p class="info-value">
                                    <?php echo $property['distance_from_campus'] ? $property['distance_from_campus'] . ' km' : 'N/A'; ?>
                                </p>
                            </div>
                            <div class="col-12 no-print">
                                <a href="property_review.php?id=<?php echo $property['id']; ?>"
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-building"></i> View Property Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lease Terms -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-file-contract"></i> Lease Terms</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <p class="info-label mb-1">Move-in Date</p>
                                <p class="info-value"><?php echo formatDate($booking['move_in_date']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="info-label mb-1">Lease Duration</p>
                                <p class="info-value"><?php echo $booking['lease_duration_months']; ?> months</p>
                            </div>
                            <div class="col-md-4">
                                <p class="info-label mb-1">Move-out Date</p>
                                <p class="info-value">
                                    <?php
                                    $moveOutDate = date('Y-m-d', strtotime($booking['move_in_date'] . ' +' . $booking['lease_duration_months'] . ' months'));
                                    echo formatDate($moveOutDate);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <?php if ($booking['approved_at'] || $booking['rejected_at'] || $booking['cancelled_at']): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Booking Timeline</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline-item">
                                <p class="mb-1"><strong>Booking Requested</strong></p>
                                <p class="text-muted small"><?php echo formatDateTime($booking['created_at']); ?></p>
                            </div>

                            <?php if ($booking['approved_at']): ?>
                                <div class="timeline-item">
                                    <p class="mb-1"><strong>Booking Approved by Landlord</strong></p>
                                    <p class="text-muted small"><?php echo formatDateTime($booking['approved_at']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($booking['rejected_at']): ?>
                                <div class="timeline-item">
                                    <p class="mb-1"><strong>Booking Rejected</strong></p>
                                    <p class="text-muted small"><?php echo formatDateTime($booking['rejected_at']); ?></p>
                                    <?php if ($booking['rejection_reason']): ?>
                                        <p class="small">Reason: <?php echo htmlspecialchars($booking['rejection_reason']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($booking['cancelled_at']): ?>
                                <div class="timeline-item">
                                    <p class="mb-1"><strong>Booking Cancelled</strong></p>
                                    <p class="text-muted small"><?php echo formatDateTime($booking['cancelled_at']); ?></p>
                                    <?php if ($booking['cancellation_reason']): ?>
                                        <p class="small">Reason: <?php echo htmlspecialchars($booking['cancellation_reason']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($booking['completed_at']): ?>
                                <div class="timeline-item">
                                    <p class="mb-1"><strong>Booking Completed</strong></p>
                                    <p class="text-muted small"><?php echo formatDateTime($booking['completed_at']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($booking['payment_completed_at']): ?>
                                <div class="timeline-item">
                                    <p class="mb-1"><strong>Payment Completed</strong></p>
                                    <p class="text-muted small"><?php echo formatDateTime($booking['payment_completed_at']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Payment Breakdown -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-wallet"></i> Payment Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td>Monthly Rent:</td>
                                <td class="text-end"><strong><?php echo formatCurrency($booking['monthly_rent']); ?></strong></td>
                            </tr>
                            <tr>
                                <td>Duration:</td>
                                <td class="text-end"><?php echo $booking['lease_duration_months']; ?> months</td>
                            </tr>
                            <tr>
                                <td>Rent Subtotal:</td>
                                <td class="text-end"><?php echo formatCurrency($booking['monthly_rent'] * $booking['lease_duration_months']); ?></td>
                            </tr>
                            <tr>
                                <td>Security Deposit:</td>
                                <td class="text-end"><?php echo formatCurrency($booking['security_deposit']); ?></td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Total Amount:</strong></td>
                                <td class="text-end"><strong class="text-success"><?php echo formatCurrency($booking['total_amount']); ?></strong></td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-warning">Platform Commission (10%):</td>
                                <td class="text-end text-warning"><strong><?php echo formatCurrency($booking['commission_amount']); ?></strong></td>
                            </tr>
                            <tr>
                                <td>Landlord Payout:</td>
                                <td class="text-end"><strong><?php echo formatCurrency($booking['landlord_payout']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Status</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($booking['payment_status'] === 'paid'): ?>
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h6 class="text-success">Payment Completed</h6>
                            <p class="small text-muted">
                                Paid on <?php echo formatDateTime($booking['payment_completed_at']); ?>
                            </p>
                        <?php elseif ($booking['status'] === 'approved'): ?>
                            <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                            <h6 class="text-warning">Awaiting Payment</h6>
                            <p class="small text-muted">Student needs to complete payment</p>
                        <?php else: ?>
                            <i class="fas fa-times-circle fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Not Paid</h6>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Admin Actions -->
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-tools"></i> Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="contactStudent()">
                                <i class="fas fa-user-graduate"></i> Contact Student
                            </button>
                            <button class="btn btn-outline-success" onclick="contactLandlord()">
                                <i class="fas fa-user-tie"></i> Contact Landlord
                            </button>
                            <button class="btn btn-outline-info" onclick="mediateDispute()">
                                <i class="fas fa-handshake"></i> Mediate Dispute
                            </button>
                            <hr>
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Details
                            </button>
                            <button class="btn btn-outline-secondary" onclick="exportBooking()">
                                <i class="fas fa-download"></i> Export Data
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Commission Rate</small>
                            <div class="h5 mb-0">10%</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Platform Earnings</small>
                            <div class="h5 mb-0 text-warning">
                                <?php echo formatCurrency($booking['commission_amount']); ?>
                            </div>
                        </div>
                        <div>
                            <small class="text-muted">Days Since Booking</small>
                            <div class="h5 mb-0">
                                <?php
                                $daysOld = floor((time() - strtotime($booking['created_at'])) / 86400);
                                echo $daysOld . ' day' . ($daysOld !== 1 ? 's' : '');
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        function contactStudent() {
            const email = '<?php echo addslashes($booking['student_email']); ?>';
            const phone = '<?php echo addslashes($booking['student_phone']); ?>';
            const name = '<?php echo addslashes($booking['student_name']); ?>';

            const action = confirm(`Contact ${name}?\n\nOK = Email\nCancel = Call`);

            if (action) {
                window.location.href = `mailto:${email}?subject=Regarding Booking #<?php echo $booking['booking_reference']; ?>`;
            } else {
                window.location.href = `tel:${phone}`;
            }
        }

        function contactLandlord() {
            const email = '<?php echo addslashes($booking['landlord_email']); ?>';
            const phone = '<?php echo addslashes($booking['landlord_phone']); ?>';
            const name = '<?php echo addslashes($booking['landlord_name']); ?>';

            const action = confirm(`Contact ${name}?\n\nOK = Email\nCancel = Call`);

            if (action) {
                window.location.href = `mailto:${email}?subject=Regarding Booking #<?php echo $booking['booking_reference']; ?>`;
            } else {
                window.location.href = `tel:${phone}`;
            }
        }

        function mediateDispute() {
            const studentEmail = '<?php echo addslashes($booking['student_email']); ?>';
            const landlordEmail = '<?php echo addslashes($booking['landlord_email']); ?>';

            const subject = encodeURIComponent('Booking Dispute Resolution - #<?php echo $booking['booking_reference']; ?>');
            const body = encodeURIComponent(`Dear Student and Landlord,\n\nThis email concerns booking #<?php echo $booking['booking_reference']; ?>.\n\nPlease provide details about the dispute so we can help resolve it.\n\nBest regards,\nCampusDigs Admin Team`);

            window.location.href = `mailto:${studentEmail},${landlordEmail}?subject=${subject}&body=${body}`;
        }

        function exportBooking() {
            window.location.href = 'export_booking.php?id=<?php echo $bookingId; ?>';
        }
    </script>
</body>
</html>
