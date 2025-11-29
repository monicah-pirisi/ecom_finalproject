<?php
/**
 * CampusDigs Kenya - Public Storefront
 * Browse properties without login - Customer-facing homepage
 */

require_once 'includes/config.php';
require_once 'includes/core.php';
require_once 'controllers/property_controller.php';

// Get filters from URL
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$locationFilter = isset($_GET['location']) ? sanitizeInput($_GET['location']) : '';
$roomTypeFilter = isset($_GET['room_type']) ? sanitizeInput($_GET['room_type']) : '';
$minPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 100000;
$amenitiesFilter = isset($_GET['amenities']) ? $_GET['amenities'] : [];
$maxDistance = isset($_GET['max_distance']) ? (int)$_GET['max_distance'] : 999;
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'featured';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;

// Build filters
$filters = [
    'search' => $searchQuery,
    'location' => $locationFilter,
    'room_type' => $roomTypeFilter,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'amenities' => $amenitiesFilter,
    'max_distance' => $maxDistance,
    'sort' => $sortBy,
    'status' => 'active' // Only show active properties
];

// Get properties
$result = getPublicPropertiesFiltered($page, $perPage, $filters);
$properties = $result['properties'];
$totalProperties = $result['total'];
$totalPages = $result['pages'];

// Get filter options
$locations = getPropertyLocations();
$locationsByUniversity = getLocationsByUniversity();
$availableAmenities = [
    'wifi' => 'WiFi',
    'parking' => 'Parking',
    'security' => '24/7 Security',
    'water' => 'Water Supply',
    'backup_power' => 'Backup Power',
    'furnished' => 'Furnished',
    'laundry' => 'Laundry',
    'kitchen' => 'Kitchen'
];

// Get featured properties (for hero section)
$featuredProperties = getFeaturedProperties(3);

