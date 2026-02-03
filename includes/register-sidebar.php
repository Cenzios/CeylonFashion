<div class="offcanvas offcanvas-end" tabindex="-1" id="registerSidebar" aria-labelledby="registerSidebarLabel">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold" id="registerSidebarLabel">Create Account</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <!-- Error/Success Messages -->
    <div id="registerMessage" style="display:none; padding:10px; border-radius:6px; margin-bottom:15px;"></div>
    
    <form method="POST" action="insert.php" id="offcanvasRegisterForm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="redirect_url" id="registerRedirectUrl" value="">
      
      <div class="mb-3">
        <label for="fname" class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-lg" id="fname" name="fname" placeholder="First Name" required>
        <div class="invalid-feedback-custom"></div>
      </div>
      <div class="mb-3">
        <label for="lname" class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-lg" id="lname" name="lname" placeholder="Last Name" required>
        <div class="invalid-feedback-custom"></div>
      </div>
      <div class="mb-3">
        <label for="emailReg" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control form-control-lg" id="emailReg" name="email" placeholder="Email" required>
        <div class="invalid-feedback-custom"></div>
      </div>
      <div class="mb-3">
        <label for="pwd" class="form-label">Password <span class="text-danger">*</span></label>
        <div class="password-wrapper">
          <input type="password" class="form-control form-control-lg" id="pwd" name="pwd" placeholder="Password" required minlength="6">
          <button type="button" class="btn-toggle-pass" onclick="togglePassword('pwd', this)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
              <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
              <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
            </svg>
          </button>
        </div>
        <div class="invalid-feedback-custom"></div>
        <small class="text-muted">At least 6 characters</small>
      </div>
      <div class="mb-3">
        <label for="pwdConfirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
        <div class="password-wrapper">
          <input type="password" class="form-control form-control-lg" id="pwdConfirm" name="pwd_confirm" placeholder="Confirm Password" required minlength="6">
          <button type="button" class="btn-toggle-pass" onclick="togglePassword('pwdConfirm', this)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
              <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
              <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
            </svg>
          </button>
        </div>
        <div class="invalid-feedback-custom"></div>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-dark-blue w-100 py-3 fw-bold" id="registerSubmitBtn">
          <span id="registerBtnText">REGISTER</span>
          <span id="registerSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
        </button>
      </div>
      <div class="mt-3 text-center">
        <small>Already have an account? <a href="#" class="text-decoration-underline" data-bs-toggle="offcanvas" data-bs-target="#loginSidebar" aria-controls="loginSidebar">Log in</a></small>
      </div>
    </form>
  </div>
</div>

