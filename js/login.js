/**
 * CampusDigs Kenya - Login JavaScript
 * Client-side form validation and handling
 */

// WAIT FOR DOM TO LOAD
document.addEventListener('DOMContentLoaded', function() {

    // Get form and elements
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const loginValueInput = document.getElementById('login_value');

    // TOGGLE PASSWORD VISIBILITY
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // REAL-TIME VALIDATION

    // Login value validation (email or phone)
    if (loginValueInput) {
        loginValueInput.addEventListener('blur', function() {
            validateLoginValue(this);
        });
    }

    // Password validation
    if (passwordInput) {
        passwordInput.addEventListener('blur', function() {
            validatePasswordField(this);
        });
    }

    // FORM SUBMISSION
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate all fields
            let isValid = true;

            // Validate login value
            if (loginValueInput && !validateLoginValue(loginValueInput)) {
                isValid = false;
            }

            // Validate password
            if (passwordInput && !validatePasswordField(passwordInput)) {
                isValid = false;
            }

            // If all validations pass, submit form
            if (isValid) {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';

                // Submit form
                loginForm.submit();
            }
        });
    }

    // VALIDATION FUNCTIONS

    /**
     * Validate login value (email or phone)
     */
    function validateLoginValue(input) {
        const value = input.value.trim();

        if (!value) {
            showError(input, 'Email or phone number is required');
            return false;
        }

        // Check if it's an email or phone
        const isEmail = value.includes('@');

        if (isEmail) {
            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                showError(input, 'Please enter a valid email address');
                return false;
            }
        } else {
            // Validate phone format
            const phone = value.replace(/\s/g, '');
            const phoneRegex = /^(\+?254|0)[17]\d{8}$/;

            if (!phoneRegex.test(phone)) {
                showError(input, 'Invalid phone format. Use +254XXXXXXXXX or 07XXXXXXXX');
                return false;
            }
        }

        clearError(input);
        return true;
    }

    /**
     * Validate password field (not empty)
     */
    function validatePasswordField(input) {
        const password = input.value;

        if (!password || password.length === 0) {
            showError(input, 'Password is required');
            return false;
        }

        clearError(input);
        return true;
    }

    /**
     * Show error message for input field
     */
    function showError(input, message) {
        input.classList.add('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
    }

    /**
     * Clear error message for input field
     */
    function clearError(input) {
        input.classList.remove('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = '';
            feedback.style.display = 'none';
        }
    }

    // AUTO-DISMISS ALERTS
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // Dismiss after 5 seconds
    });

    // ENTER KEY HANDLING
    // Make sure Enter key submits the form
    if (loginForm) {
        loginForm.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                submitBtn.click();
            }
        });
    }

});
