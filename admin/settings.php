<?php
/**
 * CampusDigs Kenya - Admin System Settings
 * Configure platform settings, notifications, payments, and security
 */

require_once '../includes/config.php';
require_once '../includes/core.php';

requireAdmin();

$adminId = $_SESSION['user_id'];
$flash = getFlashMessage();

// Get current settings from database
$settings = getSystemSettings();

// If no settings exist, use defaults
if (empty($settings)) {
    $settings = [
        // Platform Settings
        'commission_rate' => 10,
        'min_property_price' => 5000,
        'max_property_price' => 100000,
        'min_lease_duration' => 1,
        'processing_fee' => 500,

        // Email Notifications
        'email_notifications_enabled' => 1,
        'email_booking_confirmation' => 1,
        'email_payment_reminder' => 1,
        'email_property_approval' => 1,
        'email_from_name' => 'CampusDigs Kenya',
        'email_from_address' => 'noreply@campusdigs.co.ke',

        // SMS Settings
        'sms_enabled' => 1,
        'africastalking_username' => '',
        'africastalking_api_key' => '',
        'africastalking_sender_id' => 'CAMPUSDIGS',

        // Payment Settings
        'paystack_public_key' => '',
        'paystack_secret_key' => '',
        'paystack_test_mode' => 1,
        'payment_mpesa_enabled' => 1,
        'payment_card_enabled' => 1,
        'payment_bank_enabled' => 1,

        // Security Settings
        'session_timeout' => 3600,
        'max_login_attempts' => 5,
        'password_min_length' => 8,
        'password_require_uppercase' => 1,
        'password_require_lowercase' => 1,
        'password_require_number' => 1,
        'password_require_special' => 0,
        'two_factor_enabled' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">

    <style>
        .settings-card {
            border-left: 4px solid;
        }
        .settings-card.platform { border-left-color: #4e73df; }
        .settings-card.email { border-left-color: #1cc88a; }
        .settings-card.sms { border-left-color: #36b9cc; }
        .settings-card.payment { border-left-color: #f6c23e; }
        .settings-card.security { border-left-color: #e74a3b; }

        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
        }

        .alert-warning {
            border-left: 4px solid #f6c23e;
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
                            <a class="nav-link" href="moderate_reviews.php">
                                <i class="fas fa-star"></i> Reviews
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-line"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="settings.php">
                                <i class="fas fa-cog"></i> Settings
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
                        <i class="fas fa-cog text-primary"></i> System Settings
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" onclick="saveAllSettings()">
                            <i class="fas fa-save"></i> Save All Changes
                        </button>
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

                <!-- Warning Alert -->
                <div class="alert alert-warning d-flex align-items-center mb-4">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <strong>Important:</strong> Changes to these settings will affect the entire platform.
                        Make sure you understand the impact before saving.
                    </div>
                </div>

                <form id="settingsForm" method="POST" action="../actions/admin_settings_action.php">
                    <?php csrfTokenField(); ?>

                    <!-- PLATFORM SETTINGS -->
                    <div class="card settings-card platform shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-sliders-h"></i> Platform Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-percentage"></i> Commission Rate (%)
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           name="commission_rate"
                                           value="<?php echo $settings['commission_rate']; ?>"
                                           min="0"
                                           max="100"
                                           step="0.1"
                                           required>
                                    <small class="text-muted">Platform commission on each booking (currently <?php echo $settings['commission_rate']; ?>%)</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-money-bill"></i> Processing Fee (KES)
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           name="processing_fee"
                                           value="<?php echo $settings['processing_fee']; ?>"
                                           min="0"
                                           step="1"
                                           required>
                                    <small class="text-muted">Fixed fee charged per transaction</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-arrow-down"></i> Minimum Property Price (KES)
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           name="min_property_price"
                                           value="<?php echo $settings['min_property_price']; ?>"
                                           min="0"
                                           step="100"
                                           required>
                                    <small class="text-muted">Minimum allowed monthly rent</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-arrow-up"></i> Maximum Property Price (KES)
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           name="max_property_price"
                                           value="<?php echo $settings['max_property_price']; ?>"
                                           min="0"
                                           step="100"
                                           required>
                                    <small class="text-muted">Maximum allowed monthly rent</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar-alt"></i> Minimum Lease Duration (months)
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           name="min_lease_duration"
                                           value="<?php echo $settings['min_lease_duration']; ?>"
                                           min="1"
                                           max="12"
                                           required>
                                    <small class="text-muted">Minimum lease period students can book</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EMAIL NOTIFICATIONS -->
                    <div class="card settings-card email shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-envelope"></i> Email Notifications
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="email_notifications_enabled"
                                               id="emailEnabled"
                                               value="1"
                                               <?php echo $settings['email_notifications_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="emailEnabled">
                                            Enable Email Notifications
                                        </label>
                                    </div>
                                    <hr>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">From Name</label>
                                    <input type="text"
                                           class="form-control"
                                           name="email_from_name"
                                           value="<?php echo htmlspecialchars($settings['email_from_name']); ?>"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">From Email Address</label>
                                    <input type="email"
                                           class="form-control"
                                           name="email_from_address"
                                           value="<?php echo htmlspecialchars($settings['email_from_address']); ?>"
                                           required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold mb-3">Enable Specific Notifications:</label>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="email_booking_confirmation"
                                               id="emailBooking"
                                               value="1"
                                               <?php echo $settings['email_booking_confirmation'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="emailBooking">
                                            Booking Confirmations
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="email_payment_reminder"
                                               id="emailPayment"
                                               value="1"
                                               <?php echo $settings['email_payment_reminder'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="emailPayment">
                                            Payment Reminders
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="email_property_approval"
                                               id="emailProperty"
                                               value="1"
                                               <?php echo $settings['email_property_approval'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="emailProperty">
                                            Property Approval Alerts
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SMS SETTINGS -->
                    <div class="card settings-card sms shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-sms"></i> SMS Settings (Africa's Talking)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="sms_enabled"
                                               id="smsEnabled"
                                               value="1"
                                               <?php echo $settings['sms_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="smsEnabled">
                                            Enable SMS Notifications
                                        </label>
                                    </div>
                                    <hr>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Africa's Talking Username</label>
                                    <input type="text"
                                           class="form-control"
                                           name="africastalking_username"
                                           value="<?php echo htmlspecialchars($settings['africastalking_username']); ?>"
                                           placeholder="sandbox">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold">API Key</label>
                                    <input type="password"
                                           class="form-control"
                                           name="africastalking_api_key"
                                           value="<?php echo htmlspecialchars($settings['africastalking_api_key']); ?>"
                                           placeholder="Enter API key">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Sender ID</label>
                                    <input type="text"
                                           class="form-control"
                                           name="africastalking_sender_id"
                                           value="<?php echo htmlspecialchars($settings['africastalking_sender_id']); ?>"
                                           placeholder="CAMPUSDIGS"
                                           maxlength="11">
                                    <small class="text-muted">Max 11 characters</small>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>SMS Credits:</strong> Check your balance at
                                        <a href="https://account.africastalking.com" target="_blank">Africa's Talking Dashboard</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PAYMENT SETTINGS -->
                    <div class="card settings-card payment shadow-sm mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card"></i> Payment Settings (Paystack)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="paystack_test_mode"
                                               id="paystackTestMode"
                                               value="1"
                                               <?php echo $settings['paystack_test_mode'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="paystackTestMode">
                                            Test Mode (Use Paystack test keys)
                                        </label>
                                    </div>
                                    <hr>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Paystack Public Key</label>
                                    <input type="text"
                                           class="form-control"
                                           name="paystack_public_key"
                                           value="<?php echo htmlspecialchars($settings['paystack_public_key']); ?>"
                                           placeholder="pk_test_...">
                                    <small class="text-muted">Public key from Paystack dashboard</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Paystack Secret Key</label>
                                    <input type="password"
                                           class="form-control"
                                           name="paystack_secret_key"
                                           value="<?php echo htmlspecialchars($settings['paystack_secret_key']); ?>"
                                           placeholder="sk_test_...">
                                    <small class="text-muted">Secret key from Paystack dashboard</small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold mb-3">Enable Payment Methods:</label>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="payment_mpesa_enabled"
                                               id="mpesaEnabled"
                                               value="1"
                                               <?php echo $settings['payment_mpesa_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mpesaEnabled">
                                            <i class="fas fa-mobile-alt text-success"></i> M-Pesa
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="payment_card_enabled"
                                               id="cardEnabled"
                                               value="1"
                                               <?php echo $settings['payment_card_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cardEnabled">
                                            <i class="fas fa-credit-card text-primary"></i> Card Payments
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="payment_bank_enabled"
                                               id="bankEnabled"
                                               value="1"
                                               <?php echo $settings['payment_bank_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="bankEnabled">
                                            <i class="fas fa-university text-info"></i> Bank Transfer
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECURITY SETTINGS -->
                    <div class="card settings-card security shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shield-alt"></i> Security Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-clock"></i> Session Timeout (seconds)
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           name="session_timeout"
                                           value="<?php echo $settings['session_timeout']; ?>"
                                           min="300"
                                           max="86400"
                                           required>
                                    <small class="text-muted">Auto-logout after inactivity (<?php echo round($settings['session_timeout'] / 60); ?> minutes)</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-exclamation-triangle"></i> Max Login Attempts
                                    </label>
                                    <input type="number"
                                           class="form-control"
                                           name="max_login_attempts"
                                           value="<?php echo $settings['max_login_attempts']; ?>"
                                           min="3"
                                           max="10"
                                           required>
                                    <small class="text-muted">Failed attempts before account lockout</small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold mb-3">Password Requirements:</label>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Minimum Password Length</label>
                                    <input type="number"
                                           class="form-control"
                                           name="password_min_length"
                                           value="<?php echo $settings['password_min_length']; ?>"
                                           min="6"
                                           max="32"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="two_factor_enabled"
                                               id="twoFactorEnabled"
                                               value="1"
                                               <?php echo $settings['two_factor_enabled'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="twoFactorEnabled">
                                            Enable Two-Factor Authentication (2FA)
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="password_require_uppercase"
                                               id="requireUppercase"
                                               value="1"
                                               <?php echo $settings['password_require_uppercase'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="requireUppercase">
                                            Require Uppercase Letter
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="password_require_lowercase"
                                               id="requireLowercase"
                                               value="1"
                                               <?php echo $settings['password_require_lowercase'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="requireLowercase">
                                            Require Lowercase Letter
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="password_require_number"
                                               id="requireNumber"
                                               value="1"
                                               <?php echo $settings['password_require_number'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="requireNumber">
                                            Require Number
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="password_require_special"
                                               id="requireSpecial"
                                               value="1"
                                               <?php echo $settings['password_require_special'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="requireSpecial">
                                            Require Special Character
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="text-center mb-5">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save"></i> Save All Settings
                        </button>
                        <a href="../dashboard_admin.php" class="btn btn-outline-secondary btn-lg px-5 ms-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        function saveAllSettings() {
            document.getElementById('settingsForm').submit();
        }

        // Validate commission rate
        document.querySelector('input[name="commission_rate"]').addEventListener('change', function() {
            if (this.value < 0 || this.value > 100) {
                alert('Commission rate must be between 0% and 100%');
                this.value = 10;
            }
        });

        // Validate price range
        document.querySelector('input[name="min_property_price"]').addEventListener('change', validatePriceRange);
        document.querySelector('input[name="max_property_price"]').addEventListener('change', validatePriceRange);

        function validatePriceRange() {
            const min = parseInt(document.querySelector('input[name="min_property_price"]').value);
            const max = parseInt(document.querySelector('input[name="max_property_price"]').value);

            if (min >= max) {
                alert('Maximum price must be greater than minimum price');
                document.querySelector('input[name="max_property_price"]').value = min + 1000;
            }
        }

        // Confirm before submitting
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to save these settings? This will affect the entire platform.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
