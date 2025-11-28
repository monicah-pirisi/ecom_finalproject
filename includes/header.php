<?php
/**
 * CampusDigs Kenya - Header Include
 * Navigation header for dashboard pages
 */
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <!-- Brand/Logo -->
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <i class="fas fa-home text-primary"></i>
            <strong class="text-primary">CampusDigs</strong>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (isStudent()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/view/all_properties.php">
                            <i class="fas fa-search"></i> Browse
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/view/student_wishlist.php">
                            <i class="fas fa-heart"></i> Wishlist
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/view/student_bookings.php">
                            <i class="fas fa-calendar-check"></i> Bookings
                        </a>
                    </li>
                <?php elseif (isLandlord()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/view/landlord/my_properties.php">
                            <i class="fas fa-building"></i> My Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/view/landlord/add_property.php">
                            <i class="fas fa-plus-circle"></i> Add Property
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/view/landlord/manage_bookings.php">
                            <i class="fas fa-clipboard-list"></i> Bookings
                        </a>
                    </li>
                <?php elseif (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/manage_properties.php">
                            <i class="fas fa-home"></i> Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/manage_users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/reports.php">
                            <i class="fas fa-chart-line"></i> Reports
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Right Side Menu -->
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button"
                           data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                3
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <small class="text-muted">2 hours ago</small><br>
                                    Your booking was approved!
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <small class="text-muted">1 day ago</small><br>
                                    Payment reminder for next month
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
                        </ul>
                    </li>

                    <!-- User Profile -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <?php
                            $fullName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
                            echo htmlspecialchars(explode(' ', $fullName)[0]);
                            ?>
                        </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>/view/student_profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>/view/settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>/view/help.php">
                                <i class="fas fa-question-circle"></i> Help
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/login/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                    <!-- Guest Menu -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/login/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-warning text-white ms-2" href="<?php echo BASE_URL; ?>/login/register.php">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>