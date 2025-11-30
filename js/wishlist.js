/**
 * CampusDigs Kenya - Wishlist JavaScript
 * Handles wishlist add/remove functionality
 */

// TOGGLE WISHLIST

/**
 * Toggle property in wishlist
 * @param {number} propertyId Property ID
 * @param {HTMLElement} button Button element clicked
 */
function toggleWishlist(propertyId, button) {
    // Check if user is logged in
    if (!isUserLoggedIn()) {
        // Redirect to login
        window.location.href = 'login/login.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    }
    
    // Get current state
    const isInWishlist = button.classList.contains('active');
    const action = isInWishlist ? 'remove' : 'add';
    
    // Show loading state
    button.disabled = true;
    const icon = button.querySelector('i');
    const originalIconClass = icon.className;
    icon.className = 'fas fa-spinner fa-spin';

    // Send AJAX request (use BASE_URL to work on any server)
    const baseUrl = window.BASE_URL || '/campus_digs';
    fetch(baseUrl + '/actions/toggle_wishlist.php?property_id=' + propertyId, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state based on backend response
            if (data.action === 'added') {
                button.classList.add('active');
                showWishlistNotification('Property added to wishlist!', 'success');

                // Animate heart
                icon.className = originalIconClass;
                button.style.animation = 'heartBeat 0.5s';
                setTimeout(() => {
                    button.style.animation = '';
                }, 500);
            } else if (data.action === 'removed') {
                button.classList.remove('active');
                showWishlistNotification('Property removed from wishlist', 'info');
                icon.className = originalIconClass;
            }

            // Update wishlist count in navbar
            if (typeof updateWishlistCount === 'function' && data.wishlist_count !== undefined) {
                updateWishlistCount(data.wishlist_count);
            }
        } else {
            // Show error
            showWishlistNotification(data.message || 'An error occurred', 'danger');
            icon.className = originalIconClass;
        }

        button.disabled = false;
    })
    .catch(error => {
        console.error('Wishlist error:', error);
        showWishlistNotification('Failed to update wishlist. Please try again.', 'danger');
        icon.className = originalIconClass;
        button.disabled = false;
    });
}

// BULK WISHLIST OPERATIONS

/**
 * Remove property from wishlist (for wishlist page)
 * @param {number} propertyId Property ID
 * @param {HTMLElement} card Property card element
 */
function removeFromWishlist(propertyId, card) {
    if (!confirm('Are you sure you want to remove this property from your wishlist?')) {
        return;
    }
    
    // Show loading overlay on card
    card.style.opacity = '0.5';
    card.style.pointerEvents = 'none';
    
    // Send AJAX request
    fetch('actions/wishlist_remove_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `property_id=${propertyId}`,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animate removal
            card.style.transition = 'all 0.3s ease';
            card.style.transform = 'scale(0.8)';
            card.style.opacity = '0';
            
            setTimeout(() => {
                card.remove();
                
                // Check if wishlist is empty
                const container = document.getElementById('wishlistContainer');
                if (container && container.children.length === 0) {
                    showEmptyWishlist();
                }
            }, 300);
            
            // Update wishlist count
            updateWishlistCount(data.wishlist_count);
            
            showWishlistNotification('Property removed from wishlist', 'success');
        } else {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
            showWishlistNotification(data.message || 'Failed to remove property', 'danger');
        }
    })
    .catch(error => {
        console.error('Remove error:', error);
        card.style.opacity = '1';
        card.style.pointerEvents = 'auto';
        showWishlistNotification('An error occurred. Please try again.', 'danger');
    });
}

/**
 * Clear entire wishlist
 */
