<?php
/**
 * CampusDigs Kenya - Booking Details Page
 * View detailed booking information
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/booking_controller.php';

requireStudent();

$studentId = $_SESSION['user_id'];
$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$bookingId) {
    redirectWithMessage('student_bookings.php', 'Booking not found', 'error');
}

$booking = getStudentBookingById($bookingId, $studentId);

if (!$booking) {
    redirectWithMessage('student_bookings.php', 'Booking not found or access denied', 'error');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking #<?php echo $booking['booking_reference']; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-4">
        <a href="student_bookings.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Back to Bookings
        </a>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Booking Status Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Booking Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Booking Reference</h6>
                                <p class="text-primary fw-bold">#<?php echo $booking['booking_reference']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Status</h6>
                                <p>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'cancelled' => 'secondary',
                                        'completed' => 'info'
                                    ];
                                    $class = $statusClass[$booking['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?>"><?php echo ucfirst($booking['status']); ?></span>
                                </p>
                            </div>
                        </div>

                        <hr>

                        <!-- Property Information -->
                        <h6 class="mb-3">Property Information</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <strong><?php echo htmlspecialchars($booking['property_title']); ?></strong>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['property_location']); ?>
                                </p>
                            </div>
                        </div>

                        <hr>

                        <!-- Lease Information -->
                        <h6 class="mb-3">Lease Information</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <small class="text-muted">Move-in Date</small>
                                <p class="mb-0"><i class="fas fa-calendar"></i> <?php echo formatDate($booking['move_in_date']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Lease Duration</small>
                                <p class="mb-0"><i class="fas fa-clock"></i> <?php echo $booking['lease_duration_months']; ?> Months</p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Monthly Rent</small>
                                <p class="mb-0 fw-bold"><?php echo formatCurrency($booking['monthly_rent']); ?></p>
                            </div>
                        </div>

                        <?php if ($booking['message']): ?>
                            <hr>
                            <h6 class="mb-2">Your Message</h6>
                            <p class="text-muted small"><?php echo nl2br(htmlspecialchars($booking['message'])); ?></p>
                        <?php endif; ?>

                        <?php if ($booking['rejection_reason']): ?>
                            <hr>
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-circle"></i> Rejection Reason</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['rejection_reason'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Landlord Contact Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-user-tie"></i> Landlord Contact</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong><?php echo htmlspecialchars($booking['landlord_name']); ?></strong></p>
                        <p class="mb-1 small"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['landlord_email']); ?></p>
                        <p class="mb-0 small"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['landlord_phone']); ?></p>
                        
                        <div class="mt-3 d-grid gap-2">
                            <a href="tel:<?php echo $booking['landlord_phone']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-phone"></i> Call Landlord
                            </a>
                            <a href="https://wa.me/<?php echo str_replace('+', '', $booking['landlord_phone']); ?>" 
                               target="_blank" class="btn btn-success btn-sm">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Payment Summary -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-wallet"></i> Payment Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Monthly Rent</span>
                            <span><?php echo formatCurrency($booking['monthly_rent']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Duration</span>
                            <span><?php echo $booking['lease_duration_months']; ?> months</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Rent Total</span>
                            <span><?php echo formatCurrency($booking['monthly_rent'] * $booking['lease_duration_months']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Security Deposit</span>
                            <span><?php echo formatCurrency($booking['security_deposit']); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total Amount</strong>
                            <strong class="text-success"><?php echo formatCurrency($booking['total_amount']); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-cog"></i> Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Details
                            </button>
                            
                            <?php if ($booking['status'] === 'approved'): ?>
                                <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-credit-card"></i> Make Payment
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($booking['status'] === 'pending'): ?>
                                <button class="btn btn-outline-danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-times"></i> Cancel Booking
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history"></i> Timeline</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <i class="fas fa-check-circle text-success"></i>
                                <div>
                                    <strong>Booking Created</strong>
                                    <small class="text-muted d-block"><?php echo formatDateTime($booking['created_at']); ?></small>
                                </div>
                            </div>
                            
                            <?php if ($booking['approved_at']): ?>
                                <div class="timeline-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <div>
                                        <strong>Approved</strong>
                                        <small class="text-muted d-block"><?php echo formatDateTime($booking['approved_at']); ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($booking['rejected_at']): ?>
                                <div class="timeline-item">
                                    <i class="fas fa-times-circle text-danger"></i>
                                    <div>
                                        <strong>Rejected</strong>
                                        <small class="text-muted d-block"><?php echo formatDateTime($booking['rejected_at']); ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($booking['cancelled_at']): ?>
                                <div class="timeline-item">
                                    <i class="fas fa-ban text-secondary"></i>
                                    <div>
                                        <strong>Cancelled</strong>
                                        <small class="text-muted d-block"><?php echo formatDateTime($booking['cancelled_at']); ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        function cancelBooking(bookingId) {
            if (!confirm('Are you sure you want to cancel this booking? Refund policy will apply based on cancellation time.')) return;
            
            const reason = prompt('Please provide a reason for cancellation:');
            if (!reason) return;
            
            window.location.href = `../actions/cancel_booking_action.php?id=${bookingId}&reason=${encodeURIComponent(reason)}`;
        }
    </script>
    
    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 25px;
            width: 2px;
            height: calc(100% + 5px);
            background: #dee2e6;
        }
        .timeline-item i {
            flex-shrink: 0;
        }
    </style>
</body>
</html>