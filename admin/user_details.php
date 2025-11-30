<?php
/**
 * CampusDigs Kenya - Admin User Details
 * Detailed user profile with verification and management actions
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/booking_controller.php';
require_once '../controllers/wishlist_controller.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get user ID
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    $_SESSION['error'] = 'Invalid user ID';
    header('Location: manage_users.php');
    exit();
}

// Get user details
$user = getUserById($userId);

if (!$user) {
    $_SESSION['error'] = 'User not found';
    header('Location: manage_users.php');
    exit();
}

// Get user-specific data based on type
$userData = [];

if ($user['user_type'] === 'student') {
    $userData['bookings'] = getStudentBookingsAdmin($userId);
    $userData['wishlist_count'] = getWishlistCount($userId);
} elseif ($user['user_type'] === 'landlord') {
    $userData['properties'] = getLandlordPropertiesAdmin($userId);
    $userData['bookings'] = getLandlordBookingsAdmin($userId);
    $userData['total_revenue'] = getLandlordTotalRevenue($userId);
}

// Get user activity log
$activityLog = getUserActivityLog($userId, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo htmlspecialchars($user['full_name']); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            font-size: 40px;
        }
        .info-card {
            border-left: 4px solid #667eea;
        }
        .activity-item {
            border-left: 2px solid #dee2e6;
            padding-left: 20px;
            padding-bottom: 15px;
            position: relative;
        }
        .activity-item::before {
            content: '';
            width: 10px;
            height: 10px;
            background: #667eea;
            border-radius: 50%;
            position: absolute;
            left: -6px;
            top: 5px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-4">
        <!-- Back Button -->
        <a href="manage_users.php" class="btn btn-outline-secondary mb-3 no-print">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show no-print">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header shadow-sm mb-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center profile-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                    </div>
                </div>
                <div class="col">
                    <h2 class="mb-1">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                        <?php if ($user['account_verified']): ?>
                            <i class="fas fa-check-circle text-success" title="Verified Account"></i>
                        <?php endif; ?>
                    </h2>
                    <?php
                    $typeClass = [
                        'student' => 'bg-primary',
                        'landlord' => 'bg-success',
                        'admin' => 'bg-danger'
                    ][$user['user_type']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $typeClass; ?> me-2">
                        <i class="fas fa-<?php echo $user['user_type'] === 'student' ? 'user-graduate' : ($user['user_type'] === 'landlord' ? 'user-tie' : 'shield-alt'); ?>"></i>
                        <?php echo ucfirst($user['user_type']); ?>
                    </span>

                    <?php if ($user['account_status'] === 'suspended'): ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-ban"></i> Suspended
                        </span>
                    <?php elseif (!$user['account_verified']): ?>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-clock"></i> Unverified
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success">
                            <i class="fas fa-check"></i> Active
                        </span>
                    <?php endif; ?>

                    <p class="mb-0 mt-2 small">
                        User ID: #<?php echo $user['id']; ?> •
                        Joined <?php echo formatDate($user['created_at']); ?>
                        (<?php echo timeAgo($user['created_at']); ?>)
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Contact Information -->
                <div class="card shadow-sm mb-4 info-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-id-card"></i> Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Email Address</label>
                                <div class="d-flex align-items-center">
                                    <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                                    <?php if ($user['email_verified']): ?>
                                        <i class="fas fa-check-circle text-success ms-2" title="Email Verified"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-circle text-warning ms-2" title="Email Not Verified"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Phone Number</label>
                                <div><strong><?php echo htmlspecialchars($user['phone']); ?></strong></div>
                            </div>
                            <?php if ($user['user_type'] === 'student' && isset($user['university']) && $user['university']): ?>
                                <div class="col-md-6">
                                    <label class="text-muted small">University</label>
                                    <div><strong><?php echo htmlspecialchars($user['university']); ?></strong></div>
                                </div>
                                <?php if (isset($user['student_id']) && $user['student_id']): ?>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Student ID</label>
                                        <div><strong><?php echo htmlspecialchars($user['student_id']); ?></strong></div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <label class="text-muted small">Last Login</label>
                                <div>
                                    <?php if (isset($user['last_login']) && $user['last_login']): ?>
                                        <strong><?php echo formatDateTime($user['last_login']); ?></strong>
                                        <small class="text-muted">(<?php echo timeAgo($user['last_login']); ?>)</small>
                                    <?php else: ?>
                                        <span class="text-muted">Never logged in</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Account Created</label>
                                <div>
                                    <strong><?php echo formatDateTime($user['created_at']); ?></strong>
                                    <small class="text-muted">(<?php echo timeAgo($user['created_at']); ?>)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student-Specific Information -->
                <?php if ($user['user_type'] === 'student'): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-graduate"></i> Student Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-md-4">
                                    <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                                    <h4><?php echo count($userData['bookings']); ?></h4>
                                    <small class="text-muted">Total Bookings</small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                                    <h4><?php echo $userData['wishlist_count']; ?></h4>
                                    <small class="text-muted">Wishlist Items</small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                    <h4>
                                        <?php
                                        $activeBookings = array_filter($userData['bookings'], function($b) {
                                            return in_array($b['status'], ['approved', 'pending']);
                                        });
                                        echo count($activeBookings);
                                        ?>
                                    </h4>
                                    <small class="text-muted">Active Bookings</small>
                                </div>
                            </div>

                            <?php if (!empty($userData['bookings'])): ?>
                                <h6 class="mt-4 mb-3">Recent Bookings</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Property</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($userData['bookings'], 0, 5) as $booking): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($booking['property_title']); ?></td>
                                                    <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                            echo $booking['status'] === 'approved' ? 'success' :
                                                                ($booking['status'] === 'pending' ? 'warning' : 'secondary');
                                                        ?>">
                                                            <?php echo ucfirst($booking['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><small><?php echo timeAgo($booking['created_at']); ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Landlord-Specific Information -->
                <?php if ($user['user_type'] === 'landlord'): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-user-tie"></i> Landlord Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-md-4">
                                    <i class="fas fa-building fa-2x text-success mb-2"></i>
                                    <h4><?php echo count($userData['properties']); ?></h4>
                                    <small class="text-muted">Properties Listed</small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                                    <h4><?php echo count($userData['bookings']); ?></h4>
                                    <small class="text-muted">Total Bookings</small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-dollar-sign fa-2x text-warning mb-2"></i>
                                    <h4><?php echo formatCurrency($userData['total_revenue']); ?></h4>
                                    <small class="text-muted">Total Revenue</small>
                                </div>
                            </div>

                            <?php if (!empty($userData['properties'])): ?>
                                <h6 class="mt-4 mb-3">Properties</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Property</th>
                                                <th>Location</th>
                                                <th>Price</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($userData['properties'] as $property): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($property['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($property['location']); ?></td>
                                                    <td><?php echo formatCurrency($property['price_monthly']); ?>/mo</td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                            echo $property['status'] === 'active' ? 'success' :
                                                                ($property['status'] === 'pending' ? 'warning' : 'secondary');
                                                        ?>">
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
                <?php endif; ?>

                <!-- Activity Log -->
                <?php if (!empty($activityLog)): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($activityLog as $activity): ?>
                                <div class="activity-item">
                                    <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                    <p class="mb-0 small text-muted">
                                        <?php echo htmlspecialchars($activity['description'] ?? ''); ?>
                                    </p>
                                    <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Account Status Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Account Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Email Verification</label>
                            <div>
                                <?php if ($user['email_verified']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock"></i> Not Verified
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small">Account Verification</label>
                            <div>
                                <?php if ($user['account_verified']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                    <?php if (isset($user['verified_at']) && $user['verified_at']): ?>
                                        <small class="d-block text-muted">
                                            <?php echo formatDate($user['verified_at']); ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock"></i> Unverified
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <label class="text-muted small">Account Status</label>
                            <div>
                                <?php if ($user['account_status'] === 'suspended'): ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-ban"></i> Suspended
                                    </span>
                                    <?php if ($user['suspension_reason']): ?>
                                        <div class="alert alert-danger mt-2 mb-0">
                                            <strong>Reason:</strong>
                                            <?php echo htmlspecialchars($user['suspension_reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Actions Card -->
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$user['account_verified']): ?>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle"></i>
                                <strong>Action Required:</strong> This account needs verification.
                            </div>
                            <div class="d-grid gap-2 mb-3">
                                <button class="btn btn-success btn-lg" onclick="verifyUser(<?php echo $userId; ?>)">
                                    <i class="fas fa-check-circle"></i> Verify Account
                                </button>
                            </div>
                            <hr>
                        <?php endif; ?>

                        <?php if ($user['account_status'] === 'active'): ?>
                            <div class="d-grid gap-2 mb-3">
                                <button class="btn btn-danger" onclick="suspendUser(<?php echo $userId; ?>)">
                                    <i class="fas fa-ban"></i> Suspend Account
                                </button>
                            </div>
                        <?php elseif ($user['account_status'] === 'suspended'): ?>
                            <div class="d-grid gap-2 mb-3">
                                <button class="btn btn-success" onclick="reactivateUser(<?php echo $userId; ?>)">
                                    <i class="fas fa-undo"></i> Reactivate Account
                                </button>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="d-grid gap-2">
                            <?php if ($user['user_type'] !== 'admin'): ?>
                                <button class="btn btn-outline-danger" onclick="deleteUser(<?php echo $userId; ?>)">
                                    <i class="fas fa-trash"></i> Delete Account
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                            <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="btn btn-outline-primary">
                                <i class="fas fa-envelope"></i> Email User
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Contact Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-phone"></i> Quick Contact</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="tel:<?php echo htmlspecialchars($user['phone']); ?>"
                               class="btn btn-outline-success">
                                <i class="fas fa-phone"></i> Call <?php echo htmlspecialchars($user['full_name']); ?>
                            </a>
                            <a href="https://wa.me/<?php echo str_replace('+', '', $user['phone']); ?>"
                               target="_blank"
                               class="btn btn-success">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        // Verify User
        function verifyUser(userId) {
            if (!confirm('Are you sure you want to VERIFY this account?\n\nThe user will receive full platform access and a "Verified" badge.')) {
                return;
            }

            fetch('../actions/admin_users_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=verify&user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to verify user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Suspend User
        function suspendUser(userId) {
            const reason = prompt('Please provide a reason for suspension:\n\n(This will be visible to the user)');

            if (!reason || reason.trim() === '') {
                alert('Suspension reason is required');
                return;
            }

            if (!confirm('Are you sure you want to SUSPEND this account?\n\nThe user will lose access to the platform.')) {
                return;
            }

            fetch('../actions/admin_users_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=suspend&user_id=${userId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to suspend user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Reactivate User
        function reactivateUser(userId) {
            if (!confirm('Are you sure you want to REACTIVATE this suspended account?\n\nThe user will regain full platform access.')) {
                return;
            }

            fetch('../actions/admin_users_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reactivate&user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to reactivate user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Delete User
        function deleteUser(userId) {
            const confirmation1 = prompt('WARNING: This will PERMANENTLY delete this account.\n\nType "DELETE" to confirm:');

            if (confirmation1 !== 'DELETE') {
                alert('Deletion cancelled');
                return;
            }

            const reason = prompt('Please provide a reason for deletion (for audit log):');

            if (!reason || reason.trim() === '') {
                alert('Deletion reason is required for audit purposes');
                return;
            }

            if (!confirm('FINAL CONFIRMATION:\n\nAre you absolutely sure you want to permanently delete this account?\n\nThis action CANNOT be undone!')) {
                return;
            }

            fetch('../actions/admin_users_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&user_id=${userId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'manage_users.php';
                } else {
                    alert(data.message || 'Failed to delete user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html>
