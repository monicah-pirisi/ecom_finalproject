<?php
/**
 * CampusDigs Kenya - Registration Page
 * Allows new users to create student or landlord accounts
 */

// Include configuration and core functions
require_once '../includes/config.php';
require_once '../includes/core.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectToDashboard();
}

// Get flash message if any
$flash = getFlashMessage();

// Get user type from URL parameter (default: student)
$userType = isset($_GET['type']) && in_array($_GET['type'], ['student', 'landlord']) 
    ? $_GET['type'] 
    : 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/login.css?v=4">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left Side - Branding -->
            <div class="col-lg-6 d-none d-lg-flex bg-gradient-primary register-page text-white p-5 flex-column justify-content-center">
                <div class="text-center">
                    <h1 class="display-3 fw-bold mb-4">
                        <i class="fas fa-home"></i> CampusDigs
                    </h1>
                    <p class="lead mb-5">Safe Student Housing in Kenya</p>
                    
                    <div class="row g-4 mt-4">
                        <div class="col-md-6">
                            <div class="feature-card">
                                <i class="fas fa-shield-alt fa-3x mb-3"></i>
                                <h5>Verified Properties</h5>
                                <p>All landlords verified with safety scores</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <i class="fas fa-lock fa-3x mb-3"></i>
                                <h5>Secure Payments</h5>
                                <p>M-Pesa, Cards & HELB supported</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <h5>Student Community</h5>
                                <p>Real reviews from verified students</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-card">
                                <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                                <h5>Mobile Friendly</h5>
                                <p>Book anywhere, anytime</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Registration Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-5">
                <div class="w-100" style="max-width: 500px;">
                    <!-- Mobile Logo -->
                    <div class="text-center d-lg-none mb-4">
                        <h2 class="text-primary fw-bold">
                            <i class="fas fa-home"></i> CampusDigs
                        </h2>
                    </div>

                    <!-- Flash Message -->
                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($flash['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Registration Card -->
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title text-center mb-2">Create Account</h3>
                            <p class="text-center text-muted mb-4">Join CampusDigs today</p>

                            <!-- User Type Tabs -->
                            <ul class="nav nav-pills nav-fill mb-4" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?php echo $userType === 'student' ? 'active' : ''; ?>" 
                                       href="?type=student">
                                        <i class="fas fa-graduation-cap"></i> Student
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?php echo $userType === 'landlord' ? 'active' : ''; ?>" 
                                       href="?type=landlord">
                                        <i class="fas fa-building"></i> Landlord
                                    </a>
                                </li>
                            </ul>

                            <!-- Registration Form -->
                            <form id="registerForm" method="POST" action="../actions/register_user_action.php">
                                <?php csrfTokenField(); ?>
                                <input type="hidden" name="user_type" value="<?php echo $userType; ?>">

                                <!-- Full Name -->
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">
                                        <i class="fas fa-user"></i> Full Name
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="full_name"
                                           name="full_name"
                                           placeholder="John Doe"
                                           autocomplete="name"
                                           required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo $userType === 'student' ? 'University Email' : 'Email Address'; ?>
                                    </label>
                                    <input type="email"
                                           class="form-control"
                                           id="email"
                                           name="email"
                                           placeholder="<?php echo $userType === 'student' ? 'student@students.uonbi.ac.ke' : 'your@email.com'; ?>"
                                           autocomplete="email"
                                           required>
                                    <?php if ($userType === 'student'): ?>
                                        <small class="text-muted">Use your university email for verification</small>
                                    <?php endif; ?>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Phone Number -->
                                <div class="mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone"></i> Phone Number
                                    </label>
                                    <input type="tel"
                                           class="form-control"
                                           id="phone"
                                           name="phone"
                                           placeholder="+254 7XX XXX XXX"
                                           autocomplete="tel"
                                           required>
                                    <small class="text-muted">Format: +254XXXXXXXXX or 07XXXXXXXX</small>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Student-specific fields -->
                                <?php if ($userType === 'student'): ?>
                                    <!-- University -->
                                    <div class="mb-3">
                                        <label for="university" class="form-label">
                                            <i class="fas fa-university"></i> University
                                        </label>
                                        <select class="form-select" id="university" name="university" required>
                                            <option value="">Select your university</option>
                                            <?php foreach (SUPPORTED_UNIVERSITIES as $uni): ?>
                                                <option value="<?php echo htmlspecialchars($uni); ?>">
                                                    <?php echo htmlspecialchars($uni); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <!-- Student ID -->
                                    <div class="mb-3">
                                        <label for="student_id" class="form-label">
                                            <i class="fas fa-id-card"></i> Student ID
                                        </label>
                                        <input type="text"
                                               class="form-control"
                                               id="student_id"
                                               name="student_id"
                                               placeholder="UON/XXX/XXXX"
                                               autocomplete="off"
                                               required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                <?php endif; ?>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock"></i> Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password"
                                               class="form-control"
                                               id="password"
                                               name="password"
                                               placeholder="••••••••"
                                               autocomplete="new-password"
                                               required>
                                        <button class="btn btn-outline-secondary"
                                                type="button"
                                                id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        At least 8 characters, 1 uppercase, 1 lowercase, 1 number
                                    </small>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock"></i> Confirm Password
                                    </label>
                                    <input type="password"
                                           class="form-control"
                                           id="confirm_password"
                                           name="confirm_password"
                                           placeholder="••••••••"
                                           autocomplete="new-password"
                                           required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- SSL Security Notice -->
                                <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    <small>SSL Encrypted • Your data is secure</small>
                                </div>

                                <!-- Terms & Conditions -->
                                <div class="mb-3 form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="terms" 
                                           name="terms" 
                                           required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the 
                                        <a href="../view/terms.php" target="_blank">Terms of Service</a> and 
                                        <a href="../view/privacy.php" target="_blank">Privacy Policy</a>
                                    </label>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-primary w-100 py-2 mb-3" id="submitBtn">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>

                                <!-- Login Link -->
                                <p class="text-center mb-0">
                                    Already have an account? 
                                    <a href="login.php" class="text-primary fw-semibold">Sign In</a>
                                </p>
                            </form>
                        </div>
                    </div>

                    <!-- Offline Option -->
                    <div class="text-center mt-4">
                        <p class="text-muted mb-2">
                            <i class="fas fa-mobile-alt"></i> No Internet?
                        </p>
                        <p class="text-muted small">
                            Dial <strong>*384*96#</strong> for USSD registration
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS (DEBUG VERSION) -->
    <script src="../js/register_debug.js"></script>
</body>
</html>