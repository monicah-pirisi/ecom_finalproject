/**
 * CampusDigs Kenya - Dashboard JavaScript
 * Handles dashboard interactions and AJAX requests
 */

// WAIT FOR DOM TO LOAD
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize auto-dismiss alerts
    autoDismissAlerts();
    
    // Handle sidebar toggle on mobile
    handleSidebarToggle();
    
    // Handle search functionality
    handleSearch();
    
    // Initialize lazy loading for images
    initializeLazyLoading();
    
});

// TOOLTIP INITIALIZATION

function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// AUTO-DISMISS ALERTS
function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    
    alerts.forEach(function(alert) {
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// SIDEBAR TOGGLE (MOBILE)

function handleSidebarToggle() {
    const sidebarToggle = document.querySelector('[data-bs-toggle="collapse"][data-bs-target="#sidebar"]');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
}

// SEARCH FUNCTIONALITY
function handleSearch() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    
    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const query = searchInput.value.trim();
            
            if (query) {
                // Redirect to search results page
                window.location.href = `view/property_search_result.php?q=${encodeURIComponent(query)}`;
            }
        });
        
        // Real-time search suggestions (implement later)
        // searchInput.addEventListener('input', debounce(function() {
        //     const query = this.value.trim();
        //     if (query.length >= 3) {
        //         fetchSearchSuggestions(query);
        //     }
        // }, 300));
    }
}

// LAZY LOADING FOR IMAGES

function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// AJAX HELPER FUNCTIONS

/**
 * Send AJAX request
 * @param {string} url Request URL
 * @param {object} data Request data
 * @param {string} method HTTP method
 * @returns {Promise} Response promise
 */
function sendAjaxRequest(url, data = {}, method = 'POST') {
    const formData = new FormData();
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        formData.append('csrf_token', csrfToken.content);
    }
    
    // Add data to form
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    return fetch(url, {
        method: method,
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        showNotification('An error occurred. Please try again.', 'danger');
        throw error;
    });
}

// NOTIFICATION SYSTEM

/**
 * Show notification toast
 * @param {string} message Notification message
 * @param {string} type Notification type (success, danger, warning, info)
 */
function showNotification(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    
    // Create toast container if it doesn't exist
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    const container = document.getElementById('toastContainer');
    container.insertAdjacentHTML('beforeend', toastHTML);
    
    // Initialize and show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    
    toast.show();
    
    // Remove toast after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// FORM VALIDATION HELPERS

/**
 * Validate form before submission
 * @param {HTMLFormElement} form Form element
 * @returns {boolean} True if valid
 */
function validateForm(form) {
    let isValid = true;
    
    // Check required fields
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    return isValid;
}

/**
 * Show field error
 * @param {HTMLElement} field Input field
 * @param {string} message Error message
 */
function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    let feedback = field.nextElementSibling;
    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        field.parentNode.insertBefore(feedback, field.nextSibling);
    }
    
    feedback.textContent = message;
    feedback.style.display = 'block';
}

/**
 * Clear field error
 * @param {HTMLElement} field Input field
 */
function clearFieldError(field) {
    field.classList.remove('is-invalid');
    
    const feedback = field.nextElementSibling;
    if (feedback && feedback.classList.contains('invalid-feedback')) {
        feedback.style.display = 'none';
    }
}

// UTILITY FUNCTIONS

/**
 * Debounce function to limit rate of function execution
 * @param {Function} func Function to debounce
 * @param {number} wait Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Format currency (Kenyan Shillings)
 * @param {number} amount Amount to format
 * @returns {string} Formatted currency
 */
function formatCurrency(amount) {
    return 'KSh ' + amount.toLocaleString('en-KE', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

/**
 * Format date to readable format
 * @param {string} dateString Date string
 * @returns {string} Formatted date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-KE', options);
}

/**
 * Confirm action with user
 * @param {string} message Confirmation message
 * @returns {boolean} True if confirmed
 */
function confirmAction(message) {
    return confirm(message);
}

// LOADING STATES

/**
 * Show loading spinner on button
 * @param {HTMLElement} button Button element
 */
function showButtonLoading(button) {
    button.disabled = true;
    button.dataset.originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
}

/**
 * Hide loading spinner on button
 * @param {HTMLElement} button Button element
 */
function hideButtonLoading(button) {
    button.disabled = false;
    if (button.dataset.originalText) {
        button.innerHTML = button.dataset.originalText;
        delete button.dataset.originalText;
    }
}

/**
 * Show page loading overlay
 */
function showPageLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'pageLoadingOverlay';
    overlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-center bg-white bg-opacity-75';
    overlay.style.zIndex = '9999';
    overlay.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading...</p>
        </div>
    `;
    document.body.appendChild(overlay);
}

/**
 * Hide page loading overlay
 */
function hidePageLoading() {
    const overlay = document.getElementById('pageLoadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

// EXPORT FUNCTIONS FOR GLOBAL USE
window.campusDigs = {
    sendAjaxRequest,
    showNotification,
    validateForm,
    showFieldError,
    clearFieldError,
    formatCurrency,
    formatDate,
    confirmAction,
    showButtonLoading,
    hideButtonLoading,
    showPageLoading,
    hidePageLoading
};