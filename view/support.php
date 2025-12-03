<?php
/**
 * CampusDigs Kenya - Support Page
 * Contact support and help resources
 */

require_once '../includes/config.php';
require_once '../includes/core.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - <?php echo APP_NAME; ?></title>
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
                    <h1><i class="fas fa-headset text-primary"></i> Support Center</h1>
                    <p class="lead">Our friendly support team is here to help you 24/7. Get quick answers and personalized assistance.</p>
                    <div class="d-flex gap-3">
                        <a href="#contact" class="btn btn-primary btn-lg">
                            <i class="fas fa-envelope"></i> Email Us
                        </a>
                        <a href="#faq" class="btn btn-light btn-lg">
                            <i class="fas fa-question-circle"></i> View FAQs
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center d-none d-lg-block">
                    <img src="https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?w=600&h=500&fit=crop"
                         alt="Student getting help"
                         class="hero-image">
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Contact Methods -->
        <div id="contact" class="mb-5 scroll-margin">
            <div class="text-center mb-5">
                <h2 class="section-title">Get In Touch</h2>
                <p class="section-subtitle">Choose your preferred way to reach us</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="contact-method">
                        <div class="contact-icon-wrapper contact-icon-primary">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email Support</h5>
                        <p class="mb-1">support@campusdigs.co.ke</p>
                        <small class="text-muted">Response within 24 hours</small>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="contact-method">
                        <div class="contact-icon-wrapper contact-icon-primary">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5>Phone Support</h5>
                        <p class="mb-1">+254 700 000 000</p>
                        <small class="text-muted">Mon-Fri: 9AM-5PM EAT</small>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="contact-method">
                        <div class="contact-icon-wrapper contact-icon-success">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h5>WhatsApp</h5>
                        <p class="mb-1">+254 712 345 678</p>
                        <small class="text-muted">Quick responses</small>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="contact-method">
                        <div class="contact-icon-wrapper contact-icon-info">
                            <i class="fab fa-twitter"></i>
                        </div>
                        <h5>Social Media</h5>
                        <p class="mb-1">@CampusDigsKE</p>
                        <small class="text-muted">Updates & support</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Chat CTA -->
        <div class="help-card mb-5">
            <div class="card-body text-center p-5">
                <div class="help-card-icon icon-success mx-auto">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="mb-3">Need Immediate Help?</h3>
                <p class="help-card-text mb-4">Our live chat support is available during business hours for instant assistance</p>
                <button class="btn btn-primary btn-lg" disabled>
                    <i class="fas fa-comment-dots"></i> Start Live Chat <small>(Coming Soon)</small>
                </button>
            </div>
        </div>

        <!-- FAQ Section -->
        <div id="faq" class="faq-section scroll-margin">
            <div class="text-center mb-5">
                <h2 class="section-title">
                    <i class="fas fa-question-circle text-primary"></i> Frequently Asked Questions
                </h2>
                <p class="section-subtitle">Find answers to common questions</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                                    <i class="fas fa-home text-primary me-2"></i> How do I book a property?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <strong>Step-by-step booking process:</strong>
                                    <ol class="mt-2 mb-0">
                                        <li>Browse available properties using our search filters</li>
                                        <li>Click on a property to view detailed information and photos</li>
                                        <li>Click the "Book Now" button</li>
                                        <li>Fill in your booking details and any special requests</li>
                                        <li>Submit your booking request</li>
                                        <li>The landlord will review and respond within 24 hours</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                                    <i class="fas fa-credit-card text-success me-2"></i> What payment methods do you accept?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We accept multiple payment methods through our secure Paystack integration:
                                    <ul class="mt-2 mb-0">
                                        <li><strong>M-Pesa:</strong> Direct mobile money payments</li>
                                        <li><strong>Visa/Mastercard:</strong> Credit and debit cards</li>
                                        <li><strong>Bank Transfer:</strong> Direct bank transfers</li>
                                    </ul>
                                    All payments are encrypted and secure. We never store your card details.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                                    <i class="fas fa-shield-check text-info me-2"></i> How do I verify my account?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <strong>Account verification process:</strong>
                                    <ol class="mt-2 mb-2">
                                        <li>Log in to your account</li>
                                        <li>Go to your profile settings</li>
                                        <li>Upload required documents:
                                            <ul>
                                                <li><strong>Students:</strong> Valid student ID or admission letter</li>
                                                <li><strong>Landlords:</strong> National ID and property ownership documents</li>
                                            </ul>
                                        </li>
                                        <li>Our team will review within 48 hours</li>
                                        <li>You'll receive an email notification once verified</li>
                                    </ol>
                                    Verified accounts get priority support and build more trust with other users.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                                    <i class="fas fa-undo text-warning me-2"></i> What is your cancellation policy?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Our cancellation policy varies by landlord and property. Generally:
                                    <ul class="mt-2 mb-0">
                                        <li><strong>Before acceptance:</strong> Free cancellation before landlord accepts</li>
                                        <li><strong>After acceptance:</strong> Check the specific property's cancellation terms</li>
                                        <li><strong>Refunds:</strong> Processed within 7-14 business days</li>
                                    </ul>
                                    Always read the property's cancellation policy before booking.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false" aria-controls="faq5">
                                    <i class="fas fa-exclamation-triangle text-danger me-2"></i> How do I report a problem?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <strong>To report issues or suspicious activity:</strong>
                                    <ol class="mt-2 mb-0">
                                        <li>Click the "Report" button on the property or user profile</li>
                                        <li>Describe the issue in detail</li>
                                        <li>Attach any relevant screenshots or evidence</li>
                                        <li>Our team will investigate within 24 hours</li>
                                    </ol>
                                    For urgent safety concerns, call our emergency hotline: <strong>+254 700 000 000</strong>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6" aria-expanded="false" aria-controls="faq6">
                                    <i class="fas fa-star text-warning me-2"></i> Can I leave a review?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes! We encourage honest reviews to help other students make informed decisions:
                                    <ul class="mt-2 mb-0">
                                        <li>You can review properties after your booking is complete</li>
                                        <li>Rate the property from 1-5 stars</li>
                                        <li>Share your experience with photos</li>
                                        <li>Reviews are moderated for quality and honesty</li>
                                        <li>Landlords can respond to reviews</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="text-center mt-5 mb-4">
            <a href="help.php" class="btn btn-primary btn-lg me-2">
                <i class="fas fa-question-circle"></i> Help Center
            </a>
            <a href="<?php echo $_SESSION['user_type'] === 'student' ? '../dashboard_student.php' : '../dashboard_landlord.php'; ?>" class="btn btn-outline-primary btn-lg me-2">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="javascript:history.back()" class="btn btn-light btn-lg">
                <i class="fas fa-arrow-left"></i> Go Back
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

        // Auto-scroll to FAQ section if URL has #faq
        window.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash === '#faq') {
                setTimeout(function() {
                    const faqSection = document.getElementById('faq');
                    if (faqSection) {
                        const offsetTop = faqSection.offsetTop - 80;
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }
                }, 100);
            }
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
