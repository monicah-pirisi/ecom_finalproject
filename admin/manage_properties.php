<?php
/**
 * CampusDigs Kenya - Admin Manage Properties
 * Review, approve, and manage all property listings
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/property_controller.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get status filter
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Get properties based on filter
switch ($statusFilter) {
    case 'pending':
        $result = getPendingProperties($page, $perPage);
        break;
    case 'active':
        $result = getActiveProperties($page, $perPage);
        break;
    case 'rejected':
        $result = getRejectedProperties($page, $perPage);
        break;
    default:
        $result = getAllPropertiesAdmin($page, $perPage);
}

$properties = $result['properties'];
$totalProperties = $result['total'];
$totalPages = $result['pages'];

// Get counts for tabs
$counts = getPropertyStatusCounts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - Admin - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
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
                            <a class="nav-link active" href="manage_properties.php">
                                <i class="fas fa-building"></i> Properties
                                <?php if ($counts['pending'] > 0): ?>
                                    <span class="badge bg-warning rounded-pill"><?php echo $counts['pending']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_bookings.php">
                                <i class="fas fa-calendar-check"></i> Bookings
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
                        <i class="fas fa-building text-success"></i> Manage Properties
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="text-muted">Total: <?php echo $totalProperties; ?> properties</span>
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

                <!-- Pending Alert -->
                <?php if ($counts['pending'] > 0 && $statusFilter !== 'pending'): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <strong><?php echo $counts['pending']; ?> propert<?php echo $counts['pending'] === 1 ? 'y' : 'ies'; ?> awaiting approval</strong>
                            <p class="mb-0 small">Review and approve property listings to make them visible to students.</p>
                        </div>
                        <a href="?status=pending" class="btn btn-warning">
                            Review Now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Status Tabs -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" href="?status=all">
                            <i class="fas fa-list"></i> All Properties
                            <span class="badge bg-secondary"><?php echo $counts['total']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $statusFilter === 'active' ? 'active' : ''; ?>" href="?status=active">
                            <i class="fas fa-check-circle"></i> Active
                            <span class="badge bg-success"><?php echo $counts['active']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" href="?status=pending">
                            <i class="fas fa-clock"></i> Pending Review
                            <span class="badge bg-warning"><?php echo $counts['pending']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>" href="?status=rejected">
                            <i class="fas fa-times-circle"></i> Rejected
                            <span class="badge bg-danger"><?php echo $counts['rejected']; ?></span>
                        </a>
                    </li>
                </ul>

                <!-- Properties List -->
                <?php if (empty($properties)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-building fa-5x text-muted mb-4"></i>
                        <h3 class="text-muted">No properties found</h3>
                        <p class="text-muted">
                            <?php if ($statusFilter === 'pending'): ?>
                                All properties have been reviewed!
                            <?php else: ?>
                                No properties in this category yet.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Property</th>
                                    <th>Landlord</th>
                                    <th>Location</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $property): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($property['main_image']): ?>
                                                    <img src="../<?php echo htmlspecialchars($property['main_image']); ?>"
                                                         alt="Property"
                                                         class="rounded me-2"
                                                         style="width: 60px; height: 60px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded me-2 d-flex align-items-center justify-content-center"
                                                         style="width: 60px; height: 60px;">
                                                        <i class="fas fa-image text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($property['title']); ?></strong><br>
                                                    <small class="text-muted"><?php echo ROOM_TYPES[$property['room_type']] ?? $property['room_type']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?php echo htmlspecialchars($property['landlord_name']); ?>
                                                <?php if ($property['landlord_verified']): ?>
                                                    <i class="fas fa-check-circle text-success" title="Verified"></i>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><?php echo htmlspecialchars($property['landlord_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($property['location']); ?></td>
                                        <td>
                                            <strong class="text-success"><?php echo formatCurrency($property['price_monthly']); ?></strong>
                                            <small class="text-muted d-block">/month</small>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = [
                                                'pending' => 'bg-warning text-dark',
                                                'active' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'inactive' => 'bg-secondary'
                                            ][$property['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo ucfirst($property['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo timeAgo($property['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="property_review.php?id=<?php echo $property['id']; ?>"
                                                   class="btn btn-sm btn-primary"
                                                   title="Review Property">
                                                    <i class="fas fa-eye"></i> Review
                                                </a>
                                                <?php if ($property['status'] === 'active'): ?>
                                                    <a href="../view/single_property.php?id=<?php echo $property['id']; ?>"
                                                       target="_blank"
                                                       class="btn btn-sm btn-outline-secondary"
                                                       title="View Public Page">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning"
                                                            onclick="deactivateProperty(<?php echo $property['id']; ?>, '<?php echo htmlspecialchars($property['title'], ENT_QUOTES); ?>')"
                                                            title="Deactivate Property">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Properties pagination">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo $statusFilter; ?>&page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?status=<?php echo $statusFilter; ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo $statusFilter; ?>&page=<?php echo $page + 1; ?>">
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
    <script>
        function deactivateProperty(propertyId, propertyTitle) {
            const reason = prompt('Please provide a reason for deactivating "' + propertyTitle + '":');

            if (reason && reason.trim() !== '') {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../actions/admin_property_action.php';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'deactivate';
                form.appendChild(actionInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'property_id';
                idInput.value = propertyId;
                form.appendChild(idInput);

                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'reason';
                reasonInput.value = reason;
                form.appendChild(reasonInput);

                // Add CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?php echo generateCSRFToken(); ?>';
                form.appendChild(csrfInput);

                document.body.appendChild(form);
                form.submit();
            } else if (reason !== null) {
                alert('A reason is required to deactivate a property.');
            }
        }
    </script>
</body>
</html>
