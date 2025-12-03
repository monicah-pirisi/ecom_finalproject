<?php
/**
 * CampusDigs Kenya - Property Search Results
 * Display search results for properties
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/wishlist_controller.php';

// Require student authentication
requireStudent();

$studentId = $_SESSION['user_id'];

// Get search query
$searchQuery = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

if (empty($searchQuery)) {
    redirectWithMessage('all_properties.php', 'Please enter a search term', 'error');
}

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = PROPERTIES_PER_PAGE;

// Get filters
$filters = [
    'sort_by' => isset($_GET['sort_by']) ? sanitizeInput($_GET['sort_by']) : 'recent'
];

// Search properties
$result = searchProperties($searchQuery, $page, $perPage, $filters);
$properties = $result['properties'];
$totalProperties = $result['total'];
$totalPages = $result['pages'];

// Get wishlist IDs
$wishlistIds = getWishlistPropertyIds($studentId);

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?php echo htmlspecialchars($searchQuery); ?> - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-4">
        <!-- Back Button -->
        <a href="all_properties.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Back to All Properties
        </a>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Search Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2"><i class="fas fa-search text-primary"></i> Search Results</h1>
                <p class="text-muted">
                    Found <?php echo $totalProperties; ?> propert<?php echo $totalProperties === 1 ? 'y' : 'ies'; ?> 
                    for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                </p>
            </div>
            <div>
                <select name="sort_by" class="form-select" onchange="window.location.href='?q=<?php echo urlencode($searchQuery); ?>&sort_by=' + this.value">
                    <option value="recent" <?php echo $filters['sort_by'] === 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                    <option value="price_asc" <?php echo $filters['sort_by'] === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $filters['sort_by'] === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="safety_desc" <?php echo $filters['sort_by'] === 'safety_desc' ? 'selected' : ''; ?>>Highest Safety</option>
                </select>
            </div>
        </div>

        <!-- Search Again -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="property_search_result.php" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" name="q" class="form-control" 
                               placeholder="Search by location, property name, or keywords..."
                               value="<?php echo htmlspecialchars($searchQuery); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <?php if (empty($properties)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-5x text-muted mb-4"></i>
                <h3 class="text-muted">No properties found</h3>
                <p class="text-muted mb-4">We couldn't find any properties matching "<?php echo htmlspecialchars($searchQuery); ?>"</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="all_properties.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Browse All Properties
                    </a>
                    <button class="btn btn-outline-secondary" onclick="document.querySelector('input[name=q]').focus()">
                        <i class="fas fa-edit"></i> Try Different Keywords
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="row" data-user-logged-in="true" data-wishlist-ids='<?php echo json_encode($wishlistIds); ?>'>
                <?php foreach ($properties as $property): ?>
                    <div class="col-md-6 col-lg-4 mb-4" data-property-id="<?php echo $property['id']; ?>">
                        <div class="card property-card h-100">
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
                                
                                <button class="btn btn-wishlist <?php echo in_array($property['id'], $wishlistIds) ? 'active' : ''; ?>">
                                    <i class="fas fa-heart"></i>
                                </button>
                                
                                <?php if ($property['is_verified']): ?>
                                    <span class="badge badge-verified">
                                        <i class="fas fa-shield-alt"></i> Verified
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($property['title']); ?></h5>
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($property['location']); ?>
                                </p>
                                
                                <div class="mb-2">
                                    <?php
                                    $safetyScore = (int)$property['safety_score'];
                                    for ($i = 1; $i <= 5; $i++):
                                        echo $i <= $safetyScore ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-warning"></i>';
                                    endfor;
                                    ?>
                                </div>
                                
                                <div class="amenities mb-3">
                                    <?php
                                    $amenities = json_decode($property['amenities'], true);
                                    if ($amenities && is_array($amenities)):
                                        foreach (array_slice($amenities, 0, 3) as $amenity):
                                    ?>
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($amenity); ?></span>
                                    <?php endforeach; endif; ?>
                                </div>
                                
                                <h4 class="text-primary mb-0">
                                    <?php echo formatCurrency($property['price_monthly']); ?>
                                    <small class="text-muted">/month</small>
                                </h4>
                            </div>
                            
                            <div class="card-footer bg-white">
                                <div class="d-grid">
                                    <a href="single_property.php?id=<?php echo $property['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page - 1; ?>&sort_by=<?php echo $filters['sort_by']; ?>">Previous</a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $i; ?>&sort_by=<?php echo $filters['sort_by']; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $page + 1; ?>&sort_by=<?php echo $filters['sort_by']; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/wishlist.js"></script>
</body>
</html>