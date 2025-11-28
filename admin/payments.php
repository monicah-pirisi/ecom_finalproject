<?php
/**
 * Admin Payments Page
 * CampusDigs Kenya - View all payments and revenue
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/payment_controller.php';

// Check if user is logged in as admin
requireLogin();
if ($_SESSION['user_type'] !== 'admin') {
    redirectWithMessage('../index.php', 'Access denied', 'error');
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build filters array
$filters = [];
if ($status_filter !== 'all') {
    $filters['status'] = $status_filter;
}
if ($date_from) {
    $filters['date_from'] = $date_from . ' 00:00:00';
}
if ($date_to) {
    $filters['date_to'] = $date_to . ' 23:59:59';
}

// Get payments
$payments = getPaymentHistory($filters, 50, 0);
$stats = getPaymentStatistics();

$page_title = 'Payment Management';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CampusDigs Kenya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stats-card .icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }
        .stats-card .value {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .table-responsive {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-money-bill-wave"></i> Payment Management</h1>
                <p class="text-muted">Track all payments and revenue</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card bg-primary text-white">
                    <i class="fas fa-wallet icon float-end"></i>
                    <div>Total Revenue</div>
                    <div class="value">KES <?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <small><?php echo $stats['total_payments']; ?> payments</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card bg-success text-white">
                    <i class="fas fa-calendar-alt icon float-end"></i>
                    <div>This Month</div>
                    <div class="value">KES <?php echo number_format($stats['monthly_revenue'], 2); ?></div>
                    <small>Current month revenue</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card bg-warning text-white">
                    <i class="fas fa-clock icon float-end"></i>
                    <div>Pending Payments</div>
                    <div class="value"><?php echo $stats['pending_payments']; ?></div>
                    <small>Awaiting completion</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card bg-info text-white">
                    <i class="fas fa-chart-line icon float-end"></i>
                    <div>Average Payment</div>
                    <div class="value">
                        KES <?php
                        $avg = $stats['total_payments'] > 0 ? $stats['total_revenue'] / $stats['total_payments'] : 0;
                        echo number_format($avg, 2);
                        ?>
                    </div>
                    <small>Per transaction</small>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Payment Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="payments.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="table-responsive">
            <h5 class="mb-3">Recent Payments</h5>
            <?php if (empty($payments)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-receipt fa-3x mb-3"></i>
                    <p>No payments found</p>
                </div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Property</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>#<?php echo $payment['id']; ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($payment['student_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($payment['student_email']); ?></small>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($payment['property_title']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($payment['property_location']); ?></small>
                                </td>
                                <td>
                                    <strong>KES <?php echo number_format($payment['amount'], 2); ?></strong>
                                </td>
                                <td><?php echo ucfirst($payment['payment_method']); ?></td>
                                <td>
                                    <code class="small"><?php echo htmlspecialchars($payment['payment_reference']); ?></code>
                                </td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        'refunded' => 'info'
                                    ];
                                    $badge_class = $status_class[$payment['payment_status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></small><br>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($payment['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../view/payment_success.php?reference=<?php echo urlencode($payment['payment_reference']); ?>"
                                           class="btn btn-outline-primary" title="View Receipt">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($payment['payment_status'] === 'completed'): ?>
                                            <button class="btn btn-outline-danger"
                                                    onclick="processRefund(<?php echo $payment['id']; ?>)"
                                                    title="Process Refund">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processRefund(paymentId) {
            if (!confirm('Are you sure you want to process a refund for this payment?')) {
                return;
            }

            const reason = prompt('Enter refund reason:');
            if (!reason) {
                return;
            }

            // In a real implementation, this would call an API endpoint
            alert('Refund processing will be implemented with proper Paystack integration.');
        }
    </script>
</body>
</html>
