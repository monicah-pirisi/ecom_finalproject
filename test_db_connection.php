<?php
/**
 * Database Connection Test Script
 * Use this to verify database connection on server
 * DELETE THIS FILE after testing for security!
 */

echo "<h2>CampusDigs - Database Connection Test</h2>";
echo "<hr>";

// Test 1: Check environment detection
echo "<h3>1. Environment Detection</h3>";
$serverName = $_SERVER['SERVER_NAME'];
echo "Server Name: <strong>$serverName</strong><br>";

if ($serverName === 'localhost' || $serverName === '127.0.0.1') {
    echo "Environment: <strong style='color: blue;'>DEVELOPMENT (Localhost)</strong><br>";
    echo "Expected Database: <strong>campus_digs</strong><br>";
} else {
    echo "Environment: <strong style='color: green;'>PRODUCTION (Server)</strong><br>";
    echo "Expected Database: <strong>ecommerce_2025A_monicah_lekupe</strong><br>";
}

echo "<hr>";

// Test 2: Check if db_cred.php exists
echo "<h3>2. Database Credentials File</h3>";
$dbCredPath = __DIR__ . '/includes/db_cred.php';
if (file_exists($dbCredPath)) {
    echo "✅ <strong style='color: green;'>includes/db_cred.php EXISTS</strong><br>";
    echo "Will use SERVER database credentials<br>";
} else {
    echo "❌ <strong style='color: orange;'>includes/db_cred.php NOT FOUND</strong><br>";
    echo "Will use LOCALHOST defaults<br>";
}

echo "<hr>";

// Test 3: Load configuration and test connection
echo "<h3>3. Database Connection Test</h3>";

try {
    require_once 'includes/config.php';

    echo "Database Host: <strong>" . DB_HOST . "</strong><br>";
    echo "Database User: <strong>" . DB_USER . "</strong><br>";
    echo "Database Name: <strong>" . DB_NAME . "</strong><br>";
    echo "Database Password: <strong>" . (DB_PASS ? str_repeat('*', strlen(DB_PASS)) : '(empty)') . "</strong><br>";

    echo "<br>";

    if (isset($conn) && $conn instanceof mysqli) {
        if ($conn->connect_error) {
            echo "❌ <strong style='color: red;'>CONNECTION FAILED!</strong><br>";
            echo "Error: " . $conn->connect_error . "<br>";
        } else {
            echo "✅ <strong style='color: green;'>CONNECTION SUCCESSFUL!</strong><br>";
            echo "Connected to database: <strong>" . DB_NAME . "</strong><br>";

            // Test query
            $result = $conn->query("SELECT COUNT(*) as count FROM users");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "✅ Query test successful - Found {$row['count']} users in database<br>";
            } else {
                echo "⚠️ Query test failed (table might not exist yet)<br>";
            }
        }
    } else {
        echo "❌ <strong style='color: red;'>Database connection object not created</strong><br>";
    }

} catch (Exception $e) {
    echo "❌ <strong style='color: red;'>EXCEPTION OCCURRED!</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Test 4: Configuration summary
echo "<h3>4. Configuration Summary</h3>";
echo "App Name: <strong>" . (defined('APP_NAME') ? APP_NAME : 'Not defined') . "</strong><br>";
echo "Base URL: <strong>" . (defined('BASE_URL') ? BASE_URL : 'Not defined') . "</strong><br>";
echo "Environment: <strong>" . (defined('ENVIRONMENT') ? ENVIRONMENT : 'Not defined') . "</strong><br>";

echo "<hr>";
echo "<p style='color: red; font-weight: bold;'>⚠️ IMPORTANT: DELETE THIS FILE (test_db_connection.php) AFTER TESTING!</p>";
echo "<p>This file exposes database configuration and should not be accessible in production.</p>";
?>
