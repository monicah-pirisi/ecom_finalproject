<?php
/**
 * CampusDigs Kenya - Landlord Dashboard
 * Main dashboard for property managers and landlords
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/core.php';
require_once 'controllers/property_controller.php';
require_once 'controllers/booking_controller.php';

// Require landlord authentication
requireLandlord();

// Get landlord data
$landlordId = $_SESSION['user_id'];
$landlordName = $_SESSION['full_name'];
$emailVerified = $_SESSION['email_verified'];
$accountVerified = $_SESSION['account_verified'];

// Get flash message
$flash = getFlashMessage();

// Get landlord statistics
$propertyResult = getLandlordProperties($landlordId, 1, 100);
$totalProperties = $propertyResult['total'];
$properties = $propertyResult['properties'];

// Count properties by status
$activeProperties = 0;
$pendingProperties = 0;
$totalViews = 0;
$totalBookings = 0;

foreach ($properties as $property) {
    if ($property['status'] === 'active') $activeProperties++;
    if ($property['status'] === 'pending') $pendingProperties++;
    $totalViews += $property['view_count'];
    $totalBookings += $property['booking_count'];
}

// Get recent bookings
$bookingResult = getLandlordBookings($landlordId, 1, 5);
$recentBookings = $bookingResult['bookings'];

// Get pending bookings count
$pendingBookings = getLandlordPendingBookings($landlordId);
$pendingCount = count($pendingBookings);

// Calculate total revenue (from approved bookings)
$totalRevenue = 0;
foreach ($recentBookings as $booking) {
    if ($booking['status'] === 'approved' || $booking['status'] === 'completed') {
        $totalRevenue += $booking['landlord_payout'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Dashboard - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    
    <style>
        .stat-card {
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard-landlord.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/landlord/my_properties.php">
                                <i class="fas fa-building"></i> My Properties
                                <span class="badge bg-primary rounded-pill"><?php echo $totalProperties; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/landlord/add_property.php">
                                <i class="fas fa-plus-circle"></i> Add Property
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/landlord/manage_bookings.php">
                                <i class="fas fa-clipboard-list"></i> Bookings
                                <?php if ($pendingCount > 0): ?>
                                    <span class="badge bg-warning rounded-pill"><?php echo $pendingCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/landlord/analytics.php">
                                <i class="fas fa-chart-line"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/landlord_profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-muted" href="view/help.php">
                                <i class="fas fa-question-circle"></i> Help & Support
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
                        <i class="fas fa-building text-warning"></i> 
                        Welcome back, <?php echo htmlspecialchars(explode(' ', $landlordName)[0]); ?>!
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="view/landlord/add_property.php" class="btn btn-warning">
                            <i class="fas fa-plus-circle"></i> Add New Property
                        </a>
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

                <!-- Verification Alerts -->
                <?php if (!$accountVerified): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-clock"></i>
                        <strong>Account pending verification</strong> 
                        Your account is under review. Properties will be visible after verification.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Total Properties
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $totalProperties; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-building fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Active Listings
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $activeProperties; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Revenue
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo formatCurrency($totalRevenue); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-wallet fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Pending Requests
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $pendingCount; ?>
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

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-warning">
                                    <i class="fas fa-bolt"></i> Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <a href="view/landlord/add_property.php" class="quick-action-btn">
                                            <i class="fas fa-plus-circle fa-3x text-warning mb-2"></i>
                                            <p class="mb-0">Add Property</p>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="view/landlord/my_properties.php" class="quick-action-btn">
                                            <i class="fas fa-building fa-3x text-primary mb-2"></i>
                                            <p class="mb-0">My Properties</p>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="view/landlord/manage_bookings.php" class="quick-action-btn">
                                            <i class="fas fa-clipboard-check fa-3x text-success mb-2"></i>
                                            <p class="mb-0">Manage Bookings</p>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="view/landlord/analytics.php" class="quick-action-btn">
                                            <i class="fas fa-chart-bar fa-3x text-info mb-2"></i>
                                            <p class="mb-0">View Analytics</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Bookings -->
                <?php if (!empty($pendingBookings)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-warning text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-exclamation-circle"></i> Pending Booking Requests (<?php echo $pendingCount; ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Property</th>
                                            <th>Move-in Date</th>
                                            <th>Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($pendingBookings, 0, 5) as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['student_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['student_phone']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                                <td><?php echo formatDate($booking['move_in_date']); ?></td>
                                                <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                                <td>
                                                    <a href="view/landlord/booking_details.php?id=<?php echo $booking['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> Review
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($pendingCount > 5): ?>
                                <div class="text-center">
                                    <a href="view/landlord/manage_bookings.php?status=pending" class="btn btn-warning">
                                        View All <?php echo $pendingCount; ?> Pending Requests
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recent Properties -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-building"></i> Your Properties
                        </h6>
                        <a href="view/landlord/my_properties.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($properties)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-home fa-5x text-muted mb-4"></i>
                                <h4 class="text-muted">No properties yet</h4>
                                <p class="text-muted mb-4">Start by adding your first property listing</p>
                                <a href="view/landlord/add_property.php" class="btn btn-warning btn-lg">
                                    <i class="fas fa-plus-circle"></i> Add Your First Property
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach (array_slice($properties, 0, 3) as $property): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h6>
                                                <p class="card-text small text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                                                </p>
                                                <div class="mb-2">
                                                    <span class="badge bg-<?php echo $property['status'] === 'active' ? 'success' : ($property['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($property['status']); ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-success fw-bold"><?php echo formatCurrency($property['price_monthly']); ?>/mo</span>
                                                    <small class="text-muted"><?php echo $property['view_count']; ?> views</small>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-white">
                                                <a href="view/landlord/edit_property.php?id=<?php echo $property['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary w-100">
                                                    <i class="fas fa-edit"></i> Manage
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>