<?php
/**
 * CampusDigs Configuration File
 * Database connection and global constants
 * MVC Architecture - Configuration Layer
 */


// ERROR REPORTING CONFIGURATION

// Development mode: Show all errors
// Production mode: Log errors only, don't display
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    // Local development environment
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    define('ENVIRONMENT', 'development');
} else {
    // Production environment
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
    define('ENVIRONMENT', 'production');
}


// DATABASE CONFIGURATION
//
// DEPLOYMENT STRATEGY:
// - LOCALHOST: Uses defaults below (campus_digs database)
// - SERVER: Uses db_cred.php file (ecommerce_2025A_monicah_lekupe database)
//
// Priority: db_cred.php > Environment variables > Development defaults

// Check if db_cred.php exists (production server deployment)
if (file_exists(__DIR__ . '/db_cred.php')) {
    // Production: Load credentials from db_cred.php
    require_once __DIR__ . '/db_cred.php';

    // Use credentials from db_cred.php
    define('DB_HOST', defined('SERVER') ? SERVER : 'localhost');
    define('DB_USER', defined('USERNAME') ? USERNAME : 'root');
    define('DB_PASS', defined('PASSWD') ? PASSWD : '');
    define('DB_NAME', defined('DATABASE') ? DATABASE : 'campus_digs');
} else {
    // Development: Use localhost defaults or environment variables
    define('DB_HOST', getenv('DB_HOST_PROD') ?: 'localhost');
    define('DB_USER', getenv('DB_USER_PROD') ?: 'root');
    define('DB_PASS', getenv('DB_PASS_PROD') ?: '');
    define('DB_NAME', getenv('DB_NAME_PROD') ?: 'campus_digs');
}

// Database charset
define('DB_CHARSET', 'utf8mb4');

// DATABASE CONNECTION


/**
 * Create and return database connection
 * @return mysqli Database connection object
 */
function getDatabaseConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Create new MySQLi connection
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
                
                if (ENVIRONMENT === 'development') {
                    die("Database connection failed: " . $conn->connect_error);
                } else {
                    die("Service temporarily unavailable. Please try again later.");
                }
            }
            
            // Set charset to prevent SQL injection and encoding attacks
            if (!$conn->set_charset(DB_CHARSET)) {
                error_log("Error loading character set " . DB_CHARSET . ": " . $conn->error);
            }
            
            // Set timezone to Africa/Nairobi (Kenya)
            $conn->query("SET time_zone = '+03:00'");
            
        } catch (Exception $e) {
            error_log("Database connection exception: " . $e->getMessage());
            
            if (ENVIRONMENT === 'development') {
                die("Database connection exception: " . $e->getMessage());
            } else {
                die("Service temporarily unavailable. Please try again later.");
            }
        }
    }
    
    return $conn;
}

// Create global database connection
$conn = getDatabaseConnection();


// APPLICATION CONSTANTS

// Application information
define('APP_NAME', 'CampusDigs Kenya');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Safe Student Housing Platform for Kenyan Universities');

// Base URL configuration
if (ENVIRONMENT === 'development') {
    define('BASE_URL', 'http://localhost/campus_digs');
} else {
    // Production URL - Update this with your actual server URL
    define('BASE_URL', 'https://your-server-url.com/campus_digs');
}

// Directory paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('IMAGES_PATH', ROOT_PATH . '/images');
define('CSS_PATH', ROOT_PATH . '/css');
define('JS_PATH', ROOT_PATH . '/js');
define('LOGS_PATH', ROOT_PATH . '/logs');

// URL paths
define('CSS_URL', BASE_URL . '/css');
define('JS_URL', BASE_URL . '/js');
define('IMAGES_URL', BASE_URL . '/images');
define('UPLOADS_URL', BASE_URL . '/uploads');


