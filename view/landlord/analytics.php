<?php
/**
 * CampusDigs Kenya - Landlord Analytics
 * Performance analytics and statistics
 */

require_once '../../includes/config.php';
require_once '../../includes/core.php';
require_once '../../controllers/property_controller.php';
require_once '../../controllers/booking_controller.php';

requireLandlord();

$landlordId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get all landlord properties
$propertyResult = getLandlordProperties($landlordId, 1, 1000);
$properties = $propertyResult['properties'];
$totalProperties = $propertyResult['total'];

// Get all bookings
$bookingResult = getLandlordBookings($landlordId, 1, 1000);
$bookings = $bookingResult['bookings'];

// Calculate statistics
$stats = [
    'total_properties' => $totalProperties,
    'active_properties' => 0,
    'total_views' => 0,
    'total_wishlist' => 0,
    'total_bookings' => count($bookings),
    'pending_bookings' => 0,
    'approved_bookings' => 0,
    'completed_bookings' => 0,
    'total_revenue' => 0,
    'pending_revenue' => 0,
];

// Property statistics
foreach ($properties as $property) {
    if ($property['status'] === 'active') {
        $stats['active_properties']++;
    }
    $stats['total_views'] += $property['view_count'];
    $stats['total_wishlist'] += $property['wishlist_count'];
}

// Booking statistics
foreach ($bookings as $booking) {
    if ($booking['status'] === 'pending') {
        $stats['pending_bookings']++;
        $stats['pending_revenue'] += $booking['landlord_payout'];
    } elseif ($booking['status'] === 'approved') {
        $stats['approved_bookings']++;
        $stats['pending_revenue'] += $booking['landlord_payout'];
    } elseif ($booking['status'] === 'completed') {
        $stats['completed_bookings']++;
        $stats['total_revenue'] += $booking['landlord_payout'];
    }
}

// Calculate conversion rate
$conversionRate = $stats['total_views'] > 0 ? ($stats['total_bookings'] / $stats['total_views']) * 100 : 0;

// Get top performing properties
$topProperties = array_slice($properties, 0, 5);
usort($topProperties, function($a, $b) {
    return $b['view_count'] - $a['view_count'];
});

// Monthly data for chart (last 6 months)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M Y', strtotime("-$i months"));

    $monthBookings = array_filter($bookings, function($booking) use ($month) {
        return strpos($booking['created_at'], $month) === 0;
    });

    $monthRevenue = 0;
    foreach ($monthBookings as $booking) {
        if (in_array($booking['status'], ['approved', 'completed'])) {
            $monthRevenue += $booking['landlord_payout'];
        }
    }

    $monthlyData[] = [
        'month' => $monthName,
        'bookings' => count($monthBookings),
        'revenue' => $monthRevenue
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .stat-card {
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
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
                            <a class="nav-link" href="manage_bookings.php">
                                <i class="fas fa-clipboard-list"></i> Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="analytics.php">
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
                        <i class="fas fa-chart-line text-warning"></i> Performance Analytics
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Report
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

                <!-- Overview Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Properties
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['total_properties']; ?>
                                        </div>
                                        <small class="text-success"><?php echo $stats['active_properties']; ?> active</small>
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
                                            Total Revenue
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo formatCurrency($stats['total_revenue']); ?>
                                        </div>
                                        <small class="text-muted"><?php echo $stats['completed_bookings']; ?> completed</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-wallet fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Revenue
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo formatCurrency($stats['pending_revenue']); ?>
                                        </div>
                                        <small class="text-muted"><?php echo $stats['pending_bookings'] + $stats['approved_bookings']; ?> pending</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                            Total Views
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['total_views']); ?>
                                        </div>
                                        <small class="text-muted"><?php echo $stats['total_wishlist']; ?> wishlisted</small>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-eye fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="row mb-4">
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-warning">
                                    <i class="fas fa-chart-bar"></i> Monthly Bookings
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="bookingsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-warning">
                                    <i class="fas fa-chart-line"></i> Monthly Revenue
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="row mb-4">
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 bg-light">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-percentage"></i> Conversion Rate
                                </h6>
                            </div>
                            <div class="card-body text-center">
                                <h2 class="text-warning"><?php echo number_format($conversionRate, 2); ?>%</h2>
                                <p class="text-muted">Views to Bookings</p>
                                <small><?php echo $stats['total_bookings']; ?> bookings from <?php echo $stats['total_views']; ?> views</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 bg-light">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-dollar-sign"></i> Average Booking Value
                                </h6>
                            </div>
                            <div class="card-body text-center">
                                <h2 class="text-success">
                                    <?php
                                    $avgBookingValue = $stats['total_bookings'] > 0 ? ($stats['total_revenue'] + $stats['pending_revenue']) / $stats['total_bookings'] : 0;
                                    echo formatCurrency($avgBookingValue);
                                    ?>
                                </h2>
                                <p class="text-muted">Per Booking</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3 bg-light">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-building"></i> Property Performance
                                </h6>
                            </div>
                            <div class="card-body text-center">
                                <h2 class="text-info">
                                    <?php
                                    $avgViewsPerProperty = $stats['total_properties'] > 0 ? $stats['total_views'] / $stats['total_properties'] : 0;
                                    echo number_format($avgViewsPerProperty, 0);
                                    ?>
                                </h2>
                                <p class="text-muted">Avg Views per Property</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Properties -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-warning">
                            <i class="fas fa-trophy"></i> Top Performing Properties
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topProperties)): ?>
                            <p class="text-muted text-center">No properties to display</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Location</th>
                                            <th>Views</th>
                                            <th>Wishlists</th>
                                            <th>Bookings</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topProperties as $property): ?>
                                            <tr>
                                                <td>
                                                    <a href="edit_property.php?id=<?php echo $property['id']; ?>">
                                                        <?php echo htmlspecialchars($property['title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($property['location']); ?></td>
                                                <td><i class="fas fa-eye text-info"></i> <?php echo $property['view_count']; ?></td>
                                                <td><i class="fas fa-heart text-danger"></i> <?php echo $property['wishlist_count']; ?></td>
                                                <td><i class="fas fa-calendar text-success"></i> <?php echo $property['booking_count']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $property['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($property['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/dashboard.js"></script>
    <script>
        // Bookings Chart
        const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
        const bookingsChart = new Chart(bookingsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($monthlyData, 'month')); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode(array_column($monthlyData, 'bookings')); ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.6)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyData, 'month')); ?>,
                datasets: [{
                    label: 'Revenue (KSh)',
                    data: <?php echo json_encode(array_column($monthlyData, 'revenue')); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KSh ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
