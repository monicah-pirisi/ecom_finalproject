<?php
/**
 * Paystack Payment Gateway Configuration - TEMPLATE FILE
 * CampusDigs Kenya - Property Booking Payment System
 *
 * INSTRUCTIONS:
 * 1. Copy this file and rename it to 'paystack_config.php'
 * 2. Replace the test keys with your actual Paystack keys
 * 3. For production, use your LIVE keys from Paystack dashboard
 * 4. Never commit paystack_config.php to version control
 */

// Paystack API Keys - Kenya Account
// For production: Use live keys from your Paystack dashboard
if (!defined('PAYSTACK_SECRET_KEY')) {
    $secret_key = getenv('PAYSTACK_SECRET_KEY_LIVE');
    if (!$secret_key || ENVIRONMENT === 'development') {
        $secret_key = 'sk_test_YOUR_TEST_SECRET_KEY'; // Replace with your test key
    }
    define('PAYSTACK_SECRET_KEY', $secret_key);
}
if (!defined('PAYSTACK_PUBLIC_KEY')) {
    $public_key = getenv('PAYSTACK_PUBLIC_KEY_LIVE');
    if (!$public_key || ENVIRONMENT === 'development') {
        $public_key = 'pk_test_YOUR_TEST_PUBLIC_KEY'; // Replace with your test key
    }
    define('PAYSTACK_PUBLIC_KEY', $public_key);
}

// Paystack API Endpoints
if (!defined('PAYSTACK_API_URL')) {
    define('PAYSTACK_API_URL', 'https://api.paystack.co');
}
if (!defined('PAYSTACK_INITIALIZE_URL')) {
    define('PAYSTACK_INITIALIZE_URL', PAYSTACK_API_URL . '/transaction/initialize');
}
if (!defined('PAYSTACK_VERIFY_URL')) {
    define('PAYSTACK_VERIFY_URL', PAYSTACK_API_URL . '/transaction/verify/');
}

// Payment Configuration
if (!defined('PAYSTACK_CURRENCY')) {
    define('PAYSTACK_CURRENCY', 'KES'); // Kenyan Shillings (Kenya Paystack Account)
}
if (!defined('PAYSTACK_CALLBACK_URL')) {
    define('PAYSTACK_CALLBACK_URL', BASE_URL . '/actions/paystack_callback.php');
}

// Include the rest of the functions from the original file...
?>