// FILE UPLOAD CONFIGURATION
// Maximum file sizes (in bytes)
define('MAX_PROPERTY_IMAGE_SIZE', 5 * 1024 * 1024);      // 5MB
define('MAX_STUDENT_ID_SIZE', 2 * 1024 * 1024);          // 2MB
define('MAX_LANDLORD_DOC_SIZE', 5 * 1024 * 1024);        // 5MB

// Allowed file extensions
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);

// Upload directories
define('PROPERTY_IMAGES_DIR', UPLOADS_PATH . '/properties');
define('STUDENT_IDS_DIR', UPLOADS_PATH . '/student_ids');
define('LANDLORD_DOCS_DIR', UPLOADS_PATH . '/landlord_docs');

// Create upload directories if they don't exist
if (!file_exists(PROPERTY_IMAGES_DIR)) {
    mkdir(PROPERTY_IMAGES_DIR, 0755, true);
}
if (!file_exists(STUDENT_IDS_DIR)) {
    mkdir(STUDENT_IDS_DIR, 0755, true);
}
if (!file_exists(LANDLORD_DOCS_DIR)) {
    mkdir(LANDLORD_DOCS_DIR, 0755, true);
}

// PAYMENT CONFIGURATION (PAYSTACK) - Kenya Account

// Paystack API keys - Kenya Account
if (ENVIRONMENT === 'development') {
    // Test keys for development
    define('PAYSTACK_SECRET_KEY', 'sk_test_914dfc2481162415ff5c512260511569b00e73d7');
    define('PAYSTACK_PUBLIC_KEY', 'pk_test_6b4a00d825eb974aad18ecf340d9daf6e3859aaf');
} else {
    // Production Paystack keys (replace with your live keys when going to production)
    define('PAYSTACK_SECRET_KEY', 'sk_live_YOUR_LIVE_SECRET_KEY_HERE');
    define('PAYSTACK_PUBLIC_KEY', 'pk_live_YOUR_LIVE_PUBLIC_KEY_HERE');
}

// Paystack API endpoint
define('PAYSTACK_API_URL', 'https://api.paystack.co');

// Payment settings
define('CURRENCY', 'KES');                               // Kenyan Shillings
define('COMMISSION_RATE', 0.05);                         // 5% commission
define('PROCESSING_FEE', 500);                           // KSh 500 processing fee

// EMAIL CONFIGURATION

// Email settings (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');                   // Update with your SMTP host
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');         // Update with your email
define('SMTP_PASSWORD', 'your-app-password');            // Update with your password
define('SMTP_FROM_EMAIL', 'noreply@campusdigs.co.ke');
define('SMTP_FROM_NAME', 'CampusDigs Kenya');

// SMS CONFIGURATION (AFRICA'S TALKING)

// Africa's Talking API credentials
define('AT_USERNAME', 'sandbox');                        // Use 'sandbox' for testing
define('AT_API_KEY', 'your_africas_talking_api_key');
define('AT_SHORTCODE', '20000');                         // SMS shortcode

// SECURITY CONFIGURATION
// Session timeout settings (in seconds)
define('SESSION_IDLE_TIMEOUT', 1800);                    // 30 minutes
define('SESSION_ABSOLUTE_TIMEOUT', 28800);               // 8 hours

// Password settings
define('MIN_PASSWORD_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', false);

// Login attempt limits
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 900);                   // 15 minutes

// CSRF token lifetime (in seconds)
define('CSRF_TOKEN_LIFETIME', 3600);                     // 1 hour

// BUSINESS LOGIC CONSTANTS

// Property settings
define('MIN_PROPERTY_PRICE', 3000);                      // KSh 3,000
define('MAX_PROPERTY_PRICE', 50000);                     // KSh 50,000
define('PROPERTY_EXPIRATION_DAYS', 365);                 // 1 year

// Booking settings
define('MIN_LEASE_DURATION', 4);                         // 4 months
define('MAX_LEASE_DURATION', 12);                        // 12 months

// Refund policy (percentage based on days since approval)
define('REFUND_TIER_1_DAYS', 7);                        // 0-7 days
define('REFUND_TIER_1_PERCENT', 0.75);                  // 75% refund

