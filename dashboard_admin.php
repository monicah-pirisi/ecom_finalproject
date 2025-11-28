<?php
/**
 * CampusDigs Kenya - Admin Dashboard
 * Main dashboard for system administrators
 */

require_once 'includes/config.php';
require_once 'includes/core.php';
require_once 'controllers/user_controller.php';
require_once 'controllers/property_controller.php';
require_once 'controllers/booking_controller.php';
require_once 'controllers/review_controller.php';

// Require admin authentication
requireAdmin();

$adminName = $_SESSION['full_name'];
$flash = getFlashMessage();

// Get system statistics
$userStats = getUserStatistics();
$propertyStats = getAllPropertyStatistics();
$bookingStats = getBookingStatistics();
$reviewStats = getReviewStatusCounts();

// Get pending items
$pendingProperties = getPendingProperties(1, 10);
$pendingPropertiesCount = $pendingProperties['total'];
$pendingReviewsCount = ($reviewStats['pending'] ?? 0) + ($reviewStats['flagged'] ?? 0);

// Recent activity
$recentUsers = getAllUsers(1, 5);
$recentBookings = getAllBookings(1, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard_admin.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/manage_users.php">
                                <i class="fas fa-users"></i> Users
                                <span class="badge bg-primary rounded-pill"><?php echo $userStats['total_users']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/manage_properties.php">
                                <i class="fas fa-building"></i> Properties
                                <?php if ($pendingPropertiesCount > 0): ?>
                                    <span class="badge bg-warning rounded-pill"><?php echo $pendingPropertiesCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/manage_bookings.php">
                                <i class="fas fa-calendar-check"></i> Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/reports.php">
                                <i class="fas fa-chart-line"></i> Reports & Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/moderate_reviews.php">
                                <i class="fas fa-star"></i> Reviews
                                <?php if ($pendingReviewsCount > 0): ?>
                                    <span class="badge bg-warning rounded-pill"><?php echo $pendingReviewsCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-muted" href="view/settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="login/logout.php">
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
                        <i class="fas fa-shield-alt text-danger"></i> 
                        Admin Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="text-muted">Welcome, <?php echo htmlspecialchars($adminName); ?></span>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Overview -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $userStats['total_users']; ?></div>
                                        <small class="text-muted">
                                            Students: <?php echo $userStats['total_students']; ?> | 
                                            Landlords: <?php echo $userStats['total_landlords']; ?>
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Properties</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $propertyStats['total_properties']; ?></div>
                                        <small class="text-muted">
                                            Active: <?php echo $propertyStats['active_properties']; ?> | 
                                            Pending: <?php echo $propertyStats['pending_properties']; ?>
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-building fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Bookings</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $bookingStats['total_bookings']; ?></div>
                                        <small class="text-muted">
                                            Approved: <?php echo $bookingStats['approved_bookings']; ?> | 
                                            Pending: <?php echo $bookingStats['pending_bookings']; ?>
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Revenue</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($bookingStats['total_revenue']); ?></div>
                                        <small class="text-muted">
                                            Commission: <?php echo formatCurrency($bookingStats['commission_earned']); ?>
                                        </small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Properties Alert -->
                <?php if ($pendingPropertiesCount > 0): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <strong><?php echo $pendingPropertiesCount; ?> propert<?php echo $pendingPropertiesCount === 1 ? 'y' : 'ies'; ?> awaiting approval</strong>
                            <p class="mb-0 small">Review and approve property listings to make them visible to students.</p>
                        </div>
                        <a href="admin/manage_properties.php?status=pending" class="btn btn-warning">
                            Review Now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-bolt"></i> Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/manage_users.php" class="quick-action-btn">
                                            <i class="fas fa-users fa-3x text-primary mb-2"></i>
                                            <p class="mb-0">Manage Users</p>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/manage_properties.php" class="quick-action-btn">
                                            <i class="fas fa-building fa-3x text-success mb-2"></i>
                                            <p class="mb-0">Manage Properties</p>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/manage_bookings.php" class="quick-action-btn">
                                            <i class="fas fa-calendar-check fa-3x text-info mb-2"></i>
                                            <p class="mb-0">View Bookings</p>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/reports.php" class="quick-action-btn">
                                            <i class="fas fa-chart-bar fa-3x text-warning mb-2"></i>
                                            <p class="mb-0">View Reports</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-users"></i> Recent Users
                                </h6>
                                <a href="admin/manage_users.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentUsers['users'] as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                    <td><span class="badge bg-info"><?php echo ucfirst($user['user_type']); ?></span></td>
                                                    <td>
                                                        <?php if ($user['account_verified']): ?>
                                                            <span class="badge bg-success">Verified</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="admin/user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Bookings -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-calendar-check"></i> Recent Bookings
                                </h6>
                                <a href="admin/manage_bookings.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Ref</th>
                                                <th>Student</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentBookings['bookings'] as $booking): ?>
                                                <tr>
                                                    <td>#<?php echo $booking['booking_reference']; ?></td>
                                                    <td><?php echo htmlspecialchars($booking['student_name']); ?></td>
                                                    <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $booking['status'] === 'approved' ? 'success' : 
                                                                ($booking['status'] === 'pending' ? 'warning' : 'secondary'); 
                                                        ?>">
                                                            <?php echo ucfirst($booking['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>