<?php
/**
 * CampusDigs Kenya - Forgot Password Page
 * Password reset request form
 */

require_once '../includes/config.php';
require_once '../includes/core.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectToDashboard();
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <h2 class="text-primary fw-bold">
                        <i class="fas fa-home"></i> CampusDigs
                    </h2>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-lg border-0">
                    <div class="card-body p-4">
                        <h3 class="card-title text-center mb-2">Forgot Password?</h3>
                        <p class="text-center text-muted mb-4">
                            Enter your email address and we'll send you a link to reset your password.
                        </p>

                        <form method="POST" action="../actions/forgot_password_action.php">
                            <?php csrfTokenField(); ?>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="your@email.com" required autofocus>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="fas fa-paper-plane"></i> Send Reset Link
                            </button>

                            <div class="text-center">
                                <a href="../login/login.php" class="text-primary">
                                    <i class="fas fa-arrow-left"></i> Back to Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i> Password reset functionality will be implemented soon.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
