<?php
/**
 * CampusDigs Kenya - Admin Moderate Reviews
 * Review and moderate all property reviews
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/review_controller.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get filters
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$ratingFilter = isset($_GET['rating']) ? sanitizeInput($_GET['rating']) : 'all';
$propertySearch = isset($_GET['property']) ? sanitizeInput($_GET['property']) : '';
$studentSearch = isset($_GET['student']) ? sanitizeInput($_GET['student']) : '';
$sortOrder = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'recent';

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build filters array
$filters = [
    'status' => $statusFilter !== 'all' ? $statusFilter : null,
    'rating' => $ratingFilter !== 'all' ? (int)$ratingFilter : null,
    'property_search' => $propertySearch ?: null,
    'student_search' => $studentSearch ?: null,
    'sort' => $sortOrder
];

// Get reviews
$result = getAllReviewsFiltered($page, $perPage, $filters);
$reviews = $result['reviews'];
$totalReviews = $result['total'];
$totalPages = $result['pages'];

// Get counts and statistics
$counts = getReviewStatusCounts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderate Reviews - Admin - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <style>
        .star-rating {
            color: #ffc107;
            font-size: 1.1rem;
        }
        .review-text-preview {
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../dashboard_admin.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_properties.php">
                                <i class="fas fa-building"></i> Properties
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_bookings.php">
                                <i class="fas fa-calendar-check"></i> Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="moderate_reviews.php">
                                <i class="fas fa-star"></i> Reviews
                                <?php if ($counts['pending'] > 0 || $counts['flagged'] > 0): ?>
                                    <span class="badge bg-warning rounded-pill"><?php echo $counts['pending'] + $counts['flagged']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-line"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item mt-3">
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
                        <i class="fas fa-star text-warning"></i> Moderate Reviews
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="text-muted">Total: <?php echo $totalReviews; ?> reviews</span>
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

                <!-- Pending/Flagged Alert -->
                <?php if (($counts['pending'] > 0 || $counts['flagged'] > 0) && $statusFilter === 'all'): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <strong>
                                <?php echo $counts['pending']; ?> review<?php echo $counts['pending'] === 1 ? '' : 's'; ?> pending approval
                                <?php if ($counts['flagged'] > 0): ?>
                                    and <?php echo $counts['flagged']; ?> flagged for attention
                                <?php endif; ?>
                            </strong>
                            <p class="mb-0 small">Review and moderate pending reviews to ensure quality content.</p>
                        </div>
                        <a href="?status=pending" class="btn btn-warning me-2">
                            Review Pending <i class="fas fa-arrow-right"></i>
                        </a>
                        <?php if ($counts['flagged'] > 0): ?>
                            <a href="?status=flagged" class="btn btn-danger">
                                Review Flagged <i class="fas fa-flag"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Reviews
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $counts['total']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-comments fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Approved Reviews
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $counts['approved']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Reviews
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $counts['pending']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Flagged Reviews
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $counts['flagged']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-flag fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <!-- Status Filter -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>
                                        All Status (<?php echo $counts['total']; ?>)
                                    </option>
                                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>
                                        Approved (<?php echo $counts['approved']; ?>)
                                    </option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>
                                        Pending (<?php echo $counts['pending']; ?>)
                                    </option>
                                    <option value="flagged" <?php echo $statusFilter === 'flagged' ? 'selected' : ''; ?>>
                                        Flagged (<?php echo $counts['flagged']; ?>)
                                    </option>
                                    <option value="deleted" <?php echo $statusFilter === 'deleted' ? 'selected' : ''; ?>>
                                        Deleted (<?php echo $counts['deleted']; ?>)
                                    </option>
                                </select>
                            </div>

                            <!-- Rating Filter -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Rating</label>
                                <select name="rating" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $ratingFilter === 'all' ? 'selected' : ''; ?>>All Ratings</option>
                                    <option value="5" <?php echo $ratingFilter === '5' ? 'selected' : ''; ?>>5 </option>
                                    <option value="4" <?php echo $ratingFilter === '4' ? 'selected' : ''; ?>>4 </option>
                                    <option value="3" <?php echo $ratingFilter === '3' ? 'selected' : ''; ?>>3 </option>
                                    <option value="2" <?php echo $ratingFilter === '2' ? 'selected' : ''; ?>>2 </option>
                                    <option value="1" <?php echo $ratingFilter === '1' ? 'selected' : ''; ?>>1 </option>
                                </select>
                            </div>

                            <!-- Sort Order -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Sort By</label>
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="recent" <?php echo $sortOrder === 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                                    <option value="oldest" <?php echo $sortOrder === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="highest" <?php echo $sortOrder === 'highest' ? 'selected' : ''; ?>>Highest Rated</option>
                                    <option value="lowest" <?php echo $sortOrder === 'lowest' ? 'selected' : ''; ?>>Lowest Rated</option>
                                </select>
                            </div>

                            <!-- Property Search -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Property</label>
                                <input type="text" name="property" class="form-control"
                                       placeholder="Property name..."
                                       value="<?php echo htmlspecialchars($propertySearch); ?>">
                            </div>

                            <!-- Student Search -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Student</label>
                                <input type="text" name="student" class="form-control"
                                       placeholder="Student name..."
                                       value="<?php echo htmlspecialchars($studentSearch); ?>">
                            </div>

                            <!-- Buttons -->
                            <div class="col-md-1">
                                <label class="form-label fw-bold d-none d-md-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>

                            <?php if ($statusFilter !== 'all' || $ratingFilter !== 'all' || $propertySearch || $studentSearch || $sortOrder !== 'recent'): ?>
                                <div class="col-12">
                                    <a href="moderate_reviews.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-redo"></i> Clear Filters
                                    </a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Reviews List -->
                <?php if (empty($reviews)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-star fa-5x text-muted mb-4"></i>
                        <h3 class="text-muted">No reviews found</h3>
                        <p class="text-muted">Try adjusting your filters or wait for new reviews.</p>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Property</th>
                                        <th>Student</th>
                                        <th>Rating</th>
                                        <th>Review</th>
                                        <th>Flags</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reviews as $review): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-primary">#<?php echo $review['id']; ?></strong>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($review['property_title']); ?>">
                                                    <?php echo htmlspecialchars($review['property_title']); ?>
                                                </div>
                                                <small class="text-muted"><?php echo htmlspecialchars($review['property_location']); ?></small>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($review['student_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($review['student_email']); ?></small>
                                            </td>
                                            <td>
                                                <div class="star-rating">
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
                                                <small class="text-muted"><?php echo $review['rating']; ?>/5</small>
                                            </td>
                                            <td>
                                                <div class="review-text-preview" style="max-width: 250px;">
                                                    <?php echo htmlspecialchars($review['review_text']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($review['flag_count'] > 0): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-flag"></i> <?php echo $review['flag_count']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'approved' => 'bg-success',
                                                    'pending' => 'bg-warning text-dark',
                                                    'flagged' => 'bg-danger',
                                                    'deleted' => 'bg-secondary'
                                                ][$review['moderation_status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($review['moderation_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo timeAgo($review['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <a href="review_moderation.php?id=<?php echo $review['id']; ?>"
                                                   class="btn btn-sm btn-primary"
                                                   title="Moderate Review">
                                                    <i class="fas fa-gavel"></i> Moderate
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Reviews pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo $statusFilter; ?>&rating=<?php echo $ratingFilter; ?>&sort=<?php echo $sortOrder; ?>&property=<?php echo urlencode($propertySearch); ?>&student=<?php echo urlencode($studentSearch); ?>&page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?status=<?php echo $statusFilter; ?>&rating=<?php echo $ratingFilter; ?>&sort=<?php echo $sortOrder; ?>&property=<?php echo urlencode($propertySearch); ?>&student=<?php echo urlencode($studentSearch); ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo $statusFilter; ?>&rating=<?php echo $ratingFilter; ?>&sort=<?php echo $sortOrder; ?>&property=<?php echo urlencode($propertySearch); ?>&student=<?php echo urlencode($studentSearch); ?>&page=<?php echo $page + 1; ?>">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