function clearWishlist() {
    if (!confirm('Are you sure you want to clear your entire wishlist? This action cannot be undone.')) {
        return;
    }
    
    const clearBtn = document.getElementById('clearWishlistBtn');
    if (clearBtn) {
        clearBtn.disabled = true;
        clearBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Clearing...';
    }
    
    // Send AJAX request
    fetch('actions/wishlist_clear_action.php', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove all cards with animation
            const container = document.getElementById('wishlistContainer');
            if (container) {
                const cards = container.querySelectorAll('.property-card');
                cards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.transition = 'all 0.3s ease';
                        card.style.transform = 'scale(0.8)';
                        card.style.opacity = '0';
                        
                        setTimeout(() => card.remove(), 300);
                    }, index * 50);
                });
                
                setTimeout(() => {
                    showEmptyWishlist();
                }, cards.length * 50 + 300);
            }
            
            // Update wishlist count
            updateWishlistCount(0);
            
            showWishlistNotification('Wishlist cleared successfully', 'success');
        } else {
            showWishlistNotification(data.message || 'Failed to clear wishlist', 'danger');
        }
        
        if (clearBtn) {
            clearBtn.disabled = false;
            clearBtn.innerHTML = '<i class="fas fa-trash"></i> Clear Wishlist';
        }
    })
    .catch(error => {
        console.error('Clear error:', error);
        showWishlistNotification('An error occurred. Please try again.', 'danger');
        
        if (clearBtn) {
            clearBtn.disabled = false;
            clearBtn.innerHTML = '<i class="fas fa-trash"></i> Clear Wishlist';
        }
    });
}

// UI HELPERS

/**
 * Update wishlist count in navbar
 * @param {number} count New wishlist count
 */
function updateWishlistCount(count) {
    const badge = document.getElementById('wishlistCountBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Show empty wishlist message
 */
function showEmptyWishlist() {
    const container = document.getElementById('wishlistContainer');
    if (container) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-heart fa-5x text-muted mb-4"></i>
                <h3 class="text-muted">Your wishlist is empty</h3>
                <p class="text-muted mb-4">Start adding properties you love!</p>
                <a href="all_properties.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Browse Properties
                </a>
            </div>
        `;
    }
}

/**
 * Show wishlist notification
 * @param {string} message Notification message
 * @param {string} type Notification type
 */
function showWishlistNotification(message, type = 'info') {
    // Use the global notification system if available
    if (window.campusDigs && window.campusDigs.showNotification) {
        window.campusDigs.showNotification(message, type);
    } else {
        // Fallback to simple alert
        const iconMap = {
            success: 'OK',
            danger: 'X',
            warning: '!',
            info: 'i'
        };
        alert(iconMap[type] + ' ' + message);
    }
}

/**
 * Check if user is logged in
 * @returns {boolean} True if logged in
 */
function isUserLoggedIn() {
    // Check if there's a user session indicator
    const userIndicator = document.querySelector('[data-user-logged-in]');
    return userIndicator !== null;
}

// INITIALIZATION


document.addEventListener('DOMContentLoaded', function() {
    
    // Mark properties already in wishlist
    markWishlistedProperties();
    
    // Set up event listeners for wishlist buttons
    setupWishlistButtons();
    
    // Set up clear wishlist button
    const clearBtn = document.getElementById('clearWishlistBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', clearWishlist);
    }
});

/**
 * Mark properties that are already in wishlist
 */
function markWishlistedProperties() {
    const wishlistIds = document.querySelector('[data-wishlist-ids]');
    if (wishlistIds) {
        try {
            const ids = JSON.parse(wishlistIds.dataset.wishlistIds);
            ids.forEach(id => {
                const buttons = document.querySelectorAll(`[data-property-id="${id}"] .btn-wishlist`);
                buttons.forEach(btn => btn.classList.add('active'));
            });
        } catch (e) {
            console.error('Failed to parse wishlist IDs:', e);
        }
    }
}

/**
 * Set up event listeners for dynamically added wishlist buttons
 */
function setupWishlistButtons() {
    document.addEventListener('click', function(e) {
        const wishlistBtn = e.target.closest('.btn-wishlist');
        if (wishlistBtn) {
            const propertyCard = wishlistBtn.closest('[data-property-id]');
            if (propertyCard) {
                const propertyId = propertyCard.dataset.propertyId;
                toggleWishlist(propertyId, wishlistBtn);
            }
        }
    });
}

// CSS ANIMATION FOR HEART BEAT

// Add CSS animation dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes heartBeat {
        0%, 100% { transform: scale(1); }
        25% { transform: scale(1.3); }
        50% { transform: scale(1.1); }
        75% { transform: scale(1.2); }
    }
`;
document.head.appendChild(style);