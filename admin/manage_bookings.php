<?php
/**
 * CampusDigs Kenya - Admin Manage Bookings
 * Monitor and manage all platform bookings
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/booking_controller.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get filters
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$dateRange = isset($_GET['date_range']) ? sanitizeInput($_GET['date_range']) : 'all';
$studentSearch = isset($_GET['student']) ? sanitizeInput($_GET['student']) : '';
$landlordSearch = isset($_GET['landlord']) ? sanitizeInput($_GET['landlord']) : '';
$customStartDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$customEndDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build filters array
$filters = [
    'status' => $statusFilter !== 'all' ? $statusFilter : null,
    'date_range' => $dateRange,
    'start_date' => $customStartDate,
    'end_date' => $customEndDate,
    'student_search' => $studentSearch ?: null,
    'landlord_search' => $landlordSearch ?: null
];

// Get bookings
$result = getAllBookingsFiltered($page, $perPage, $filters);
$bookings = $result['bookings'];
$totalBookings = $result['total'];
$totalPages = $result['pages'];

// Get counts and statistics
$counts = getBookingStatusCounts();
$statistics = getBookingStatistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../dashboard_admin.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_properties.php">
                                <i class="fas fa-building"></i> Properties
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_bookings.php">
                                <i class="fas fa-calendar-check"></i> Bookings
                                <?php if ($counts['pending'] > 0): ?>
                                    <span class="badge bg-warning rounded-pill"><?php echo $counts['pending']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-line"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="../login/logout.php">
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
                        <i class="fas fa-calendar-check text-info"></i> Manage Bookings
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportBookings()">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Bookings
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $counts['total']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Revenue
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo formatCurrency($statistics['total_revenue']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Commission Earned
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo formatCurrency($statistics['commission_earned']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Pending Bookings
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $counts['pending']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <!-- Status Filter -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>
                                        All Status (<?php echo $counts['total']; ?>)
                                    </option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>
                                        Pending (<?php echo $counts['pending']; ?>)
                                    </option>
                                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>
                                        Approved (<?php echo $counts['approved']; ?>)
                                    </option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>
                                        Rejected (<?php echo $counts['rejected']; ?>)
                                    </option>
                                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>
                                        Cancelled (<?php echo $counts['cancelled']; ?>)
                                    </option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>
                                        Completed (<?php echo $counts['completed']; ?>)
                                    </option>
                                </select>
                            </div>

                            <!-- Date Range Filter -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Date Range</label>
                                <select name="date_range" class="form-select" id="dateRangeSelect" onchange="toggleCustomDates()">
                                    <option value="all" <?php echo $dateRange === 'all' ? 'selected' : ''; ?>>All Time</option>
                                    <option value="7" <?php echo $dateRange === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="30" <?php echo $dateRange === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="90" <?php echo $dateRange === '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                                    <option value="custom" <?php echo $dateRange === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                </select>
                            </div>

                            <!-- Custom Date Range (hidden by default) -->
                            <div class="col-md-3" id="customDatesContainer" style="display: <?php echo $dateRange === 'custom' ? 'block' : 'none'; ?>;">
                                <label class="form-label fw-bold">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($customStartDate); ?>">
                            </div>

                            <div class="col-md-3" id="customDatesContainer2" style="display: <?php echo $dateRange === 'custom' ? 'block' : 'none'; ?>;">
                                <label class="form-label fw-bold">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($customEndDate); ?>">
                            </div>

                            <!-- Student Search -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Student</label>
                                <input type="text" name="student" class="form-control"
                                       placeholder="Search by student name..."
                                       value="<?php echo htmlspecialchars($studentSearch); ?>">
                            </div>

                            <!-- Landlord Search -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Landlord</label>
                                <input type="text" name="landlord" class="form-control"
                                       placeholder="Search by landlord name..."
                                       value="<?php echo htmlspecialchars($landlordSearch); ?>">
                            </div>

                            <!-- Buttons -->
                            <div class="col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <?php if ($statusFilter !== 'all' || $dateRange !== 'all' || $studentSearch || $landlordSearch): ?>
                                        <a href="manage_bookings.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-redo"></i> Clear Filters
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bookings List -->
                <?php if (empty($bookings)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-5x text-muted mb-4"></i>
                        <h3 class="text-muted">No bookings found</h3>
                        <p class="text-muted">Try adjusting your filters or wait for new bookings.</p>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ref #</th>
                                        <th>Student</th>
                                        <th>Landlord</th>
                                        <th>Property</th>
                                        <th>Amount</th>
                                        <th>Commission</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-primary">#<?php echo $booking['booking_reference']; ?></strong>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($booking['student_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['student_email']); ?></small>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($booking['landlord_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['landlord_email']); ?></small>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($booking['property_title']); ?>">
                                                    <?php echo htmlspecialchars($booking['property_title']); ?>
                                                </div>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['property_location']); ?></small>
                                            </td>
                                            <td>
                                                <strong class="text-success"><?php echo formatCurrency($booking['total_amount']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="text-warning"><?php echo formatCurrency($booking['commission_amount']); ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'pending' => 'bg-warning text-dark',
                                                    'approved' => 'bg-success',
                                                    'rejected' => 'bg-danger',
                                                    'cancelled' => 'bg-secondary',
                                                    'completed' => 'bg-info'
                                                ][$booking['status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo timeAgo($booking['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <a href="booking_details.php?id=<?php echo $booking['id']; ?>"
                                                   class="btn btn-sm btn-primary"
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Bookings pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo $statusFilter; ?>&date_range=<?php echo $dateRange; ?>&student=<?php echo urlencode($studentSearch); ?>&landlord=<?php echo urlencode($landlordSearch); ?>&page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?status=<?php echo $statusFilter; ?>&date_range=<?php echo $dateRange; ?>&student=<?php echo urlencode($studentSearch); ?>&landlord=<?php echo urlencode($landlordSearch); ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo $statusFilter; ?>&date_range=<?php echo $dateRange; ?>&student=<?php echo urlencode($studentSearch); ?>&landlord=<?php echo urlencode($landlordSearch); ?>&page=<?php echo $page + 1; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        // Toggle custom date range inputs
        function toggleCustomDates() {
            const dateRange = document.getElementById('dateRangeSelect').value;
            const customDates1 = document.getElementById('customDatesContainer');
            const customDates2 = document.getElementById('customDatesContainer2');

            if (dateRange === 'custom') {
                customDates1.style.display = 'block';
                customDates2.style.display = 'block';
            } else {
                customDates1.style.display = 'none';
                customDates2.style.display = 'none';
                // Submit form when changing from custom to preset range
                document.querySelector('form').submit();
            }
        }

        // Export bookings to CSV
        function exportBookings() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = 'export_bookings.php?' + params.toString();
        }
    </script>
</body>
</html>
