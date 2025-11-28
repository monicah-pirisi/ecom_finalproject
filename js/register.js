/**
 * CampusDigs Kenya - Registration JavaScript
 * Client-side form validation and handling
 */


// WAIT FOR DOM TO LOAD
document.addEventListener('DOMContentLoaded', function() {
    
    // Get form and elements
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    
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
    
    // Email validation
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            validateEmail(this);
        });
    }
    
    // Phone validation
    if (phoneInput) {
        phoneInput.addEventListener('blur', function() {
            validatePhone(this);
        });
    }
    
    // Password validation
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            validatePassword(this);
        });
    }
    
    // Confirm password validation
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            validateConfirmPassword(this);
        });
    }
    
    // FORM SUBMISSION
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate all fields
            let isValid = true;
            
            // Validate required fields
            const requiredFields = registerForm.querySelectorAll('[required]');
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    showError(field, 'This field is required');
                    isValid = false;
                } else {
                    clearError(field);
                }
            });
            
            // Specific validations
            if (emailInput && !validateEmail(emailInput)) {
                isValid = false;
            }
            
            if (phoneInput && !validatePhone(phoneInput)) {
                isValid = false;
            }
            
            if (passwordInput && !validatePassword(passwordInput)) {
                isValid = false;
            }
            
            if (confirmPasswordInput && !validateConfirmPassword(confirmPasswordInput)) {
                isValid = false;
            }
            
            // Check terms checkbox
            const termsCheckbox = document.getElementById('terms');
            if (termsCheckbox && !termsCheckbox.checked) {
                alert('You must accept the Terms of Service and Privacy Policy');
                isValid = false;
            }
            
            // If all validations pass, submit form
            if (isValid) {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating account...';
                
                // Submit form
                registerForm.submit();
            }
        });
    }
    
    // VALIDATION FUNCTIONS
    
    /**
     * Validate email format and university domain for students
     */
    function validateEmail(input) {
        const email = input.value.trim();
        const userType = document.querySelector('input[name="user_type"]').value;
        
        // Basic email format validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError(input, 'Please enter a valid email address');
            return false;
        }
        
        // For students, check university domain
        if (userType === 'student') {
            const universityDomainRegex = /@[a-zA-Z0-9-]+\.(ac\.ke|edu)$/i;
            if (!universityDomainRegex.test(email)) {
                showError(input, 'Please use a valid university email (.ac.ke or .edu)');
                return false;
            }
        }
        
        clearError(input);
        return true;
    }
    
    /**
     * Validate Kenyan phone number format
     */
    function validatePhone(input) {
        const phone = input.value.trim().replace(/\s/g, '');
        
        // Kenyan phone format: +254XXXXXXXXX, 254XXXXXXXXX, 07XXXXXXXX, 01XXXXXXXX
        const phoneRegex = /^(\+?254|0)[17]\d{8}$/;
        
        if (!phoneRegex.test(phone)) {
            showError(input, 'Invalid phone format. Use +254XXXXXXXXX or 07XXXXXXXX');
            return false;
        }
        
        clearError(input);
        return true;
    }
    
    /**
     * Validate password strength
     */
    function validatePassword(input) {
        const password = input.value;
        
        if (password.length < 8) {
            showError(input, 'Password must be at least 8 characters long');
            return false;
        }
        
        if (!/[A-Z]/.test(password)) {
            showError(input, 'Password must contain at least one uppercase letter');
            return false;
        }
        
        if (!/[a-z]/.test(password)) {
            showError(input, 'Password must contain at least one lowercase letter');
            return false;
        }
        
        if (!/[0-9]/.test(password)) {
            showError(input, 'Password must contain at least one number');
            return false;
        }
        
        clearError(input);
        return true;
    }
    
    /**
     * Validate password confirmation
     */
    function validateConfirmPassword(input) {
        const password = passwordInput.value;
        const confirmPassword = input.value;
        
        if (password !== confirmPassword) {
            showError(input, 'Passwords do not match');
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
    
});