// Get statistics for trust indicators
$stats = [
    'total_properties' => getTotalActiveProperties(),
    'total_students' => getTotalStudents(),
    'total_bookings' => getTotalBookings()
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusDigs Kenya - Find Your Perfect Student Accommodation</title>
    <meta name="description" content="Discover safe, affordable student housing near Kenyan universities. Verified landlords, secure payments, real reviews.">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-green: #059669;
            --primary-green-dark: #047857;
            --primary-green-light: #10b981;
            --secondary-gold: #d97706;
            --secondary-gold-dark: #b45309;
            --secondary-gold-light: #f59e0b;
            --primary-color: #059669;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #fef3c7 100%);
            background-attachment: fixed;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-gold) 100%);
            color: white;
            padding: 120px 0 100px;
            position: relative;
            overflow: hidden;
        }

        /* Hero Background Options */
        .hero-bg-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }

        .hero-bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            z-index: 0;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.5) 0%, rgba(217, 119, 6, 0.4) 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            text-shadow: 3px 3px 10px rgba(0, 0, 0, 0.8);
            font-size: 3.5rem;
            line-height: 1.2;
        }

        .hero-content p {
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7);
            font-size: 1.3rem;
        }

        .hero-highlight {
            font-weight: 800;
            color: #fef3c7;
        }

        /* About Section with Background Image */
        .about-section-image {
            position: relative;
            min-height: 600px;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            border-radius: 20px;
        }

        .about-section-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.65) 0%, rgba(217, 119, 6, 0.55) 100%);
            border-radius: 20px;
            z-index: 1;
        }

        .about-content-overlay {
            position: relative;
            z-index: 2;
            color: white;
            padding: 60px;
        }

        .about-content-overlay h3 {
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .about-content-overlay p,
        .about-content-overlay li {
            color: #f0f9ff;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            font-size: 1.05rem;
        }

        .about-content-overlay .list-unstyled li {
            color: white;
        }

        @media (max-width: 768px) {
            .about-content-overlay {
                padding: 30px 20px;
            }

            .about-section-image {
                min-height: auto;
            }
        }

        .search-box {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        /* Trust Badges */
        .trust-badges {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }

        .trust-badge {
            text-align: center;
        }

        .trust-badge i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Filter Sidebar */
        .filter-sidebar {
            position: sticky;
            top: 20px;
        }

        .filter-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-card .card-header {
            background: #f8f9fa;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 600;
        }

        /* Amenities Dropdown */
        .amenities-dropdown-menu {
            min-width: 250px;
            max-height: 350px;
            overflow-y: auto;
        }

        .amenities-dropdown .dropdown-toggle {
            border: 1px solid #ced4da;
            background: white;
            color: #495057;
        }

        .amenities-dropdown .dropdown-toggle:hover {
            background: #f8f9fa;
            border-color: var(--primary-green);
        }

        .amenities-dropdown .dropdown-toggle::after {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }

        .amenities-dropdown-menu .form-check {
            cursor: pointer;
            padding: 5px 0;
        }

        .amenities-dropdown-menu .form-check:hover {
            background: #f8f9fa;
            border-radius: 4px;
        }

        .amenities-dropdown-menu .form-check-label {
            cursor: pointer;
            width: 100%;
        }

        /* Property Cards */
        .property-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }

        .property-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .property-image {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .property-card:hover .property-image img {
            transform: scale(1.1);
        }

        .property-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .wishlist-btn {
            position: absolute;
            top: 15px;
            left: 15px;
            width: 40px;
            height: 40px;
            background: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s;
        }

        .wishlist-btn:hover {
            background: var(--danger-color);
            color: white;
            transform: scale(1.1);
        }

        .price-tag {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
            color: white;
            padding: 8px 15px;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .property-features {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.875rem;
            color: #666;
        }

        /* CTA Buttons */
        .btn-cta {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(5, 150, 105, 0.4);
        }

        /* Stats Section */
        .stats-section {
            background: #f8f9fa;
            padding: 60px 0;
        }

        .stat-card {
            text-align: center;
            padding: 30px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 10px;
        }

        /* How It Works */
        .how-it-works {
            padding: 80px 0;
            background: white;
        }

        .step-card {
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            background: #f8f9fa;
            height: 100%;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 20px;
        }

        /* Testimonials */
        .testimonial-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 100%;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-gold) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .stars {
            color: #ffc107;
        }

        /* Footer */
        footer {
            background: #2c3e50;
            color: white;
            padding: 60px 0 20px;
        }

        footer h5 {
            color: white;
            margin-bottom: 20px;
        }

        footer a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s;
        }

        footer a:hover {
            color: white;
        }

        footer p {
            color: #bdc3c7;
        }

        footer .text-muted {
            color: #95a5a6 !important;
        }

        /* Range Slider */
        .price-range-values {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.875rem;
            color: #666;
        }

        /* Loading State */
        .property-skeleton {
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Bootstrap Color Overrides */
        .text-primary {
            color: var(--primary-green) !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-green-dark) 0%, var(--primary-green) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: var(--primary-green-dark);
            border-color: var(--primary-green-dark);
        }

        .btn-outline-success {
            color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .btn-outline-success:hover {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .navbar-brand {
            color: var(--primary-green) !important;
        }

        .nav-item {
            margin: 0 5px;
        }

        .nav-link {
            padding: 8px 15px !important;
        }

        .page-link {
            color: var(--primary-green);
        }

        .page-item.active .page-link {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .hero-content h1 {
                font-size: 2.8rem;
            }

            .hero-content p {
                font-size: 1.15rem;
            }
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 70px 0 50px;
            }

            .hero-content h1 {
                font-size: 2.2rem;
            }

            .hero-content p {
                font-size: 1.05rem;
            }

            .search-box {
                padding: 20px;
            }

            .trust-badges {
                padding: 15px;
            }

            .trust-badge {
                margin-bottom: 15px;
            }

            .filter-sidebar {
                position: static;
            }

            .stat-number {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .hero-content h1 {
                font-size: 1.8rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .search-box {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-home"></i> CampusDigs Kenya
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Browse Properties</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo getDashboardUrl(); ?>">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="login/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login/login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm ms-2" href="login/register.php">
                                <i class="fas fa-user-plus"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <!-- Background Video Option -->
        <video class="hero-bg-video" autoplay muted loop playsinline>
            <source src="assets/images/hero/hero-video.mp4" type="video/mp4">
        </video>

        <!-- Background Image Option (Uncomment to use) -->
        <!-- <div class="hero-bg-image" style="background-image: url('assets/images/hero/hero-bg.jpg');"></div> -->

        <div class="container hero-content">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h1 class="display-4 fw-bold text-center mb-4">
                        Find Your <span class="hero-highlight">Perfect</span> Student <span class="hero-highlight">Home</span>
                    </h1>
                    <p class="lead text-center mb-5">
                        <strong>Verified</strong> properties • <strong>Safe</strong> neighborhoods • <strong>Affordable</strong> prices
                    </p>

                    <!-- Quick Search Box -->
                    <div class="search-box">
                        <form action="index.php" method="GET">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <input type="text"
                                           class="form-control form-control-lg"
                                           name="search"
                                           placeholder="Search by property name or description..."
                                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select form-control-lg" name="location">
                                        <option value="">All Locations</option>
                                        <?php foreach ($locationsByUniversity as $university => $locs): ?>
                                            <optgroup label="<?php echo htmlspecialchars($university); ?>">
                                                <?php foreach ($locs as $loc): ?>
                                                    <option value="<?php echo htmlspecialchars($loc); ?>"
                                                            <?php echo $locationFilter === $loc ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($loc); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-control-lg" name="room_type">
                                        <option value="">Room Type</option>
                                        <?php foreach (ROOM_TYPES as $key => $label): ?>
                                            <option value="<?php echo $key; ?>"
                                                    <?php echo $roomTypeFilter === $key ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Trust Badges -->
                    <div class="trust-badges">
                        <div class="row text-center">
                            <div class="col-md-4 trust-badge">
                                <i class="fas fa-shield-alt"></i>
                                <div class="fw-bold"><?php echo number_format($stats['total_properties']); ?>+ Verified Properties</div>
                            </div>
                            <div class="col-md-4 trust-badge">
                                <i class="fas fa-users"></i>
                                <div class="fw-bold"><?php echo number_format($stats['total_students']); ?>+ Happy Students</div>
                            </div>
                            <div class="col-md-4 trust-badge">
                                <i class="fas fa-check-circle"></i>
                                <div class="fw-bold"><?php echo number_format($stats['total_bookings']); ?>+ Successful Bookings</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Filter Sidebar -->
                <div class="col-lg-3">
                    <div class="filter-sidebar">
                        <h5 class="mb-3">
                            <i class="fas fa-filter"></i> Filter Properties
                        </h5>

                        <form id="filterForm" action="index.php" method="GET">
                            <!-- Preserve search query -->
                            <?php if ($searchQuery): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <?php endif; ?>

                            <!-- Price Range -->
                            <div class="filter-card card">
                                <div class="card-header">
                                    <i class="fas fa-money-bill-wave"></i> Price Range
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label small">Min Price (KES)</label>
                                        <input type="number"
                                               class="form-control"
                                               name="min_price"
                                               value="<?php echo $minPrice; ?>"
                                               min="0"
                                               step="1000">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small">Max Price (KES)</label>
                                        <input type="number"
                                               class="form-control"
                                               name="max_price"
                                               value="<?php echo $maxPrice; ?>"
                                               min="0"
                                               step="1000">
                                    </div>
                                </div>
                            </div>

                            <!-- Location -->
                            <div class="filter-card card">
                                <div class="card-header">
                                    <i class="fas fa-map-marker-alt"></i> Location
                                </div>
                                <div class="card-body">
                                    <select class="form-select" name="location">
                                        <option value="">All Locations</option>
                                        <?php foreach ($locationsByUniversity as $university => $locs): ?>
                                            <optgroup label="<?php echo htmlspecialchars($university); ?>">
                                                <?php foreach ($locs as $loc): ?>
                                                    <option value="<?php echo htmlspecialchars($loc); ?>"
                                                            <?php echo $locationFilter === $loc ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($loc); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Room Type -->
                            <div class="filter-card card">
                                <div class="card-header">
                                    <i class="fas fa-bed"></i> Room Type
                                </div>
                                <div class="card-body">
                                    <select class="form-select" name="room_type">
                                        <option value="">All Types</option>
                                        <?php foreach (ROOM_TYPES as $key => $label): ?>
                                            <option value="<?php echo $key; ?>"
                                                    <?php echo $roomTypeFilter === $key ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Distance from Campus -->
                            <div class="filter-card card">
                                <div class="card-header">
                                    <i class="fas fa-route"></i> Distance from Campus
                                </div>
                                <div class="card-body">
                                    <select class="form-select" name="max_distance">
                                        <option value="999" <?php echo $maxDistance == 999 ? 'selected' : ''; ?>>Any Distance</option>
                                        <option value="1" <?php echo $maxDistance == 1 ? 'selected' : ''; ?>>Within 1 km</option>
                                        <option value="3" <?php echo $maxDistance == 3 ? 'selected' : ''; ?>>Within 3 km</option>
                                        <option value="5" <?php echo $maxDistance == 5 ? 'selected' : ''; ?>>Within 5 km</option>
                                        <option value="10" <?php echo $maxDistance == 10 ? 'selected' : ''; ?>>Within 10 km</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Amenities -->
                            <div class="filter-card card">
                                <div class="card-header">
                                    <i class="fas fa-star"></i> Amenities
                                </div>
                                <div class="card-body">
                                    <div class="dropdown amenities-dropdown w-100">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
                                                type="button"
                                                id="amenitiesDropdown"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <span id="amenitiesCount">
                                                <?php
                                                $selectedCount = count($amenitiesFilter);
                                                echo $selectedCount > 0 ? "$selectedCount selected" : "Select Amenities";
                                                ?>
                                            </span>
                                        </button>
                                        <div class="dropdown-menu p-3 amenities-dropdown-menu" aria-labelledby="amenitiesDropdown" onclick="event.stopPropagation();">
                                            <?php foreach ($availableAmenities as $key => $label): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input amenity-checkbox"
                                                           type="checkbox"
                                                           name="amenities[]"
                                                           value="<?php echo $key; ?>"
                                                           id="amenity_<?php echo $key; ?>"
                                                           <?php echo in_array($key, $amenitiesFilter) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="amenity_<?php echo $key; ?>">
                                                        <?php echo $label; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filter Buttons -->
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo"></i> Clear All
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Property Listings -->
                <div class="col-lg-9">
                    <!-- Results Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4><?php echo number_format($totalProperties); ?> Properties Found</h4>
                            <?php if ($searchQuery || $locationFilter || $roomTypeFilter): ?>
                                <p class="text-muted mb-0">
                                    <?php if ($searchQuery): ?>
                                        Searching for "<?php echo htmlspecialchars($searchQuery); ?>"
                                    <?php endif; ?>
                                    <?php if ($locationFilter): ?>
                                        in <?php echo htmlspecialchars($locationFilter); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <select class="form-select" name="sort" onchange="updateSort(this.value)">
                                <option value="featured" <?php echo $sortBy === 'featured' ? 'selected' : ''; ?>>Featured</option>
                                <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="popular" <?php echo $sortBy === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                            </select>
                        </div>
                    </div>

                    <!-- Property Grid -->
                    <?php if (empty($properties)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-home fa-5x text-muted mb-4"></i>
                            <h3 class="text-muted">No Properties Found</h3>
                            <p class="text-muted">Try adjusting your filters or search terms</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Clear Filters
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($properties as $property): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card property-card">
                                        <!-- Property Image -->
                                        <div class="property-image">
                                            <?php
                                            $images = getPropertyImages($property['id']);
                                            $mainImage = !empty($images) ? $images[0]['image_path'] : 'uploads/properties/default.jpg';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($mainImage); ?>"
                                                 alt="<?php echo htmlspecialchars($property['title']); ?>">

                                            <!-- Wishlist Button -->
                                            <button class="wishlist-btn" onclick="toggleWishlist(<?php echo $property['id']; ?>)">
                                                <i class="far fa-heart"></i>
                                            </button>

                                            <!-- Badge -->
                                            <?php if ($property['is_premium']): ?>
                                                <span class="property-badge bg-warning text-dark">
                                                    <i class="fas fa-star"></i> Featured
                                                </span>
                                            <?php elseif (strtotime($property['created_at']) > strtotime('-7 days')): ?>
                                                <span class="property-badge bg-success">
                                                    <i class="fas fa-sparkles"></i> New
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Property Info -->
                                        <div class="card-body p-0">
                                            <div class="price-tag">
                                                KES <?php echo number_format($property['price_monthly']); ?><small>/month</small>
                                            </div>

                                            <div class="p-3">
                                                <h5 class="card-title mb-2">
                                                    <a href="view/single_property.php?id=<?php echo $property['id']; ?>"
                                                       class="text-decoration-none text-dark">
                                                        <?php echo htmlspecialchars($property['title']); ?>
                                                    </a>
                                                </h5>

                                                <p class="text-muted small mb-2">
                                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                                    <?php echo htmlspecialchars($property['location']); ?>
                                                </p>

                                                <div class="property-features">
                                                    <div class="feature-item">
                                                        <i class="fas fa-bed text-primary"></i>
                                                        <span><?php echo htmlspecialchars(ROOM_TYPES[$property['room_type']] ?? $property['room_type']); ?></span>
                                                    </div>
                                                    <div class="feature-item">
                                                        <i class="fas fa-users text-success"></i>
                                                        <span><?php echo $property['capacity']; ?> people</span>
                                                    </div>
                                                    <?php if ($property['distance_from_campus']): ?>
                                                        <div class="feature-item">
                                                            <i class="fas fa-route text-info"></i>
                                                            <span><?php echo $property['distance_from_campus']; ?> km</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Amenities Icons -->
                                                <div class="mb-3">
                                                    <?php
                                                    $amenitiesList = json_decode($property['amenities'], true);
                                                    $amenitiesList = is_array($amenitiesList) ? $amenitiesList : [];
                                                    $displayAmenities = array_slice($amenitiesList, 0, 4);
                                                    foreach ($displayAmenities as $amenity):
                                                        if (isset($availableAmenities[$amenity])):
                                                    ?>
                                                        <span class="badge bg-light text-dark me-1 mb-1">
                                                            <?php echo $availableAmenities[$amenity]; ?>
                                                        </span>
                                                    <?php
                                                        endif;
                                                    endforeach;
                                                    ?>
                                                </div>

                                                <!-- Rating & Reviews -->
                                                <?php if ($property['average_rating']): ?>
                                                    <div class="mb-3">
                                                        <span class="text-warning">
                                                            <?php
                                                            $rating = round($property['average_rating']);
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                                            }
                                                            ?>
                                                        </span>
                                                        <span class="small text-muted ms-1">
                                                            (<?php echo $property['review_count']; ?> reviews)
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Action Buttons -->
                                                <div class="d-grid gap-2">
                                                    <a href="view/single_property.php?id=<?php echo $property['id']; ?>"
                                                       class="btn btn-primary">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                    <?php if (isLoggedIn() && $_SESSION['user_type'] === 'student'): ?>
                                                        <a href="view/single_property.php?id=<?php echo $property['id']; ?>#booking"
                                                           class="btn btn-success">
                                                            <i class="fas fa-calendar-check"></i> Book Now
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-success" onclick="showLoginPrompt()">
                                                            <i class="fas fa-calendar-check"></i> Login to Book
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-5">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo buildFilterUrl($page - 1); ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>

                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo buildFilterUrl($i); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo buildFilterUrl($page + 1); ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">About CampusDigs Kenya</h2>
                <p class="lead text-muted">Your Trusted Partner for Student Accommodation</p>
            </div>

            <!-- Background Image with Content Overlay -->
            <div class="about-section-image" style="background-image: url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1600&h=900&fit=crop');">

                <!-- Content Overlay -->
                <div class="about-content-overlay">
                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <h3 class="mb-4 fw-bold">Our Mission</h3>
                            <p class="mb-4">
                                CampusDigs Kenya is dedicated to helping students find safe, affordable, and comfortable housing near their universities.
                                We connect students with verified landlords, ensuring a secure and transparent rental experience.
                            </p>

                            <h3 class="mb-4 fw-bold">Why Choose Us?</h3>
                            <ul class="list-unstyled">
                                <li class="mb-3"><i class="fas fa-check-circle me-2"></i> <strong>Verified Properties & Landlords</strong></li>
                                <li class="mb-3"><i class="fas fa-check-circle me-2"></i> <strong>Secure Payment via M-Pesa</strong></li>
                                <li class="mb-3"><i class="fas fa-check-circle me-2"></i> <strong>Real Reviews from Students</strong></li>
                                <li class="mb-3"><i class="fas fa-check-circle me-2"></i> <strong>24/7 Customer Support</strong></li>
                                <li class="mb-3"><i class="fas fa-check-circle me-2"></i> <strong>No Hidden Fees</strong></li>
                            </ul>

                            <div class="mt-4">
                                <a href="login/register.php" class="btn btn-light btn-lg me-3">
                                    <i class="fas fa-user-plus"></i> Get Started
                                </a>
                                <a href="#how-it-works" class="btn btn-outline-light btn-lg">
                                    <i class="fas fa-arrow-down"></i> Learn More
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">How It Works</h2>
                <p class="lead text-muted">Find your perfect student home in 4 easy steps</p>
            </div>

            <div class="row g-4">
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <i class="fas fa-search fa-3x text-primary mb-3"></i>
                        <h5>Search Properties</h5>
                        <p class="text-muted">Browse verified student accommodations near your campus</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <i class="fas fa-eye fa-3x text-success mb-3"></i>
                        <h5>View Details</h5>
                        <p class="text-muted">Check photos, amenities, reviews from fellow students</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <i class="fas fa-calendar-check fa-3x text-info mb-3"></i>
                        <h5>Book Securely</h5>
                        <p class="text-muted">Make a booking request and pay securely via M-Pesa</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <i class="fas fa-key fa-3x text-warning mb-3"></i>
                        <h5>Move In</h5>
                        <p class="text-muted">Coordinate with landlord and move into your new home</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 position-relative" style="background: linear-gradient(135deg, rgba(5, 150, 105, 0.95) 0%, rgba(217, 119, 6, 0.95) 100%); background-image: url('https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1600&h=400&fit=crop'); background-size: cover; background-position: center; background-blend-mode: overlay;">
        <div class="container text-center text-white position-relative" style="z-index: 2;">
            <h2 class="display-5 fw-bold mb-3">Ready to Find Your Perfect Student Home?</h2>
            <p class="lead mb-4">Join thousands of students who trust CampusDigs Kenya</p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="login/register.php?type=student" class="btn btn-light btn-lg">
                    <i class="fas fa-graduation-cap"></i> Register as Student
                </a>
                <a href="login/register.php?type=landlord" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-building"></i> List Your Property
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-home"></i> CampusDigs Kenya</h5>
                    <p class="text-muted">Safe, affordable student housing near Kenyan universities. Verified landlords, secure payments, real reviews.</p>
                    <div class="social-links">
                        <a href="mailto:info@campusdigskenya.com" class="me-3" title="Email Us"><i class="fas fa-envelope fa-2x"></i></a>
                        <a href="tel:+254700000000" class="me-3" title="Call Us"><i class="fas fa-phone fa-2x"></i></a>
                        <a href="https://wa.me/254700000000" class="me-3" title="WhatsApp" target="_blank"><i class="fab fa-whatsapp fa-2x"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>For Students</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php">Browse Properties</a></li>
                        <li class="mb-2"><a href="#how-it-works">How It Works</a></li>
                        <li class="mb-2"><a href="login/register.php?type=student">Sign Up</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>For Landlords</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="login/register.php?type=landlord">List Property</a></li>
                        <li class="mb-2"><a href="login/register.php?type=landlord">Get Started</a></li>
                        <li class="mb-2"><a href="login/login.php">Landlord Login</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Company</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#how-it-works">About Us</a></li>
                        <li class="mb-2"><a href="mailto:info@campusdigskenya.com">Contact</a></li>
                        <li class="mb-2"><a href="index.php">Properties</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#how-it-works">Help Center</a></li>
                        <li class="mb-2"><a href="#how-it-works">Safety Tips</a></li>
                        <li class="mb-2"><a href="login/register.php">Get Started</a></li>
                        <li class="mb-2"><a href="login/login.php">Login</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center py-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> CampusDigs Kenya. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle wishlist
        function toggleWishlist(propertyId) {
            <?php if (!isLoggedIn()): ?>
                showLoginPrompt();
                return;
            <?php endif; ?>

            fetch(`actions/toggle_wishlist.php?property_id=${propertyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Toggle heart icon
                        event.target.classList.toggle('fas');
                        event.target.classList.toggle('far');
                    } else {
                        alert(data.message || 'Failed to update wishlist');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Show login prompt for non-logged-in users
        function showLoginPrompt() {
            if (confirm('You need to login to perform this action. Would you like to login now?')) {
                window.location.href = 'login/login.php?redirect=' + encodeURIComponent(window.location.href);
            }
        }

        // Update amenities count dynamically
        document.addEventListener('DOMContentLoaded', function() {
            const amenityCheckboxes = document.querySelectorAll('.amenity-checkbox');
            const amenitiesCountSpan = document.getElementById('amenitiesCount');

            function updateAmenitiesCount() {
                const checkedCount = document.querySelectorAll('.amenity-checkbox:checked').length;
                if (checkedCount > 0) {
                    amenitiesCountSpan.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>' + checkedCount + ' selected';
                } else {
                    amenitiesCountSpan.textContent = 'Select Amenities';
                }
            }

            amenityCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateAmenitiesCount);
            });
        })

        // Update sort order
        function updateSort(sortValue) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortValue);
            url.searchParams.set('page', '1'); // Reset to page 1
            window.location.href = url.toString();
        }
    </script>

    <?php
    // Helper function to build filter URLs
    function buildFilterUrl($page) {
        $params = $_GET;
        $params['page'] = $page;
        return 'index.php?' . http_build_query($params);
    }
    ?>
</body>
</html>
