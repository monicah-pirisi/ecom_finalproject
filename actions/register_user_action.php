<?php
/**
 * CampusDigs Kenya - Registration Action
 * Handles user registration for students and landlords
 * MVC Architecture - Controller Layer
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/core.php';
require_once '../controllers/user_controller.php';

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// VALIDATE REQUEST METHOD

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('../login/register.php', 'Invalid request method', 'error');
}


// VALIDATE CSRF TOKEN

if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    redirectWithMessage('../login/register.php', 'Invalid security token. Please try again.', 'error');
}

// SANITIZE AND VALIDATE INPUT

// Get and sanitize user type
$userType = isset($_POST['user_type']) ? sanitizeInput($_POST['user_type']) : '';
if (!in_array($userType, ['student', 'landlord'])) {
    redirectWithMessage('../login/register.php', 'Invalid user type', 'error');
}

// Get and sanitize basic fields
$fullName = isset($_POST['full_name']) ? sanitizeInput($_POST['full_name']) : '';
$email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
$phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$termsAccepted = isset($_POST['terms']) ? true : false;

// Student-specific fields
$university = '';
$studentId = '';
if ($userType === 'student') {
    $university = isset($_POST['university']) ? sanitizeInput($_POST['university']) : '';
    $studentId = isset($_POST['student_id']) ? sanitizeInput($_POST['student_id']) : '';
}

// VALIDATION CHECKS


// Check if all required fields are filled
if (empty($fullName) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
    redirectWithMessage('../login/register.php?type=' . $userType, 'All fields are required', 'error');
}

// Check if terms are accepted
if (!$termsAccepted) {
    redirectWithMessage('../login/register.php?type=' . $userType, 'You must accept the Terms of Service', 'error');
}

// Validate full name (minimum 3 characters)
if (strlen($fullName) < 3) {
    redirectWithMessage('../login/register.php?type=' . $userType, 'Full name must be at least 3 characters', 'error');
}

// Validate email format
if (!validateEmail($email)) {
    redirectWithMessage('../login/register.php?type=' . $userType, 'Invalid email format', 'error');
}

// Validate university email for students
if ($userType === 'student' && !validateUniversityEmail($email)) {
    redirectWithMessage('../login/register.php?type=' . $userType, 'Please use a valid university email address (.ac.ke or .edu)', 'error');
}

// Validate phone number
if (!validateKenyanPhone($phone)) {
    redirectWithMessage('../login/register.php?type=' . $userType, 'Invalid phone number format. Use +254XXXXXXXXX or 07XXXXXXXX', 'error');
}

// Format phone number
$phone = formatKenyanPhone($phone);

// Validate student-specific fields
if ($userType === 'student') {
    if (empty($university)) {
        redirectWithMessage('../login/register.php?type=' . $userType, 'Please select your university', 'error');
    }
    if (empty($studentId)) {
        redirectWithMessage('../login/register.php?type=' . $userType, 'Student ID is required', 'error');
    }
}

// Validate password strength
$passwordValidation = validatePassword($password);
if (!$passwordValidation['valid']) {
    redirectWithMessage('../login/register.php?type=' . $userType, $passwordValidation['message'], 'error');
}

// Check if passwords match
if ($password !== $confirmPassword) {
    redirectWithMessage('../login/register.php?type=' . $userType, 'Passwords do not match', 'error');
}

// CHECK FOR EXISTING USER
// 

// Check if email already exists
if (emailExists($email)) {
    redirectWithMessage('../login/register.php?type=' . $userType, 'An account with this email already exists', 'error');
}

// Check if phone already exists
if (phoneExists($phone)) {
    redirectWithMessage('../login/register.php?type=' . $userType, 'An account with this phone number already exists', 'error');
}

// REGISTER USER


// Prepare user data
$userData = [
    'user_type' => $userType,
    'full_name' => $fullName,
    'email' => $email,
    'phone' => $phone,
    'password' => $password,
    'university' => $university,
    'student_id' => $studentId
];

// Attempt to register user
$userId = registerUser($userData);

if ($userId) {
    // Registration successful
    
    // Log the activity
    logActivity($userId, 'user_registered', "User registered as $userType");
    
    // Auto-login the user
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_type'] = $userType;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;
    $_SESSION['email_verified'] = 0;
    $_SESSION['account_verified'] = 0;
    $_SESSION['session_start_time'] = time();
    $_SESSION['last_activity_time'] = time();
    
    // Regenerate session ID for security
    regenerateSession();

    // MERGE GUEST WISHLIST: If new user is a student and has items in guest wishlist, merge them
    if ($userType === 'student') {
        $guestWishlistCount = getGuestWishlistCount();
        if ($guestWishlistCount > 0) {
            // Merge guest wishlist with new user's database wishlist
            $merged = mergeGuestWishlistWithUser($userId);
            if ($merged) {
                // Log the merge activity
                logActivity($userId, 'wishlist_merged', "Merged {$guestWishlistCount} properties from guest session on registration");
            }
        }
    }

    // Send welcome email (optional - implement later)
    // sendWelcomeEmail($email, $fullName);

    // Redirect to appropriate dashboard
    if ($userType === 'student') {
        redirectWithMessage('../dashboard_student.php', 'Welcome to CampusDigs! Your account has been created successfully.', 'success');
    } else {
        redirectWithMessage('../dashboard_landlord.php', 'Welcome to CampusDigs! Your account has been created successfully.', 'success');
    }
    
} else {
    // Registration failed
    redirectWithMessage('../login/register.php?type=' . $userType, 'Registration failed. Please try again.', 'error');
}

?>