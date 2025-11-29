<?php
/**
 * CampusDigs Kenya - Manage Bookings Page
 * View and manage all property bookings
 */

require_once '../../includes/config.php';
require_once '../../includes/core.php';
require_once '../../controllers/booking_controller.php';

requireLandlord();

$landlordId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get pagination and filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;

// Get landlord bookings
$result = getLandlordBookings($landlordId, $page, $perPage, $statusFilter);
$bookings = $result['bookings'];
$totalBookings = $result['total'];
$totalPages = $result['pages'];

// Get pending count
$pendingBookings = getLandlordPendingBookings($landlordId);
$pendingCount = count($pendingBookings);

// Count by status
$statusCounts = [
    'all' => $totalBookings,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'completed' => 0,
    'cancelled' => 0
];

// Get counts for all statuses
foreach (['pending', 'approved', 'rejected', 'completed', 'cancelled'] as $status) {
    $statusResult = getLandlordBookings($landlordId, 1, 1, $status);
    $statusCounts[$status] = $statusResult['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">

    <style>
        .status-filter {
            border-bottom: 2px solid transparent;
            padding: 10px 15px;
            cursor: pointer;
            text-decoration: none;
        }
        .status-filter.active {
            border-bottom-color: #ffc107;
            font-weight: bold;
        }
        .booking-row {
            transition: background-color 0.2s ease;
        }
        .booking-row:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../../dashboard_landlord.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_properties.php">
                                <i class="fas fa-building"></i> My Properties
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_property.php">
                                <i class="fas fa-plus-circle"></i> Add Property
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_bookings.php">
                                <i class="fas fa-clipboard-list"></i> Bookings
                                <?php if ($pendingCount > 0): ?>
                                    <span class="badge bg-warning rounded-pill"><?php echo $pendingCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">
                                <i class="fas fa-chart-line"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../landlord_profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="../../login/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-clipboard-list text-warning"></i> Manage Bookings
                    </h1>
                </div>

                <!-- Flash Message -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Pending Alerts -->
                <?php if ($pendingCount > 0): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>You have <?php echo $pendingCount; ?> pending booking request<?php echo $pendingCount > 1 ? 's' : ''; ?></strong>
                        Review and respond to booking requests as soon as possible.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Status Filter -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-start gap-3 flex-wrap">
                            <a href="?" class="status-filter <?php echo !$statusFilter ? 'active' : ''; ?>">
                                All (<?php echo $statusCounts['all']; ?>)
                            </a>
                            <a href="?status=pending" class="status-filter <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                                <span class="text-warning"><i class="fas fa-clock"></i> Pending</span> (<?php echo $statusCounts['pending']; ?>)
                            </a>
                            <a href="?status=approved" class="status-filter <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
                                <span class="text-success"><i class="fas fa-check-circle"></i> Approved</span> (<?php echo $statusCounts['approved']; ?>)
                            </a>
                            <a href="?status=completed" class="status-filter <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
                                <span class="text-info"><i class="fas fa-flag-checkered"></i> Completed</span> (<?php echo $statusCounts['completed']; ?>)
                            </a>
                            <a href="?status=rejected" class="status-filter <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
                                <span class="text-danger"><i class="fas fa-times-circle"></i> Rejected</span> (<?php echo $statusCounts['rejected']; ?>)
                            </a>
                            <a href="?status=cancelled" class="status-filter <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
                                <span class="text-secondary"><i class="fas fa-ban"></i> Cancelled</span> (<?php echo $statusCounts['cancelled']; ?>)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-warning">
                            <i class="fas fa-list"></i> Booking Requests
                            <?php if ($statusFilter): ?>
                                - <?php echo ucfirst($statusFilter); ?>
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clipboard fa-5x text-muted mb-4"></i>
                                <h4 class="text-muted">No bookings found</h4>
                                <p class="text-muted">
                                    <?php if ($statusFilter): ?>
                                        No bookings with status: <?php echo ucfirst($statusFilter); ?>
                                    <?php else: ?>
                                        You haven't received any booking requests yet
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Student</th>
                                            <th>Property</th>
                                            <th>Move-in Date</th>
                                            <th>Duration</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date Requested</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr class="booking-row">
                                                <td><strong>#<?php echo $booking['id']; ?></strong></td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($booking['student_name']); ?></strong><br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['student_phone']); ?><br>
                                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['student_email']); ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="../single_property.php?id=<?php echo $booking['property_id']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars($booking['property_title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo formatDate($booking['move_in_date']); ?></td>
                                                <td><?php echo $booking['lease_duration_months']; ?> month<?php echo $booking['lease_duration_months'] > 1 ? 's' : ''; ?></td>
                                                <td>
                                                    <strong><?php echo formatCurrency($booking['total_amount']); ?></strong><br>
                                                    <small class="text-muted">Payout: <?php echo formatCurrency($booking['landlord_payout']); ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badgeClass = [
                                                        'pending' => 'bg-warning',
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        'completed' => 'bg-info',
                                                        'cancelled' => 'bg-secondary'
                                                    ][$booking['status']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($booking['created_at']); ?></td>
                                                <td>
                                                    <a href="booking_details.php?id=<?php echo $booking['id']; ?>"
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <a href="#" class="btn btn-sm btn-success"
                                                           onclick="approveBooking(<?php echo $booking['id']; ?>); return false;">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-danger"
                                                           onclick="rejectBooking(<?php echo $booking['id']; ?>); return false;">
                                                            <i class="fas fa-times"></i> Reject
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Bookings pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $statusFilter ? '&status='.$statusFilter : ''; ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $statusFilter ? '&status='.$statusFilter : ''; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $statusFilter ? '&status='.$statusFilter : ''; ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/dashboard.js"></script>
    <script src="../../js/bookings.js"></script>
    <script>
        function approveBooking(bookingId) {
            if (!confirm('Are you sure you want to approve this booking request?')) {
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
    </script>
</body>
</html>
