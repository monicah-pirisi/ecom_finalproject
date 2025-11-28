<?php
/**
 * CampusDigs Kenya - My Properties Page
 * View and manage all landlord properties
 */

require_once '../../includes/config.php';
require_once '../../includes/core.php';
require_once '../../controllers/property_controller.php';

requireLandlord();

$landlordId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;

// Get status filter
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';

// Get landlord properties
$result = getLandlordProperties($landlordId, $page, $perPage);
$properties = $result['properties'];
$totalProperties = $result['total'];
$totalPages = $result['pages'];

// Filter by status if needed
if ($statusFilter !== 'all') {
    $properties = array_filter($properties, function($prop) use ($statusFilter) {
        return $prop['status'] === $statusFilter;
    });
}

// Count by status
$statusCounts = ['all' => $totalProperties, 'active' => 0, 'pending' => 0, 'inactive' => 0];
foreach ($result['properties'] as $prop) {
    if (isset($statusCounts[$prop['status']])) {
        $statusCounts[$prop['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">

    <style>
        .property-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .property-image {
            height: 200px;
            object-fit: cover;
        }
        .status-filter {
            border-bottom: 2px solid transparent;
            padding: 10px 15px;
            cursor: pointer;
        }
        .status-filter.active {
            border-bottom-color: #ffc107;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../../dashboard_landlord.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="my_properties.php">
                                <i class="fas fa-building"></i> My Properties
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_property.php">
                                <i class="fas fa-plus-circle"></i> Add Property
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_bookings.php">
                                <i class="fas fa-clipboard-list"></i> Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">
                                <i class="fas fa-chart-line"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../landlord_profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="../../login/logout.php">
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
                        <i class="fas fa-building text-warning"></i> My Properties
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add_property.php" class="btn btn-warning">
                            <i class="fas fa-plus-circle"></i> Add New Property
                        </a>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Status Filter -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-start gap-3">
                            <a href="?status=all" class="status-filter <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                                All (<?php echo $statusCounts['all']; ?>)
                            </a>
                            <a href="?status=active" class="status-filter <?php echo $statusFilter === 'active' ? 'active' : ''; ?>">
                                <span class="text-success"><i class="fas fa-check-circle"></i> Active</span> (<?php echo $statusCounts['active']; ?>)
                            </a>
                            <a href="?status=pending" class="status-filter <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                                <span class="text-warning"><i class="fas fa-clock"></i> Pending</span> (<?php echo $statusCounts['pending']; ?>)
                            </a>
                            <a href="?status=inactive" class="status-filter <?php echo $statusFilter === 'inactive' ? 'active' : ''; ?>">
                                <span class="text-secondary"><i class="fas fa-times-circle"></i> Inactive</span> (<?php echo $statusCounts['inactive']; ?>)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Properties Grid -->
                <?php if (empty($properties)): ?>
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-home fa-5x text-muted mb-4"></i>
                            <h4 class="text-muted">No properties found</h4>
                            <p class="text-muted mb-4">
                                <?php if ($statusFilter !== 'all'): ?>
                                    No properties with status: <?php echo ucfirst($statusFilter); ?>
                                <?php else: ?>
                                    Start by adding your first property listing
                                <?php endif; ?>
                            </p>
                            <a href="add_property.php" class="btn btn-warning btn-lg">
                                <i class="fas fa-plus-circle"></i> Add Your First Property
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($properties as $property): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                <div class="card property-card h-100 shadow-sm">
                                    <!-- Property Image -->
                                    <?php
                                    $images = getPropertyImages($property['id']);
                                    $mainImage = !empty($images) ? $images[0]['image_path'] : 'images/property_placeholder.jpg';
                                    ?>
                                    <img src="../../<?php echo htmlspecialchars($mainImage); ?>"
                                         class="card-img-top property-image"
                                         alt="<?php echo htmlspecialchars($property['title']); ?>">

                                    <!-- Status Badge -->
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-<?php echo $property['status'] === 'active' ? 'success' : ($property['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                            <?php echo ucfirst($property['status']); ?>
                                        </span>
                                    </div>

                                    <div class="card-body">
                                        <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($property['title']); ?>">
                                            <?php echo htmlspecialchars($property['title']); ?>
                                        </h6>
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?>
                                        </p>
                                        <p class="card-text small text-muted mb-2">
                                            <i class="fas fa-bed"></i> <?php echo htmlspecialchars(ROOM_TYPES[$property['room_type']] ?? $property['room_type']); ?>
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-success fw-bold h5 mb-0">
                                                <?php echo formatCurrency($property['price_monthly']); ?>/mo
                                            </span>
                                        </div>

                                        <!-- Stats -->
                                        <div class="d-flex justify-content-between text-muted small mb-3">
                                            <span><i class="fas fa-eye"></i> <?php echo $property['view_count']; ?> views</span>
                                            <span><i class="fas fa-heart"></i> <?php echo $property['wishlist_count']; ?> saves</span>
                                            <span><i class="fas fa-calendar"></i> <?php echo $property['booking_count']; ?> bookings</span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="card-footer bg-white border-top-0">
                                        <div class="d-grid gap-2">
                                            <a href="edit_property.php?id=<?php echo $property['id']; ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Manage
                                            </a>
                                            <?php if ($property['status'] === 'active'): ?>
                                                <a href="../single_property.php?id=<?php echo $property['id']; ?>"
                                                   class="btn btn-sm btn-outline-secondary" target="_blank">
                                                    <i class="fas fa-external-link-alt"></i> View Listing
                                                </a>
                                            <?php endif; ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteProperty(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars($property['title'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash"></i> Delete Property
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Properties pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/dashboard.js"></script>
    <script>
        function deleteProperty(propertyId, propertyTitle) {
            if (confirm('Are you sure you want to delete "' + propertyTitle + '"?\n\nThis action cannot be undone. The property, all its images, and associated data will be permanently deleted.')) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../actions/landlord_properties_action.php';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'property_id';
                idInput.value = propertyId;
                form.appendChild(idInput);

                // Add CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?php echo generateCSRFToken(); ?>';
                form.appendChild(csrfInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
