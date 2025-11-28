<?php
/**
 * Booking Payment Page
 * CampusDigs Kenya - Student pays for booking
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../includes/paystack_config.php';
require_once '../controllers/booking_controller.php';
require_once '../controllers/property_controller.php';

// Check if user is logged in as student
requireLogin();
if ($_SESSION['user_type'] !== 'student') {
    redirectWithMessage('../index.php', 'Only students can make payments', 'error');
}

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    redirectWithMessage('student_bookings.php', 'Invalid booking ID', 'error');
}

// Get booking details
$booking = getBookingById($booking_id);

if (!$booking) {
    redirectWithMessage('student_bookings.php', 'Booking not found', 'error');
}

// Verify booking belongs to logged-in student
if ($booking['student_id'] != $_SESSION['user_id']) {
    redirectWithMessage('student_bookings.php', 'Unauthorized access', 'error');
}

// Check if already paid
if ($booking['payment_status'] === 'paid') {
    redirectWithMessage('student_bookings.php', 'This booking has already been paid', 'info');
}

// Get property details
$property = getPropertyById($booking['property_id']);

if (!$property) {
    redirectWithMessage('student_bookings.php', 'Property not found', 'error');
}

// Calculate amount to pay (use booking total amount)
$amount_to_pay = $booking['total_amount'];
$payment_type = 'full'; // Can be 'full', 'deposit', or 'rent'

// Page title
$page_title = 'Payment - ' . $property['title'];
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
        :root {
            --primary-green: #059669;
            --primary-green-dark: #047857;
            --primary-green-light: #10b981;
            --secondary-gold: #d97706;
            --bg-light-green: #f0fdf4;
            --bg-light-gold: #fef3c7;
        }

        body {
            background: linear-gradient(135deg, var(--bg-light-green) 0%, var(--bg-light-gold) 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .payment-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .payment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(5, 150, 105, 0.15);
            overflow: hidden;
            border: 1px solid rgba(5, 150, 105, 0.1);
        }
        .payment-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-light) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .payment-body {
            padding: 30px;
        }
        .property-info {
            background: var(--bg-light-green);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #d1fae5;
        }
        .property-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .amount-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-green);
            text-align: center;
            margin: 30px 0;
        }
        .payment-details {
            border-top: 2px dashed #d1fae5;
            border-bottom: 2px dashed #d1fae5;
            padding: 20px 0;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .detail-row i {
            color: var(--primary-green);
            margin-right: 5px;
        }
        .detail-row strong {
            color: var(--primary-green-dark);
        }
        .pay-btn {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-light) 100%);
            border: none;
            padding: 15px;
            font-size: 1.2rem;
            border-radius: 10px;
            width: 100%;
            color: white;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);
        }
        .pay-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
            background: linear-gradient(135deg, var(--primary-green-dark) 0%, var(--primary-green) 100%);
        }
        .pay-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .secure-badge {
            text-align: center;
            color: var(--primary-green);
            margin-top: 20px;
            font-size: 0.9rem;
        }
        .secure-badge i {
            color: var(--secondary-gold);
        }
        .btn-link {
            color: var(--primary-green);
            text-decoration: none;
        }
        .btn-link:hover {
            color: var(--primary-green-dark);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <i class="fas fa-lock fa-3x mb-3"></i>
                <h2>Secure Payment</h2>
                <p class="mb-0">Powered by Paystack</p>
            </div>

            <div class="payment-body">
                <div class="property-info">
                    <?php if (!empty($property['images'])): ?>
                        <img src="<?php echo BASE_URL . '/' . $property['images']; ?>"
                             alt="<?php echo htmlspecialchars($property['title']); ?>"
                             class="property-image">
                    <?php endif; ?>

                    <h4><?php echo htmlspecialchars($property['title']); ?></h4>
                    <p class="text-muted mb-2">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($property['location']); ?>
                    </p>
                    <p class="mb-0">
                        <strong>Booking Reference:</strong> #<?php echo $booking['id']; ?>
                    </p>
                </div>

                <div class="amount-display">
                    KES <?php echo number_format($amount_to_pay, 2); ?>
                </div>

                <div class="payment-details">
                    <div class="detail-row">
                        <span><i class="fas fa-home"></i> Property Type</span>
                        <strong><?php echo htmlspecialchars($property['type'] ?? 'N/A'); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span><i class="fas fa-calendar-check"></i> Move-in Date</span>
                        <strong><?php echo date('M d, Y', strtotime($booking['move_in_date'])); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span><i class="fas fa-clock"></i> Lease Duration</span>
                        <strong><?php echo $booking['lease_duration_months']; ?> Months</strong>
                    </div>
                    <div class="detail-row">
                        <span><i class="fas fa-money-bill-wave"></i> Total Amount</span>
                        <strong>KES <?php echo number_format($booking['total_amount'], 2); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span><i class="fas fa-user"></i> Guest Name</span>
                        <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span><i class="fas fa-envelope"></i> Email</span>
                        <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong>
                    </div>
                </div>

                <button id="pay-btn" class="pay-btn">
                    <i class="fas fa-credit-card"></i> Pay Now
                </button>

                <div class="secure-badge">
                    <i class="fas fa-shield-alt"></i>
                    Your payment is secure and encrypted
                </div>

                <div class="text-center mt-3">
                    <a href="student_bookings.php" class="btn btn-link">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const payBtn = document.getElementById('pay-btn');
        const bookingId = <?php echo $booking_id; ?>;
        const propertyId = <?php echo $property['id']; ?>;
        const amount = <?php echo $amount_to_pay; ?>;
        const paymentType = '<?php echo $payment_type; ?>';

        payBtn.addEventListener('click', function() {
            // Disable button
            payBtn.disabled = true;
            payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            // Initialize payment with Paystack
            fetch('<?php echo BASE_URL; ?>/actions/paystack_initialize.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    booking_id: bookingId,
                    property_id: propertyId,
                    amount: amount,
                    payment_type: paymentType
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Payment initialization response:', data);

                if (data.status === 'success') {
                    // Redirect to Paystack payment page
                    window.location.href = data.authorization_url;
                } else {
                    // Show error
                    alert('Payment initialization failed: ' + (data.message || 'Unknown error'));

                    // Re-enable button
                    payBtn.disabled = false;
                    payBtn.innerHTML = '<i class="fas fa-credit-card"></i> Pay Now';
                }
            })
            .catch(error => {
                console.error('Payment initialization error:', error);
                alert('Network error. Please check your connection and try again.');

                // Re-enable button
                payBtn.disabled = false;
                payBtn.innerHTML = '<i class="fas fa-credit-card"></i> Pay Now';
            });
        });
    </script>
</body>
</html>
