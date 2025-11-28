<?php
/**
 * CampusDigs Kenya - Admin Login Portal
 * Separate login page for administrators only
 */

require_once '../includes/config.php';
require_once '../includes/core.php';

// Redirect if already logged in as admin
if (isLoggedIn() && isAdmin()) {
    header('Location: ../dashboard_admin.php');
    exit();
}

// Get flash message if any
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --admin-primary: #1e3a8a;
            --admin-secondary: #3b82f6;
            --admin-dark: #1e293b;
        }

        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }

        .admin-login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .admin-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .admin-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .admin-header h3 {
            margin: 0;
            font-weight: bold;
        }

        .admin-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .admin-body {
            padding: 2rem;
        }

        .form-control:focus {
            border-color: var(--admin-secondary);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        .btn-admin {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border: none;
            color: white;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-admin:hover {
            background: linear-gradient(135deg, #1e293b 0%, #1e3a8a 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 58, 138, 0.4);
        }

        .security-notice {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .security-notice i {
            color: #f59e0b;
            margin-right: 0.5rem;
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
            color: white;
        }

        .back-link a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <!-- Admin Header -->
            <div class="admin-header">
                <i class="fas fa-shield-alt"></i>
                <h3>Admin Portal</h3>
                <p>CampusDigs Kenya Administration</p>
            </div>

            <!-- Admin Body -->
            <div class="admin-body">
                <!-- Security Notice -->
                <div class="security-notice">
                    <i class="fas fa-lock"></i>
                    <small><strong>Authorized Access Only</strong> - This portal is restricted to administrators.</small>
                </div>

                <!-- Flash Message -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="../actions/login_user_action.php">
                    <?php csrfTokenField(); ?>

                    <!-- Hidden field to identify admin login -->
                    <input type="hidden" name="admin_login" value="1">

                    <!-- Email or Phone -->
                    <div class="mb-3">
                        <label for="login_value" class="form-label">
                            <i class="fas fa-user"></i> Email or Phone Number
                        </label>
                        <input type="text"
                               class="form-control"
                               id="login_value"
                               name="login_value"
                               placeholder="admin@campusdigs.co.ke"
                               required
                               autofocus>
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
                                   placeholder="••••••••"
                                   required>
                            <button class="btn btn-outline-secondary"
                                    type="button"
                                    id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="form-check mb-3">
                        <input type="checkbox"
                               class="form-check-input"
                               id="remember_me"
                               name="remember_me">
                        <label class="form-check-label" for="remember_me">
                            Remember me
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-admin w-100">
                        <i class="fas fa-sign-in-alt"></i> Sign In to Admin Portal
                    </button>
                </form>

                <!-- Help Text -->
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Need help? Contact system administrator
                    </small>
                </div>
            </div>
        </div>

        <!-- Back to Main Site -->
        <div class="back-link">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Back to Main Site
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Toggle Password -->
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
