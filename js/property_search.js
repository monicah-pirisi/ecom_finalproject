/**
 * Property Search - AJAX Live Search and Filtering
 * Handles real-time property search without page reloads
 */

(function() {
    'use strict';

    // DOM elements
    const searchForm = document.getElementById('searchForm');
    const filterForm = document.getElementById('filterForm');
    const searchInput = document.querySelector('input[name="q"]');
    const propertiesGrid = document.getElementById('propertiesGrid');
    const paginationContainer = document.getElementById('paginationContainer');
    const resultsInfo = document.getElementById('resultsInfo');
    const sortSelect = document.querySelector('select[name="sort_by"]');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // Debounce timer for search input
    let searchDebounceTimer;
    const DEBOUNCE_DELAY = 500;

    // Current page
    let currentPage = 1;

    /**
     * Show loading state
     */
    function showLoading() {
        if (loadingOverlay) {
            loadingOverlay.classList.remove('d-none');
        }
        if (propertiesGrid) {
            propertiesGrid.style.opacity = '0.5';
            propertiesGrid.style.pointerEvents = 'none';
        }
    }

    /**
     * Hide loading state
     */
    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.classList.add('d-none');
        }
        if (propertiesGrid) {
            propertiesGrid.style.opacity = '1';
            propertiesGrid.style.pointerEvents = 'auto';
        }
    }

    /**
     * Get all form data as query parameters
     */
    function getFormData() {
        const params = new URLSearchParams();

        // Get search query
        if (searchInput && searchInput.value.trim()) {
            params.append('q', searchInput.value.trim());
        }

        // Get filter values
        if (filterForm) {
            const formData = new FormData(filterForm);
            for (const [key, value] of formData.entries()) {
                if (value && value !== '') {
                    params.append(key, value);
                }
            }
        }

        // Add current page
        params.append('page', currentPage);

        return params.toString();
    }

    /**
     * Perform AJAX search
     */
    async function performSearch() {
        showLoading();

        const queryString = getFormData();

        try {
            const response = await fetch(`../actions/search_properties_action.php?${queryString}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Search request failed');
            }

            const data = await response.json();

            if (data.success) {
                // Update properties grid
                if (propertiesGrid) {
                    propertiesGrid.innerHTML = data.html;
                }

                // Update pagination
                if (paginationContainer) {
                    paginationContainer.innerHTML = data.pagination;
                    attachPaginationHandlers();
                }

                // Update results info
                if (resultsInfo) {
                    const searchQuery = searchInput && searchInput.value.trim() ? searchInput.value.trim() : null;
                    let infoText = `Showing ${data.count} of ${data.total} properties`;
                    if (searchQuery) {
                        infoText += ` for "<strong>${escapeHtml(searchQuery)}</strong>"`;
                    }
                    resultsInfo.innerHTML = infoText;
                }

                // Scroll to top of results smoothly
                if (propertiesGrid) {
                    propertiesGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                showToast('error', data.message || 'Failed to load properties');
            }
        } catch (error) {
            console.error('Search error:', error);
            showToast('error', 'An error occurred while searching. Please try again.');
        } finally {
            hideLoading();
        }
    }

    /**
     * Attach event handlers to pagination links
     */
    function attachPaginationHandlers() {
        const paginationLinks = document.querySelectorAll('.pagination .page-link');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (page && page > 0) {
                    currentPage = page;
                    performSearch();
                }
            });
        });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show toast notification
     */
    function showToast(type, message) {
        const alertType = type === 'error' ? 'danger' : type;
        const toastHtml = `
            <div class="alert alert-${alertType} alert-dismissible fade show" role="alert">
                ${escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        const container = document.querySelector('.alert-container') || document.querySelector('main');
        if (container) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = toastHtml;
            container.insertBefore(tempDiv.firstElementChild, container.firstChild);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => alert.remove(), 150);
                }
            }, 5000);
        }
    }

    /**
     * Initialize search functionality
     */
    function initializeSearch() {
        // Search form submission
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                currentPage = 1;
                performSearch();
            });
        }

        // Live search on input (with debounce)
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    currentPage = 1;
                    performSearch();
                }, DEBOUNCE_DELAY);
            });
        }

        // Filter form changes
        if (filterForm) {
            const filterInputs = filterForm.querySelectorAll('input, select');
            filterInputs.forEach(input => {
                input.addEventListener('change', function() {
                    currentPage = 1;
                    performSearch();
                });
            });

            // Prevent form submission
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
            });
        }

        // Sort select change
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                currentPage = 1;
                performSearch();
            });
        }

        // Clear filters button
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Reset all form fields
                if (searchInput) {
                    searchInput.value = '';
                }
                if (filterForm) {
                    filterForm.reset();
                }

                // Reset page and search
                currentPage = 1;
                performSearch();
            });
        }

        // Initial pagination handler attachment
        attachPaginationHandlers();
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSearch);
    } else {
        initializeSearch();
    }

    // Expose toggleWishlist function globally for inline onclick handlers
    window.toggleWishlist = async function(propertyId, buttonElement) {
        try {
            const response = await fetch(`../actions/toggle_wishlist.php?property_id=${propertyId}`);
            const data = await response.json();

            if (data.success) {
                // Toggle heart icon
                const heartIcon = buttonElement.querySelector('i');
                if (heartIcon) {
                    heartIcon.classList.toggle('fas');
                    heartIcon.classList.toggle('far');
                }

                // Show notification
                showToast('success', data.message || 'Wishlist updated');

                // Update wishlist count in header if exists
                const wishlistBadge = document.querySelector('.wishlist-count');
                if (wishlistBadge && data.wishlist_count !== undefined) {
                    wishlistBadge.textContent = data.wishlist_count;
                }
            } else {
                showToast('error', data.message || 'Failed to update wishlist');
            }
        } catch (error) {
            console.error('Wishlist error:', error);
            showToast('error', 'An error occurred. Please try again.');
        }
    };

})();
