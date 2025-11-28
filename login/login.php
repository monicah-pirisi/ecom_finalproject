<?php
/**
 * CampusDigs Kenya - Login Page
 * Allows users to sign in to their accounts
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

// Check for session timeout message
$sessionTimeout = isset($_GET['timeout']) && $_GET['timeout'] == 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>

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
            <div class="col-lg-6 d-none d-lg-flex bg-gradient-primary login-page text-white p-5 flex-column justify-content-center">
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

            <!-- Right Side - Login Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center p-5">
                <div class="w-100" style="max-width: 500px;">
                    <!-- Mobile Logo -->
                    <div class="text-center d-lg-none mb-4">
                        <h2 class="text-primary fw-bold">
                            <i class="fas fa-home"></i> CampusDigs
                        </h2>
                    </div>

                    <!-- Session Timeout Alert -->
                    <?php if ($sessionTimeout): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-clock"></i> Your session has expired. Please log in again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Flash Message -->
                    <?php if ($flash): ?>
                        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($flash['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Login Card -->
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4">
                            <h3 class="card-title text-center mb-2">Welcome Back</h3>
                            <p class="text-center text-muted mb-4">Sign in to your CampusDigs account</p>

                            <!-- Login Form -->
                            <form id="loginForm" method="POST" action="../actions/login_user_action.php">
                                <?php csrfTokenField(); ?>

                                <!-- Email or Phone -->
                                <div class="mb-3">
                                    <label for="login_value" class="form-label">
                                        <i class="fas fa-user"></i> Email or Phone Number
                                    </label>
                                    <input type="text"
                                           class="form-control"
                                           id="login_value"
                                           name="login_value"
                                           placeholder="student@uonbi.ac.ke or +254712345678"
                                           required
                                           autofocus>
                                    <div class="invalid-feedback"></div>
                                </div>

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
                                               placeholder=""""""""""
                                               required>
                                        <button class="btn btn-outline-secondary"
                                                type="button"
                                                id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Remember Me & Forgot Password -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="form-check">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               id="remember_me"
                                               name="remember_me">
                                        <label class="form-check-label" for="remember_me">
                                            Remember me
                                        </label>
                                    </div>
                                    <a href="../view/forgot_password.php" class="text-primary text-decoration-none">
                                        Forgot password?
                                    </a>
                                </div>

                                <!-- SSL Security Notice -->
                                <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    <small>SSL Encrypted " Your data is secure</small>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-primary w-100 py-2 mb-3" id="submitBtn">
                                    <i class="fas fa-sign-in-alt"></i> Sign In
                                </button>

                                <!-- Register Link -->
                                <p class="text-center mb-0">
                                    Don't have an account?
                                    <a href="register.php" class="text-primary fw-semibold">Create Account</a>
                                </p>
                            </form>

                            <!-- Social Divider -->
                            <div class="text-center my-3">
                                <span class="text-muted">or continue with</span>
                            </div>

                            <!-- Quick Access Buttons -->
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="register.php?type=student" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-graduation-cap"></i> Student
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="register.php?type=landlord" class="btn btn-outline-success w-100">
                                        <i class="fas fa-building"></i> Landlord
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Offline Option -->
                    <div class="text-center mt-4">
                        <p class="text-muted mb-2">
                            <i class="fas fa-mobile-alt"></i> No Internet?
                        </p>
                        <p class="text-muted small">
                            Dial <strong>*384*96#</strong> for USSD access
                        </p>
                    </div>

                    <!-- Support Link -->
                    <div class="text-center mt-3">
                        <p class="text-muted small">
                            Need help? <a href="../view/support.php" class="text-primary">Contact Support</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="../js/login.js"></script>
</body>
</html>
