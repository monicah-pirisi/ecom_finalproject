<?php
/**
 * CampusDigs Kenya - Help Center
 */

require_once '../includes/config.php';
require_once '../includes/core.php';

// Require login
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/help.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="help-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 help-hero-content">
                    <h1><i class="fas fa-hands-helping text-primary"></i> Help Center</h1>
                    <p class="lead">We're here to help you find your perfect student accommodation. Get answers, learn tips, and stay safe.</p>
                    <div class="d-flex gap-3">
                        <a href="support.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-headset"></i> Contact Support
                        </a>
                        <a href="support.php#faq" class="btn btn-light btn-lg">
                            <i class="fas fa-question-circle"></i> Browse FAQs
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center d-none d-lg-block">
                    <img src="https://images.unsplash.com/photo-1553877522-43269d4ea984?w=600&h=500&fit=crop"
                         alt="Friendly support team"
                         class="hero-image">
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Help Topics Grid -->
        <div class="text-center mb-5">
            <h2 class="section-title">How Can We Help You?</h2>
            <p class="section-subtitle">Choose a topic to get started</p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-3">
                <div class="help-card">
                    <div class="card-body text-center p-4">
                        <div class="help-card-icon icon-primary">
                            <i class="fas fa-book"></i>
                        </div>
                        <h5 class="help-card-title">Getting Started</h5>
                        <p class="help-card-text">Learn the basics of using CampusDigs to find your ideal accommodation</p>
                        <a href="#getting-started" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-right"></i> Learn More
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="help-card">
                    <div class="card-body text-center p-4">
                        <div class="help-card-icon icon-success">
                            <i class="fas fa-home"></i>
                        </div>
                        <h5 class="help-card-title">Booking Properties</h5>
                        <p class="help-card-text">Step-by-step guide to searching, viewing, and booking properties</p>
                        <a href="#booking" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-right"></i> Learn More
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="help-card">
                    <div class="card-body text-center p-4">
                        <div class="help-card-icon icon-info">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h5 class="help-card-title">Payments</h5>
                        <p class="help-card-text">Understand payment methods, security, and transaction processes</p>
                        <a href="#payments" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-right"></i> Learn More
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="help-card">
                    <div class="card-body text-center p-4">
                        <div class="help-card-icon icon-warning">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h5 class="help-card-title">Account & Profile</h5>
                        <p class="help-card-text">Manage your account settings, verification, and preferences</p>
                        <a href="#account" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-right"></i> Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Getting Started Section -->
        <div id="getting-started" class="mb-5 scroll-margin">
            <div class="help-card">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="help-card-icon icon-primary mx-auto mb-3">
                            <i class="fas fa-book"></i>
                        </div>
                        <h2 class="section-title">Getting Started with CampusDigs</h2>
                        <p class="section-subtitle">Your complete guide to finding student accommodation</p>
                    </div>

                    <div class="row">
                        <div class="col-lg-10 mx-auto">
                            <h4 class="mb-3"><i class="fas fa-user-plus text-primary"></i> Creating Your Account</h4>
                            <ol class="mb-4">
                                <li>Click "Register" on the homepage</li>
                                <li>Choose your account type: Student or Landlord</li>
                                <li>Fill in your personal details (name, email, phone)</li>
                                <li>Verify your email address via the confirmation link</li>
                                <li>Complete your profile with university information</li>
                            </ol>

                            <h4 class="mb-3"><i class="fas fa-search text-success"></i> Finding Properties</h4>
                            <ol class="mb-4">
                                <li>Use the search bar on the homepage</li>
                                <li>Filter by location, price range, and property type</li>
                                <li>View property photos and detailed descriptions</li>
                                <li>Check landlord ratings and reviews</li>
                                <li>Save favorites to your wishlist for later</li>
                            </ol>

                            <h4 class="mb-3"><i class="fas fa-shield-check text-info"></i> Account Verification</h4>
                            <p class="mb-4">Verified accounts get priority support and build trust:</p>
                            <ul class="mb-4">
                                <li><strong>Students:</strong> Upload your student ID or admission letter</li>
                                <li><strong>Landlords:</strong> Upload ID and property ownership documents</li>
                                <li>Verification usually takes 24-48 hours</li>
                                <li>You'll receive an email notification once verified</li>
                            </ul>

                            <div class="text-center mt-4">
                                <a href="all_properties.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-search"></i> Start Searching Properties
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Properties Section -->
        <div id="booking" class="mb-5 scroll-margin">
            <div class="help-card">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="help-card-icon icon-success mx-auto mb-3">
                            <i class="fas fa-home"></i>
                        </div>
                        <h2 class="section-title">How to Book Properties</h2>
                        <p class="section-subtitle">Step-by-step booking process</p>
                    </div>

                    <div class="row">
                        <div class="col-lg-10 mx-auto">
                            <h4 class="mb-3"><i class="fas fa-clipboard-list text-primary"></i> Booking Steps</h4>
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="p-4 border rounded bg-light">
                                        <h5><span class="badge bg-primary">1</span> Browse & Select</h5>
                                        <p class="mb-0">Search for properties that match your criteria and view detailed information</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 border rounded bg-light">
                                        <h5><span class="badge bg-primary">2</span> Visit Property</h5>
                                        <p class="mb-0">Contact landlord to schedule an in-person viewing before booking</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 border rounded bg-light">
                                        <h5><span class="badge bg-primary">3</span> Submit Request</h5>
                                        <p class="mb-0">Fill in booking form with move-in date and special requests</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 border rounded bg-light">
                                        <h5><span class="badge bg-primary">4</span> Confirmation</h5>
                                        <p class="mb-0">Landlord reviews and responds within 24 hours</p>
                                    </div>
                                </div>
                            </div>

                            <h4 class="mb-3"><i class="fas fa-exclamation-circle text-warning"></i> Important Tips</h4>
                            <ul class="mb-4">
                                <li><strong>Never pay before viewing:</strong> Always visit the property in person first</li>
                                <li><strong>Check lease agreement:</strong> Read all terms carefully before signing</li>
                                <li><strong>Document condition:</strong> Take photos of the property during move-in</li>
                                <li><strong>Keep receipts:</strong> Save all payment confirmations and receipts</li>
                            </ul>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> <strong>Tip:</strong> Properties near your university campus fill up quickly. Start your search early to get the best options!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Section -->
        <div id="payments" class="mb-5 scroll-margin">
            <div class="help-card">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="help-card-icon icon-info mx-auto mb-3">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h2 class="section-title">Payment Methods & Security</h2>
                        <p class="section-subtitle">Safe and secure transactions</p>
                    </div>

                    <div class="row">
                        <div class="col-lg-10 mx-auto">
                            <h4 class="mb-3"><i class="fas fa-wallet text-success"></i> Accepted Payment Methods</h4>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <div class="text-center p-4 border rounded">
                                        <i class="fas fa-mobile-alt fa-3x text-success mb-3"></i>
                                        <h5>M-Pesa</h5>
                                        <p class="mb-0">Direct mobile money payments via Paystack</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-4 border rounded">
                                        <i class="fas fa-credit-card fa-3x text-primary mb-3"></i>
                                        <h5>Card Payments</h5>
                                        <p class="mb-0">Visa, Mastercard accepted securely</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-4 border rounded">
                                        <i class="fas fa-university fa-3x text-info mb-3"></i>
                                        <h5>Bank Transfer</h5>
                                        <p class="mb-0">Direct bank-to-bank transfers</p>
                                    </div>
                                </div>
                            </div>

                            <h4 class="mb-3"><i class="fas fa-lock text-warning"></i> Payment Security</h4>
                            <ul class="mb-4">
                                <li><strong>Encrypted transactions:</strong> All payments are encrypted with SSL/TLS</li>
                                <li><strong>PCI compliant:</strong> We never store your card details</li>
                                <li><strong>Paystack integration:</strong> Secure payment gateway</li>
                                <li><strong>Payment protection:</strong> Refunds available per cancellation policy</li>
                            </ul>

                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Never send money directly to landlords outside the platform. Always use our secure payment system for your protection.
                            </div>

                            <h4 class="mb-3"><i class="fas fa-receipt text-info"></i> Payment Schedule</h4>
                            <p class="mb-3">Typical payment timeline:</p>
                            <ul class="mb-4">
                                <li><strong>Booking deposit:</strong> Usually 1 month's rent to secure property</li>
                                <li><strong>First payment:</strong> Due before move-in date</li>
                                <li><strong>Monthly rent:</strong> Due on agreed date each month</li>
                                <li><strong>Security deposit:</strong> Refundable at end of lease</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account & Profile Section -->
        <div id="account" class="mb-5 scroll-margin">
            <div class="help-card">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="help-card-icon icon-warning mx-auto mb-3">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h2 class="section-title">Account & Profile Management</h2>
                        <p class="section-subtitle">Manage your settings and preferences</p>
                    </div>

                    <div class="row">
                        <div class="col-lg-10 mx-auto">
                            <h4 class="mb-3"><i class="fas fa-cog text-primary"></i> Profile Settings</h4>
                            <p class="mb-3">Access your profile settings to:</p>
                            <ul class="mb-4">
                                <li>Update personal information (name, email, phone)</li>
                                <li>Upload or change profile picture</li>
                                <li>Add university and student information</li>
                                <li>Update contact preferences</li>
                                <li>Manage notification settings</li>
                            </ul>

                            <h4 class="mb-3"><i class="fas fa-lock text-danger"></i> Security Settings</h4>
                            <ul class="mb-4">
                                <li><strong>Change password:</strong> Update your password regularly</li>
                                <li><strong>Two-factor authentication:</strong> Add extra security (coming soon)</li>
                                <li><strong>Login history:</strong> Review recent account activity</li>
                                <li><strong>Session management:</strong> Log out from all devices</li>
                            </ul>

                            <h4 class="mb-3"><i class="fas fa-bell text-warning"></i> Notification Preferences</h4>
                            <p class="mb-3">Control what notifications you receive:</p>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded">
                                        <i class="fas fa-envelope text-primary"></i> <strong>Email Notifications</strong>
                                        <p class="mb-0 small text-muted">Booking confirmations, payment receipts</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded">
                                        <i class="fas fa-sms text-success"></i> <strong>SMS Alerts</strong>
                                        <p class="mb-0 small text-muted">Urgent updates and reminders</p>
                                    </div>
                                </div>
                            </div>

                            <h4 class="mb-3"><i class="fas fa-trash-alt text-danger"></i> Account Deletion</h4>
                            <p class="mb-3">If you need to delete your account:</p>
                            <ol class="mb-4">
                                <li>Contact support to request account deletion</li>
                                <li>Complete any pending bookings or payments</li>
                                <li>Your data will be permanently deleted within 30 days</li>
                                <li>You can create a new account anytime</li>
                            </ol>

                            <div class="text-center mt-4">
                                <a href="settings.php" class="btn btn-primary btn-lg me-3">
                                    <i class="fas fa-cog"></i> Go to Settings
                                </a>
                                <a href="<?php echo $_SESSION['user_type'] === 'student' ? 'student_profile.php' : 'landlord_profile.php'; ?>" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Safety Tips Section -->
        <div class="safety-section">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="section-title">
                        <i class="fas fa-shield-alt text-danger"></i> Safety & Security Tips
                    </h2>
                    <p class="section-subtitle">Stay safe while using CampusDigs</p>
                </div>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="safety-tip-card">
                            <div class="safety-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h5 class="safety-tip-title">Visit Properties</h5>
                            <p class="safety-tip-text">Always visit properties in person before making payments. Never pay for properties you haven't seen.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="safety-tip-card">
                            <div class="safety-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <h5 class="safety-tip-title">Secure Payments</h5>
                            <p class="safety-tip-text">Only use our secure payment system. Never send money directly to landlords via mobile money.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="safety-tip-card">
                            <div class="safety-icon">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <h5 class="safety-tip-title">Read Agreements</h5>
                            <p class="safety-tip-text">Carefully read rental agreements before signing. Ask questions if anything is unclear.</p>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="safety-tip-card">
                            <div class="safety-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h5 class="safety-tip-title">Report Issues</h5>
                            <p class="safety-tip-text">Report suspicious listings or behavior immediately to our support team.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="help-cta">
            <h3><i class="fas fa-comment-dots"></i> Still Need Help?</h3>
            <p>Our friendly support team is ready to assist you with any questions or concerns</p>
            <a href="support.php" class="btn btn-light btn-lg">
                <i class="fas fa-headset"></i> Contact Support Team
            </a>
        </div>

        <!-- Navigation Buttons -->
        <div class="text-center mt-5 mb-4">
            <a href="<?php echo $_SESSION['user_type'] === 'student' ? '../dashboard_student.php' : '../dashboard_landlord.php'; ?>" class="btn btn-outline-primary btn-lg me-3">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="support.php#faq" class="btn btn-primary btn-lg">
                <i class="fas fa-question-circle"></i> View All FAQs
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#' && document.querySelector(href)) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    const offsetTop = target.offsetTop - 80; // Account for fixed header
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Back to top button functionality
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > 300) {
                if (!document.getElementById('backToTop')) {
                    const backToTop = document.createElement('button');
                    backToTop.id = 'backToTop';
                    backToTop.className = 'btn btn-primary rounded-circle';
                    backToTop.style.cssText = 'position: fixed; bottom: 30px; right: 30px; width: 50px; height: 50px; z-index: 999; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
                    backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
                    backToTop.onclick = function() {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    };
                    document.body.appendChild(backToTop);
                }
            } else {
                const backToTop = document.getElementById('backToTop');
                if (backToTop) {
                    backToTop.remove();
                }
            }
        });
    </script>
</body>
</html>
