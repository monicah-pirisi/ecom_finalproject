<?php
/**
 * CampusDigs Kenya - AI Recommendations Component
 * Reusable component for displaying property recommendations
 */

if (!function_exists('renderRecommendations')) {
    function renderRecommendations($title, $type = 'personalized', $limit = 6, $showReason = true) {
        ?>
        <div class="recommendations-section mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <i class="fas fa-sparkles text-warning"></i> <?php echo htmlspecialchars($title); ?>
                </h4>
                <span class="badge bg-primary">AI-Powered</span>
            </div>

            <div id="recommendations-<?php echo $type; ?>" class="row g-3">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading recommendations...</span>
                    </div>
                    <p class="text-muted mt-3">Analyzing your preferences...</p>
                </div>
            </div>
        </div>

        <script>
        (function() {
            const container = document.getElementById('recommendations-<?php echo $type; ?>');

            // Determine correct path based on current location
            const basePath = window.location.pathname.includes('/view/') ? '../actions/' : 'actions/';
            const fetchUrl = basePath + 'get_recommendations.php?type=<?php echo $type; ?>&limit=<?php echo $limit; ?>';

            fetch(fetchUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.recommendations.length > 0) {
                        renderProperties(data.recommendations, <?php echo $showReason ? 'true' : 'false'; ?>);
                    } else {
                        showNoRecommendations();
                    }
                })
                .catch(error => {
                    console.error('Error loading recommendations:', error);
                    showError();
                });

            function renderProperties(properties, showReason) {
                container.innerHTML = '';

                properties.forEach(property => {
                    const propertyCard = createPropertyCard(property, showReason);
                    container.appendChild(propertyCard);
                });
            }

            function createPropertyCard(property, showReason) {
                const col = document.createElement('div');
                col.className = 'col-md-6 col-lg-4';

                const scorePercentage = Math.min(100, (property.recommendation_score / 100) * 100);
                const scoreColor = scorePercentage >= 70 ? 'success' : scorePercentage >= 50 ? 'warning' : 'info';

                // Determine correct image path
                const imagePath = window.location.pathname.includes('/view/')
                    ? '../' + (property.main_image || 'assets/images/placeholder.jpg')
                    : (property.main_image || 'assets/images/placeholder.jpg');

                col.innerHTML = `
                    <div class="card property-card h-100 shadow-sm hover-shadow">
                        <div class="position-relative">
                            <img src="${imagePath}"
                                 class="card-img-top" alt="${escapeHtml(property.title)}"
                                 style="height: 200px; object-fit: cover;"
                                 onerror="this.src='${window.location.pathname.includes('/view/') ? '../' : ''}assets/images/placeholder.jpg'">
                            ${property.is_premium ? '<span class="badge bg-warning position-absolute top-0 start-0 m-2"><i class="fas fa-crown"></i> Premium</span>' : ''}
                            ${showReason && property.recommendation_score ? `
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-${scoreColor}">
                                        <i class="fas fa-star"></i> ${Math.round(scorePercentage)}% Match
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">${escapeHtml(property.title)}</h5>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt"></i> ${escapeHtml(property.location)}
                            </p>
                            <div class="mb-2">
                                <span class="badge bg-light text-dark me-1">
                                    <i class="fas fa-bed"></i> ${property.bedrooms} Bed
                                </span>
                                <span class="badge bg-light text-dark me-1">
                                    <i class="fas fa-bath"></i> ${property.bathrooms} Bath
                                </span>
                                ${property.avg_rating > 0 ? `
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-star text-warning"></i> ${parseFloat(property.avg_rating).toFixed(1)}
                                    </span>
                                ` : ''}
                            </div>
                            ${showReason && property.recommendation_reasons && property.recommendation_reasons.length > 0 ? `
                                <div class="recommendation-reasons mb-2">
                                    ${property.recommendation_reasons.map(reason =>
                                        `<small class="d-block text-success"><i class="fas fa-check-circle"></i> ${escapeHtml(reason)}</small>`
                                    ).join('')}
                                </div>
                            ` : ''}
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <h5 class="text-primary mb-0">KSh ${parseInt(property.price_monthly).toLocaleString()}/mo</h5>
                                <a href="${window.location.pathname.includes('/view/') ? '' : 'view/'}single_property.php?id=${property.id}" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                `;

                return col;
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function showNoRecommendations() {
                const allPropertiesUrl = window.location.pathname.includes('/view/')
                    ? 'all_properties.php'
                    : 'view/all_properties.php';

                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>No recommendations available yet</h5>
                        <p class="text-muted">Start browsing properties to get personalized recommendations!</p>
                        <a href="${allPropertiesUrl}" class="btn btn-primary mt-3">
                            <i class="fas fa-search"></i> Browse All Properties
                        </a>
                    </div>
                `;
            }

            function showError() {
                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Failed to load recommendations</h5>
                        <p class="text-muted">Please try again later</p>
                    </div>
                `;
            }
        })();
        </script>

        <style>
        .property-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .property-card:hover {
            transform: translateY(-5px);
        }

        .hover-shadow:hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
        }

        .recommendation-reasons small {
            font-size: 0.75rem;
            line-height: 1.6;
        }
        </style>
        <?php
    }
}
?>
