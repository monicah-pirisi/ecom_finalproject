<?php
/**
 * AJAX Property Search Handler
 * Handles live property search and filtering without page reload
 */

// Start output buffering to prevent any accidental output
ob_start();

// Disable error display and log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set JSON header immediately
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/property_controller.php';
require_once '../controllers/wishlist_controller.php';

// Check if user is logged in (for wishlist functionality)
$isLoggedIn = isLoggedIn();
$wishlistIds = [];
if ($isLoggedIn && $_SESSION['user_type'] === 'student') {
    $studentId = $_SESSION['user_id'];
    $wishlistIds = getWishlistPropertyIds($studentId);
}

try {
    // Get search query
    $searchQuery = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

    // Get filters from request
    $filters = [
        'min_price' => isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : '',
        'max_price' => isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : '',
        'room_type' => isset($_GET['room_type']) ? sanitizeInput($_GET['room_type']) : '',
        'location' => isset($_GET['location']) ? sanitizeInput($_GET['location']) : '',
        'university' => isset($_GET['university']) ? sanitizeInput($_GET['university']) : '',
        'min_safety_score' => isset($_GET['min_safety_score']) && $_GET['min_safety_score'] !== '' ? (float)$_GET['min_safety_score'] : '',
        'has_cctv' => isset($_GET['has_cctv']) ? 1 : 0,
        'has_security_guard' => isset($_GET['has_security_guard']) ? 1 : 0,
        'max_distance' => isset($_GET['max_distance']) && $_GET['max_distance'] !== '' ? (float)$_GET['max_distance'] : '',
        'sort_by' => isset($_GET['sort_by']) ? sanitizeInput($_GET['sort_by']) : 'recent',
        'status' => 'active' // Only show active properties
    ];

    // Get pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = PROPERTIES_PER_PAGE;

    // Get properties based on search or filter
    if (!empty($searchQuery)) {
        $result = searchProperties($searchQuery, $page, $perPage, $filters);
    } else {
        $result = getAllProperties($page, $perPage, $filters);
    }

    $properties = $result['properties'];
    $totalProperties = $result['total'];
    $totalPages = $result['pages'];

    // Generate HTML for property cards
    $html = '';

    if (empty($properties)) {
        // No results found
        $html = '
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-home fa-5x text-muted mb-3"></i>
                <h4 class="text-muted">No Properties Found</h4>
                <p class="text-muted">Try adjusting your search criteria or filters</p>
                <a href="all_properties.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Clear All Filters
                </a>
            </div>
        </div>';
    } else {
        // Loop through properties and generate cards
        foreach ($properties as $property) {
            $propertyId = $property['id'];
            $isInWishlist = in_array($propertyId, $wishlistIds);
            $heartClass = $isInWishlist ? 'fas' : 'far';

            // Get property images
            $images = getPropertyImages($propertyId);
            $mainImage = !empty($images) ? htmlspecialchars($images[0]['image_path']) : '../uploads/properties/default.jpg';

            // Parse amenities
            $amenitiesData = json_decode($property['amenities'], true);
            $amenitiesData = is_array($amenitiesData) ? $amenitiesData : [];

            // Build amenity badges
            $amenitiesBadges = '';
            $amenityIcons = [
                'wifi' => 'fa-wifi',
                'parking' => 'fa-car',
                'security' => 'fa-shield-alt',
                'water' => 'fa-tint',
                'kitchen' => 'fa-utensils'
            ];

            $displayCount = 0;
            foreach ($amenitiesData as $amenity) {
                if ($displayCount >= 3) break;
                $icon = isset($amenityIcons[$amenity]) ? $amenityIcons[$amenity] : 'fa-check';
                $amenitiesBadges .= '<span class="badge bg-light text-dark me-1"><i class="fas ' . $icon . '"></i> ' . ucfirst($amenity) . '</span>';
                $displayCount++;
            }

            // Room type label
            $roomTypeLabel = isset(ROOM_TYPES[$property['room_type']]) ? ROOM_TYPES[$property['room_type']] : $property['room_type'];

            // Safety score stars
            $safetyStars = '';
            $safetyScore = $property['safety_score'] ?? 0;
            for ($i = 1; $i <= 5; $i++) {
                $safetyStars .= $i <= $safetyScore ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-warning"></i>';
            }

            // Generate property card HTML
            $html .= '
            <div class="col-md-6 col-lg-4 property-card-wrapper">
                <div class="card property-card h-100 shadow-sm">
                    <div class="position-relative">
                        <img src="' . $mainImage . '" class="card-img-top property-image" alt="' . htmlspecialchars($property['title']) . '">
                        <button class="wishlist-btn position-absolute top-0 end-0 m-2 btn btn-sm btn-light rounded-circle"
                                onclick="toggleWishlist(' . $propertyId . ', this)"
                                data-property-id="' . $propertyId . '">
                            <i class="' . $heartClass . ' fa-heart text-danger"></i>
                        </button>
                        ' . (!empty($property['is_premium']) ? '<span class="badge bg-warning position-absolute top-0 start-0 m-2">Featured</span>' : '') . '
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="single_property.php?id=' . $propertyId . '" class="text-decoration-none text-dark">
                                ' . htmlspecialchars($property['title']) . '
                            </a>
                        </h5>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt text-danger"></i> ' . htmlspecialchars($property['location']) . '
                            ' . (!empty($property['university']) ? '(' . htmlspecialchars($property['university']) . ')' : '') . '
                        </p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="h4 text-success mb-0">KES ' . number_format($property['price_monthly']) . '<small class="text-muted">/mo</small></span>
                            <span class="badge bg-primary">' . htmlspecialchars($roomTypeLabel) . '</span>
                        </div>
                        <div class="mb-2">
                            ' . $amenitiesBadges . '
                        </div>
                        <div class="d-flex justify-content-between text-muted small mb-3">
                            <span><i class="fas fa-users"></i> ' . $property['capacity'] . ' people</span>
                            ' . (!empty($property['distance_from_campus']) ? '<span><i class="fas fa-route"></i> ' . $property['distance_from_campus'] . ' km</span>' : '') . '
                            <span title="Safety Score">' . $safetyStars . '</span>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="single_property.php?id=' . $propertyId . '" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>';
        }
    }

    // Generate pagination HTML
    $paginationHtml = '';
    if ($totalPages > 1) {
        $paginationHtml = '<nav aria-label="Property pagination"><ul class="pagination justify-content-center">';

        // Previous button
        $paginationHtml .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">
            <a class="page-link" href="#" data-page="' . ($page - 1) . '">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        </li>';

        // Page numbers
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);

        for ($i = $startPage; $i <= $endPage; $i++) {
            $paginationHtml .= '<li class="page-item ' . ($i === $page ? 'active' : '') . '">
                <a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>
            </li>';
        }

        // Next button
        $paginationHtml .= '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '">
            <a class="page-link" href="#" data-page="' . ($page + 1) . '">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </li>';

        $paginationHtml .= '</ul></nav>';
    }

    // Clear output buffer before sending JSON
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Send JSON response
    echo json_encode([
        'success' => true,
        'html' => $html,
        'pagination' => $paginationHtml,
        'total' => $totalProperties,
        'count' => count($properties),
        'currentPage' => $page,
        'totalPages' => $totalPages
    ]);

} catch (Exception $e) {
    // Clear output buffer before sending error
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Log the error
    error_log("Search properties error: " . $e->getMessage());

    // Error handling
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching properties'
    ]);
}
