<?php
/**
 * Test Paystack API Connection
 * Access this file to check if Paystack is working
 */

// Suppress errors
error_reporting(0);
ini_set('display_errors', 1);

echo "<h2>Paystack Connection Test</h2>";
echo "<hr>";

// Test 1: Check CURL
echo "<h3>1. CURL Extension:</h3>";
if (function_exists('curl_init')) {
    echo "✅ CURL is installed<br>";
} else {
    echo "❌ CURL is NOT installed<br>";
    exit;
}

// Test 2: Check connectivity
echo "<h3>2. Testing Paystack API Connectivity:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_error) {
    echo "❌ CURL Error: $curl_error<br>";
} else {
    echo "✅ Can connect to Paystack API (HTTP $http_code)<br>";
}

// Test 3: Test API Keys
echo "<h3>3. Testing API Keys:</h3>";
$secret_key = 'sk_test_914dfc2481162415ff5c512260511569b00e73d7';
$public_key = 'pk_test_6b4a00d825eb974aad18ecf340d9daf6e3859aaf';

echo "Secret Key: " . substr($secret_key, 0, 15) . "...<br>";
echo "Public Key: " . substr($public_key, 0, 15) . "...<br>";

// Test 4: Try to initialize a test transaction
echo "<h3>4. Testing Transaction Initialization:</h3>";

$url = 'https://api.paystack.co/transaction/initialize';
$data = [
    'email' => 'test@example.com',
    'amount' => 100000, // 1000 KES in kobo
    'currency' => 'KES',
    'reference' => 'TEST-' . time()
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $secret_key,
    'Content-Type: application/json',
    'Cache-Control: no-cache'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code<br>";

if ($curl_error) {
    echo "❌ CURL Error: $curl_error<br>";
} else {
    $result = json_decode($response, true);
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    if ($http_code === 200 && isset($result['status']) && $result['status'] === true) {
        echo "✅ <strong>SUCCESS!</strong> Paystack API is working correctly!<br>";
    } elseif ($http_code === 401) {
        echo "❌ <strong>ERROR 401:</strong> API Keys are INVALID or EXPIRED<br>";
        echo "Please check your Paystack dashboard: https://dashboard.paystack.com/settings/developer<br>";
    } else {
        echo "❌ <strong>ERROR $http_code:</strong> " . ($result['message'] ?? 'Unknown error') . "<br>";
    }
}

echo "<hr>";
echo "<p>Server: " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";
?>
