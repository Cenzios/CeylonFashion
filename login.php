<?php
if (session_id() == '' || !isset($_SESSION)) {
  session_start();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if user already logged in
if (isset($_SESSION["username"])) {
  header("location:index.php");
  exit;
}

// Get return URL if provided
$returnUrl = isset($_GET['return']) ? htmlspecialchars($_GET['return']) : 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login || Ceylon Fashion.lk</title>
  <link rel="stylesheet" href="css/foundation.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script src="js/vendor/modernizr.js"></script>
  <style>
    .login-container {
      max-width: 500px;
      margin: 50px auto;
      padding: 30px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .login-header h2 {
      color: #0078A0;
      margin-bottom: 10px;
    }
    .alert-message {
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      display: none;
    }
    .alert-success {
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
    }
    .alert-error {
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
      color: #333;
    }
    .form-group input {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 16px;
    }
    .form-group input:focus {
      outline: none;
      border-color: #0078A0;
      box-shadow: 0 0 0 3px rgba(0,120,160,0.1);
    }
    .btn-login {
      width: 100%;
      padding: 12px;
      background: #0078A0;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    .btn-login:hover {
      background: #006080;
    }
    .btn-login:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    .divider {
      text-align: center;
      margin: 20px 0;
      position: relative;
    }
    .divider:before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      width: 100%;
      height: 1px;
      background: #ddd;
    }
    .divider span {
      background: white;
      padding: 0 10px;
      position: relative;
      z-index: 1;
      color: #666;
    }
    .register-link {
      text-align: center;
      margin-top: 20px;
    }
    .register-link a {
      color: #0078A0;
      text-decoration: none;
      font-weight: 600;
    }
    .forgot-password {
      text-align: right;
      margin-top: 10px;
    }
    .forgot-password a {
      color: #666;
      font-size: 14px;
      text-decoration: none;
    }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="login-container">
  <div class="login-header">
    <h2>Welcome Back!</h2>
    <p>Login to your Ceylon Fashion account</p>
  </div>

  <!-- Alert Message -->
  <div id="alertMessage" class="alert-message"></div>

  <form id="loginForm">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrl); ?>">

    <div class="form-group">
      <label for="email">Email Address <span style="color:red;">*</span></label>
      <input type="email" id="email" name="username" placeholder="you@example.com" required>
    </div>

    <div class="form-group">
      <label for="password">Password <span style="color:red;">*</span></label>
      <input type="password" id="password" name="pwd" placeholder="Enter your password" required>
    </div>

    <div class="forgot-password">
      <a href="forgot-password.php">Forgot your password?</a>
    </div>

    <button type="submit" class="btn-login" id="loginBtn">
      <span id="loginBtnText">LOGIN</span>
      <span id="loginSpinner" style="display:none;">
        <i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Loading...
      </span>
    </button>
  </form>

  <div class="divider">
    <span>OR</span>
  </div>

  <div class="register-link">
    <p>Don't have an account? <a href="register.php">Create Account</a></p>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="js/vendor/jquery.js"></script>
<script src="js/foundation.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).foundation();

  // Show alert message
  function showAlert(message, type) {
    const alertEl = document.getElementById('alertMessage');
    alertEl.textContent = message;
    alertEl.className = 'alert-message alert-' + type;
    alertEl.style.display = 'block';

    // Auto-hide after 5 seconds
    setTimeout(() => {
      alertEl.style.display = 'none';
    }, 5000);
  }

  // Get CSRF token
  function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  // Login form handler
  document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const pwd = document.getElementById('password').value;
    const returnUrl = document.querySelector('input[name="return_url"]').value;

    // Validation
    const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    if (!emailOk) {
      showAlert('Please enter a valid email address.', 'error');
      document.getElementById('email').focus();
      return;
    }

    if (pwd.length < 6) {
      showAlert('Password must be at least 6 characters.', 'error');
      document.getElementById('password').focus();
      return;
    }

    // Show loading state
    const loginBtn = document.getElementById('loginBtn');
    const btnText = document.getElementById('loginBtnText');
    const spinner = document.getElementById('loginSpinner');
    
    loginBtn.disabled = true;
    btnText.style.display = 'none';
    spinner.style.display = 'inline';

    // Prepare request
    const body = new URLSearchParams();
    body.set('username', email);
    body.set('pwd', pwd);
    body.set('csrf_token', getCsrf());

    try {
      const res = await fetch('verify.php', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: body.toString()
      });

      if (!res.ok) {
        throw new Error('Login request failed');
      }

      const data = await res.json();

      if (data.ok) {
        showAlert('Login successful! Redirecting...', 'success');
        setTimeout(() => {
          window.location.href = returnUrl || data.redirect || 'index.php';
        }, 1000);
      } else {
        showAlert(data.message || 'Invalid email or password.', 'error');
        loginBtn.disabled = false;
        btnText.style.display = 'inline';
        spinner.style.display = 'none';
      }
    } catch (err) {
      console.error(err);
      showAlert('Network error. Please try again.', 'error');
      loginBtn.disabled = false;
      btnText.style.display = 'inline';
      spinner.style.display = 'none';
    }
  });

  // Check for session errors on page load
  <?php if (isset($_SESSION['login_error'])): ?>
    showAlert('<?php echo addslashes($_SESSION['login_error']); unset($_SESSION['login_error']); ?>', 'error');
  <?php endif; ?>
</script>

<style>
  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
</style>

</body>
</html>