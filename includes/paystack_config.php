<?php
/**
 * Paystack Payment Gateway Configuration
 * CampusDigs Kenya - Property Booking Payment System
 *
 * PRODUCTION DEPLOYMENT:
 * Replace test keys with live keys from Paystack dashboard OR set environment variables:
 * - PAYSTACK_SECRET_KEY_LIVE
 * - PAYSTACK_PUBLIC_KEY_LIVE
 */

// Paystack API Keys - Kenya Account
// For production: Use environment variables or replace test keys with live keys
if (!defined('PAYSTACK_SECRET_KEY')) {
    $secret_key = getenv('PAYSTACK_SECRET_KEY_LIVE');
    if (!$secret_key || ENVIRONMENT === 'development') {
        $secret_key = 'sk_test_914dfc2481162415ff5c512260511569b00e73d7'; // Test key
    }
    define('PAYSTACK_SECRET_KEY', $secret_key);
}
if (!defined('PAYSTACK_PUBLIC_KEY')) {
    $public_key = getenv('PAYSTACK_PUBLIC_KEY_LIVE');
    if (!$public_key || ENVIRONMENT === 'development') {
        $public_key = 'pk_test_6b4a00d825eb974aad18ecf340d9daf6e3859aaf'; // Test key
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

/**
 * Initialize Paystack transaction
 * @param float $amount Amount in KES
 * @param string $email Customer email
 * @param string $reference Unique transaction reference
 * @param array $metadata Optional metadata
 * @return array|false Response from Paystack API
 */
function paystack_initialize_transaction($amount, $email, $reference, $metadata = []) {
    // Convert amount to kobo (smallest currency unit) - 1 KES = 100 cents
    $amount_in_cents = $amount * 100;

    // Prepare request data
    $data = [
        'email' => $email,
        'amount' => $amount_in_cents,
        'reference' => $reference,
        'currency' => PAYSTACK_CURRENCY,
        'callback_url' => PAYSTACK_CALLBACK_URL,
        'metadata' => array_merge([
            'custom_fields' => []
        ], $metadata)
    ];

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYSTACK_INITIALIZE_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
        'Cache-Control: no-cache'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return false;
    }

    if ($http_code !== 200) {
        return false;
    }

    // Parse response
    $result = json_decode($response, true);
    return $result;
}

/**
 * Verify Paystack transaction
 * @param string $reference Transaction reference
 * @return array|false Response from Paystack API
 */
function paystack_verify_transaction($reference) {
    $url = PAYSTACK_VERIFY_URL . $reference;

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Cache-Control: no-cache'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return false;
    }

    if ($http_code !== 200) {
        return false;
    }

    // Parse response
    $result = json_decode($response, true);
    return $result;
}

/**
 * Format amount for display
 * @param float $amount Amount in KES
 * @return string Formatted amount
 */
function format_paystack_amount($amount) {
    return 'KES ' . number_format($amount, 2);
}

/**
 * Generate unique payment reference
 * @param int $booking_id Booking ID
 * @param int $student_id Student ID
 * @return string Unique reference
 */
function generate_payment_reference($booking_id, $student_id) {
    return 'CDIGS-B' . $booking_id . '-U' . $student_id . '-' . time();
}
?>
