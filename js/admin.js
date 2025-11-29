/**
 * Admin Quick Actions - AJAX Handling
 * Handles admin user management actions (verify, suspend, reactivate, delete) without page reload
 */

(function() {
    'use strict';

    /**
     * Show toast notification
     */
    function showToast(type, message) {
        const alertClass = type === 'error' ? 'danger' : type;
        const iconClass = type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle';
        const toastHTML = `
            <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas fa-${iconClass}"></i> ${escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        const container = document.querySelector('.alert-container') || document.querySelector('main');
        if (container) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = toastHTML;
            const alert = tempDiv.firstElementChild;
            container.insertBefore(alert, container.firstChild);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }, 5000);
        }
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
     * Show confirmation modal
     */
    function showConfirmationModal(title, message, onConfirm, requireReason = false, dangerAction = false) {
        const modalHTML = `
            <div class="modal fade" id="adminConfirmModal" tabindex="-1" aria-labelledby="adminConfirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header ${dangerAction ? 'bg-danger text-white' : ''}">
                            <h5 class="modal-title" id="adminConfirmModalLabel">${title}</h5>
                            <button type="button" class="btn-close ${dangerAction ? 'btn-close-white' : ''}" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                            ${requireReason ? `
                                <div class="mb-3">
                                    <label for="adminReasonInput" class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="adminReasonInput" rows="3" required placeholder="Please provide a reason..."></textarea>
                                    <div class="invalid-feedback">Please provide a reason.</div>
                                </div>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn ${dangerAction ? 'btn-danger' : 'btn-primary'}" id="adminConfirmButton">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove any existing modal
        const existingModal = document.getElementById('adminConfirmModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = document.getElementById('adminConfirmModal');
        const bsModal = new bootstrap.Modal(modal);
        const confirmBtn = modal.querySelector('#adminConfirmButton');
        const reasonInput = modal.querySelector('#adminReasonInput');

        // Handle confirm button click
        confirmBtn.addEventListener('click', function() {
            if (requireReason) {
                const reason = reasonInput.value.trim();
                if (!reason) {
                    reasonInput.classList.add('is-invalid');
                    return;
                }
                onConfirm(reason);
            } else {
                onConfirm();
            }
            bsModal.hide();
        });

        // Clear invalid state on input
        if (reasonInput) {
            reasonInput.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        }

        // Remove modal from DOM after hidden
        modal.addEventListener('hidden.bs.modal', function() {
            modal.remove();
        });

        bsModal.show();
    }

    /**
     * Update user row after action
     */
    function updateUserRow(userRow, newStatus, statusBadge, statusText) {
        const badge = userRow.querySelector('.user-status-badge');
        if (badge) {
            badge.className = `badge user-status-badge ${statusBadge}`;
            badge.textContent = statusText;
        }

        // Update action buttons based on new status
        const actionBtns = userRow.querySelectorAll('.user-action-btn');
        actionBtns.forEach(btn => {
            const action = btn.getAttribute('data-action');

            // Hide/show buttons based on new status
            if (newStatus === 'verified' && action === 'verify') {
                btn.style.display = 'none';
            } else if (newStatus === 'suspended') {
                if (action === 'suspend') {
                    btn.style.display = 'none';
                } else if (action === 'reactivate') {
                    btn.style.display = 'inline-block';
                }
            } else if (newStatus === 'active') {
                if (action === 'reactivate') {
                    btn.style.display = 'none';
                } else if (action === 'suspend') {
                    btn.style.display = 'inline-block';
                }
            }
        });
    }

    /**
     * Verify User
     */
    window.verifyUser = function(userId, buttonElement) {
        showConfirmationModal(
            'Verify User Account',
            'Are you sure you want to verify this user account? This will grant them full platform access.',
            async function() {
                const btn = buttonElement;
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'verify');
                    formData.append('user_id', userId);

                    const response = await fetch('../actions/admin_users_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('success', data.message);

                        // Update user row
                        const userRow = btn.closest('.user-row') || btn.closest('tr');
                        if (userRow) {
                            updateUserRow(userRow, 'verified', 'bg-success', 'Verified');
                        }

                        // Reload after 2 seconds
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showToast('error', data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                } catch (error) {
                    console.error('Verify user error:', error);
                    showToast('error', 'An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }
        );
    };

    /**
     * Suspend User
     */
    window.suspendUser = function(userId, buttonElement) {
        showConfirmationModal(
            'Suspend User Account',
            'Please provide a reason for suspending this user account:',
            async function(reason) {
                const btn = buttonElement;
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'suspend');
                    formData.append('user_id', userId);
                    formData.append('reason', reason);

                    const response = await fetch('../actions/admin_users_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('success', data.message);

                        // Update user row
                        const userRow = btn.closest('.user-row') || btn.closest('tr');
                        if (userRow) {
                            updateUserRow(userRow, 'suspended', 'bg-danger', 'Suspended');
                        }

                        // Reload after 2 seconds
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showToast('error', data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                } catch (error) {
                    console.error('Suspend user error:', error);
                    showToast('error', 'An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            },
            true, // Require reason
            true  // Danger action
        );
    };

    /**
     * Reactivate User
     */
    window.reactivateUser = function(userId, buttonElement) {
        showConfirmationModal(
            'Reactivate User Account',
            'Are you sure you want to reactivate this suspended user account?',
            async function() {
                const btn = buttonElement;
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'reactivate');
                    formData.append('user_id', userId);

                    const response = await fetch('../actions/admin_users_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('success', data.message);

                        // Update user row
                        const userRow = btn.closest('.user-row') || btn.closest('tr');
                        if (userRow) {
                            updateUserRow(userRow, 'active', 'bg-success', 'Active');
                        }

                        // Reload after 2 seconds
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showToast('error', data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                } catch (error) {
                    console.error('Reactivate user error:', error);
                    showToast('error', 'An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }
        );
    };

    /**
     * Delete User
     */
    window.deleteUser = function(userId, buttonElement) {
        showConfirmationModal(
            'Delete User Account',
            '<strong class="text-danger">Warning:</strong> This action will permanently delete the user account and all associated data. Please provide a reason for this deletion (for audit purposes):',
            async function(reason) {
                const btn = buttonElement;
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('user_id', userId);
                    formData.append('reason', reason);

                    const response = await fetch('../actions/admin_users_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('success', data.message);

                        // Remove user row with animation
                        const userRow = btn.closest('.user-row') || btn.closest('tr');
                        if (userRow) {
                            userRow.style.opacity = '0';
                            userRow.style.transition = 'opacity 0.5s';
                            setTimeout(() => {
                                userRow.remove();
                            }, 500);
                        }

                        // Reload after 3 seconds
                        setTimeout(() => window.location.reload(), 3000);
                    } else {
                        showToast('error', data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                } catch (error) {
                    console.error('Delete user error:', error);
                    showToast('error', 'An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            },
            true, // Require reason
            true  // Danger action
        );
    };

    /**
     * Initialize tooltips and popovers
     */
    function initializeBootstrapComponents() {
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Initialize popovers
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeBootstrapComponents);
    } else {
        initializeBootstrapComponents();
    }

})();
