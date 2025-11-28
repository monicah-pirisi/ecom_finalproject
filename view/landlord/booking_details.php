<?php
/**
 * CampusDigs Kenya - Booking Details Page
 * View detailed information about a single booking
 */

require_once '../../includes/config.php';
require_once '../../includes/core.php';
require_once '../../controllers/booking_controller.php';
require_once '../../controllers/property_controller.php';
require_once '../../classes/booking_class.php';

requireLandlord();

$landlordId = $_SESSION['user_id'];
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

if (!$booking || $booking['landlord_id'] != $landlordId) {
    $_SESSION['error'] = 'Booking not found or access denied';
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
    <title>Booking Details #<?php echo $bookingId; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">

    <style>
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .info-value {
            font-size: 1.1rem;
        }
        .timeline-item {
            border-left: 2px solid #dee2e6;
            padding-left: 20px;
            padding-bottom: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            width: 12px;
            height: 12px;
            background: #ffc107;
            border-radius: 50%;
            position: absolute;
            left: -7px;
            top: 0;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container py-4">
        <a href="manage_bookings.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Back to Bookings
        </a>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Booking Header -->
        <div class="card shadow mb-4">
            <div class="card-header bg-warning text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-clipboard-list"></i> Booking #<?php echo $bookingId; ?></h4>
                    <?php
                    $badgeClass = [
                        'pending' => 'bg-dark',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'completed' => 'bg-info',
                        'cancelled' => 'bg-secondary'
                    ][$booking['status']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $badgeClass; ?> fs-6">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="info-label mb-1">Booking Date</p>
                        <p class="info-value"><?php echo formatDate($booking['created_at']); ?></p>
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

                <!-- Action Buttons -->
                <?php if ($booking['status'] === 'pending'): ?>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-success" onclick="approveBooking(<?php echo $bookingId; ?>)">
                            <i class="fas fa-check"></i> Approve Booking
                        </button>
                        <button class="btn btn-danger" onclick="rejectBooking(<?php echo $bookingId; ?>)">
                            <i class="fas fa-times"></i> Reject Booking
                        </button>
                    </div>
                <?php elseif ($booking['status'] === 'approved'): ?>
                    <?php if ($booking['payment_status'] === 'paid'): ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="fas fa-check-circle"></i>
                            Payment received. Once the lease period ends, you can mark this booking as completed.
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-info" onclick="completeBooking(<?php echo $bookingId; ?>)">
                                <i class="fas fa-flag-checkered"></i> Mark as Completed
                            </button>
                            <p class="small text-muted mt-2 mb-0">
                                <i class="fas fa-info-circle"></i> Mark as completed after the student has moved out. This allows the student to leave a review.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle"></i>
                            This booking has been approved. The student should complete payment to confirm the reservation.
                        </div>
                    <?php endif; ?>
                <?php elseif ($booking['status'] === 'completed'): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="fas fa-check-double"></i>
                        This booking has been completed. The student can now leave a review.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Student Information -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user-graduate"></i> Student Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="info-label mb-1">Full Name</p>
                                <p class="info-value"><?php echo htmlspecialchars($booking['student_name']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="info-label mb-1">Email Address</p>
                                <p class="info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($booking['student_email']); ?>">
                                        <?php echo htmlspecialchars($booking['student_email']); ?>
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
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
                        </div>
                    </div>
                </div>

                <!-- Property Information -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-building"></i> Property Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($propertyImages)): ?>
                            <img src="../../<?php echo htmlspecialchars($propertyImages[0]['image_path']); ?>"
                                 class="img-fluid rounded mb-3" alt="Property image"
                                 style="max-height: 200px; width: 100%; object-fit: cover;">
                        <?php endif; ?>

                        <h6><?php echo htmlspecialchars($property['title']); ?></h6>
                        <p class="text-muted mb-3">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                        </p>

                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <p class="info-label mb-1">Room Type</p>
                                <p><?php echo htmlspecialchars(ROOM_TYPES[$property['room_type']] ?? $property['room_type']); ?></p>
                            </div>
                            <div class="col-md-6 mb-2">
                                <p class="info-label mb-1">Capacity</p>
                                <p><?php echo $property['capacity']; ?> person(s)</p>
                            </div>
                        </div>

                        <a href="../single_property.php?id=<?php echo $property['id']; ?>"
                           class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View Full Property Details
                        </a>
                    </div>
                </div>

                <!-- Timeline -->
                <?php if ($booking['approved_at'] || $booking['rejected_at'] || $booking['cancelled_at']): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Booking Timeline</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline-item">
                                <p class="mb-1"><strong>Booking Requested</strong></p>
                                <p class="text-muted small"><?php echo formatDate($booking['created_at']); ?></p>
                            </div>

                            <?php if ($booking['approved_at']): ?>
                                <div class="timeline-item">
                                    <p class="mb-1"><strong>Booking Approved</strong></p>
                                    <p class="text-muted small"><?php echo formatDate($booking['approved_at']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($booking['rejected_at']): ?>
                                <div class="timeline-item">
                                    <p class="mb-1"><strong>Booking Rejected</strong></p>
                                    <p class="text-muted small"><?php echo formatDate($booking['rejected_at']); ?></p>
                                    <?php if ($booking['rejection_reason']): ?>
                                        <p class="small">Reason: <?php echo htmlspecialchars($booking['rejection_reason']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($booking['cancelled_at']): ?>
                                <div class="timeline-item">
                                    <p class="mb-1"><strong>Booking Cancelled</strong></p>
                                    <p class="text-muted small"><?php echo formatDate($booking['cancelled_at']); ?></p>
                                    <?php if ($booking['cancellation_reason']): ?>
                                        <p class="small">Reason: <?php echo htmlspecialchars($booking['cancellation_reason']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($booking['completed_at']): ?>
                                <div class="timeline-item">
                                    <p class="mb-1"><strong>Booking Completed</strong></p>
                                    <p class="text-muted small"><?php echo formatDate($booking['completed_at']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Booking Summary -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign"></i> Booking Summary</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td>Monthly Rent:</td>
                                <td class="text-end"><strong><?php echo formatCurrency($booking['monthly_rent']); ?></strong></td>
                            </tr>
                            <tr>
                                <td>Lease Duration:</td>
                                <td class="text-end"><?php echo $booking['lease_duration_months']; ?> months</td>
                            </tr>
                            <tr>
                                <td>Subtotal:</td>
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
                                <td>Commission (10%):</td>
                                <td class="text-end text-muted">-<?php echo formatCurrency($booking['commission_amount']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Your Payout:</strong></td>
                                <td class="text-end"><strong class="text-warning"><?php echo formatCurrency($booking['landlord_payout']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Status</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($booking['payment_status'] === 'paid'): ?>
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h6 class="text-success">Payment Completed</h6>
                            <p class="small text-muted">
                                <?php
                                if (isset($booking['payment_completed_at']) && $booking['payment_completed_at']) {
                                    echo 'Paid on ' . formatDate($booking['payment_completed_at']);
                                } elseif (isset($booking['updated_at'])) {
                                    echo 'Paid on ' . formatDate($booking['updated_at']);
                                } else {
                                    echo 'Payment received';
                                }
                                ?>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/dashboard.js"></script>
    <script>
        function approveBooking(bookingId) {
            if (!confirm('Are you sure you want to approve this booking request?\n\nThe student will be notified and asked to complete payment.')) {
                return;
            }

            fetch('../../actions/landlord_bookings_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=approve&booking_id=${bookingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to approve booking');
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }

        function rejectBooking(bookingId) {
            const reason = prompt('Please provide a reason for rejection:');
            if (!reason) {
                return;
            }

            fetch('../../actions/landlord_bookings_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reject&booking_id=${bookingId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to reject booking');
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }

        function completeBooking(bookingId) {
            if (!confirm('Mark this booking as completed?\n\nThis indicates the lease period has ended and the student has moved out.\n\nThe student will be able to leave a review after this action.')) {
                return;
            }

            fetch('../../actions/landlord_bookings_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=complete&booking_id=${bookingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Booking marked as completed successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to mark booking as completed');
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
