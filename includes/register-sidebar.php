<div class="offcanvas offcanvas-end" tabindex="-1" id="registerSidebar" aria-labelledby="registerSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="registerSidebarLabel">Create Account</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <!-- Error/Success Messages -->
    <div id="registerMessage" style="display:none; padding:10px; border-radius:6px; margin-bottom:15px;"></div>
    
    <form method="POST" action="insert.php" id="offcanvasRegisterForm">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      
      <div class="mb-3">
        <label for="fname" class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="fname" name="fname" placeholder="John" required>
      </div>
      <div class="mb-3">
        <label for="lname" class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="lname" name="lname" placeholder="Doe" required>
      </div>
      <div class="mb-3">
        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="address" name="address" placeholder="123 Main Street" required>
      </div>
      <div class="mb-3">
        <label for="city" class="form-label">City <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="city" name="city" placeholder="Colombo" required>
      </div>
      <div class="mb-3">
        <label for="pin" class="form-label">Pin Code <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="pin" name="pin" placeholder="10100" required pattern="[0-9]{5,6}">
      </div>
      <div class="mb-3">
        <label for="emailReg" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="emailReg" name="email" placeholder="john@example.com" required>
      </div>
      <div class="mb-3">
        <label for="pwd" class="form-label">Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="pwd" name="pwd" required minlength="6">
        <small class="text-muted">At least 6 characters</small>
      </div>
      <div class="mb-3">
        <label for="pwdConfirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="pwdConfirm" name="pwd_confirm" required minlength="6">
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary w-100" id="registerSubmitBtn">
          <span id="registerBtnText">REGISTER</span>
          <span id="registerSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
        </button>
      </div>
      <div class="mt-3 text-center">
        <small>Already have an account? <a href="#" data-bs-toggle="offcanvas" data-bs-target="#loginSidebar" aria-controls="loginSidebar">Log in</a></small>
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
</style>