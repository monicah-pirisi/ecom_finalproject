<?php
/**
 * CampusDigs Kenya - Admin Manage Users
 * Review, verify, and manage all user accounts
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get filters
$userType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'all';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;

// Build filter array
$filters = [
    'user_type' => $userType !== 'all' ? $userType : null,
    'status' => $status !== 'all' ? $status : null,
    'search' => $search ?: null
];

// Get users
$result = getAllUsersFiltered($page, $perPage, $filters);
$users = $result['users'];
$totalUsers = $result['total'];
$totalPages = $result['pages'];

// Get counts for filters
$counts = getUserStatusCounts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin - <?php echo APP_NAME; ?></title>
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
                            <a class="nav-link active" href="manage_users.php">
                                <i class="fas fa-users"></i> Users
                                <?php if ($counts['unverified'] > 0): ?>
                                    <span class="badge bg-warning rounded-pill"><?php echo $counts['unverified']; ?></span>
                                <?php endif; ?>
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
                        <i class="fas fa-users text-primary"></i> Manage Users
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="text-muted">Total: <?php echo $totalUsers; ?> users</span>
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

                <!-- Unverified Alert -->
                <?php if ($counts['unverified'] > 0 && $status !== 'unverified'): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <strong><?php echo $counts['unverified']; ?> user<?php echo $counts['unverified'] === 1 ? '' : 's'; ?> awaiting verification</strong>
                            <p class="mb-0 small">Review and verify user accounts to grant full platform access.</p>
                        </div>
                        <a href="?status=unverified" class="btn btn-warning">
                            Review Now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <!-- User Type Filter -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">User Type</label>
                                <select name="type" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $userType === 'all' ? 'selected' : ''; ?>>All Users</option>
                                    <option value="student" <?php echo $userType === 'student' ? 'selected' : ''; ?>>
                                        Students (<?php echo $counts['students']; ?>)
                                    </option>
                                    <option value="landlord" <?php echo $userType === 'landlord' ? 'selected' : ''; ?>>
                                        Landlords (<?php echo $counts['landlords']; ?>)
                                    </option>
                                    <option value="admin" <?php echo $userType === 'admin' ? 'selected' : ''; ?>>
                                        Admins (<?php echo $counts['admins']; ?>)
                                    </option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Account Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                                        Active (<?php echo $counts['active']; ?>)
                                    </option>
                                    <option value="unverified" <?php echo $status === 'unverified' ? 'selected' : ''; ?>>
                                        Unverified (<?php echo $counts['unverified']; ?>)
                                    </option>
                                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>
                                        Suspended (<?php echo $counts['suspended']; ?>)
                                    </option>
                                </select>
                            </div>

                            <!-- Search -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Search</label>
                                <input type="text" name="search" class="form-control"
                                       placeholder="Search by name, email, phone..."
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>

                            <!-- Buttons -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold d-none d-md-block">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <?php if ($search || $userType !== 'all' || $status !== 'all'): ?>
                                        <a href="manage_users.php" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-redo"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users List -->
                <?php if (empty($users)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-5x text-muted mb-4"></i>
                        <h3 class="text-muted">No users found</h3>
                        <p class="text-muted">Try adjusting your filters or search terms.</p>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
                                                         style="width: 40px; height: 40px; font-size: 16px;">
                                                        <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                        <?php if ($user['account_verified']): ?>
                                                            <i class="fas fa-check-circle text-success" title="Verified"></i>
                                                        <?php endif; ?>
                                                        <?php if ($user['email_verified']): ?>
                                                            <i class="fas fa-envelope-circle-check text-info" title="Email Verified"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $typeClass = [
                                                    'student' => 'bg-primary',
                                                    'landlord' => 'bg-success',
                                                    'admin' => 'bg-danger'
                                                ][$user['user_type']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $typeClass; ?>">
                                                    <?php echo ucfirst($user['user_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="d-block"><?php echo htmlspecialchars($user['email']); ?></small>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['phone']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($user['account_status'] === 'suspended'): ?>
                                                    <span class="badge bg-danger">Suspended</span>
                                                <?php elseif (!$user['account_verified']): ?>
                                                    <span class="badge bg-warning text-dark">Unverified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo timeAgo($user['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php if ($user['last_login']): ?>
                                                        <?php echo timeAgo($user['last_login']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Never</span>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <a href="user_details.php?id=<?php echo $user['id']; ?>"
                                                   class="btn btn-sm btn-primary"
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i> View
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
                        <nav aria-label="Users pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?type=<?php echo $userType; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?type=<?php echo $userType; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?type=<?php echo $userType; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">
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
