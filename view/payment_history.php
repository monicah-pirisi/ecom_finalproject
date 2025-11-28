<?php
/**
 * Payment History Page
 * CampusDigs Kenya - Student payment history
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/payment_controller.php';
require_once '../controllers/property_controller.php';

// Check if user is logged in as student
requireLogin();
if ($_SESSION['user_type'] !== 'student') {
    redirectWithMessage('../index.php', 'Access denied', 'error');
}

$student_id = $_SESSION['user_id'];

// Get all payments for this student
$payments = getStudentPayments($student_id);
$total_paid = getStudentTotalPayments($student_id);

$page_title = 'My Payment History';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CampusDigs Kenya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .payment-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .payment-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 5px 12px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../dashboard_student.php"><i class="fas fa-home"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="all_properties.php"><i class="fas fa-search"></i> Browse Properties</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_wishlist.php"><i class="fas fa-heart"></i> My Wishlist</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="payment_history.php"><i class="fas fa-receipt"></i> Payment History</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_profile.php"><i class="fas fa-user"></i> My Profile</a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-history text-primary"></i> Payment History</h1>
                </div>

                <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <i class="fas fa-wallet fa-2x text-primary mb-2"></i>
                    <div class="number">KES <?php echo number_format($total_paid, 2); ?></div>
                    <div class="text-muted">Total Paid</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <i class="fas fa-receipt fa-2x text-success mb-2"></i>
                    <div class="number"><?php echo count($payments); ?></div>
                    <div class="text-muted">Total Payments</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                    <div class="number">
                        <?php
                        $completed = array_filter($payments, function($p) {
                            return $p['payment_status'] === 'completed';
                        });
                        echo count($completed);
                        ?>
                    </div>
                    <div class="text-muted">Completed</div>
                </div>
            </div>
        </div>

        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <h3>No Payment History</h3>
                <p class="text-muted">You haven't made any payments yet.</p>
                <a href="all_properties.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Browse Properties
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <h4 class="mb-3">All Payments</h4>

                    <?php foreach ($payments as $payment): ?>
                        <div class="payment-card">
                            <div class="payment-header">
                                <div>
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($payment['property_title']); ?>
                                    </h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($payment['property_location']); ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <div class="payment-amount">
                                        KES <?php echo number_format($payment['amount'], 2); ?>
                                    </div>
                                    <?php
                                    $status_class = [
                                        'completed' => 'bg-success',
                                        'pending' => 'bg-warning',
                                        'failed' => 'bg-danger',
                                        'refunded' => 'bg-info'
                                    ];
                                    $badge_class = $status_class[$payment['payment_status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> status-badge">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Payment Reference</small>
                                    <code><?php echo htmlspecialchars($payment['payment_reference']); ?></code>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Payment Date</small>
                                    <strong>
                                        <?php echo date('M j, Y - g:i A', strtotime($payment['created_at'])); ?>
                                    </strong>
                                </div>
                                <?php if ($payment['check_in_date'] && $payment['check_out_date']): ?>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Check-in</small>
                                    <strong>
                                        <?php echo date('M j, Y', strtotime($payment['check_in_date'])); ?>
                                    </strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Check-out</small>
                                    <strong>
                                        <?php echo date('M j, Y', strtotime($payment['check_out_date'])); ?>
                                    </strong>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Payment Method</small>
                                    <strong><?php echo ucfirst($payment['payment_method']); ?></strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Currency</small>
                                    <strong><?php echo $payment['currency']; ?></strong>
                                </div>
                            </div>

                            <div class="mt-3 d-flex gap-2">
                                <a href="payment_success.php?reference=<?php echo urlencode($payment['payment_reference']); ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View Receipt
                                </a>
                                <?php if ($payment['booking_id']): ?>
                                <a href="booking_details.php?id=<?php echo $payment['booking_id']; ?>"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-calendar"></i> View Booking
                                </a>
                                <?php endif; ?>
                                <a href="property_details.php?id=<?php echo $payment['property_id']; ?>"
                                   class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-home"></i> View Property
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
