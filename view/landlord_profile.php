<?php
/**
 * CampusDigs Kenya - Landlord Profile
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/booking_controller.php';

// Require landlord login
requireLandlord();

$userId = $_SESSION['user_id'];
$user = getUserById($userId);
$flash = getFlashMessage();

// Get landlord statistics (with null-safe access)
$propertyCount = 0;
$activeProperties = 0;
$totalBookings = 0;
$totalRevenue = 0;

try {
    // Get property count
    $propertiesResult = getLandlordProperties($userId);

    // Check if result is valid
    if (is_array($propertiesResult)) {
        $propertyCount = count($propertiesResult);

        // Count active properties with proper validation
        foreach ($propertiesResult as $property) {
            if (is_array($property) && isset($property['status']) && $property['status'] === 'active') {
                $activeProperties++;
            }
        }
    }

    // Get total revenue (returns float/int)
    $revenueResult = getLandlordTotalRevenue($userId);
    $totalRevenue = is_numeric($revenueResult) ? $revenueResult : 0;

    // Get booking count
    $bookingResult = getLandlordBookingsAdmin($userId);
    $totalBookings = is_array($bookingResult) ? count($bookingResult) : 0;
} catch (Exception $e) {
    // Silently fail - stats will show 0
    error_log("Landlord profile stats error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/profile.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Profile Header with Cover Photo -->
    <div class="profile-header">
        <div class="profile-cover">
            <!-- Cover Photo (professional business/property image) -->
            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=1600&h=400&fit=crop"
                 alt="Cover Photo"
                 class="cover-photo">

            <!-- Edit Cover Button (for future implementation) -->
            <button class="btn btn-light btn-sm edit-cover-btn" disabled title="Coming Soon">
                <i class="fas fa-camera"></i> Change Cover
            </button>
        </div>

        <div class="container">
            <div class="profile-info-header">
                <div class="profile-avatar-wrapper">
                    <!-- Avatar (placeholder for now - can be replaced with upload feature) -->
                    <div class="profile-avatar">
                        <?php if (isset($user['profile_picture']) && $user['profile_picture']): ?>
                            <img src="<?php echo UPLOADS_URL . '/avatars/' . $user['profile_picture']; ?>"
                                 alt="Profile Picture">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Upload Avatar Button (for future implementation) -->
                    <button class="btn btn-sm btn-light avatar-upload-btn" disabled title="Coming Soon">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>

                <div class="profile-info-text">
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="profile-role">
                        <i class="fas fa-building"></i> Property Owner & Landlord
                    </p>
                    <div class="profile-badges">
                        <?php if ($user['email_verified']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i> Email Verified
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning">
                                <i class="fas fa-exclamation-circle"></i> Email Unverified
                            </span>
                        <?php endif; ?>

                        <?php if ($user['account_verified']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-shield-check"></i> Account Verified
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning">
                                <i class="fas fa-clock"></i> Pending Verification
                            </span>
                        <?php endif; ?>

                        <span class="badge bg-primary">
                            <i class="fas fa-home"></i> <?php echo $propertyCount; ?> Properties
                        </span>
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="settings.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="../landlord/landlord_properties.php" class="btn btn-outline-primary">
                        <i class="fas fa-building"></i> My Properties
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-4">
                <!-- Business Stats Card -->
                <div class="card stats-card shadow-sm mb-4">
                    <div class="card-header bg-gradient-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Business Overview</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="stat-item">
                            <div class="stat-icon bg-success-light">
                                <i class="fas fa-home text-success"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo $propertyCount; ?></span>
                                <span class="stat-label">Total Properties</span>
                            </div>
                            <a href="../landlord/landlord_properties.php" class="stat-link">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="stat-item">
                            <div class="stat-icon bg-primary-light">
                                <i class="fas fa-check-circle text-primary"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo $activeProperties; ?></span>
                                <span class="stat-label">Active Listings</span>
                            </div>
                            <a href="../landlord/landlord_properties.php?status=active" class="stat-link">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="stat-item">
                            <div class="stat-icon bg-info-light">
                                <i class="fas fa-calendar-check text-info"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo $totalBookings; ?></span>
                                <span class="stat-label">Total Bookings</span>
                            </div>
                            <a href="../landlord/landlord_bookings.php" class="stat-link">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="stat-item border-0">
                            <div class="stat-icon bg-warning" style="background: rgba(217, 119, 6, 0.1);">
                                <i class="fas fa-money-bill-wave text-warning"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value"><?php echo formatCurrency($totalRevenue); ?></span>
                                <span class="stat-label">Total Revenue</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th width="30%">Full Name:</th>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>
                                        <?php echo htmlspecialchars($user['email']); ?>
                                        <?php if ($user['email_verified']): ?>
                                            <i class="fas fa-check-circle text-success" title="Verified"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>
                                        <?php echo htmlspecialchars($user['phone']); ?>
                                        <?php if (isset($user['phone_verified']) && $user['phone_verified']): ?>
                                            <i class="fas fa-check-circle text-success" title="Verified"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Member Since:</th>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Account Security</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Last Login:</strong>
                            <?php echo isset($user['last_login']) && $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?>
                        </div>

                        <div class="mb-3">
                            <strong>Account Status:</strong>
                            <span class="badge bg-<?php echo $user['account_status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($user['account_status']); ?>
                            </span>
                        </div>

                        <a href="settings.php#security" class="btn btn-outline-success">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-tasks"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="landlord/add_property.php" class="btn btn-success w-100">
                                    <i class="fas fa-plus-circle"></i> Add New Property
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="landlord/my_properties.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-home"></i> My Properties
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="landlord/manage_bookings.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-calendar-check"></i> Manage Bookings
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="landlord/analytics.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-chart-bar"></i> View Analytics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
