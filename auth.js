document.addEventListener('DOMContentLoaded', function() {
  
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    if (togglePasswordButtons.length > 0) {
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling;
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
             
                const icon = this.querySelector('i');
                if (type === 'text') {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }
    

    const passwordInput = document.getElementById('password');
    const strengthMeter = document.querySelector('.strength-meter-fill');
    const strengthText = document.querySelector('.strength-text span');
    
    if (passwordInput && strengthMeter && strengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
    
            if (password.length >= 8) {
                strength += 1;
            }
            
 
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
                strength += 1;
            }
       
            if (password.match(/[0-9]/)) {
                strength += 1;
            }
            
       
            if (password.match(/[^a-zA-Z0-9]/)) {
                strength += 1;
            }
            
        
            strengthMeter.setAttribute('data-strength', strength);
            
     
            switch (strength) {
                case 0:
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#ef4444';
                    break;
                case 1:
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#ef4444';
                    break;
                case 2:
                    strengthText.textContent = 'Medium';
                    strengthText.style.color = '#f59e0b';
                    break;
                case 3:
                    strengthText.textContent = 'Strong';
                    strengthText.style.color = '#10b981';
                    break;
                case 4:
                    strengthText.textContent = 'Very Strong';
                    strengthText.style.color = '#10b981';
                    break;
            }
        });
    }
    

    const fileInput = document.getElementById('profile_picture');
    const fileInputText = document.querySelector('.file-input-text');
    
    if (fileInput && fileInputText) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileInputText.textContent = this.files[0].name;
            } else {
                fileInputText.textContent = 'No file chosen';
            }
        });
    }
    
 
    const registerForm = document.getElementById('register-form');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            let isValid = true;
            
         
            const firstName = document.getElementById('first_name');
            if (firstName.value.trim() === '') {
                showError(firstName, 'First name is required');
                isValid = false;
            } else {
                clearError(firstName);
            }
            
         
            const lastName = document.getElementById('last_name');
            if (lastName.value.trim() === '') {
                showError(lastName, 'Last name is required');
                isValid = false;
            } else {
                clearError(lastName);
            }
            
    
            const email = document.getElementById('email');
            if (email.value.trim() === '') {
                showError(email, 'Email address is required');
                isValid = false;
            } else if (!isValidEmail(email.value)) {
                showError(email, 'Please enter a valid email address');
                isValid = false;
            } else {
                clearError(email);
            }
            
       
            const phone = document.getElementById('phone');
            if (phone.value.trim() === '') {
                showError(phone, 'Phone number is required');
                isValid = false;
            } else {
                clearError(phone);
            }
            
         
            const location = document.getElementById('location');
            if (location.value === '') {
                showError(location, 'Location is required');
                isValid = false;
            } else {
                clearError(location);
            }
            
       
            const password = document.getElementById('password');
            if (password.value === '') {
                showError(password, 'Password is required');
                isValid = false;
            } else if (password.value.length < 8) {
                showError(password, 'Password must be at least 8 characters long');
                isValid = false;
            } else {
                clearError(password);
            }
            
           
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value === '') {
                showError(confirmPassword, 'Please confirm your password');
                isValid = false;
            } else if (confirmPassword.value !== password.value) {
                showError(confirmPassword, 'Passwords do not match');
                isValid = false;
            } else {
                clearError(confirmPassword);
            }
            
          
            const agreeTerms = document.getElementById('agree_terms');
            if (!agreeTerms.checked) {
                showError(agreeTerms, 'You must agree to the Terms and Conditions');
                isValid = false;
            } else {
                clearError(agreeTerms);
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
  
    const loginForm = document.getElementById('login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            
        
            const email = document.getElementById('email');
            if (email.value.trim() === '') {
                showError(email, 'Email address is required');
                isValid = false;
            } else if (!isValidEmail(email.value)) {
                showError(email, 'Please enter a valid email address');
                isValid = false;
            } else {
                clearError(email);
            }
            
        
            const password = document.getElementById('password');
            if (password.value === '') {
                showError(password, 'Password is required');
                isValid = false;
            } else {
                clearError(password);
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
 
    function showError(input, message) {
    
        clearError(input);
        

        const error = document.createElement('div');
        error.className = 'error';
        error.innerText = message;
        
      
        const formGroup = input.closest('.form-group');
        
     
        const passwordInput = formGroup.querySelector('.password-input');
        if (passwordInput) {
            formGroup.insertBefore(error, passwordInput.nextSibling);
        } else if (input.type === 'checkbox') {
            const checkboxGroup = input.closest('.checkbox-group');
            checkboxGroup.appendChild(error);
        } else {
            formGroup.appendChild(error);
        }
    }
    
    function clearError(input) {

        const formGroup = input.closest('.form-group');

        const error = formGroup.querySelector('.error');
        if (error) {
            error.remove();
        }
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});