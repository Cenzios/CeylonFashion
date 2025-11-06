<nav class="navbar navbar-expand-lg navbar-purple" style="position: relative; z-index: 1030;">
  <div class="container-fluid">
    <a href="index.php" class="navbar-brand">
      <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav">
        <a class="nav-link" href="index.php#newArrivalsSection">New Arrivals</a>
        <a class="nav-link" href="index.php#bridalAttireSection">Bridal Attire</a>
        <a class="nav-link" href="index.php#brideMaidsSection">Bridemaids Attire</a>
        <a class="nav-link" href="index.php#partyWearSection">Party Wear</a>
        <a class="nav-link" href="index.php#usedCollectionSection">Used Collection</a>
        
        <!-- Start Reselling Button - Show only when logged in -->
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
          <div class="text-center">
            <a href="start-reselling.php" class="btn btn-primary">Start Reselling</a>
          </div>
        <?php endif; ?>
      </div>
      <div class="icons" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); display: flex; gap: 15px; align-items: center; font-size: 32px; color: white; z-index: 1040;">
        
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
          <!-- Profile Dropdown when logged in -->
          <div class="dropdown" style="position: relative;">
            <i class="bi bi-person-circle" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;"></i>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown" style="position: absolute; z-index: 1050;">
              <li><a class="dropdown-item" href="account.php"><i class="bi bi-person"></i> Profile</a></li>
              <li><a class="dropdown-item" href="orders.php"><i class="bi bi-box-seam"></i> My Orders</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
          </div>
          
          <!-- Wishlist and Cart links (logged in users) -->
          <a href="wishlist.php" style="text-decoration: none; color: white;">
            <i class="bi bi-heart" id="wishlistIcon" style="cursor: pointer;"></i>
          </a>
          <a href="cart.php" style="text-decoration: none; color: white;">
            <i class="bi bi-cart2" id="cartIcon" style="cursor: pointer;"></i>
          </a>
          
        <?php else: ?>
          <!-- Show Login button when not logged in -->
          <button 
            class="btn btn-light btn-sm" 
            style="font-size: 14px; padding: 6px 16px;" 
            type="button" 
            data-bs-toggle="offcanvas" 
            data-bs-target="#loginSidebar" 
            aria-controls="loginSidebar">
            Login
          </button>
          
          <!-- Wishlist and Cart icons (not logged in - trigger login) -->
          <a href="#" onclick="requireLogin(event, 'wishlist.php')" style="text-decoration: none; color: white;">
            <i class="bi bi-heart" id="wishlistIcon" style="cursor: pointer;"></i>
          </a>
          <a href="#" onclick="requireLogin(event, 'cart.php')" style="text-decoration: none; color: white;">
            <i class="bi bi-cart2" id="cartIcon" style="cursor: pointer;"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- Login Sidebar -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="loginSidebar" aria-labelledby="loginSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="loginSidebarLabel">Log In</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <!-- Info message for required login -->
    <div id="loginRequiredMessage" style="display:none; padding:10px; border-radius:6px; margin-bottom:15px; background-color: #fff3cd; border: 1px solid #ffc107; color: #856404;">
      Please log in to access this feature.
    </div>
    
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
/* Fix z-index for navbar and dropdown */
.navbar-purple {
  position: relative;
  z-index: 1030 !important;
}

/* Navbar brand/logo link */
.navbar-brand {
  padding: 0;
  margin-right: 1rem;
}

.navbar-brand:hover {
  opacity: 0.8;
}

/* Ensure offcanvas appears above everything */
.offcanvas {
  z-index: 1060 !important;
}

.offcanvas-backdrop {
  z-index: 1055 !important;
}

/* Custom styling for the profile dropdown */
.dropdown-menu {
  min-width: 180px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  z-index: 1050 !important;
  position: absolute !important;
}

.dropdown-item {
  padding: 10px 20px;
  font-size: 15px;
}

.dropdown-item i {
  margin-right: 8px;
  width: 16px;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}

/* Ensure the profile icon has hover effect */
#profileDropdown:hover {
  opacity: 0.8;
}

