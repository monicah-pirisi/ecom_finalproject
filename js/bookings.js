/**
 * Bookings Management - AJAX Handling
 * Handles booking actions (approve, reject, cancel, complete) without page reload
 */

(function() {
    'use strict';

    /**
     * Show toast notification
     */
    function showToast(type, message) {
        const alertClass = type === 'error' ? 'danger' : type;
        const toastHTML = `
            <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${escapeHtml(message)}
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
    function showConfirmationModal(title, message, onConfirm, requireReason = false) {
        // Create modal HTML
        const modalHTML = `
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmModalLabel">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>${message}</p>
                            ${requireReason ? `
                                <div class="mb-3">
                                    <label for="reasonInput" class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="reasonInput" rows="3" required placeholder="Please provide a reason..."></textarea>
                                    <div class="invalid-feedback">Please provide a reason.</div>
                                </div>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmButton">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove any existing modal
        const existingModal = document.getElementById('confirmModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = document.getElementById('confirmModal');
        const bsModal = new bootstrap.Modal(modal);
        const confirmBtn = modal.querySelector('#confirmButton');
        const reasonInput = modal.querySelector('#reasonInput');

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
     * Update booking card status
     */
    function updateBookingCard(bookingCard, newStatus, statusClass, statusText) {
        const statusBadge = bookingCard.querySelector('.booking-status-badge');
        if (statusBadge) {
            statusBadge.className = `badge booking-status-badge ${statusClass}`;
            statusBadge.textContent = statusText;
        }

        // Hide action buttons for completed/cancelled/rejected bookings
        if (['cancelled', 'rejected', 'completed'].includes(newStatus)) {
            const actionBtns = bookingCard.querySelectorAll('.booking-action-btn');
            actionBtns.forEach(btn => {
                btn.style.display = 'none';
            });
        }
    }

    /**
     * Approve Booking (Landlord)
     */
    window.approveBooking = function(bookingId, buttonElement) {
        showConfirmationModal(
            'Approve Booking',
            'Are you sure you want to approve this booking? The student will be notified.',
            async function() {
                const btn = buttonElement;
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'approve');
                    formData.append('booking_id', bookingId);

                    const response = await fetch('../actions/landlord_bookings_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('success', data.message);

                        // Update card status
                        const bookingCard = btn.closest('.booking-card') || btn.closest('.card');
                        if (bookingCard) {
                            updateBookingCard(bookingCard, 'approved', 'bg-success', 'Approved');
                        }

                        // Reload page after 2 seconds
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showToast('error', data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                } catch (error) {
                    console.error('Approve booking error:', error);
                    showToast('error', 'An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }
        );
    };

    /**
     * Reject Booking (Landlord)
     */
    window.rejectBooking = function(bookingId, buttonElement) {
        showConfirmationModal(
            'Reject Booking',
            'Please provide a reason for rejecting this booking:',
            async function(reason) {
                const btn = buttonElement;
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'reject');
                    formData.append('booking_id', bookingId);
                    formData.append('reason', reason);

                    const response = await fetch('../actions/landlord_bookings_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('success', data.message);

                        // Update card status
                        const bookingCard = btn.closest('.booking-card') || btn.closest('.card');
                        if (bookingCard) {
                            updateBookingCard(bookingCard, 'rejected', 'bg-danger', 'Rejected');
                        }

                        // Reload page after 2 seconds
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showToast('error', data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                } catch (error) {
                    console.error('Reject booking error:', error);
                    showToast('error', 'An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            },
            true // Require reason
        );
    };

    /**
     * Complete Booking (Landlord)
     */
    window.completeBooking = function(bookingId, buttonElement) {
        showConfirmationModal(
            'Complete Booking',
            'Mark this booking as completed? This action confirms that the student has moved in.',
            async function() {
                const btn = buttonElement;
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'complete');
                    formData.append('booking_id', bookingId);

                    const response = await fetch('../actions/landlord_bookings_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('success', data.message);

                        // Update card status
                        const bookingCard = btn.closest('.booking-card') || btn.closest('.card');
                        if (bookingCard) {
                            updateBookingCard(bookingCard, 'completed', 'bg-info', 'Completed');
                        }

                        // Reload page after 2 seconds
                        setTimeout(() => window.location.reload(), 2000);
                    } else {
                        showToast('error', data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                } catch (error) {
                    console.error('Complete booking error:', error);
                    showToast('error', 'An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            }
        );
    };

    /**
     * Cancel Booking (Student)
     */
    window.cancelBooking = function(bookingId, buttonElement) {
        showConfirmationModal(
            'Cancel Booking',
            'Please provide a reason for cancelling this booking:',
            async function(reason) {
                const btn = buttonElement;
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'cancel');
                    formData.append('booking_id', bookingId);
                    formData.append('reason', reason);

                    const response = await fetch('../actions/student_bookings_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        let message = data.message;
                        if (data.details) {
                            message += ' ' + data.details;
                        }
                        showToast('success', message);

                        // Update card status
                        const bookingCard = btn.closest('.booking-card') || btn.closest('.card');
                        if (bookingCard) {
                            updateBookingCard(bookingCard, 'cancelled', 'bg-secondary', 'Cancelled');
                        }

                        // Reload page after 3 seconds
                        setTimeout(() => window.location.reload(), 3000);
                    } else {
                        showToast('error', data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                } catch (error) {
                    console.error('Cancel booking error:', error);
                    showToast('error', 'An error occurred. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            },
            true // Require reason
        );
    };

    /**
     * Initialize tooltips
     */
    function initializeTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTooltips);
    } else {
        initializeTooltips();
    }

})();
