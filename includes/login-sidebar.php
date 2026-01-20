<div class="offcanvas offcanvas-end" tabindex="-1" id="loginSidebar" aria-labelledby="loginSidebarLabel">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold" id="loginSidebarLabel">Login</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <!-- Error/Success Messages -->
    <div id="loginMessage" style="display:none; padding:10px; border-radius:6px; margin-bottom:15px;"></div>

    <form id="offcanvasLoginForm">
      <input type="hidden" name="redirect_url" id="loginRedirectUrl" value="">
      <div class="mb-3">
        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
        <input 
          type="email" 
          class="form-control form-control-lg" 
          id="email" 
          placeholder="Email Address"
          required
        >
        <div class="invalid-feedback-custom"></div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
        <div class="password-wrapper">
          <input 
            type="password" 
            class="form-control form-control-lg" 
            id="password" 
            placeholder="Password"
            required
          >
          <button type="button" class="btn-toggle-pass" onclick="togglePassword('password', this)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
              <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
              <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
            </svg>
          </button>
          <div class="invalid-feedback-custom"></div>
        </div>
      </div>

      <button type="submit" class="btn btn-dark-blue w-100 py-3 fw-bold mb-3" id="loginSubmitBtn">
        <span id="loginBtnText">LOG IN</span>
        <span id="loginSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
      </button>

      <div class="text-center mb-3">
        <a href="forgot-password.php" class="text-decoration-underline text-dark">Forgot your password?</a>
      </div>

      <button 
        type="button"
        class="btn btn-outline-dark-blue w-100 py-3 fw-bold"
        data-bs-toggle="offcanvas"
        data-bs-target="#registerSidebar"
        aria-controls="registerSidebar"
      >
        CREATE ACCOUNT
      </button>
    </form>
  </div>
</div>

<style>
  .login-message-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
  }
  .login-message-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
  }

  /* Dark blue button - matching the image exactly */
  #loginSidebar .btn-dark-blue {
    background-color: #0a1e42;
    border: none;
    color: white;
    font-size: 1rem;
    letter-spacing: 1px;
    border-radius: 0;
  }

  #loginSidebar .btn-dark-blue:hover {
    background-color: #071633;
    color: white;
  }

  #loginSidebar .btn-dark-blue:focus {
    background-color: #0a1e42;
    box-shadow: none;
  }

  /* Outline button */
  #loginSidebar .btn-outline-dark-blue {
    background-color: transparent;
    border: 2px solid #0a1e42;
    color: #0a1e42;
    font-size: 1rem;
    letter-spacing: 1px;
    border-radius: 0;
  }

  #loginSidebar .btn-outline-dark-blue:hover {
    background-color: #0a1e42;
    border-color: #0a1e42;
    color: white;
  }

  /* Form controls */
  #loginSidebar .form-control-lg {
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 1px solid #ced4da;
    border-radius: 0;
    background-color: #f8f9fa;
  }

  #loginSidebar .form-control:focus {
    border-color: #0a1e42;
    box-shadow: none;
    background-color: white;
  }

  /* Visible placeholders */
  #loginSidebar ::placeholder {
    color: #6c757d !important;
    opacity: 0.7 !important;
  }

  #loginSidebar .offcanvas-header {
    padding: 1.5rem;
  }

  #loginSidebar .offcanvas-body {
    padding: 2rem 1.5rem;
  }

  #loginSidebar .form-label {
    font-weight: 400;
    margin-bottom: 0.5rem;
    color: #212529;
    font-size: 0.95rem;
  }

  .password-wrapper {
    position: relative;
  }
  .btn-toggle-pass {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #6c757d;
    padding: 0;
    display: flex;
    align-items: center;
    z-index: 5;
  }
  .btn-toggle-pass:hover {
    color: #343a40;
  }
  
  /* Validation Styles - Local Override */
  .invalid-feedback-custom {
    color: red !important; /* Explicit red color */
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    display: none;
  }
  
  .form-control.is-invalid-custom {
    border-color: #dc3545 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    padding-right: calc(1.5em + 0.75rem);
  }
  
  .form-control.is-invalid-custom ~ .invalid-feedback-custom {
    display: block;
  }
  
  .password-wrapper .form-control.is-invalid-custom {
    background-position: right 2.5rem center;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('offcanvasLoginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    const validateField = (input, validator) => {
        const value = input.value.trim();
        const errorMessage = validator(value);
        const errorDiv = input.parentElement.querySelector('.invalid-feedback-custom') || input.nextElementSibling;
        
        if (errorMessage) {
            input.classList.add('is-invalid-custom');
            if (errorDiv) errorDiv.textContent = errorMessage;
            return false;
        } else {
            input.classList.remove('is-invalid-custom');
            return true;
        }
    };

    const validators = {
        email: (value) => {
            if (!value) return 'Email is required.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Please enter a valid email address.';
            return '';
        },
        password: (value) => {
            if (!value) return 'Password is required.';
            return '';
        }
    };

    if (emailInput) {
        emailInput.addEventListener('input', () => validateField(emailInput, validators.email));
        emailInput.addEventListener('blur', () => validateField(emailInput, validators.email));
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', () => validateField(passwordInput, validators.password));
        passwordInput.addEventListener('blur', () => validateField(passwordInput, validators.password));
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            if (emailInput && !validateField(emailInput, validators.email)) isValid = false;
            if (passwordInput && !validateField(passwordInput, validators.password)) isValid = false;

            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});
</script>