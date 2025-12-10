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
      
      <div class="mb-3">
        <label for="fname" class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-lg" id="fname" name="fname" placeholder="First Name" required>
      </div>
      <div class="mb-3">
        <label for="lname" class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-lg" id="lname" name="lname" placeholder="Last Name" required>
      </div>
      <div class="mb-3">
        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-lg" id="address" name="address" placeholder="Address" required>
      </div>
      <div class="mb-3">
        <label for="city" class="form-label">City <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-lg" id="city" name="city" placeholder="City" required>
      </div>
      <div class="mb-3">
        <label for="pin" class="form-label">Pin Code <span class="text-danger">*</span></label>
        <input type="text" class="form-control form-control-lg" id="pin" name="pin" placeholder="Pin Code" required pattern="[0-9]{5,6}">
      </div>
      <div class="mb-3">
        <label for="emailReg" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control form-control-lg" id="emailReg" name="email" placeholder="Email" required>
      </div>
      <div class="mb-3">
        <label for="pwd" class="form-label">Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control form-control-lg" id="pwd" name="pwd" placeholder="Password" required minlength="6">
        <small class="text-muted">At least 6 characters</small>
      </div>
      <div class="mb-3">
        <label for="pwdConfirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control form-control-lg" id="pwdConfirm" name="pwd_confirm" placeholder="Confirm Password" required minlength="6">
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
</style>