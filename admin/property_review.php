<?php
/**
 * CampusDigs Kenya - Admin Property Review
 * Detailed property review page with approve/reject actions
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/property_controller.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get property ID
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$propertyId) {
    $_SESSION['error'] = 'Invalid property ID';
    header('Location: manage_properties.php');
    exit();
}

// Get property details
$property = getPropertyById($propertyId);

if (!$property) {
    $_SESSION['error'] = 'Property not found';
    header('Location: manage_properties.php');
    exit();
}

// Get property images
$images = getPropertyImages($propertyId);

// Get property statistics
$stats = getPropertyStatistics($propertyId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Property - <?php echo htmlspecialchars($property['title']); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <style>
        .property-image {
            height: 400px;
            object-fit: cover;
        }
        .thumbnail {
            height: 80px;
            object-fit: cover;
            cursor: pointer;
            transition: all 0.3s;
        }
        .thumbnail:hover {
            transform: scale(1.05);
            opacity: 0.8;
        }
        .verification-checklist {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
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
        <a href="manage_properties.php" class="btn btn-outline-secondary mb-3 no-print">
            <i class="fas fa-arrow-left"></i> Back to Properties
        </a>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show no-print">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Property Header -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-building"></i> Property Review</h4>
                    <?php
                    $statusClass = [
                        'pending' => 'bg-warning text-dark',
                        'active' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'inactive' => 'bg-secondary'
                    ][$property['status']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $statusClass; ?> fs-6">
                        <?php echo ucfirst($property['status']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h2><?php echo htmlspecialchars($property['title']); ?></h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                    <?php if ($property['distance_from_campus']): ?>
                        • <?php echo $property['distance_from_campus']; ?>km from campus
                    <?php endif; ?>
                </p>
                <p class="text-muted small">
                    Submitted <?php echo formatDate($property['created_at']); ?>
                    (<?php echo timeAgo($property['created_at']); ?>)
                </p>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Property Images -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-images"></i> Property Images</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($images)): ?>
                            <img src="../<?php echo htmlspecialchars($images[0]['image_path']); ?>"
                                 class="card-img-top property-image"
                                 id="mainImage"
                                 alt="Property">

                            <?php if (count($images) > 1): ?>
                                <div class="p-3">
                                    <div class="row g-2">
                                        <?php foreach ($images as $image): ?>
                                            <div class="col-3">
                                                <img src="../<?php echo htmlspecialchars($image['image_path']); ?>"
                                                     class="thumbnail w-100 rounded"
                                                     onclick="changeMainImage(this.src)"
                                                     alt="Thumbnail">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning m-3">
                                <i class="fas fa-exclamation-triangle"></i> No images uploaded
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Property Details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Property Details</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-3">Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>

                        <hr>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Room Type:</strong>
                                <p><?php echo ROOM_TYPES[$property['room_type']] ?? $property['room_type']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Capacity:</strong>
                                <p><?php echo $property['capacity']; ?> person(s)</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Monthly Rent:</strong>
                                <p class="text-success fw-bold"><?php echo formatCurrency($property['price_monthly']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Security Deposit:</strong>
                                <p><?php echo formatCurrency($property['security_deposit']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Lease Duration:</strong>
                                <p><?php echo $property['min_lease_months']; ?> - <?php echo $property['max_lease_months']; ?> months</p>
                            </div>
                            <?php if ($property['university_nearby']): ?>
                                <div class="col-md-6">
                                    <strong>Nearby University:</strong>
                                    <p><?php echo htmlspecialchars($property['university_nearby']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <h6 class="mb-3">Amenities & Features</h6>
                        <div class="row g-2">
                            <?php
                            $amenities = json_decode($property['amenities'], true);
                            if ($amenities && is_array($amenities)):
                                foreach ($amenities as $amenity):
                            ?>
                                <div class="col-md-6">
                                    <i class="fas fa-check-circle text-success"></i> <?php echo htmlspecialchars($amenity); ?>
                                </div>
                            <?php endforeach; endif; ?>

                            <?php if ($property['has_cctv']): ?>
                                <div class="col-md-6">
                                    <i class="fas fa-check-circle text-success"></i> CCTV Surveillance
                                </div>
                            <?php endif; ?>

                            <?php if ($property['has_security_guard']): ?>
                                <div class="col-md-6">
                                    <i class="fas fa-check-circle text-success"></i> 24/7 Security Guard
                                </div>
                            <?php endif; ?>

                            <?php if ($property['has_secure_entry']): ?>
                                <div class="col-md-6">
                                    <i class="fas fa-check-circle text-success"></i> Secure Entry System
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <h6 class="mb-3">Safety Score</h6>
                        <div class="d-flex align-items-center">
                            <?php
                            $safetyScore = (int)$property['safety_score'];
                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= $safetyScore):
                            ?>
                                <i class="fas fa-star text-warning fa-2x me-1"></i>
                            <?php else: ?>
                                <i class="far fa-star text-warning fa-2x me-1"></i>
                            <?php endif; endfor; ?>
                            <span class="ms-2 h4 mb-0"><?php echo $property['safety_score']; ?>/5.0</span>
                        </div>
                    </div>
                </div>

                <!-- Property Statistics -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Property Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <i class="fas fa-eye fa-2x text-primary mb-2"></i>
                                <h4><?php echo $stats['view_count'] ?? 0; ?></h4>
                                <small class="text-muted">Views</small>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                                <h4><?php echo $stats['total_wishlist'] ?? 0; ?></h4>
                                <small class="text-muted">Wishlists</small>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                <h4><?php echo $stats['total_bookings'] ?? 0; ?></h4>
                                <small class="text-muted">Bookings</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Landlord Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-tie"></i> Landlord Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width: 80px; height: 80px; font-size: 32px;">
                                <?php echo strtoupper(substr($property['landlord_name'], 0, 2)); ?>
                            </div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($property['landlord_name']); ?></h5>
                            <?php if ($property['landlord_verified']): ?>
                                <small class="text-success">
                                    <i class="fas fa-check-circle"></i> Verified Landlord
                                </small>
                            <?php else: ?>
                                <small class="text-warning">
                                    <i class="fas fa-clock"></i> Unverified
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-2">
                            <small class="text-muted">Email:</small>
                            <div><?php echo htmlspecialchars($property['landlord_email']); ?></div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">Phone:</small>
                            <div><?php echo htmlspecialchars($property['landlord_phone']); ?></div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="mailto:<?php echo htmlspecialchars($property['landlord_email']); ?>"
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-envelope"></i> Email Landlord
                            </a>
                            <a href="tel:<?php echo htmlspecialchars($property['landlord_phone']); ?>"
                               class="btn btn-outline-success btn-sm">
                                <i class="fas fa-phone"></i> Call Landlord
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Verification Checklist -->
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Review Checklist</h5>
                    </div>
                    <div class="card-body">
                        <div class="verification-checklist">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check1">
                                <label class="form-check-label" for="check1">
                                    Property images are clear and accurate
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check2">
                                <label class="form-check-label" for="check2">
                                    Location information is valid
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check3">
                                <label class="form-check-label" for="check3">
                                    Pricing is reasonable and verified
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check4">
                                <label class="form-check-label" for="check4">
                                    Amenities match description
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="check5">
                                <label class="form-check-label" for="check5">
                                    Landlord information verified
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check6">
                                <label class="form-check-label" for="check6">
                                    No policy violations detected
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Actions</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($property['status'] === 'pending'): ?>
                            <div class="d-grid gap-2 mb-3">
                                <button class="btn btn-success btn-lg" onclick="approveProperty(<?php echo $propertyId; ?>)">
                                    <i class="fas fa-check-circle"></i> Approve Property
                                </button>
                                <button class="btn btn-danger btn-lg" onclick="rejectProperty(<?php echo $propertyId; ?>)">
                                    <i class="fas fa-times-circle"></i> Reject Property
                                </button>
                            </div>
                            <hr>
                        <?php elseif ($property['status'] === 'active'): ?>
                            <div class="alert alert-success mb-3">
                                <i class="fas fa-check-circle"></i> This property is <strong>approved</strong> and visible to students.
                            </div>
                            <div class="d-grid gap-2 mb-3">
                                <button class="btn btn-warning" onclick="deactivateProperty(<?php echo $propertyId; ?>)">
                                    <i class="fas fa-ban"></i> Deactivate Property
                                </button>
                            </div>
                            <hr>
                        <?php elseif ($property['status'] === 'rejected'): ?>
                            <div class="alert alert-danger mb-3">
                                <i class="fas fa-times-circle"></i> This property was <strong>rejected</strong>.
                                <?php if ($property['rejection_reason']): ?>
                                    <hr>
                                    <strong>Reason:</strong> <?php echo htmlspecialchars($property['rejection_reason']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="d-grid gap-2 mb-3">
                                <button class="btn btn-success" onclick="approveProperty(<?php echo $propertyId; ?>)">
                                    <i class="fas fa-check-circle"></i> Approve Property
                                </button>
                            </div>
                            <hr>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        // Change main image
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
        }

        // Approve Property
        function approveProperty(propertyId) {
            if (!confirm('Are you sure you want to APPROVE this property?\n\nThe property will become visible to students immediately.')) {
                return;
            }

            fetch('../actions/admin_properties_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=approve&property_id=${propertyId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to approve property');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Reject Property
        function rejectProperty(propertyId) {
            const reason = prompt('Please provide a reason for rejection:\n\n(This will be sent to the landlord)');

            if (!reason || reason.trim() === '') {
                alert('Rejection reason is required');
                return;
            }

            if (!confirm('Are you sure you want to REJECT this property?\n\nThe landlord will be notified with your reason.')) {
                return;
            }

            fetch('../actions/admin_properties_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=reject&property_id=${propertyId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to reject property');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Deactivate Property
        function deactivateProperty(propertyId) {
            const reason = prompt('Please provide a reason for deactivation:');

            if (!reason || reason.trim() === '') {
                return;
            }

            if (!confirm('Are you sure you want to DEACTIVATE this property?\n\nIt will no longer be visible to students.')) {
                return;
            }

            fetch('../actions/admin_properties_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=deactivate&property_id=${propertyId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to deactivate property');
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