/* Make login button match the theme */
.btn-light {
  background-color: white;
  border: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-light:hover {
  background-color: #f8f9fa;
  transform: scale(1.05);
}

/* Icon hover effects */
.icons a:hover i,
.icons i:hover {
  opacity: 0.8;
  transform: scale(1.1);
  transition: all 0.2s ease;
}

/* Fix carousel z-index if needed */
.carousel,
#heroCarousel,
.hero-section {
  position: relative;
  z-index: 1 !important;
}

/* Login message styles */
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

/* Responsive navbar icons */
@media (max-width: 991px) {
  .icons {
    position: relative !important;
    right: auto !important;
    top: auto !important;
    transform: none !important;
    margin-top: 15px;
    justify-content: center;
  }
  
  .navbar-nav {
    text-align: center;
  }
  
  .navbar-nav .text-center {
    margin-top: 10px;
  }
}
</style>

<script>
// Store the intended destination after login
let intendedDestination = null;

// Function to require login before accessing a page
function requireLogin(event, destination) {
  event.preventDefault();
  
  // Store the destination
  intendedDestination = destination;
  
  // Show the login required message
  const loginRequiredMsg = document.getElementById('loginRequiredMessage');
  if (loginRequiredMsg) {
    loginRequiredMsg.style.display = 'block';
  }
  
  // Open the login sidebar
  const loginSidebar = new bootstrap.Offcanvas(document.getElementById('loginSidebar'));
  loginSidebar.show();
}

// Smooth scroll to sections when coming from another page
document.addEventListener('DOMContentLoaded', function() {
  // Check if there's a hash in the URL
  if (window.location.hash) {
    setTimeout(function() {
      const target = document.querySelector(window.location.hash);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }, 100);
  }
  
  // Handle login form submission
  const loginForm = document.getElementById('offcanvasLoginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      const messageDiv = document.getElementById('loginMessage');
      const loginRequiredMsg = document.getElementById('loginRequiredMessage');
      const submitBtn = document.getElementById('loginSubmitBtn');
      const btnText = document.getElementById('loginBtnText');
      const spinner = document.getElementById('loginSpinner');
      
      // Hide the login required message
      if (loginRequiredMsg) {
        loginRequiredMsg.style.display = 'none';
      }
      
      // Show loading state
      submitBtn.disabled = true;
      btnText.style.display = 'none';
      spinner.style.display = 'inline-block';
      
      // Send login request
      fetch('login-process.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
      })
      .then(response => response.json())
      .then(data => {
        // Reset button state
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        spinner.style.display = 'none';
        
        if (data.success) {
          messageDiv.className = 'login-message-success';
          messageDiv.textContent = 'Login successful! Redirecting...';
          messageDiv.style.display = 'block';
          
          // Redirect after short delay
          setTimeout(() => {
            // If there's an intended destination, go there
            if (intendedDestination) {
              window.location.href = intendedDestination;
            } else {
              window.location.href = data.redirect || 'index.php';
            }
          }, 1000);
        } else {
          messageDiv.className = 'login-message-error';
          messageDiv.textContent = data.message || 'Login failed. Please try again.';
          messageDiv.style.display = 'block';
        }
      })
      .catch(error => {
        // Reset button state
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        spinner.style.display = 'none';
        
        messageDiv.className = 'login-message-error';
        messageDiv.textContent = 'An error occurred. Please try again.';
        messageDiv.style.display = 'block';
      });
    });
  }
  
  // Reset messages when sidebar is closed
  document.getElementById('loginSidebar')?.addEventListener('hidden.bs.offcanvas', function() {
    const loginRequiredMsg = document.getElementById('loginRequiredMessage');
    const loginMessage = document.getElementById('loginMessage');
    
    if (loginRequiredMsg) loginRequiredMsg.style.display = 'none';
    if (loginMessage) loginMessage.style.display = 'none';
    
    // Don't clear intendedDestination here in case user wants to reopen
  });
});

// Alternative: Global function that can be called from anywhere
function showLoginModal() {
  const loginSidebar = new bootstrap.Offcanvas(document.getElementById('loginSidebar'));
  loginSidebar.show();
}
</script>