<?php
/**
 * CampusDigs Kenya - Single Property Page
 * Detailed property information and booking
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/wishlist_controller.php';

// Allow logged-in users (students, landlords, admin) to view properties
// Guests can also view but cannot book or wishlist
$studentId = (isLoggedIn() && $_SESSION['user_type'] === 'student') ? $_SESSION['user_id'] : null;

// Get property ID
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$propertyId) {
    redirectWithMessage('all_properties.php', 'Property not found', 'error');
}

// Get property details
$property = getPropertyById($propertyId);

if (!$property) {
    redirectWithMessage('all_properties.php', 'Property not found', 'error');
}

// Increment view count
incrementPropertyViews($propertyId);

// Check if in wishlist (only for students)
$isInWishlist = $studentId ? isInWishlist($studentId, $propertyId) : false;

// Get property images
$images = getPropertyImages($propertyId);

// Get flash message
$flash = getFlashMessage();

// Calculate semester total
$semesterTotal = ($property['price_monthly'] * 4) + $property['security_deposit'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/property-details.css?v=1">

    <style>
        .property-main-image {
            height: 500px;
            object-fit: cover;
            cursor: zoom-in;
            border-radius: 12px;
        }
        .property-thumbnail {
            height: 120px;
            object-fit: cover;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 8px;
            border: 3px solid transparent;
        }
        .property-thumbnail:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .property-thumbnail.active {
            border-color: #059669;
            box-shadow: 0 0 15px rgba(5, 150, 105, 0.4);
        }
        .landlord-card {
            position: sticky;
            top: 80px;
        }
        .image-counter {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }
        .zoom-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #059669;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .zoom-icon:hover {
            background: #059669;
            color: white;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-4" data-user-logged-in="true" data-property-id="<?php echo $propertyId; ?>">
        <!-- Back Button -->
        <?php
        // Dynamic back button based on user type
        if (isLoggedIn() && $_SESSION['user_type'] === 'admin') {
            // Admin viewing property - go back to admin panel
            $backUrl = '../admin/manage_properties.php';
            $backText = 'Back to Admin Panel';
        } elseif (isLoggedIn() && $_SESSION['user_type'] === 'landlord' && $property['landlord_id'] == $_SESSION['user_id']) {
            // Landlord viewing their own property - go back to their properties
            $backUrl = 'landlord/my_properties.php';
            $backText = 'Back to My Properties';
        } else {
            // Student or other user - go back to all properties
            $backUrl = 'all_properties.php';
            $backText = 'Back to Properties';
        }
        ?>
        <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left"></i> <?php echo $backText; ?>
        </a>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column - Property Details -->
            <div class="col-lg-8">
                <!-- Property Images -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-0">
                        <?php if (!empty($images)): ?>
                            <div class="position-relative">
                                <img src="<?php echo BASE_URL . '/' . $images[0]['image_path']; ?>"
                                     class="card-img-top property-main-image"
                                     id="mainImage"
                                     onclick="openLightbox(0)"
                                     alt="<?php echo htmlspecialchars($property['title']); ?>"
                                     loading="eager">

                                <!-- Zoom Icon -->
                                <div class="zoom-icon" onclick="openLightbox(0)" title="View Full Screen">
                                    <i class="fas fa-search-plus"></i>
                                </div>

                                <!-- Image Counter -->
                                <?php if (count($images) > 1): ?>
                                    <div class="image-counter">
                                        <i class="fas fa-images"></i>
                                        <span id="currentImageIndex">1</span> / <?php echo count($images); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (count($images) > 1): ?>
                                <div class="p-3 bg-light">
                                    <div class="row g-3" id="thumbnailGallery">
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="col-6 col-md-3">
                                                <img src="<?php echo BASE_URL . '/' . $image['image_path']; ?>"
                                                     class="property-thumbnail w-100 <?php echo $index === 0 ? 'active' : ''; ?>"
                                                     onclick="changeMainImage(this.src, <?php echo $index; ?>)"
                                                     data-index="<?php echo $index; ?>"
                                                     alt="Property thumbnail"
                                                     loading="lazy">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <img src="<?php echo IMAGES_URL; ?>/default-property.png"
                                 class="card-img-top property-main-image"
                                 alt="Default Property"
                                 loading="eager">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Property Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h1 class="h3 mb-2"><?php echo htmlspecialchars($property['title']); ?></h1>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($property['location']); ?>
                                </p>
                                <?php if ($property['distance_from_campus']): ?>
                                    <p class="text-muted small">
                                        <i class="fas fa-walking"></i> 
                                        <?php echo $property['distance_from_campus']; ?>km from <?php echo htmlspecialchars($property['university_nearby'] ?? 'campus'); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php if ($studentId): ?>
                                <button class="btn btn-wishlist btn-lg <?php echo $isInWishlist ? 'active' : ''; ?>">
                                    <i class="fas fa-heart"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Verified Badge -->
                        <?php if ($property['is_verified']): ?>
                            <span class="badge bg-success mb-3">
                                <i class="fas fa-shield-alt"></i> Verified Property
                            </span>
                        <?php endif; ?>

                        <!-- Safety Score -->
                        <div class="alert alert-info d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Safety Score</h6>
                                <div>
                                    <?php
                                    $safetyScore = (int)$property['safety_score'];
                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= $safetyScore):
                                    ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-warning"></i>
                                    <?php endif; endfor; ?>
                                    <span class="ms-2"><?php echo $property['safety_score']; ?>/5.0</span>
                                </div>
                            </div>
                            <i class="fas fa-shield-alt fa-3x text-info"></i>
                        </div>

                        <!-- Description -->
                        <h5 class="mb-3">About This Property</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>

                        <!-- Property Details -->
                        <h5 class="mb-3 mt-4">Property Details</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-door-open fa-2x text-primary me-3"></i>
                                    <div>
                                        <small class="text-muted">Room Type</small>
                                        <div class="fw-bold"><?php echo ROOM_TYPES[$property['room_type']] ?? $property['room_type']; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users fa-2x text-primary me-3"></i>
                                    <div>
                                        <small class="text-muted">Capacity</small>
                                        <div class="fw-bold"><?php echo $property['capacity']; ?> Person(s)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-alt fa-2x text-primary me-3"></i>
                                    <div>
                                        <small class="text-muted">Minimum Lease</small>
                                        <div class="fw-bold"><?php echo $property['min_lease_months']; ?> Months</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-eye fa-2x text-primary me-3"></i>
                                    <div>
                                        <small class="text-muted">Views</small>
                                        <div class="fw-bold"><?php echo $property['view_count']; ?> Views</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <h5 class="mb-3 mt-4">Amenities & Features</h5>
                        <div class="row g-3">
                            <?php
                            $amenities = json_decode($property['amenities'], true);
                            if ($amenities && is_array($amenities)):
                                foreach ($amenities as $amenity):
                            ?>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span><?php echo htmlspecialchars($amenity); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                            
                            <!-- Security Features -->
                            <?php if ($property['has_cctv']): ?>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>CCTV Surveillance</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($property['has_security_guard']): ?>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>24/7 Security Guard</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($property['has_secure_entry']): ?>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span>Secure Entry System</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Booking & Landlord Info -->
            <div class="col-lg-4">
                <!-- Booking Card -->
                <div class="card shadow-sm mb-4 landlord-card">
                    <div class="card-body">
                        <h4 class="text-primary mb-3">
                            <?php echo formatCurrency($property['price_monthly']); ?>
                            <small class="text-muted">/month</small>
                        </h4>

                        <div class="mb-3">
                            <small class="text-muted">Security Deposit:</small>
                            <div class="fw-bold"><?php echo formatCurrency($property['security_deposit']); ?></div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">4-Month Semester Total:</small>
                            <div class="h5 text-success mb-0"><?php echo formatCurrency($semesterTotal); ?></div>
                        </div>

                        <hr>

                        <?php if ($studentId): ?>
                            <!-- Booking Form (Students Only) -->
                            <form action="booking_process.php" method="POST" id="bookingForm">
                                <?php csrfTokenField(); ?>
                                <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
                                <input type="hidden" name="landlord_id" value="<?php echo $property['landlord_id']; ?>">

                                <div class="mb-3">
                                    <label class="form-label">Lease Duration</label>
                                    <select name="lease_duration" class="form-select" required>
                                        <option value="4">4 Months (Semester) - <?php echo formatCurrency($property['price_monthly'] * 4); ?></option>
                                        <option value="10">10 Months (Academic Year) - <?php echo formatCurrency($property['price_monthly'] * 10); ?></option>
                                        <option value="12">12 Months (Full Year) - <?php echo formatCurrency($property['price_monthly'] * 12); ?></option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Move-in Date</label>
                                    <input type="date" name="move_in_date" class="form-control"
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Message to Landlord (Optional)</label>
                                    <textarea name="message" class="form-control" rows="3"
                                              placeholder="Introduce yourself..."></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-calendar-check"></i> Request Booking
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="window.print()">
                                        <i class="fas fa-print"></i> Print Details
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Non-Student View -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <?php if (isLoggedIn()): ?>
                                    <?php if ($_SESSION['user_type'] === 'landlord'): ?>
                                        <strong>Landlord Preview</strong><br>
                                        This is how your property appears to students.
                                    <?php else: ?>
                                        <strong>Admin Preview</strong><br>
                                        This is the public property listing view.
                                    <?php endif; ?>
                                <?php else: ?>
                                    <strong>Login Required</strong><br>
                                    Please <a href="../login/login.php">login as a student</a> to book this property.
                                <?php endif; ?>
                            </div>
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="window.print()">
                                    <i class="fas fa-print"></i> Print Details
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Landlord Info Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Landlord Information</h5>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                 style="width: 60px; height: 60px; font-size: 24px;">
                                <?php echo strtoupper(substr($property['landlord_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($property['landlord_name']); ?></h6>
                                <?php if ($property['landlord_verified']): ?>
                                    <small class="text-success">
                                        <i class="fas fa-check-circle"></i> Verified Landlord
                                    </small>
                                <?php endif; ?>
                            </div>
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
                            <a href="tel:<?php echo $property['landlord_phone']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-phone"></i> Call Landlord
                            </a>
                            <a href="https://wa.me/<?php echo str_replace('+', '', $property['landlord_phone']); ?>" 
                               target="_blank" class="btn btn-success">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Safety Notice -->
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Safety First!</h6>
                    <small>Always visit the property before making payments. Report suspicious landlords immediately.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Lightbox Modal -->
    <?php if (!empty($images)): ?>
    <div class="modal fade" id="imageLightbox" tabindex="-1" aria-labelledby="lightboxLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-white" id="lightboxLabel">
                        <?php echo htmlspecialchars($property['title']); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center position-relative p-0">
                    <!-- Previous Button -->
                    <button class="btn btn-light position-absolute start-0 ms-3 rounded-circle"
                            style="width: 50px; height: 50px; z-index: 10;"
                            onclick="changeLightboxImage(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>

                    <!-- Main Lightbox Image -->
                    <img id="lightboxImage"
                         src=""
                         alt="Property Image"
                         class="img-fluid"
                         style="max-height: 90vh; object-fit: contain;">

                    <!-- Next Button -->
                    <button class="btn btn-light position-absolute end-0 me-3 rounded-circle"
                            style="width: 50px; height: 50px; z-index: 10;"
                            onclick="changeLightboxImage(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>

                    <!-- Image Counter -->
                    <div class="position-absolute bottom-0 mb-3 text-white bg-dark bg-opacity-75 px-3 py-2 rounded">
                        <span id="lightboxCounter">1 / <?php echo count($images); ?></span>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <small class="text-white-50">Use arrow keys to navigate • Click outside to close</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>

    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    <script src="../js/wishlist.js"></script>

    <script>
        // Store all images
        const propertyImages = [
            <?php
            if (!empty($images)) {
                foreach ($images as $index => $image) {
                    echo "'" . BASE_URL . '/' . $image['image_path'] . "'";
                    if ($index < count($images) - 1) echo ", ";
                }
            }
            ?>
        ];

        let currentImageIndex = 0;

        // Change main image
        function changeMainImage(src, index) {
            const mainImage = document.getElementById('mainImage');
            mainImage.src = src;
            currentImageIndex = index;

            // Update counter
            const counter = document.getElementById('currentImageIndex');
            if (counter) {
                counter.textContent = index + 1;
            }

            // Update active thumbnail
            document.querySelectorAll('.property-thumbnail').forEach((thumb, i) => {
                if (i === index) {
                    thumb.classList.add('active');
                } else {
                    thumb.classList.remove('active');
                }
            });

            // Update zoom icon onclick
            document.querySelector('.zoom-icon').setAttribute('onclick', `openLightbox(${index})`);
        }

        // Open lightbox
        function openLightbox(index) {
            if (propertyImages.length === 0) return;

            currentImageIndex = index;
            const lightboxImage = document.getElementById('lightboxImage');
            const lightboxCounter = document.getElementById('lightboxCounter');

            lightboxImage.src = propertyImages[currentImageIndex];
            if (lightboxCounter) {
                lightboxCounter.textContent = `${currentImageIndex + 1} / ${propertyImages.length}`;
            }

            const modal = new bootstrap.Modal(document.getElementById('imageLightbox'));
            modal.show();
        }

        // Change lightbox image
        function changeLightboxImage(direction) {
            currentImageIndex += direction;

            // Loop around
            if (currentImageIndex < 0) {
                currentImageIndex = propertyImages.length - 1;
            } else if (currentImageIndex >= propertyImages.length) {
                currentImageIndex = 0;
            }

            const lightboxImage = document.getElementById('lightboxImage');
            const lightboxCounter = document.getElementById('lightboxCounter');

            lightboxImage.src = propertyImages[currentImageIndex];
            if (lightboxCounter) {
                lightboxCounter.textContent = `${currentImageIndex + 1} / ${propertyImages.length}`;
            }
        }

        // Keyboard navigation for lightbox
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('imageLightbox');
            if (modal && modal.classList.contains('show')) {
                if (e.key === 'ArrowLeft') {
                    changeLightboxImage(-1);
                } else if (e.key === 'ArrowRight') {
                    changeLightboxImage(1);
                } else if (e.key === 'Escape') {
                    bootstrap.Modal.getInstance(modal).hide();
                }
            }
        });

        // Form validation (only if booking form exists - for students)
        const bookingForm = document.getElementById('bookingForm');
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(e) {
                const moveInDate = new Date(this.move_in_date.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (moveInDate < today) {
                    e.preventDefault();
                    alert('Move-in date cannot be in the past');
                    return false;
                }

                if (!confirm('Are you sure you want to request this booking?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    </script>
</body>
</html>