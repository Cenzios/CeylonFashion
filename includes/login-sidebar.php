<div class="offcanvas offcanvas-end" tabindex="-1" id="loginSidebar" aria-labelledby="loginSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="loginSidebarLabel">Log In</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <!-- Error/Success Messages -->
    <div id="loginMessage" style="display:none; padding:10px; border-radius:6px; margin-bottom:15px;"></div>
    
    <form id="offcanvasLoginForm">
      <div class="mb-3">
        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100" id="loginSubmitBtn">
        <span id="loginBtnText">LOG IN</span>
        <span id="loginSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
      </button>
      <div class="mt-3 text-center"><a href="forgot-password.php">Forgot your password?</a></div>
      <hr>
      <button type="button"
        class="btn btn-outline-secondary w-100"
        data-bs-toggle="offcanvas"
        data-bs-target="#registerSidebar"
        aria-controls="registerSidebar">
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
</style>