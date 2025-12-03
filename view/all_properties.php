<?php
/**
 * CampusDigs Kenya - All Properties Page
 * Browse and search all available properties
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

// Get filters from URL
$filters = [
    'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : '',
    'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : '',
    'room_type' => isset($_GET['room_type']) ? sanitizeInput($_GET['room_type']) : '',
    'location' => isset($_GET['location']) ? sanitizeInput($_GET['location']) : '',
    'university' => isset($_GET['university']) ? sanitizeInput($_GET['university']) : '',
    'min_safety_score' => isset($_GET['min_safety_score']) ? (float)$_GET['min_safety_score'] : '',
    'has_cctv' => isset($_GET['has_cctv']) ? 1 : 0,
    'has_security_guard' => isset($_GET['has_security_guard']) ? 1 : 0,
    'max_distance' => isset($_GET['max_distance']) ? (float)$_GET['max_distance'] : '',
    'sort_by' => isset($_GET['sort_by']) ? sanitizeInput($_GET['sort_by']) : 'recent'
];

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = PROPERTIES_PER_PAGE;

// Get properties
if (!empty($searchQuery)) {
    $result = searchProperties($searchQuery, $page, $perPage, $filters);
} else {
    $result = getAllProperties($page, $perPage, $filters);
}

$properties = $result['properties'];
$totalProperties = $result['total'];
$totalPages = $result['pages'];

// Get wishlist IDs for current student
$wishlistIds = getWishlistPropertyIds($studentId);

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Properties - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body data-user-logged-in="true">
    <!-- Include Header -->
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Filters -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>FILTERS</span>
                    </h6>
                    
                    <form method="GET" action="" id="filterForm">
                        <!-- Preserve search query -->
                        <?php if ($searchQuery): ?>
                            <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <?php endif; ?>
                        
                        <!-- Price Range -->
                        <div class="px-3 mb-4">
                            <label class="form-label fw-bold">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control form-control-sm" 
                                           placeholder="Min" value="<?php echo $filters['min_price']; ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control form-control-sm" 
                                           placeholder="Max" value="<?php echo $filters['max_price']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Room Type -->
                        <div class="px-3 mb-4">
                            <label class="form-label fw-bold">Room Type</label>
                            <select name="room_type" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                <?php foreach (ROOM_TYPES as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                            <?php echo $filters['room_type'] === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- University -->
                        <div class="px-3 mb-4">
                            <label class="form-label fw-bold">Near University</label>
                            <select name="university" class="form-select form-select-sm">
                                <option value="">All Universities</option>
                                <?php foreach (SUPPORTED_UNIVERSITIES as $uni): ?>
                                    <option value="<?php echo htmlspecialchars($uni); ?>"
                                            <?php echo $filters['university'] === $uni ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($uni); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Safety Score -->
                        <div class="px-3 mb-4">
                            <label class="form-label fw-bold">Minimum Safety Score</label>
                            <select name="min_safety_score" class="form-select form-select-sm">
                                <option value="">Any Rating</option>
                                <option value="3" <?php echo $filters['min_safety_score'] == 3 ? 'selected' : ''; ?>>3+ Stars</option>
                                <option value="4" <?php echo $filters['min_safety_score'] == 4 ? 'selected' : ''; ?>>4+ Stars</option>
                                <option value="5" <?php echo $filters['min_safety_score'] == 5 ? 'selected' : ''; ?>>5 Stars</option>
                            </select>
                        </div>
                        
                        <!-- Amenities -->
                        <div class="px-3 mb-4">
                            <label class="form-label fw-bold">Amenities</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_cctv" value="1" 
                                       <?php echo $filters['has_cctv'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">CCTV Security</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_security_guard" value="1"
                                       <?php echo $filters['has_security_guard'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Security Guard</label>
                            </div>
                        </div>
                        
                        <!-- Distance -->
                        <div class="px-3 mb-4">
                            <label class="form-label fw-bold">Max Distance from Campus</label>
                            <select name="max_distance" class="form-select form-select-sm">
                                <option value="">Any Distance</option>
                                <option value="2" <?php echo $filters['max_distance'] == 2 ? 'selected' : ''; ?>>Within 2km</option>
                                <option value="5" <?php echo $filters['max_distance'] == 5 ? 'selected' : ''; ?>>Within 5km</option>
                                <option value="10" <?php echo $filters['max_distance'] == 10 ? 'selected' : ''; ?>>Within 10km</option>
                            </select>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="px-3 mb-3 d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm" style="display:none;">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <button type="button" id="clearFiltersBtn" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-redo"></i> Clear All
                            </button>
                        </div>
                    </form>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="pt-3 pb-2 mb-3">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
                        <div>
                            <h1 class="h2">
                                <?php if ($searchQuery): ?>
                                    <i class="fas fa-search text-primary"></i> Search Results
                                <?php else: ?>
                                    <i class="fas fa-home text-primary"></i> Browse Properties
                                <?php endif; ?>
                            </h1>
                            <p class="text-muted" id="resultsInfo">
                                Showing <?php echo count($properties); ?> of <?php echo $totalProperties; ?> properties
                                <?php if ($searchQuery): ?>
                                    for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Flash Message -->
                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                            <?php echo htmlspecialchars($flash['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Search Bar & Sort -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <form id="searchForm" method="GET" action="all_properties.php" class="input-group">
                                <input type="text" name="q" class="form-control"
                                       placeholder="Search by location, property name, or keywords..."
                                       value="<?php echo htmlspecialchars($searchQuery); ?>"
                                       autocomplete="off">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <select name="sort_by" class="form-select" form="filterForm">
                                <option value="recent" <?php echo $filters['sort_by'] === 'recent' ? 'selected' : ''; ?>>
                                    Most Recent
                                </option>
                                <option value="price_asc" <?php echo $filters['sort_by'] === 'price_asc' ? 'selected' : ''; ?>>
                                    Price: Low to High
                                </option>
                                <option value="price_desc" <?php echo $filters['sort_by'] === 'price_desc' ? 'selected' : ''; ?>>
                                    Price: High to Low
                                </option>
                                <option value="safety_desc" <?php echo $filters['sort_by'] === 'safety_desc' ? 'selected' : ''; ?>>
                                    Highest Safety Score
                                </option>
                                <option value="distance_asc" <?php echo $filters['sort_by'] === 'distance_asc' ? 'selected' : ''; ?>>
                                    Nearest First
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Loading Overlay -->
                    <div id="loadingOverlay" class="d-none text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Searching properties...</p>
                    </div>

                    <!-- Properties Grid -->
                    <div id="propertiesGrid" class="row">
                    <?php if (empty($properties)): ?>
                        <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-5x text-muted mb-4"></i>
                            <h3 class="text-muted">No properties found</h3>
                            <p class="text-muted mb-4">Try adjusting your filters or search terms</p>
                            <a href="all_properties.php" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Clear Filters
                            </a>
                        </div>
                        </div>
                    <?php else: ?>
                            <?php foreach ($properties as $property): ?>
                                <div class="col-md-6 col-lg-4 property-card-wrapper" data-property-id="<?php echo $property['id']; ?>">
                                    <div class="card property-card h-100 shadow-sm">
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
                                            <button class="btn btn-wishlist <?php echo in_array($property['id'], $wishlistIds) ? 'active' : ''; ?>">
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
                                            
                                            <?php if ($property['distance_from_campus']): ?>
                                                <p class="card-text text-muted small mb-2">
                                                    <i class="fas fa-walking"></i> 
                                                    <?php echo $property['distance_from_campus']; ?>km from campus
                                                </p>
                                            <?php endif; ?>
                                            
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
                                        </div>
                                        
                                        <div class="card-footer bg-white">
                                            <div class="d-grid">
                                                <a href="single_property.php?id=<?php echo $property['id']; ?>" 
                                                   class="btn btn-primary">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                    <?php endif; ?>
                    </div>

                        <!-- Pagination -->
                        <div id="paginationContainer">
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Property pagination">
                                <ul class="pagination justify-content-center">
                                    <!-- Previous Button -->
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
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
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Next Button -->
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Set base URL for AJAX requests
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/wishlist.js"></script>
    <script src="../js/property_search.js"></script>
</body>
</html>