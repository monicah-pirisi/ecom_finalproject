<?php
/**
 * CampusDigs Kenya - Student Wishlist Page
 * View and manage saved properties
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/wishlist_controller.php';

// Require student authentication
requireStudent();

$studentId = $_SESSION['user_id'];

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;

// Get wishlist
$result = getStudentWishlist($studentId, $page, $perPage);
$properties = $result['properties'];
$totalProperties = $result['total'];
$totalPages = $result['pages'];

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <!-- Include Header -->
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../dashboard_student.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="all_properties.php">
                                <i class="fas fa-search"></i> Browse Properties
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="student_wishlist.php">
                                <i class="fas fa-heart"></i> My Wishlist
                                <?php if ($totalProperties > 0): ?>
                                    <span class="badge bg-danger rounded-pill"><?php echo $totalProperties; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_bookings.php">
                                <i class="fas fa-calendar-check"></i> My Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-muted" href="help.php">
                                <i class="fas fa-question-circle"></i> Help & Support
                            </a>
                        </li>
                        <li class="nav-item">
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
                        <i class="fas fa-heart text-danger"></i> My Wishlist
                    </h1>
                    <?php if ($totalProperties > 0): ?>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-outline-danger" id="clearWishlistBtn">
                                <i class="fas fa-trash"></i> Clear All
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Flash Message -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Wishlist Summary -->
                <?php if ($totalProperties > 0): ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="fas fa-info-circle fa-2x me-3"></i>
                        <div>
                            <strong>You have <?php echo $totalProperties; ?> propert<?php echo $totalProperties === 1 ? 'y' : 'ies'; ?> in your wishlist</strong>
                            <p class="mb-0 small">Review your saved properties and book when ready!</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Wishlist Grid -->
                <?php if (empty($properties)): ?>
                    <div class="text-center py-5" id="wishlistContainer">
                        <i class="fas fa-heart fa-5x text-muted mb-4"></i>
                        <h3 class="text-muted">Your wishlist is empty</h3>
                        <p class="text-muted mb-4">Save properties you love and come back to them later!</p>
                        <a href="all_properties.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-search"></i> Browse Properties
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row" id="wishlistContainer" data-user-logged-in="true">
                        <?php foreach ($properties as $property): ?>
                            <div class="col-md-6 col-lg-4 mb-4" data-property-id="<?php echo $property['id']; ?>">
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
                                        
                                        <!-- Remove Button -->
                                        <button class="btn btn-wishlist active" 
                                                onclick="removeFromWishlistPage(<?php echo $property['id']; ?>, this.closest('[data-property-id]'))">
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
                                        <p class="card-text text-muted small mb-2">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars($property['location']); ?>
                                        </p>
                                        
                                        <!-- Date Added -->
                                        <p class="card-text text-muted small mb-2">
                                            <i class="fas fa-clock"></i> 
                                            Added <?php echo timeAgo($property['added_on']); ?>
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
                                            <small class="text-muted">(<?php echo $property['view_count']; ?> views)</small>
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
                                            <?php endforeach;
                                                if (count($amenities) > 3):
                                            ?>
                                                <span class="badge bg-light text-dark">
                                                    +<?php echo count($amenities) - 3; ?> more
                                                </span>
                                            <?php endif; endif; ?>
                                        </div>
                                        
                                        <!-- Price -->
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h4 class="text-primary mb-0">
                                                <?php echo formatCurrency($property['price_monthly']); ?>
                                                <small class="text-muted">/month</small>
                                            </h4>
                                        </div>
                                        
                                        <!-- Availability Status -->
                                        <?php if ($property['status'] !== 'active'): ?>
                                            <div class="alert alert-warning py-2 mb-3">
                                                <small><i class="fas fa-exclamation-triangle"></i> No longer available</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-footer bg-white">
                                        <div class="d-grid gap-2">
                                            <a href="single_property.php?id=<?php echo $property['id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="removeFromWishlistPage(<?php echo $property['id']; ?>, this.closest('[data-property-id]'))">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Wishlist pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <!-- Previous Button -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                                
                                <!-- Page Numbers -->
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <!-- Next Button -->
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Define BASE_URL for JavaScript -->
    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>

    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    <script src="../js/wishlist.js"></script>

    <script>
        // Remove from wishlist (specific to wishlist page)
        function removeFromWishlistPage(propertyId, card) {
            if (!confirm('Remove this property from your wishlist?')) {
                return;
            }
            
            // Show loading
            card.style.opacity = '0.5';
            card.style.pointerEvents = 'none';
            
            // Send AJAX request
            fetch('../actions/wishlist_remove_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'property_id=' + propertyId,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animate removal
                    card.style.transition = 'all 0.3s ease';
                    card.style.transform = 'scale(0.8)';
                    card.style.opacity = '0';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        // Check if wishlist is empty
                        const container = document.getElementById('wishlistContainer');
                        if (container && container.children.length === 0) {
                            location.reload();
                        }
                    }, 300);
                    
                    if (window.campusDigs) {
                        window.campusDigs.showNotification('Property removed from wishlist', 'success');
                    }
                } else {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                    alert(data.message || 'Failed to remove property');
                }
            })
            .catch(error => {
                console.error('Remove error:', error);
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html>