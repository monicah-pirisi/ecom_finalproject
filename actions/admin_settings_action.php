<?php
/**
 * CampusDigs Kenya - Admin Settings Action
 * Handles saving system settings
 */

session_start();

require_once '../includes/config.php';
require_once '../includes/core.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirectWithMessage('../login/login.php', 'Unauthorized access', 'error');
}

$adminId = $_SESSION['user_id'];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('../admin/settings.php', 'Invalid request method', 'error');
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    redirectWithMessage('../admin/settings.php', 'Invalid security token', 'error');
}

try {
    global $conn;

    // Prepare settings array
    $settings = [
        // Platform Settings
        'commission_rate' => isset($_POST['commission_rate']) ? (float)$_POST['commission_rate'] : 10,
        'min_property_price' => isset($_POST['min_property_price']) ? (int)$_POST['min_property_price'] : 5000,
        'max_property_price' => isset($_POST['max_property_price']) ? (int)$_POST['max_property_price'] : 100000,
        'min_lease_duration' => isset($_POST['min_lease_duration']) ? (int)$_POST['min_lease_duration'] : 1,
        'processing_fee' => isset($_POST['processing_fee']) ? (int)$_POST['processing_fee'] : 500,

        // Email Notifications
        'email_notifications_enabled' => isset($_POST['email_notifications_enabled']) ? 1 : 0,
        'email_booking_confirmation' => isset($_POST['email_booking_confirmation']) ? 1 : 0,
        'email_payment_reminder' => isset($_POST['email_payment_reminder']) ? 1 : 0,
        'email_property_approval' => isset($_POST['email_property_approval']) ? 1 : 0,
        'email_from_name' => isset($_POST['email_from_name']) ? sanitizeInput($_POST['email_from_name']) : 'CampusDigs Kenya',
        'email_from_address' => isset($_POST['email_from_address']) ? sanitizeInput($_POST['email_from_address']) : 'noreply@campusdigs.co.ke',

        // SMS Settings
        'sms_enabled' => isset($_POST['sms_enabled']) ? 1 : 0,
        'africastalking_username' => isset($_POST['africastalking_username']) ? sanitizeInput($_POST['africastalking_username']) : '',
        'africastalking_api_key' => isset($_POST['africastalking_api_key']) ? $_POST['africastalking_api_key'] : '',
        'africastalking_sender_id' => isset($_POST['africastalking_sender_id']) ? sanitizeInput($_POST['africastalking_sender_id']) : 'CAMPUSDIGS',

        // Payment Settings
        'paystack_public_key' => isset($_POST['paystack_public_key']) ? sanitizeInput($_POST['paystack_public_key']) : '',
        'paystack_secret_key' => isset($_POST['paystack_secret_key']) ? $_POST['paystack_secret_key'] : '',
        'paystack_test_mode' => isset($_POST['paystack_test_mode']) ? 1 : 0,
        'payment_mpesa_enabled' => isset($_POST['payment_mpesa_enabled']) ? 1 : 0,
        'payment_card_enabled' => isset($_POST['payment_card_enabled']) ? 1 : 0,
        'payment_bank_enabled' => isset($_POST['payment_bank_enabled']) ? 1 : 0,

        // Security Settings
        'session_timeout' => isset($_POST['session_timeout']) ? (int)$_POST['session_timeout'] : 3600,
        'max_login_attempts' => isset($_POST['max_login_attempts']) ? (int)$_POST['max_login_attempts'] : 5,
        'password_min_length' => isset($_POST['password_min_length']) ? (int)$_POST['password_min_length'] : 8,
        'password_require_uppercase' => isset($_POST['password_require_uppercase']) ? 1 : 0,
        'password_require_lowercase' => isset($_POST['password_require_lowercase']) ? 1 : 0,
        'password_require_number' => isset($_POST['password_require_number']) ? 1 : 0,
        'password_require_special' => isset($_POST['password_require_special']) ? 1 : 0,
        'two_factor_enabled' => isset($_POST['two_factor_enabled']) ? 1 : 0
    ];

    // Validate settings
    if ($settings['commission_rate'] < 0 || $settings['commission_rate'] > 100) {
        throw new Exception('Commission rate must be between 0% and 100%');
    }

    if ($settings['min_property_price'] >= $settings['max_property_price']) {
        throw new Exception('Maximum property price must be greater than minimum property price');
    }

    if ($settings['min_lease_duration'] < 1) {
        throw new Exception('Minimum lease duration must be at least 1 month');
    }

    if ($settings['session_timeout'] < 300) {
        throw new Exception('Session timeout must be at least 5 minutes (300 seconds)');
    }

    if ($settings['max_login_attempts'] < 3) {
        throw new Exception('Maximum login attempts must be at least 3');
    }

    if ($settings['password_min_length'] < 6) {
        throw new Exception('Minimum password length must be at least 6 characters');
    }

    // Save each setting to database
    $savedCount = 0;
    foreach ($settings as $key => $value) {
        // Check if setting exists
        $stmt = $conn->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();

        if ($exists) {
            // Update existing setting
            $stmt = $conn->prepare("
                UPDATE system_settings
                SET setting_value = ?, updated_at = NOW(), updated_by = ?
                WHERE setting_key = ?
            ");
            $valueStr = (string)$value;
            $stmt->bind_param("sis", $valueStr, $adminId, $key);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new setting
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value, created_by, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $valueStr = (string)$value;
            $stmt->bind_param("ssi", $key, $valueStr, $adminId);
            $stmt->execute();
            $stmt->close();
        }
        $savedCount++;
    }

    // Log admin action
    $stmt = $conn->prepare("
        INSERT INTO admin_logs (admin_id, action, details, created_at)
        VALUES (?, 'update_settings', ?, NOW())
    ");
    $details = "Updated system settings ($savedCount settings changed)";
    $stmt->bind_param("is", $adminId, $details);
    $stmt->execute();
    $stmt->close();

    redirectWithMessage('../admin/settings.php', 'Settings saved successfully!', 'success');

} catch (Exception $e) {
    error_log("Error saving settings: " . $e->getMessage());
    redirectWithMessage('../admin/settings.php', $e->getMessage(), 'error');
}
?>
