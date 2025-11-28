<?php
/**
 * CampusDigs Kenya - Admin Reports & Analytics
 * Comprehensive analytics dashboard with charts and export functionality
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/booking_controller.php';
require_once '../controllers/review_controller.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get date range filter
$dateRange = isset($_GET['range']) ? sanitizeInput($_GET['range']) : '30';
$customStartDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$customEndDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';

// Calculate date range
if ($dateRange === 'custom' && $customStartDate && $customEndDate) {
    $startDate = $customStartDate;
    $endDate = $customEndDate;
} else {
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
}

// Get all analytics data
$userAnalytics = getUserAnalytics($startDate, $endDate);
$propertyAnalytics = getPropertyAnalytics($startDate, $endDate);
$bookingAnalytics = getBookingAnalytics($startDate, $endDate);
$revenueAnalytics = getRevenueAnalytics($startDate, $endDate);

// Get overview statistics
$overviewStats = [
    'total_users' => $userAnalytics['total_users'],
    'total_students' => $userAnalytics['total_students'],
    'total_landlords' => $userAnalytics['total_landlords'],
    'total_properties' => $propertyAnalytics['total_properties'],
    'total_bookings' => $bookingAnalytics['total_bookings'],
    'total_revenue' => $revenueAnalytics['total_revenue'],
    'commission_earned' => $revenueAnalytics['commission_earned'],
    'growth_rate' => $userAnalytics['growth_rate']
];

// Get chart data
$registrationTrendData = getUserRegistrationTrend($startDate, $endDate);
$bookingVolumeData = getBookingVolumeTrend($startDate, $endDate);
$revenueOverTimeData = getRevenueOverTime($startDate, $endDate);
$propertyDistributionData = getPropertyDistribution();

// Get recent activity
$recentActivity = getRecentAdminActivity(20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <style>
        .metric-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        .activity-item {
            border-left: 3px solid #dee2e6;
            padding-left: 15px;
            padding-bottom: 15px;
            position: relative;
        }
        .activity-item::before {
            content: '';
            width: 10px;
            height: 10px;
            background: #17a2b8;
            border: 2px solid white;
            border-radius: 50%;
            position: absolute;
            left: -7px;
            top: 5px;
            box-shadow: 0 0 0 2px #17a2b8;
        }
        .activity-item:last-child {
            border-left-color: transparent;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
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
                            <a class="nav-link" href="manage_bookings.php">
                                <i class="fas fa-calendar-check"></i> Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="moderate_reviews.php">
                                <i class="fas fa-star"></i> Reviews
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="reports.php">
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
                        <i class="fas fa-chart-line text-info"></i> Reports & Analytics
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0 no-print">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="exportPDF()">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportExcel()">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportCSV()">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show no-print">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Date Range Filter -->
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Date Range</label>
                                <select name="range" class="form-select" id="dateRangeSelect" onchange="toggleCustomDates()">
                                    <option value="7" <?php echo $dateRange === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="30" <?php echo $dateRange === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                                    <option value="90" <?php echo $dateRange === '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                                    <option value="365" <?php echo $dateRange === '365' ? 'selected' : ''; ?>>Last Year</option>
                                    <option value="custom" <?php echo $dateRange === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                </select>
                            </div>

                            <div class="col-md-3" id="customStartDate" style="display: <?php echo $dateRange === 'custom' ? 'block' : 'none'; ?>;">
                                <label class="form-label fw-bold">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($customStartDate); ?>">
                            </div>

                            <div class="col-md-3" id="customEndDate" style="display: <?php echo $dateRange === 'custom' ? 'block' : 'none'; ?>;">
                                <label class="form-label fw-bold">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($customEndDate); ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold d-none d-md-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sync"></i> Update Report
                                </button>
                            </div>
                        </form>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i>
                                Showing data from <?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Analytics Tabs -->
                <ul class="nav nav-tabs mb-4 no-print" id="analyticsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                            <i class="fas fa-chart-pie"></i> Overview
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">
                            <i class="fas fa-users"></i> Users
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="properties-tab" data-bs-toggle="tab" data-bs-target="#properties" type="button">
                            <i class="fas fa-building"></i> Properties
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button">
                            <i class="fas fa-calendar-check"></i> Bookings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="revenue-tab" data-bs-toggle="tab" data-bs-target="#revenue" type="button">
                            <i class="fas fa-dollar-sign"></i> Revenue
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="analyticsTabContent">
                    <!-- OVERVIEW TAB -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                        <!-- Key Metrics Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="card metric-card shadow h-100 py-2" style="border-left-color: #4e73df;">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($overviewStats['total_users']); ?></div>
                                                <small class="text-muted">
                                                    Students: <?php echo number_format($overviewStats['total_students']); ?> |
                                                    Landlords: <?php echo number_format($overviewStats['total_landlords']); ?>
                                                </small>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-users fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="card metric-card shadow h-100 py-2" style="border-left-color: #1cc88a;">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Properties</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($overviewStats['total_properties']); ?></div>
                                                <small class="text-muted">Listed on platform</small>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-building fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="card metric-card shadow h-100 py-2" style="border-left-color: #36b9cc;">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Bookings</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($overviewStats['total_bookings']); ?></div>
                                                <small class="text-muted">All time bookings</small>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="card metric-card shadow h-100 py-2" style="border-left-color: #f6c23e;">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Revenue</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($overviewStats['total_revenue']); ?></div>
                                                <small class="text-success">
                                                    <i class="fas fa-coins"></i> Commission: <?php echo formatCurrency($overviewStats['commission_earned']); ?>
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

                        <!-- Growth Rate Card -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-chart-line text-success"></i> Platform Growth Rate</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <h3 class="text-success"><?php echo number_format($overviewStats['growth_rate'], 1); ?>%</h3>
                                        <p class="text-muted mb-0">User Growth</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h3 class="text-info"><?php echo number_format($propertyAnalytics['growth_rate'] ?? 0, 1); ?>%</h3>
                                        <p class="text-muted mb-0">Property Growth</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h3 class="text-warning"><?php echo number_format($bookingAnalytics['growth_rate'] ?? 0, 1); ?>%</h3>
                                        <p class="text-muted mb-0">Booking Growth</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h3 class="text-primary"><?php echo number_format($revenueAnalytics['growth_rate'] ?? 0, 1); ?>%</h3>
                                        <p class="text-muted mb-0">Revenue Growth</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Charts Column -->
                            <div class="col-lg-8">
                                <!-- User Registration Trend -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-user-plus text-primary"></i> User Registration Trend</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="registrationTrendChart"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <!-- Booking Volume -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-chart-bar text-info"></i> Booking Volume</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="bookingVolumeChart"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <!-- Revenue Over Time -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-money-bill-wave text-success"></i> Revenue Over Time</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="revenueChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Activity Column -->
                            <div class="col-lg-4">
                                <!-- Property Distribution -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-map-marker-alt text-warning"></i> Property Distribution</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="propertyDistributionChart"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Activity Feed -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-history text-secondary"></i> Recent Activity</h6>
                                    </div>
                                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                        <?php if (!empty($recentActivity)): ?>
                                            <?php foreach ($recentActivity as $activity): ?>
                                                <div class="activity-item">
                                                    <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                                    <p class="mb-0 small">
                                                        <strong><?php echo htmlspecialchars($activity['admin_name']); ?></strong>
                                                        <?php echo htmlspecialchars($activity['details']); ?>
                                                    </p>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted text-center">No recent activity</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- USER ANALYTICS TAB -->
                    <div class="tab-pane fade" id="users" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                        <h4><?php echo number_format($userAnalytics['total_users']); ?></h4>
                                        <p class="text-muted mb-0">Total Registered Users</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                                        <h4><?php echo number_format($userAnalytics['active_users']); ?></h4>
                                        <p class="text-muted mb-0">Active Users (30 days)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-plus fa-3x text-info mb-3"></i>
                                        <h4><?php echo number_format($userAnalytics['new_users']); ?></h4>
                                        <p class="text-muted mb-0">New Registrations</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-percentage fa-3x text-warning mb-3"></i>
                                        <h4><?php echo number_format($userAnalytics['retention_rate'], 1); ?>%</h4>
                                        <p class="text-muted mb-0">User Retention Rate</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-trophy text-warning"></i> Top Active Students</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Bookings</th>
                                                        <th>Reviews</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (($userAnalytics['top_students'] ?? []) as $student): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                                            <td><?php echo $student['booking_count']; ?></td>
                                                            <td><?php echo $student['review_count']; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-star text-success"></i> Top Active Landlords</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Properties</th>
                                                        <th>Bookings</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (($userAnalytics['top_landlords'] ?? []) as $landlord): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($landlord['full_name']); ?></td>
                                                            <td><?php echo $landlord['property_count']; ?></td>
                                                            <td><?php echo $landlord['booking_count']; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PROPERTY ANALYTICS TAB -->
                    <div class="tab-pane fade" id="properties" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-building fa-3x text-success mb-3"></i>
                                        <h4><?php echo number_format($propertyAnalytics['total_properties']); ?></h4>
                                        <p class="text-muted mb-0">Total Properties Listed</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-check-circle fa-3x text-primary mb-3"></i>
                                        <h4><?php echo number_format($propertyAnalytics['active_properties']); ?></h4>
                                        <p class="text-muted mb-0">Active Properties</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                        <h4><?php echo number_format($propertyAnalytics['pending_properties']); ?></h4>
                                        <p class="text-muted mb-0">Pending Approval</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-dollar-sign fa-3x text-info mb-3"></i>
                                        <h4><?php echo formatCurrency($propertyAnalytics['average_price']); ?></h4>
                                        <p class="text-muted mb-0">Average Property Price</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-map-marked-alt text-primary"></i> Properties by Location</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Location</th>
                                                        <th>Count</th>
                                                        <th>Percentage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (($propertyAnalytics['by_location'] ?? []) as $location): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($location['location']); ?></td>
                                                            <td><?php echo $location['count']; ?></td>
                                                            <td>
                                                                <div class="progress" style="height: 20px;">
                                                                    <div class="progress-bar" style="width: <?php echo $location['percentage']; ?>%">
                                                                        <?php echo number_format($location['percentage'], 1); ?>%
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-eye text-info"></i> Most Viewed Properties</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Property</th>
                                                        <th>Views</th>
                                                        <th>Bookings</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (($propertyAnalytics['most_viewed'] ?? []) as $property): ?>
                                                        <tr>
                                                            <td class="text-truncate" style="max-width: 200px;">
                                                                <?php echo htmlspecialchars($property['title']); ?>
                                                            </td>
                                                            <td><?php echo number_format($property['view_count']); ?></td>
                                                            <td><?php echo $property['booking_count']; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-fire text-danger"></i> Most Booked Properties</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Property</th>
                                                        <th>Bookings</th>
                                                        <th>Revenue</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (($propertyAnalytics['most_booked'] ?? []) as $property): ?>
                                                        <tr>
                                                            <td class="text-truncate" style="max-width: 200px;">
                                                                <?php echo htmlspecialchars($property['title']); ?>
                                                            </td>
                                                            <td><?php echo $property['booking_count']; ?></td>
                                                            <td><?php echo formatCurrency($property['total_revenue']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-chart-pie text-warning"></i> Property Status Distribution</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="propertyStatusChart" style="height: 250px;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BOOKING ANALYTICS TAB -->
                    <div class="tab-pane fade" id="bookings" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-check fa-3x text-info mb-3"></i>
                                        <h4><?php echo number_format($bookingAnalytics['total_bookings']); ?></h4>
                                        <p class="text-muted mb-0">Total Bookings</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-check-double fa-3x text-success mb-3"></i>
                                        <h4><?php echo number_format($bookingAnalytics['approved_bookings']); ?></h4>
                                        <p class="text-muted mb-0">Approved Bookings</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-percentage fa-3x text-primary mb-3"></i>
                                        <h4><?php echo number_format($bookingAnalytics['conversion_rate'], 1); ?>%</h4>
                                        <p class="text-muted mb-0">Booking Conversion Rate</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-ban fa-3x text-danger mb-3"></i>
                                        <h4><?php echo number_format($bookingAnalytics['cancellation_rate'], 1); ?>%</h4>
                                        <p class="text-muted mb-0">Cancellation Rate</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-chart-bar text-primary"></i> Bookings by Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="bookingStatusChart" style="height: 300px;"></canvas>
                                    </div>
                                </div>

                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-dollar-sign text-success"></i> Average Booking Value</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <h2 class="text-success"><?php echo formatCurrency($bookingAnalytics['average_booking_value']); ?></h2>
                                        <p class="text-muted">Per booking transaction</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-calendar-alt text-warning"></i> Peak Booking Periods</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="peakBookingChart" style="height: 300px;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- REVENUE ANALYTICS TAB -->
                    <div class="tab-pane fade" id="revenue" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                                        <h4><?php echo formatCurrency($revenueAnalytics['total_revenue']); ?></h4>
                                        <p class="text-muted mb-0">Total Revenue Generated</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-coins fa-3x text-warning mb-3"></i>
                                        <h4><?php echo formatCurrency($revenueAnalytics['commission_earned']); ?></h4>
                                        <p class="text-muted mb-0">Commission Earned (10%)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                                        <h4><?php echo formatCurrency($revenueAnalytics['monthly_revenue']); ?></h4>
                                        <p class="text-muted mb-0">This Month's Revenue</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-receipt fa-3x text-primary mb-3"></i>
                                        <h4><?php echo formatCurrency($revenueAnalytics['average_transaction']); ?></h4>
                                        <p class="text-muted mb-0">Average Transaction Value</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-chart-area text-success"></i> Revenue by Month</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="monthlyRevenueChart" style="height: 300px;"></canvas>
                                    </div>
                                </div>

                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-trophy text-warning"></i> Top Revenue Properties</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Property</th>
                                                        <th>Bookings</th>
                                                        <th>Revenue</th>
                                                        <th>Commission</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach (($revenueAnalytics['top_properties'] ?? []) as $property): ?>
                                                        <tr>
                                                            <td class="text-truncate" style="max-width: 250px;">
                                                                <?php echo htmlspecialchars($property['title']); ?>
                                                            </td>
                                                            <td><?php echo $property['booking_count']; ?></td>
                                                            <td><?php echo formatCurrency($property['total_revenue']); ?></td>
                                                            <td class="text-success"><?php echo formatCurrency($property['commission']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-credit-card text-info"></i> Payment Methods</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="paymentMethodsChart" style="height: 250px;"></canvas>
                                    </div>
                                </div>

                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-percentage text-primary"></i> Commission Breakdown</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Commission Rate</span>
                                                <strong>10%</strong>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-warning" style="width: 10%"></div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Landlord Payout</span>
                                                <strong>90%</strong>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" style="width: 90%"></div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="text-center">
                                            <p class="text-muted mb-1">Total Commission Earned</p>
                                            <h4 class="text-success"><?php echo formatCurrency($revenueAnalytics['commission_earned']); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        // Toggle custom date range inputs
        function toggleCustomDates() {
            const dateRange = document.getElementById('dateRangeSelect').value;
            const customStart = document.getElementById('customStartDate');
            const customEnd = document.getElementById('customEndDate');

            if (dateRange === 'custom') {
                customStart.style.display = 'block';
                customEnd.style.display = 'block';
            } else {
                customStart.style.display = 'none';
                customEnd.style.display = 'none';
                document.querySelector('form').submit();
            }
        }

        // Export functions
        function exportPDF() {
            window.location.href = '../actions/export_report.php?format=pdf&range=<?php echo $dateRange; ?>&start=<?php echo $customStartDate; ?>&end=<?php echo $customEndDate; ?>';
        }

        function exportExcel() {
            window.location.href = '../actions/export_report.php?format=excel&range=<?php echo $dateRange; ?>&start=<?php echo $customStartDate; ?>&end=<?php echo $customEndDate; ?>';
        }

        function exportCSV() {
            window.location.href = '../actions/export_report.php?format=csv&range=<?php echo $dateRange; ?>&start=<?php echo $customStartDate; ?>&end=<?php echo $customEndDate; ?>';
        }

        // Chart.js configurations
        const chartColors = {
            primary: '#4e73df',
            success: '#1cc88a',
            info: '#36b9cc',
            warning: '#f6c23e',
            danger: '#e74a3b'
        };

        // User Registration Trend Chart
        const registrationData = <?php echo json_encode($registrationTrendData); ?>;
        new Chart(document.getElementById('registrationTrendChart'), {
            type: 'line',
            data: {
                labels: registrationData.labels,
                datasets: [{
                    label: 'New Users',
                    data: registrationData.data,
                    borderColor: chartColors.primary,
                    backgroundColor: chartColors.primary + '20',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Booking Volume Chart
        const bookingData = <?php echo json_encode($bookingVolumeData); ?>;
        new Chart(document.getElementById('bookingVolumeChart'), {
            type: 'bar',
            data: {
                labels: bookingData.labels,
                datasets: [{
                    label: 'Bookings',
                    data: bookingData.data,
                    backgroundColor: chartColors.info
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Revenue Over Time Chart
        const revenueData = <?php echo json_encode($revenueOverTimeData); ?>;
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: revenueData.labels,
                datasets: [{
                    label: 'Revenue',
                    data: revenueData.data,
                    borderColor: chartColors.success,
                    backgroundColor: chartColors.success + '30',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Property Distribution Chart
        const propertyDistData = <?php echo json_encode($propertyDistributionData); ?>;
        new Chart(document.getElementById('propertyDistributionChart'), {
            type: 'pie',
            data: {
                labels: propertyDistData.labels,
                datasets: [{
                    data: propertyDistData.data,
                    backgroundColor: [
                        chartColors.primary,
                        chartColors.success,
                        chartColors.info,
                        chartColors.warning,
                        chartColors.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Property Status Chart
        <?php if (isset($propertyAnalytics['status_distribution'])): ?>
        new Chart(document.getElementById('propertyStatusChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($propertyAnalytics['status_distribution'], 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($propertyAnalytics['status_distribution'], 'count')); ?>,
                    backgroundColor: [chartColors.success, chartColors.warning, chartColors.danger, chartColors.info]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        <?php endif; ?>

        // Booking Status Chart
        <?php if (isset($bookingAnalytics['status_distribution'])): ?>
        new Chart(document.getElementById('bookingStatusChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($bookingAnalytics['status_distribution'], 'status')); ?>,
                datasets: [{
                    label: 'Count',
                    data: <?php echo json_encode(array_column($bookingAnalytics['status_distribution'], 'count')); ?>,
                    backgroundColor: [chartColors.warning, chartColors.success, chartColors.danger, chartColors.info]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y'
            }
        });
        <?php endif; ?>

        // Peak Booking Chart
        <?php if (isset($bookingAnalytics['peak_periods'])): ?>
        new Chart(document.getElementById('peakBookingChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($bookingAnalytics['peak_periods'], 'month')); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode(array_column($bookingAnalytics['peak_periods'], 'count')); ?>,
                    backgroundColor: chartColors.warning
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        <?php endif; ?>

        // Monthly Revenue Chart
        <?php if (isset($revenueAnalytics['monthly_breakdown'])): ?>
        new Chart(document.getElementById('monthlyRevenueChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenueAnalytics['monthly_breakdown'], 'month')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($revenueAnalytics['monthly_breakdown'], 'revenue')); ?>,
                    borderColor: chartColors.success,
                    backgroundColor: chartColors.success + '30',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Payment Methods Chart
        <?php if (isset($revenueAnalytics['payment_methods'])): ?>
        new Chart(document.getElementById('paymentMethodsChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($revenueAnalytics['payment_methods'], 'method')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($revenueAnalytics['payment_methods'], 'count')); ?>,
                    backgroundColor: [chartColors.success, chartColors.info, chartColors.warning]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
