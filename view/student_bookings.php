<?php
/**
 * CampusDigs Kenya - Student Bookings Page
 * View and manage all bookings
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/booking_controller.php';

requireStudent();

$studentId = $_SESSION['user_id'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;

$result = getStudentBookings($studentId, $page, $perPage);
$bookings = $result['bookings'];
$totalBookings = $result['total'];
$totalPages = $result['pages'];

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../dashboard_student.php"><i class="fas fa-home"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="all_properties.php"><i class="fas fa-search"></i> Browse Properties</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_wishlist.php"><i class="fas fa-heart"></i> My Wishlist</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="student_bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payment_history.php"><i class="fas fa-receipt"></i> Payment History</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_profile.php"><i class="fas fa-user"></i> My Profile</a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-muted" href="help.php"><i class="fas fa-question-circle"></i> Help & Support</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-calendar-check text-primary"></i> My Bookings</h1>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($bookings)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-5x text-muted mb-4"></i>
                        <h3 class="text-muted">No bookings yet</h3>
                        <p class="text-muted mb-4">Start exploring properties and make your first booking!</p>
                        <a href="all_properties.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-search"></i> Browse Properties
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($bookings as $booking): 
                            $statusClass = [
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'secondary',
                                'completed' => 'info'
                            ];
                            $class = $statusClass[$booking['status']] ?? 'secondary';
                        ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-<?php echo $class; ?> text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold">Booking #<?php echo $booking['booking_reference']; ?></span>
                                            <span class="badge bg-light text-dark"><?php echo ucfirst($booking['status']); ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title mb-3"><?php echo htmlspecialchars($booking['property_title']); ?></h5>
                                        
                                        <div class="row g-3 mb-3">
                                            <div class="col-6">
                                                <small class="text-muted">Location</small>
                                                <div class="small"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['property_location']); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Move-in Date</small>
                                                <div class="small"><i class="fas fa-calendar"></i> <?php echo formatDate($booking['move_in_date']); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Duration</small>
                                                <div class="small"><i class="fas fa-clock"></i> <?php echo $booking['lease_duration_months']; ?> Months</div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Total Amount</small>
                                                <div class="small fw-bold text-success"><?php echo formatCurrency($booking['total_amount']); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Payment Status</small>
                                                <div class="small">
                                                    <?php
                                                    $payment_status = $booking['payment_status'] ?? 'pending';
                                                    $payment_badge_class = [
                                                        'paid' => 'bg-success',
                                                        'pending' => 'bg-warning',
                                                        'refunded' => 'bg-info'
                                                    ];
                                                    $payment_class = $payment_badge_class[$payment_status] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?php echo $payment_class; ?>">
                                                        <?php echo ucfirst($payment_status); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <?php if (isset($booking['payment_status']) && $booking['payment_status'] === 'pending'): ?>
                                                <a href="booking_payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-credit-card"></i> Pay Now
                                                </a>
                                            <?php endif; ?>
                                            <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                            <?php if ($booking['status'] === 'completed'): ?>
                                                <?php if ($booking['has_review']): ?>
                                                    <button class="btn btn-outline-success btn-sm" disabled>
                                                        <i class="fas fa-check-circle"></i> Review Submitted
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-warning btn-sm" onclick="openReviewModal(<?php echo $booking['id']; ?>, <?php echo $booking['property_id']; ?>, '<?php echo htmlspecialchars($booking['property_title'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-star"></i> Leave a Review
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <button class="btn btn-outline-danger btn-sm" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-times"></i> Cancel Booking
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light small text-muted">
                                        <i class="fas fa-clock"></i> Booked <?php echo timeAgo($booking['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="reviewModalLabel">
                        <i class="fas fa-star"></i> Leave a Review
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reviewForm">
                        <input type="hidden" id="review_booking_id" name="booking_id">
                        <input type="hidden" id="review_property_id" name="property_id">

                        <div class="mb-3">
                            <h6 class="mb-2" id="property_title_display"></h6>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Rating <span class="text-danger">*</span></label>
                            <div class="star-rating">
                                <i class="far fa-star star-icon" data-rating="1"></i>
                                <i class="far fa-star star-icon" data-rating="2"></i>
                                <i class="far fa-star star-icon" data-rating="3"></i>
                                <i class="far fa-star star-icon" data-rating="4"></i>
                                <i class="far fa-star star-icon" data-rating="5"></i>
                            </div>
                            <input type="hidden" id="rating" name="rating" required>
                            <div class="invalid-feedback" id="rating-error">
                                Please select a rating
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label fw-bold">Your Review</label>
                            <textarea
                                class="form-control"
                                id="comment"
                                name="comment"
                                rows="4"
                                placeholder="Share your experience with this property..."
                                maxlength="1000"></textarea>
                            <small class="text-muted">Optional - Maximum 1000 characters</small>
                        </div>

                        <div class="alert alert-info small mb-0">
                            <i class="fas fa-info-circle"></i> Your review will be moderated before being published.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitReview()">
                        <i class="fas fa-paper-plane"></i> Submit Review
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/bookings.js"></script>
    <script>
        let selectedRating = 0;
        let reviewModal = null;

        document.addEventListener('DOMContentLoaded', function() {
            reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));

            // Star rating click handlers
            const stars = document.querySelectorAll('.star-icon');
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    selectedRating = parseInt(this.getAttribute('data-rating'));
                    document.getElementById('rating').value = selectedRating;
                    updateStarDisplay(selectedRating);
                    document.getElementById('rating-error').style.display = 'none';
                });

                // Hover effect
                star.addEventListener('mouseenter', function() {
                    const hoverRating = parseInt(this.getAttribute('data-rating'));
                    updateStarDisplay(hoverRating);
                });
            });

            // Reset star display on mouse leave
            document.querySelector('.star-rating').addEventListener('mouseleave', function() {
                updateStarDisplay(selectedRating);
            });
        });

        function updateStarDisplay(rating) {
            const stars = document.querySelectorAll('.star-icon');
            stars.forEach(star => {
                const starValue = parseInt(star.getAttribute('data-rating'));
                if (starValue <= rating) {
                    star.classList.remove('far');
                    star.classList.add('fas');
                    star.style.color = '#ffc107';
                } else {
                    star.classList.remove('fas');
                    star.classList.add('far');
                    star.style.color = '#6c757d';
                }
            });
        }

        function openReviewModal(bookingId, propertyId, propertyTitle) {
            // Reset form
            document.getElementById('reviewForm').reset();
            selectedRating = 0;
            updateStarDisplay(0);
            document.getElementById('rating-error').style.display = 'none';

            // Set values
            document.getElementById('review_booking_id').value = bookingId;
            document.getElementById('review_property_id').value = propertyId;
            document.getElementById('property_title_display').textContent = propertyTitle;

            // Show modal
            reviewModal.show();
        }

        function submitReview() {
            const bookingId = document.getElementById('review_booking_id').value;
            const propertyId = document.getElementById('review_property_id').value;
            const rating = document.getElementById('rating').value;
            const comment = document.getElementById('comment').value;

            // Validate rating
            if (!rating || rating < 1 || rating > 5) {
                document.getElementById('rating-error').style.display = 'block';
                return;
            }

            // Disable submit button
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            // Submit via AJAX
            fetch('../actions/submit_review_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    booking_id: bookingId,
                    property_id: propertyId,
                    rating: rating,
                    comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    reviewModal.hide();

                    // Show success message
                    alert(data.message);

                    // Reload page to show updated status
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your review. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        function cancelBooking(bookingId) {
            if (!confirm('Are you sure you want to cancel this booking? Refund policy will apply.')) return;

            const reason = prompt('Please provide a reason for cancellation:');
            if (!reason) return;

            window.location.href = `../actions/cancel_booking_action.php?id=${bookingId}&reason=${encodeURIComponent(reason)}`;
        }
    </script>

    <style>
        .star-rating {
            font-size: 2rem;
            cursor: pointer;
            user-select: none;
        }
        .star-icon {
            color: #6c757d;
            transition: color 0.2s ease;
            margin: 0 2px;
        }
        .star-icon:hover {
            transform: scale(1.1);
        }
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</body>
</html>