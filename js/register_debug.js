/**
 * CampusDigs Kenya - Registration JavaScript (WITH DEBUGGING)
 * Client-side form validation and handling
 */

// WAIT FOR DOM TO LOAD
document.addEventListener('DOMContentLoaded', function() {

    console.log('Register.js loaded successfully');

    // Get form and elements
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');

    console.log('Form elements:', {
        form: registerForm ? 'Found' : 'Missing',
        submitBtn: submitBtn ? 'Found' : 'Missing',
        passwordInput: passwordInput ? 'Found' : 'Missing',
        emailInput: emailInput ? 'Found' : 'Missing'
    });

    // TOGGLE PASSWORD VISIBILITY
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
            console.log('Password visibility toggled:', type);
        });
    }

    // FORM SUBMISSION
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            console.log('========== FORM SUBMISSION STARTED ==========');
            e.preventDefault();

            // Validate all fields
            let isValid = true;
            let errors = [];

            // Validate required fields
            const requiredFields = registerForm.querySelectorAll('[required]');
            console.log('Checking', requiredFields.length, 'required fields...');

            requiredFields.forEach(function(field) {
                const fieldName = field.name || field.id;
                if (!field.value.trim() && field.type !== 'checkbox') {
                    showError(field, 'This field is required');
                    errors.push(fieldName + ': Empty');
                    isValid = false;
                } else {
                    clearError(field);
                    console.log(fieldName, ':', field.value.substring(0, 20) + '...');
                }
            });

            // Specific validations
            if (emailInput) {
                console.log('Validating email:', emailInput.value);
                if (!validateEmail(emailInput)) {
                    errors.push('Email validation failed');
                    isValid = false;
                }
            }

            if (phoneInput) {
                console.log('Validating phone:', phoneInput.value);
                if (!validatePhone(phoneInput)) {
                    errors.push('Phone validation failed');
                    isValid = false;
                }
            }

            if (passwordInput) {
                console.log('Validating password...');
                if (!validatePassword(passwordInput)) {
                    errors.push('Password validation failed');
                    isValid = false;
                }
            }

            if (confirmPasswordInput) {
                console.log('Validating confirm password...');
                if (!validateConfirmPassword(confirmPasswordInput)) {
                    errors.push('Passwords do not match');
                    isValid = false;
                }
            }

            // Check terms checkbox
            const termsCheckbox = document.getElementById('terms');
            if (termsCheckbox && !termsCheckbox.checked) {
                console.error('Terms not accepted');
                alert('You must accept the Terms of Service and Privacy Policy');
                errors.push('Terms not accepted');
                isValid = false;
            } else {
                console.log('Terms accepted');
            }

            console.log('Validation result:', isValid ? 'VALID' : 'INVALID');
            if (errors.length > 0) {
                console.error('Validation errors:', errors);
            }

            // If all validations pass, submit form
            if (isValid) {
                console.log('SUBMITTING FORM...');
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating account...';

                // Submit form
                console.log('Form action:', registerForm.action);
                registerForm.submit();
            } else {
                console.error('FORM NOT SUBMITTED - Validation failed');
            }

            console.log('========== FORM SUBMISSION ENDED ==========');
        });
    } else {
        console.error('ERROR: Register form not found!');
    }

    // VALIDATION FUNCTIONS

    function validateEmail(input) {
        const email = input.value.trim();
        const userType = document.querySelector('input[name="user_type"]').value;

        console.log('validateEmail called:', email, 'User type:', userType);

        // Basic email format validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            console.error('Email basic format failed');
            showError(input, 'Please enter a valid email address');
            return false;
        }
        console.log('Email basic format valid');

        // For students, check university domain
        if (userType === 'student') {
            const universityDomainRegex = /@[a-zA-Z0-9-]+\.(ac\.ke|edu)$/i;
            if (!universityDomainRegex.test(email)) {
                console.error('University domain check failed');
                showError(input, 'Please use a valid university email (.ac.ke or .edu)');
                return false;
            }
            console.log('University domain valid');
        }

        clearError(input);
        return true;
    }

    function validatePhone(input) {
        const phone = input.value.trim().replace(/\s/g, '');
        console.log('validatePhone called:', phone);

        const phoneRegex = /^(\+?254|0)[17]\d{8}$/;

        if (!phoneRegex.test(phone)) {
            console.error('Phone format invalid');
            showError(input, 'Invalid phone format. Use +254XXXXXXXXX or 07XXXXXXXX');
            return false;
        }

        console.log('Phone format valid');
        clearError(input);
        return true;
    }

    function validatePassword(input) {
        const password = input.value;
        console.log('validatePassword called, length:', password.length);

        if (password.length < 8) {
            console.error('Password too short');
            showError(input, 'Password must be at least 8 characters long');
            return false;
        }

        if (!/[A-Z]/.test(password)) {
            console.error('Password missing uppercase');
            showError(input, 'Password must contain at least one uppercase letter');
            return false;
        }

        if (!/[a-z]/.test(password)) {
            console.error('Password missing lowercase');
            showError(input, 'Password must contain at least one lowercase letter');
            return false;
        }

        if (!/[0-9]/.test(password)) {
            console.error('Password missing number');
            showError(input, 'Password must contain at least one number');
            return false;
        }

        console.log('Password valid');
        clearError(input);
        return true;
    }

    function validateConfirmPassword(input) {
        const password = passwordInput.value;
        const confirmPassword = input.value;

        if (password !== confirmPassword) {
            console.error('Passwords do not match');
            showError(input, 'Passwords do not match');
            return false;
        }

        console.log('Passwords match');
        clearError(input);
        return true;
    }

    function showError(input, message) {
        input.classList.add('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
    }

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
        }, 5000);
    });

    console.log('Register.js initialization complete');
});
