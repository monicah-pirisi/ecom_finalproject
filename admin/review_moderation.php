<?php
/**
 * CampusDigs Kenya - Admin Review Moderation
 * Detailed review moderation page with admin actions
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/review_controller.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/user_controller.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get review ID
$reviewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reviewId) {
    $_SESSION['error'] = 'Invalid review ID';
    header('Location: moderate_reviews.php');
    exit();
}

// Get review details
$review = getReviewById($reviewId);

if (!$review) {
    $_SESSION['error'] = 'Review not found';
    header('Location: moderate_reviews.php');
    exit();
}

// Get property details
$property = getPropertyById($review['property_id']);
$propertyImages = getPropertyImages($review['property_id']);

// Get student/reviewer details
$student = getUserById($review['student_id']);

// Get student's review statistics
$studentReviewStats = getStudentReviewStats($review['student_id']);

// Get flag details if flagged
$flagDetails = [];
if ($review['moderation_status'] === 'flagged') {
    $flagDetails = getReviewFlagDetails($reviewId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review #<?php echo $reviewId; ?> - Moderation - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <style>
        .star-rating {
            color: #ffc107;
            font-size: 1.5rem;
        }
        .star-rating-small {
            color: #ffc107;
            font-size: 1rem;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.875rem;
        }
        .info-value {
            font-size: 1rem;
            font-weight: 500;
        }
        .checklist-item {
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 8px;
        }
        .checklist-item:hover {
            background-color: #f8f9fa;
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
        <a href="moderate_reviews.php" class="btn btn-outline-secondary mb-3 no-print">
            <i class="fas fa-arrow-left"></i> Back to Reviews
        </a>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show no-print">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Review Header -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-star"></i>
                        Review #<?php echo $reviewId; ?>
                    </h4>
                    <?php
                    $statusClass = [
                        'approved' => 'bg-success',
                        'pending' => 'bg-warning text-dark',
                        'flagged' => 'bg-danger',
                        'deleted' => 'bg-secondary'
                    ][$review['moderation_status']] ?? 'bg-secondary';
                    ?>
                    <span class="badge <?php echo $statusClass; ?> fs-6">
                        <?php echo ucfirst($review['moderation_status']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="info-label mb-1">Posted Date</p>
                        <p class="info-value"><?php echo formatDateTime($review['created_at']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="info-label mb-1">Last Updated</p>
                        <p class="info-value">
                            <?php echo $review['updated_at'] ? formatDateTime($review['updated_at']) : 'Never'; ?>
                            <?php if ($review['edited_by_admin']): ?>
                                <span class="badge bg-info">Edited by Admin</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Review Content -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-comment-alt text-primary"></i> Review Content</h5>
                    </div>
                    <div class="card-body">
                        <!-- Rating Display -->
                        <div class="mb-3">
                            <div class="star-rating mb-2">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $review['rating']) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <p class="text-muted mb-0"><?php echo $review['rating']; ?> out of 5 stars</p>
                        </div>

                        <!-- Review Text -->
                        <div class="mb-3">
                            <p class="info-label mb-2">Review Text:</p>
                            <div class="p-3 bg-light rounded">
                                <p class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($review['review_text']); ?></p>
                            </div>
                        </div>

                        <!-- Character/Word Count -->
                        <div class="d-flex justify-content-between text-muted small">
                            <span><?php echo str_word_count($review['review_text']); ?> words</span>
                            <span><?php echo strlen($review['review_text']); ?> characters</span>
                        </div>
                    </div>
                </div>

                <!-- Property Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-building text-warning"></i> Property Being Reviewed</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($propertyImages)): ?>
                            <img src="../../<?php echo htmlspecialchars($propertyImages[0]['image_path']); ?>"
                                 class="img-fluid rounded mb-3"
                                 alt="Property"
                                 style="max-height: 200px; width: 100%; object-fit: cover;">
                        <?php endif; ?>

                        <h6><?php echo htmlspecialchars($property['title']); ?></h6>
                        <p class="text-muted mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($property['location']); ?>
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="info-label mb-1">Property ID</p>
                                <p class="info-value">#<?php echo $property['id']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Average Rating</p>
                                <div class="star-rating-small">
                                    <?php
                                    $avgRating = round($property['average_rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $avgRating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <small class="text-muted"><?php echo number_format($property['average_rating'] ?? 0, 1); ?> (<?php echo $property['review_count'] ?? 0; ?> reviews)</small>
                            </div>
                            <div class="col-12 no-print">
                                <a href="property_review.php?id=<?php echo $property['id']; ?>"
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-building"></i> View Property Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student/Reviewer Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user-graduate text-primary"></i> Reviewer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="info-label mb-1">Full Name</p>
                                <p class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Student ID</p>
                                <p class="info-value">#<?php echo $student['id']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Email</p>
                                <p class="info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>">
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Account Status</p>
                                <p class="info-value">
                                    <?php if ($student['account_verified']): ?>
                                        <span class="badge bg-success">Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Unverified</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Total Reviews</p>
                                <p class="info-value"><?php echo $studentReviewStats['total_reviews']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="info-label mb-1">Average Rating Given</p>
                                <p class="info-value"><?php echo number_format($studentReviewStats['average_rating_given'], 1); ?> ★</p>
                            </div>
                            <div class="col-12 no-print">
                                <a href="user_details.php?id=<?php echo $student['id']; ?>"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Student Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flag Information (if flagged) -->
                <?php if ($review['moderation_status'] === 'flagged' && !empty($flagDetails)): ?>
                    <div class="card shadow-sm mb-4 border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-flag"></i> Flag Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <strong>This review has been flagged <?php echo count($flagDetails); ?> time<?php echo count($flagDetails) === 1 ? '' : 's'; ?></strong>
                            </div>

                            <?php foreach ($flagDetails as $flag): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <strong><?php echo htmlspecialchars($flag['flagger_name']); ?></strong>
                                        <small class="text-muted"><?php echo timeAgo($flag['flagged_at']); ?></small>
                                    </div>
                                    <p class="mb-1"><strong>Reason:</strong> <?php echo htmlspecialchars($flag['flag_reason']); ?></p>
                                    <?php if (!empty($flag['flag_details'])): ?>
                                        <p class="mb-0 small text-muted"><?php echo htmlspecialchars($flag['flag_details']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Moderation Checklist -->
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-tasks"></i> Moderation Checklist</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Check for the following issues before taking action:</p>

                        <div class="checklist-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>No inappropriate language</strong>
                            <p class="small text-muted mb-0">Profanity, vulgar, or offensive language</p>
                        </div>

                        <div class="checklist-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>No false information</strong>
                            <p class="small text-muted mb-0">Misleading or inaccurate claims</p>
                        </div>

                        <div class="checklist-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>Not spam content</strong>
                            <p class="small text-muted mb-0">Promotional links, repeated text, or irrelevant content</p>
                        </div>

                        <div class="checklist-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>No personal attacks</strong>
                            <p class="small text-muted mb-0">Attacks on landlord or other individuals</p>
                        </div>

                        <div class="checklist-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>No hate speech</strong>
                            <p class="small text-muted mb-0">Discriminatory or hateful language</p>
                        </div>

                        <div class="checklist-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>Appears genuine</strong>
                            <p class="small text-muted mb-0">Not fake or artificially positive/negative</p>
                        </div>
                    </div>
                </div>

                <!-- Admin Actions -->
                <div class="card shadow-sm mb-4 no-print">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-gavel"></i> Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($review['moderation_status'] !== 'approved'): ?>
                                <button class="btn btn-success btn-lg" onclick="approveReview(<?php echo $reviewId; ?>)">
                                    <i class="fas fa-check-circle"></i> Approve Review
                                </button>
                            <?php endif; ?>

                            <?php if ($review['moderation_status'] !== 'deleted'): ?>
                                <button class="btn btn-danger" onclick="deleteReview(<?php echo $reviewId; ?>)">
                                    <i class="fas fa-trash"></i> Delete Review
                                </button>
                            <?php endif; ?>

                            <?php if ($review['moderation_status'] !== 'flagged'): ?>
                                <button class="btn btn-warning" onclick="flagReview(<?php echo $reviewId; ?>)">
                                    <i class="fas fa-flag"></i> Flag for Attention
                                </button>
                            <?php endif; ?>

                            <button class="btn btn-info" onclick="editReview(<?php echo $reviewId; ?>)">
                                <i class="fas fa-edit"></i> Edit Review
                            </button>

                            <hr>

                            <a href="moderate_reviews.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Days Since Posted</small>
                            <div class="h5 mb-0">
                                <?php
                                $daysOld = floor((time() - strtotime($review['created_at'])) / 86400);
                                echo $daysOld . ' day' . ($daysOld !== 1 ? 's' : '');
                                ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Helpful Votes</small>
                            <div class="h5 mb-0"><?php echo $review['helpful_count'] ?? 0; ?></div>
                        </div>
                        <div>
                            <small class="text-muted">Report Count</small>
                            <div class="h5 mb-0 <?php echo ($review['flag_count'] ?? 0) > 0 ? 'text-danger' : ''; ?>">
                                <?php echo $review['flag_count'] ?? 0; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Review Modal -->
    <div class="modal fade" id="editReviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Edit the review to remove inappropriate content. The review will be marked as "Edited by Admin".
                    </div>
                    <form id="editReviewForm">
                        <div class="mb-3">
                            <label class="form-label">Review Text:</label>
                            <textarea class="form-control" id="editedReviewText" rows="6"><?php echo htmlspecialchars($review['review_text']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Edit Reason (for audit log):</label>
                            <input type="text" class="form-control" id="editReason" placeholder="e.g., Removed profanity" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditReview(<?php echo $reviewId; ?>)">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        function approveReview(reviewId) {
            if (!confirm('Are you sure you want to APPROVE this review?\n\nThis will make it visible to all users.')) {
                return;
            }

            fetch('../actions/admin_reviews_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=approve&review_id=${reviewId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to approve review');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function deleteReview(reviewId) {
            const reason = prompt('Enter reason for deleting this review (required for audit):');

            if (!reason || reason.trim() === '') {
                alert('Deletion reason is required');
                return;
            }

            if (!confirm('Are you sure you want to DELETE this review?\n\nThis action cannot be undone.')) {
                return;
            }

            fetch('../actions/admin_reviews_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&review_id=${reviewId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'moderate_reviews.php';
                } else {
                    alert(data.message || 'Failed to delete review');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function flagReview(reviewId) {
            const reason = prompt('Enter reason for flagging this review:');

            if (!reason || reason.trim() === '') {
                alert('Flag reason is required');
                return;
            }

            fetch('../actions/admin_reviews_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=flag&review_id=${reviewId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to flag review');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        function editReview(reviewId) {
            const modal = new bootstrap.Modal(document.getElementById('editReviewModal'));
            modal.show();
        }

        function submitEditReview(reviewId) {
            const editedText = document.getElementById('editedReviewText').value;
            const editReason = document.getElementById('editReason').value;

            if (!editedText.trim()) {
                alert('Review text cannot be empty');
                return;
            }

            if (!editReason.trim()) {
                alert('Edit reason is required');
                return;
            }

            fetch('../actions/admin_reviews_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=edit&review_id=${reviewId}&review_text=${encodeURIComponent(editedText)}&edit_reason=${encodeURIComponent(editReason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to edit review');
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
