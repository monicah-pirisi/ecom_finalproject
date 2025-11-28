<?php
/**
 * Database Credentials - TEMPLATE FILE
 * CampusDigs Kenya - Server Configuration
 *
 * INSTRUCTIONS:
 * 1. Copy this file and rename it to 'db_cred.php'
 * 2. Update the database credentials below with your actual values
 * 3. Never commit db_cred.php to version control
 */

// Database server credentials
define('SERVER', 'localhost');
define('USERNAME', 'your_database_username');
define('PASSWD', 'your_database_password');
define('DATABASE', 'your_database_name');

// Optional: Database connection using these credentials
function getDatabaseConnection() {
    $conn = new mysqli(SERVER, USERNAME, PASSWD, DATABASE);

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}
?>