<style>
  .register-message-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
  }
  .register-message-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
  }

  /* Enhanced form styling to match design */
  #registerSidebar .form-control-lg {
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 1px solid #ced4da;
    border-radius: 0;
    background-color: #f8f9fa;
  }

  #registerSidebar .form-control:focus {
    border-color: #0a1e42;
    box-shadow: none;
    background-color: white;
  }

  #registerSidebar .btn-dark-blue {
    background-color: #0a1e42;
    border: none;
    color: white;
    font-size: 1rem;
    letter-spacing: 1px;
    border-radius: 0;
  }

  #registerSidebar .btn-dark-blue:hover {
    background-color: #071633;
    color: white;
  }

  /* Visible placeholders */
  #registerSidebar ::placeholder {
    color: #6c757d !important;
    opacity: 0.7 !important;
  }

  #registerSidebar .offcanvas-header {
    padding: 1.5rem;
  }

  #registerSidebar .offcanvas-body {
    padding: 2rem 1.5rem;
  }

  #registerSidebar .form-label {
    font-weight: 400;
    margin-bottom: 0.5rem;
    color: #212529;
    font-size: 0.95rem;
  }

  #registerSidebar .text-muted {
    font-size: 0.875rem;
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
  // Copy redirect URL from login form when opening register sidebar
  document.getElementById('registerSidebar').addEventListener('show.bs.offcanvas', function () {
    const loginRedirect = document.getElementById('loginRedirectUrl');
    const registerRedirect = document.getElementById('registerRedirectUrl');
    if (loginRedirect && registerRedirect) {
      registerRedirect.value = loginRedirect.value;
    }
  });

  // Client-side Registration Validation
  document.addEventListener('DOMContentLoaded', function() {
      const registerForm = document.getElementById('offcanvasRegisterForm');
      const fnameInput = document.getElementById('fname');
      const lnameInput = document.getElementById('lname');
      const emailInput = document.getElementById('emailReg');
      const pwdInput = document.getElementById('pwd');
      const pwdConfirmInput = document.getElementById('pwdConfirm');

      const validateField = (input, validator) => {
          const value = input.value.trim();
          const errorMessage = validator(value);
          // Find error div: try next sibling, or if in wrapper, sibling of input (this logic needs to be robust)
          // For password wrappers, input is inside wrapper. Error div is appended to wrapper (child of wrapper, sibling of input).
          // For normal inputs, error div is next sibling.
          let errorDiv;
          if (input.parentElement.classList.contains('password-wrapper')) {
             // Wrapper is parent. Error div is next sibling of Wrapper.
             errorDiv = input.parentElement.nextElementSibling;
          } else {
             errorDiv = input.nextElementSibling;
          }
          
          if (errorMessage) {
              input.classList.add('is-invalid-custom');
              if (errorDiv) {
                  errorDiv.textContent = errorMessage;
                  errorDiv.style.display = 'block';
              }
              return false;
          } else {
              input.classList.remove('is-invalid-custom');
              if (errorDiv) {
                  errorDiv.style.display = 'none';
              }
              return true;
          }
      };

      const validators = {
          fname: (value) => {
              if (!value) return 'First Name is required';
              if (!/^[A-Za-z]+$/.test(value)) return 'First Name must contain only letters';
              if (value.length < 2) return 'First Name must be at least 2 characters long';
              if (value.length > 50) return 'First Name cannot exceed 50 characters';
              return '';
          },
          lname: (value) => {
              if (!value) return 'Last Name is required';
              if (!/^[A-Za-z]+$/.test(value)) return 'Last Name must contain only letters';
              if (value.length < 2) return 'Last Name must be at least 2 characters long';
              if (value.length > 50) return 'Last Name cannot exceed 50 characters';
              return '';
          },
          email: (value) => {
              if (!value) return 'Email is required';
              if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Please enter a valid email address';
              return '';
          },
          password: (value) => {
              // Min 8, Upper, Lower, Number, Special
              if (!value) return 'Password is required'; // implicit from table "When input is below 8..." but empty usually means "Password is required" or "Min 8" logic dominates. Table says "Minimum length: 8 characters... When input is below 8...". I'll use the long message for length < 8.
              // Logic check:
              const hasUpper = /[A-Z]/.test(value);
              const hasLower = /[a-z]/.test(value);
              const hasNum = /[0-9]/.test(value);
              const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(value);
              
              if (value.length < 8 || !hasUpper || !hasLower || !hasNum || !hasSpecial) {
                  return 'Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character';
              }
              return '';
          },
          confirmPassword: (value) => {
              if (!value) return 'Confirm Password is required';
              if (value !== pwdInput.value) return 'Passwords do not match';
              return '';
          }
      };

      const inputs = [
          { input: fnameInput, validator: validators.fname },
          { input: lnameInput, validator: validators.lname },
          { input: emailInput, validator: validators.email },
          { input: pwdInput, validator: validators.password },
          { input: pwdConfirmInput, validator: validators.confirmPassword }
      ];

      inputs.forEach(({ input, validator }) => {
          if (input) {
              input.addEventListener('input', () => validateField(input, validator));
              input.addEventListener('blur', () => validateField(input, validator));
          }
      });

      // Also re-validate confirm password when main password changes
      if (pwdInput) {
          pwdInput.addEventListener('input', () => {
              if (pwdConfirmInput.value) validateField(pwdConfirmInput, validators.confirmPassword);
          });
      }

      if (registerForm) {
          registerForm.addEventListener('submit', function(e) {
              let isValid = true;
              inputs.forEach(({ input, validator }) => {
                  if (input && !validateField(input, validator)) {
                      isValid = false;
                  }
              });

              if (!isValid) {
                  e.preventDefault();
              } else {
                  // Show loading state only if available and valid
                  const submitBtn = document.getElementById('registerSubmitBtn');
                  const btnText = document.getElementById('registerBtnText');
                  const spinner = document.getElementById('registerSpinner');

                  if (submitBtn && btnText && spinner) {
                      submitBtn.disabled = true;
                      btnText.style.display = 'none';
                      spinner.style.display = 'inline-block';
                  }
              }
          });
      }
  });
</script>