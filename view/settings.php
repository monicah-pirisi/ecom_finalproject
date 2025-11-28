<?php
/**
 * CampusDigs Kenya - Account Settings
 */

require_once '../includes/config.php';
require_once '../includes/core.php';

// Require login
requireLogin();

$userId = $_SESSION['user_id'];
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container my-5">
        <h1 class="mb-4"><i class="fas fa-cog text-primary"></i> Account Settings</h1>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-3">
                <div class="list-group">
                    <a href="#profile" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action">
                        <i class="fas fa-lock"></i> Security
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action">
                        <i class="fas fa-bell"></i> Notifications
                    </a>
                    <a href="#privacy" class="list-group-item list-group-item-action">
                        <i class="fas fa-shield-alt"></i> Privacy
                    </a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Settings</h5>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>

                        <hr class="my-4">

                        <h5>Change Password</h5>
                        <form class="mt-3">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control">
                            </div>

                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