define('REFUND_TIER_2_DAYS', 14);                       // 8-14 days
define('REFUND_TIER_2_PERCENT', 0.50);                  // 50% refund

define('REFUND_TIER_3_DAYS', 15);                       // 15+ days
define('REFUND_TIER_3_PERCENT', 0.00);                  // No refund

// Verification badges
define('VERIFIED_LANDLORD_BADGE', '✓ Verified Landlord');
define('VERIFIED_STUDENT_BADGE', '✓ Verified Student');
define('PREMIUM_LISTING_BADGE', '★ Premium');

// PAGINATION SETTINGS

define('PROPERTIES_PER_PAGE', 12);
define('BOOKINGS_PER_PAGE', 10);
define('USERS_PER_PAGE', 20);
define('REVIEWS_PER_PAGE', 5);

// NOTIFICATION SETTINGS

// Email notification events
define('NOTIFY_NEW_BOOKING', true);
define('NOTIFY_BOOKING_APPROVED', true);
define('NOTIFY_BOOKING_REJECTED', true);
define('NOTIFY_PAYMENT_RECEIVED', true);
define('NOTIFY_PAYMENT_DUE', true);
define('NOTIFY_PROPERTY_APPROVED', true);

// SMS notification events
define('SMS_BOOKING_CONFIRMATION', true);
define('SMS_PAYMENT_REMINDER', true);
define('SMS_MOVE_IN_REMINDER', true);

// SUPPORTED UNIVERSITIES

define('SUPPORTED_UNIVERSITIES', [
    'University of Nairobi',
    'Kenyatta University',
    'Strathmore University',
    'United States International University',
    'Jomo Kenyatta University of Agriculture and Technology',
    'Technical University of Kenya',
    'Daystar University',
    'Catholic University of Eastern Africa',
    'Egerton University',
    'Moi University'
]);

// PROPERTY AMENITIES

define('AVAILABLE_AMENITIES', [
    'Wi-Fi',
    'CCTV',
    'Parking',
    'Furnished',
    'Water 24/7',
    'Electricity Backup',
    'Security Guard',
    'Laundry',
    'Kitchen',
    'Gym',
    'Study Room',
    'Common Area'
]);

// ROOM TYPES

define('ROOM_TYPES', [
    'shared' => 'Shared Room',
    'private' => 'Private Room',
    'studio' => 'Studio Apartment',
    'one_bedroom' => '1 Bedroom Apartment',
    'two_bedroom' => '2 Bedroom Apartment'
]);

// USER ROLES

define('USER_ROLES', [
    'student' => 'Student',
    'landlord' => 'Landlord',
    'admin' => 'Administrator'
]);

// STATUS CONSTANTS

// Property status
define('PROPERTY_STATUS_ACTIVE', 'active');
define('PROPERTY_STATUS_INACTIVE', 'inactive');
define('PROPERTY_STATUS_PENDING', 'pending');
define('PROPERTY_STATUS_EXPIRED', 'expired');

// Booking status
define('BOOKING_STATUS_PENDING', 'pending');
define('BOOKING_STATUS_APPROVED', 'approved');
define('BOOKING_STATUS_REJECTED', 'rejected');
define('BOOKING_STATUS_CANCELLED', 'cancelled');
define('BOOKING_STATUS_COMPLETED', 'completed');

// Payment status
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_PAID', 'paid');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// TIMEZONE CONFIGURATION

// Set default timezone to Kenya (EAT - East Africa Time)
date_default_timezone_set('Africa/Nairobi');

// HELPER FUNCTION: Close Database Connection

/**
 * Close database connection
 * Call this at the end of scripts
 */
function closeDatabaseConnection() {
    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            // Suppress errors and attempt to close
            @$conn->close();
        } catch (Exception $e) {
            // Connection already closed, ignore
        }
    }
}

// Register shutdown function to close connection
// DISABLED: Causes errors when connection is already closed
// register_shutdown_function('closeDatabaseConnection');

?>