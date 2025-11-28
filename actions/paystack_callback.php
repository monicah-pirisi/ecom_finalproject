<?php
/**
 * Paystack Payment Callback Page
 * CampusDigs Kenya - User returns here after payment
 */

require_once '../includes/config.php';
require_once '../includes/core.php';

// Check if user is logged in
requireLogin();

// Get reference from URL parameter
$reference = isset($_GET['reference']) ? sanitizeInput($_GET['reference']) : '';
$trxref = isset($_GET['trxref']) ? sanitizeInput($_GET['trxref']) : $reference;

if (!$reference && !$trxref) {
    redirectWithMessage('../index.php', 'Invalid payment reference', 'error');
}

// Use trxref if reference is not set (Paystack standard)
$payment_reference = $reference ?: $trxref;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - CampusDigs Kenya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #059669;
            --primary-green-dark: #047857;
            --primary-green-light: #10b981;
            --secondary-gold: #d97706;
        }

        body {
            background: linear-gradient(135deg, #f0fdf4 0%, #fef3c7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .payment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary-green) !important;
        }
        .success-icon {
            font-size: 4rem;
            color: var(--primary-green-light);
        }
        .error-icon {
            font-size: 4rem;
            color: #ef4444;
        }
        .btn-success {
            background: var(--primary-green);
            border-color: var(--primary-green);
        }
        .btn-success:hover {
            background: var(--primary-green-dark);
            border-color: var(--primary-green-dark);
        }
        .btn-primary {
            background: var(--primary-green);
            border-color: var(--primary-green);
        }
        .btn-primary:hover {
            background: var(--primary-green-dark);
            border-color: var(--primary-green-dark);
        }
        .text-success {
            color: var(--primary-green) !important;
        }
        .text-primary {
            color: var(--primary-green) !important;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <div id="verifying" class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h4>Verifying Payment...</h4>
            <p class="text-muted">Please wait while we confirm your payment</p>
        </div>

        <div id="success" class="text-center" style="display: none;">
            <i class="fas fa-check-circle success-icon mb-3"></i>
            <h3 class="text-success mb-3">Payment Successful!</h3>
            <p class="mb-4">Your booking has been confirmed.</p>
            <div id="payment-details" class="text-start mb-4"></div>
            <a href="<?php echo BASE_URL; ?>/view/student_bookings.php" class="btn btn-success btn-lg w-100">
                <i class="fas fa-calendar-check"></i> View My Bookings
            </a>
        </div>

        <div id="error" class="text-center" style="display: none;">
            <i class="fas fa-times-circle error-icon mb-3"></i>
            <h3 class="text-danger mb-3">Payment Failed</h3>
            <p id="error-message" class="mb-4"></p>
            <a href="<?php echo BASE_URL; ?>/view/all_properties.php" class="btn btn-primary btn-lg w-100 mb-2">
                <i class="fas fa-search"></i> Browse Properties
            </a>
            <a href="<?php echo BASE_URL; ?>/view/support.php" class="btn btn-outline-secondary w-100">
                <i class="fas fa-headset"></i> Contact Support
            </a>
        </div>
    </div>

    <script>
        // Verify payment on page load
        document.addEventListener('DOMContentLoaded', function() {
            const reference = '<?php echo $payment_reference; ?>';

            fetch('<?php echo BASE_URL; ?>/actions/paystack_verify_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reference: reference })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Verification response:', data);

                document.getElementById('verifying').style.display = 'none';

                if (data.status === 'success' && data.verified) {
                    // Show success
                    document.getElementById('success').style.display = 'block';

                    // Display payment details
                    const detailsHtml = `
                        <div class="card bg-light">
                            <div class="card-body">
                                <p class="mb-2"><strong>Property:</strong> ${data.property_title}</p>
                                <p class="mb-2"><strong>Amount Paid:</strong> KES ${data.amount_paid}</p>
                                <p class="mb-2"><strong>Payment Date:</strong> ${data.payment_date}</p>
                                <p class="mb-0"><strong>Reference:</strong> <code>${data.payment_reference}</code></p>
                            </div>
                        </div>
                    `;
                    document.getElementById('payment-details').innerHTML = detailsHtml;
                } else {
                    // Show error
                    document.getElementById('error').style.display = 'block';
                    document.getElementById('error-message').textContent = data.message || 'Payment verification failed';
                }
            })
            .catch(error => {
                console.error('Verification error:', error);
                document.getElementById('verifying').style.display = 'none';
                document.getElementById('error').style.display = 'block';
                document.getElementById('error-message').textContent = 'Network error. Please check your connection and try again.';
            });
        });
    </script>
</body>
</html>
