<?php
/**
 * CampusDigs Kenya - Guest Wishlist Page
 * Shows wishlist for non-logged-in users
 * Encourages registration/login to book properties
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/property_controller.php';

// Get guest wishlist property IDs
$guestWishlist = getGuestWishlist();
$wishlistCount = count($guestWishlist);

// Get property details for each wishlist item
$properties = [];
if (!empty($guestWishlist)) {
    foreach ($guestWishlist as $propertyId) {
        $property = getPropertyById($propertyId);
        if ($property && $property['status'] === 'active') {
            $properties[] = $property;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .property-card {
            transition: transform 0.2s;
        }
        .property-card:hover {
            transform: translateY(-5px);
        }
        .register-prompt {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Registration Prompt -->
        <div class="register-prompt text-center">
            <h2><i class="fas fa-heart"></i> Your Property Wishlist</h2>
            <p class="lead">You have <?php echo $wishlistCount; ?> property saved</p>
            <p>Create a free account to save your wishlist permanently and book properties!</p>
            <div class="mt-3">
                <a href="../login/register.php?type=student" class="btn btn-light btn-lg me-2">
                    <i class="fas fa-user-plus"></i> Sign Up Free
                </a>
                <a href="../login/login.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>

        <?php if (empty($properties)): ?>
            <!-- Empty Wishlist -->
            <div class="text-center py-5">
                <i class="fas fa-heart fa-5x text-muted mb-4"></i>
                <h3 class="text-muted">Your wishlist is empty</h3>
                <p class="text-muted mb-4">Start browsing properties and save your favorites!</p>
                <a href="all_properties.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search"></i> Browse Properties
                </a>
            </div>
        <?php else: ?>
            <!-- Wishlist Properties -->
            <div class="row">
                <?php foreach ($properties as $property): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card property-card h-100 shadow-sm">
                            <!-- Property Image -->
                            <div class="position-relative">
                                <?php if ($property['main_image']): ?>
                                    <img src="<?php echo BASE_URL . '/' . $property['main_image']; ?>"
                                         class="card-img-top"
                                         alt="<?php echo htmlspecialchars($property['title']); ?>"
                                         style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center"
                                         style="height: 200px;">
                                        <i class="fas fa-image fa-3x"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Remove from Wishlist Button -->
                                <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                                        onclick="removeFromWishlist(<?php echo $property['id']; ?>, this.closest('.col-md-6'))">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($property['location']); ?>
                                </p>
                                <h4 class="text-primary mb-3">
                                    KSh <?php echo number_format($property['price_monthly']); ?>/month
                                </h4>

                                <div class="d-flex justify-content-between">
                                    <a href="single_property.php?id=<?php echo $property['id']; ?>"
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <button class="btn btn-success" onclick="showLoginPrompt()">
                                        <i class="fas fa-calendar-check"></i> Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Clear Wishlist Button -->
            <div class="text-center mt-4 mb-5">
                <button class="btn btn-outline-danger btn-lg" onclick="clearWishlist()">
                    <i class="fas fa-trash-alt"></i> Clear All
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>

    <script>
        // Remove property from guest wishlist
        function removeFromWishlist(propertyId, card) {
            if (!confirm('Remove this property from your wishlist?')) {
                return;
            }

            // Show loading
            card.style.opacity = '0.5';

            fetch(`${window.BASE_URL}/actions/wishlist_remove_action.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `property_id=${propertyId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove card with animation
                    card.style.transition = 'all 0.3s ease';
                    card.style.transform = 'scale(0)';
                    setTimeout(() => {
                        card.remove();
                        // Reload if wishlist is now empty
                        if (data.wishlist_count === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    alert(data.message || 'Failed to remove property');
                    card.style.opacity = '1';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                card.style.opacity = '1';
            });
        }

        // Clear entire wishlist
        function clearWishlist() {
            if (!confirm('Are you sure you want to clear your entire wishlist?')) {
                return;
            }

            fetch(`${window.BASE_URL}/actions/wishlist_clear_action.php`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to clear wishlist');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Show login prompt for booking
        function showLoginPrompt() {
            if (confirm('Please create an account or login to book properties. Would you like to sign up now?')) {
                window.location.href = '<?php echo BASE_URL; ?>/login/register.php?type=student';
            }
        }
    </script>
</body>
</html>
