<?php
/**
 * CampusDigs Kenya - Student Dashboard
 * Main dashboard for student users
 */

// Include required files
require_once 'includes/config.php';
require_once 'includes/core.php';
require_once 'controllers/property_controller.php';
require_once 'controllers/booking_controller.php';
require_once 'controllers/wishlist_controller.php';

// Require student authentication
requireStudent();

// Get student data
$studentId = $_SESSION['user_id'];
$studentName = $_SESSION['full_name'];
$emailVerified = $_SESSION['email_verified'];
$accountVerified = $_SESSION['account_verified'];

// Get flash message
$flash = getFlashMessage();

// Get dashboard statistics
$stats = getStudentDashboardStats($studentId);

// Get recent properties
$recentProperties = getRecentProperties(6);

// Get student's active bookings
$activeBookings = getStudentActiveBookings($studentId);

// Get wishlist count
$wishlistCount = getWishlistCount($studentId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
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
                            <a class="nav-link active" href="dashboard_student.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/all_properties.php">
                                <i class="fas fa-search"></i> Browse Properties
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/student_wishlist.php">
                                <i class="fas fa-heart"></i> My Wishlist
                                <?php if ($wishlistCount > 0): ?>
                                    <span class="badge bg-danger rounded-pill"><?php echo $wishlistCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/student_bookings.php">
                                <i class="fas fa-calendar-check"></i> My Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/student_payments.php">
                                <i class="fas fa-credit-card"></i> Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view/student_profile.php">
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
                        <i class="fas fa-home text-primary"></i> 
                        Welcome back, <?php echo htmlspecialchars(explode(' ', $studentName)[0]); ?>!
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="view/all_properties.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Properties
                        </a>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Verification Alerts -->
                <?php if (!$emailVerified): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-envelope"></i>
                        <strong>Verify your email!</strong> 
                        Please check your inbox and verify your email address to access all features.
                        <a href="actions/resend_verification.php" class="alert-link">Resend verification email</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$accountVerified): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-clock"></i>
                        <strong>Account pending verification</strong> 
                        Your account is under review. You can browse properties but bookings require verification.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Active Bookings
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['active_bookings']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Wishlist Items
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['wishlist_items']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-heart fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Spent
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo formatCurrency($stats['total_spent']); ?>
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
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Payments
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo formatCurrency($stats['pending_payments']); ?>
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

                <!-- Active Bookings Section -->
                <?php if (!empty($activeBookings)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-home"></i> Your Active Bookings
                            </h6>
                            <a href="view/student_bookings.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Move-in Date</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeBookings as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['property_title']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt"></i> 
                                                        <?php echo htmlspecialchars($booking['property_location']); ?>
                                                    </small>
                                                </td>
                                                <td><?php echo formatDate($booking['move_in_date']); ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = [
                                                        'pending' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        'cancelled' => 'secondary'
                                                    ];
                                                    $class = $statusClass[$booking['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $class; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                                <td>
                                                    <a href="view/booking_details.php?id=<?php echo $booking['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- AI-Powered Recommendations Section -->
                <?php require_once 'view/components/recommendations.php'; ?>
                <?php renderRecommendations('Recommended For You', 'personalized', 6, true); ?>

                <!-- Trending Properties -->
                <?php renderRecommendations('Trending Properties', 'trending', 6, false); ?>

                <!-- Recent Properties Section -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-star"></i> Recommended Properties for You
                        </h6>
                        <a href="view/all_properties.php" class="btn btn-sm btn-primary">Browse All</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($recentProperties as $property): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card property-card h-100">
                                        <!-- Property Image -->
                                        <div class="property-image-wrapper">
                                            <?php if ($property['main_image']): ?>
                                                <img src="<?php echo BASE_URL . '/' . $property['main_image']; ?>"
                                                     class="card-img-top"
                                                     alt="<?php echo htmlspecialchars($property['title']); ?>">
                                            <?php else: ?>
                                                <img src="<?php echo IMAGES_URL; ?>/default-property.png" 
                                                     class="card-img-top" 
                                                     alt="Default Property">
                                            <?php endif; ?>
                                            
                                            <!-- Wishlist Button -->
                                            <button class="btn btn-wishlist" 
                                                    onclick="toggleWishlist(<?php echo $property['id']; ?>, this)">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                            
                                            <!-- Verified Badge -->
                                            <?php if ($property['is_verified']): ?>
                                                <span class="badge badge-verified">
                                                    <i class="fas fa-shield-alt"></i> Verified
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($property['title']); ?>
                                            </h5>
                                            <p class="card-text text-muted small">
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo htmlspecialchars($property['location']); ?>
                                            </p>
                                            
                                            <!-- Safety Score -->
                                            <div class="mb-2">
                                                <?php
                                                $safetyScore = (int)$property['safety_score'];
                                                for ($i = 1; $i <= 5; $i++):
                                                    if ($i <= $safetyScore):
                                                ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-warning"></i>
                                                <?php endif; endfor; ?>
                                                <small class="text-muted">(Safety Score)</small>
                                            </div>
                                            
                                            <!-- Amenities -->
                                            <div class="amenities mb-3">
                                                <?php
                                                $amenities = json_decode($property['amenities'], true);
                                                if ($amenities && is_array($amenities)):
                                                    $displayed = array_slice($amenities, 0, 3);
                                                    foreach ($displayed as $amenity):
                                                ?>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo htmlspecialchars($amenity); ?>
                                                    </span>
                                                <?php endforeach; endif; ?>
                                            </div>
                                            
                                            <!-- Price -->
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h4 class="text-primary mb-0">
                                                    <?php echo formatCurrency($property['price_monthly']); ?>
                                                    <small class="text-muted">/month</small>
                                                </h4>
                                            </div>
                                        </div>
                                        
                                        <div class="card-footer bg-white">
                                            <div class="d-grid gap-2">
                                                <a href="view/single_property.php?id=<?php echo $property['id']; ?>" 
                                                   class="btn btn-primary">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-bolt"></i> Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <a href="view/all_properties.php" class="quick-action-btn">
                                            <i class="fas fa-search fa-3x text-primary mb-2"></i>
                                            <p class="mb-0">Search Properties</p>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="view/student_wishlist.php" class="quick-action-btn">
                                            <i class="fas fa-heart fa-3x text-danger mb-2"></i>
                                            <p class="mb-0">My Wishlist</p>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="view/student_bookings.php" class="quick-action-btn">
                                            <i class="fas fa-calendar-check fa-3x text-success mb-2"></i>
                                            <p class="mb-0">My Bookings</p>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="view/help.php" class="quick-action-btn">
                                            <i class="fas fa-question-circle fa-3x text-info mb-2"></i>
                                            <p class="mb-0">Help & Support</p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="js/dashboard.js"></script>
    <script src="js/wishlist.js"></script>
</body>
</html>