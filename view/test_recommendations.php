<?php
/**
 * CampusDigs Kenya - AI Recommendations Demo/Test Page
 * Demonstrates the recommendation engine capabilities
 */

require_once '../includes/config.php';
require_once '../includes/core.php';

requireStudent();

$studentId = $_SESSION['user_id'];
$studentName = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Recommendations Demo - <?php echo APP_NAME; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4><i class="fas fa-robot"></i> AI-Powered Recommendation System Demo</h4>
                    <p class="mb-0">
                        This page demonstrates the intelligent recommendation engine.
                        The system analyzes your preferences, browsing history, and similar users' behavior
                        to suggest properties you'll love.
                    </p>
                </div>
            </div>
        </div>

        <!-- Student Profile Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-circle"></i> Your Recommendation Profile</h5>
                    </div>
                    <div class="card-body">
                        <div id="profile-summary">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading profile...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personalized Recommendations -->
        <?php require_once 'components/recommendations.php'; ?>
        <?php renderRecommendations('Personalized Recommendations', 'personalized', 6, true); ?>

        <hr class="my-5">

        <!-- Trending Properties -->
        <?php renderRecommendations('Trending This Week', 'trending', 6, false); ?>

        <!-- How It Works -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb"></i> How Recommendations Work</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="text-center">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-2"
                                         style="width: 60px; height: 60px;">
                                        <i class="fas fa-brain fa-2x"></i>
                                    </div>
                                    <h6>Content-Based</h6>
                                    <small class="text-muted">Matches property features to your preferences</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center">
                                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-2"
                                         style="width: 60px; height: 60px;">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                    <h6>Collaborative</h6>
                                    <small class="text-muted">Learns from similar students' choices</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center">
                                    <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center mb-2"
                                         style="width: 60px; height: 60px;">
                                        <i class="fas fa-fire fa-2x"></i>
                                    </div>
                                    <h6>Popularity</h6>
                                    <small class="text-muted">Considers ratings and reviews</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center">
                                    <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center mb-2"
                                         style="width: 60px; height: 60px;">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                    <h6>Real-Time</h6>
                                    <small class="text-muted">Updates based on your activity</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light mt-3">
                            <h6><i class="fas fa-info-circle"></i> Tip to Get Better Recommendations:</h6>
                            <ul class="mb-0">
                                <li>Add properties to your wishlist</li>
                                <li>Complete bookings to build your preference profile</li>
                                <li>Browse different property types and locations</li>
                                <li>Leave reviews for properties you've stayed in</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Load profile summary
    fetch('../actions/get_student_profile.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProfile(data.profile);
            } else {
                document.getElementById('profile-summary').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        ${data.message || 'Unable to load profile'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('profile-summary').innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-user-plus"></i>
                    Start adding properties to your wishlist to build your recommendation profile!
                </div>
            `;
        });

    function displayProfile(profile) {
        const activityCount = profile.activity_count || 0;
        const budgetAvg = profile.budget_avg || 0;
        const preferredLocations = Object.keys(profile.preferred_locations || {});
        const preferredTypes = Object.keys(profile.preferred_property_types || {});

        document.getElementById('profile-summary').innerHTML = `
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="text-primary">${activityCount}</h3>
                        <small class="text-muted">Total Activities</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="text-success">KSh ${budgetAvg.toLocaleString()}</h3>
                        <small class="text-muted">Average Budget</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="text-info">${preferredLocations.length}</h3>
                        <small class="text-muted">Preferred Locations</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h3 class="text-warning">${preferredTypes.length}</h3>
                        <small class="text-muted">Property Types</small>
                    </div>
                </div>
            </div>
            ${preferredLocations.length > 0 ? `
                <div class="mt-3">
                    <strong>Top Locations:</strong>
                    ${preferredLocations.slice(0, 3).map(loc =>
                        `<span class="badge bg-secondary me-1">${loc}</span>`
                    ).join('')}
                </div>
            ` : ''}
        `;
    }
    </script>
</body>
</html>
