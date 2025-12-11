<!-- Scripts -->
<script src="js/vendor/jquery.js"></script>
<script src="js/foundation.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>

<script>
  $(document).foundation();

  // ============================================
  // UTILITY FUNCTIONS
  // ============================================
  function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('svg');
    if (input.type === "password") {
      input.type = "text";
      // Change to eye-slash
      icon.innerHTML = '<path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>';
    } else {
      input.type = "password";
      // Change back to eye
      icon.innerHTML = '<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>';
    }
  }

  function showLoginModal() {
    const loginSidebar = new bootstrap.Offcanvas(document.getElementById('loginSidebar'));
    loginSidebar.show();
  }

  function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function showMessage(elementId, message, type) {
    const messageEl = document.getElementById(elementId);
    if (!messageEl) return;
    
    messageEl.textContent = message;
    messageEl.className = type === 'success' ? 'login-message-success' : 'login-message-error';
    messageEl.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
      messageEl.style.display = 'none';
    }, 5000);
  }

  // ============================================
  // GLOBAL LOGIN REQUIREMENT FUNCTION
  // ============================================
  function requireLogin(event, action) {
    event.preventDefault();
    event.stopPropagation();
    
    <?php if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])): ?>
      const loginSidebar = new bootstrap.Offcanvas(document.getElementById('loginSidebar'));
      loginSidebar.show();
      
      const actionText = action === 'wishlist' ? 'add items to your wishlist' : 'add items to your cart';
      console.log('Please login to ' + actionText);
    <?php else: ?>
      if (action === 'wishlist') {
        console.log('Adding to wishlist');
        // Add your wishlist logic here
      } else if (action === 'cart') {
        console.log('Adding to cart');
        // Add your cart logic here
      }
    <?php endif; ?>
  }

  // ============================================
  // LOGIN FORM HANDLER (AJAX)
  // ============================================
  document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('offcanvasLoginForm');
    const loginSidebar = document.getElementById('loginSidebar');
    let loginOffcanvas = null;

    if (loginSidebar) {
      loginOffcanvas = new bootstrap.Offcanvas(loginSidebar);
    }

    if (loginForm) {
      const emailEl = document.getElementById('email');
      const pwdEl = document.getElementById('password');
      const submitBtn = document.getElementById('loginSubmitBtn');
      const btnText = document.getElementById('loginBtnText');
      const spinner = document.getElementById('loginSpinner');

      loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email = (emailEl.value || '').trim();
        const pwd = (pwdEl.value || '');

        // Client-side validation
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        if (!emailOk) {
          showMessage('loginMessage', 'Please enter a valid email address.', 'error');
          emailEl.focus();
          return;
        }
        if (pwd.length < 6) {
          showMessage('loginMessage', 'Password must be at least 6 characters.', 'error');
          pwdEl.focus();
          return;
        }

        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        spinner.style.display = 'inline-block';

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
            showMessage('loginMessage', 'Login successful! Redirecting...', 'success');
            setTimeout(() => {
              window.location.href = data.redirect || 'index.php';
            }, 1000);
          } else {
            showMessage('loginMessage', data.message || 'Invalid email or password.', 'error');
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            spinner.style.display = 'none';
          }
        } catch (err) {
          console.error(err);
          showMessage('loginMessage', 'Network error. Please try again.', 'error');
          submitBtn.disabled = false;
          btnText.style.display = 'inline';
          spinner.style.display = 'none';
        }
      });
    }

    // ============================================
    // REGISTER FORM HANDLER
    // ============================================
    const registerForm = document.getElementById('offcanvasRegisterForm');
    
    if (registerForm) {
      registerForm.addEventListener('submit', function(e) {
        const pwd = document.getElementById('pwd').value;
        const pwdConfirm = document.getElementById('pwdConfirm').value;
        
        if (pwd !== pwdConfirm) {
          e.preventDefault();
          showMessage('registerMessage', 'Passwords do not match!', 'error');
          return false;
        }
        
        // Show loading state
        const submitBtn = document.getElementById('registerSubmitBtn');
        const btnText = document.getElementById('registerBtnText');
        const spinner = document.getElementById('registerSpinner');
        
        if (submitBtn) {
          submitBtn.disabled = true;
          btnText.style.display = 'none';
          spinner.style.display = 'inline-block';
        }
      });
    }

    // ============================================
    // ICON CLICK HANDLERS
    // ============================================

    // Start Reselling button
    const startResellingBtn = document.getElementById('startResellingBtn');
    if (startResellingBtn) {
      startResellingBtn.addEventListener('click', function(e) {
        e.preventDefault();
        <?php if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])): ?>
          if (loginOffcanvas) loginOffcanvas.show();
        <?php else: ?>
          window.location.href = 'start-reselling.php';
        <?php endif; ?>
      });
    }

    // Person icon opens login
    const personIcon = document.getElementById('personIcon');
    if (personIcon && loginOffcanvas) {
      personIcon.addEventListener('click', function() {
        loginOffcanvas.show();
      });
    }

    // Wishlist icon
    const wishlistIcon = document.getElementById('wishlistIcon');
    if (wishlistIcon) {
      wishlistIcon.addEventListener('click', function() {
        <?php if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])): ?>
          if (loginOffcanvas) loginOffcanvas.show();
        <?php else: ?>
          window.location.href = 'wishlist.php';
        <?php endif; ?>
      });
    }

    // Cart icon
    const cartIcon = document.getElementById('cartIcon');
    if (cartIcon) {
      cartIcon.addEventListener('click', function() {
        <?php if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])): ?>
          if (loginOffcanvas) loginOffcanvas.show();
        <?php else: ?>
          window.location.href = 'cart.php';
        <?php endif; ?>
      });
    }

    // ============================================
    // COLOR BUTTONS
    // ============================================
    document.querySelectorAll('.custom-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.custom-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
      });
    });

    // ============================================
    // WISHLIST TOGGLE (FOR LOGGED IN USERS)
    // ============================================
    document.querySelectorAll('.wishlist-wrapper').forEach(wrapper => {
      const wishlistBtn = wrapper.querySelector('.wishlist-btn');
      if (!wishlistBtn) return;
      
      const wishlistIcon = wishlistBtn.querySelector('i');

      wishlistBtn.addEventListener('click', (e) => {
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
          e.preventDefault();
          wishlistIcon.classList.toggle('bi-heart');
          wishlistIcon.classList.toggle('bi-heart-fill');
          wishlistBtn.classList.toggle('btn-danger');
          wishlistBtn.classList.toggle('btn-outline-danger');
          
          // Here you can add AJAX call to update wishlist in database
          // const productId = wishlistBtn.dataset.productId;
          // updateWishlist(productId);
        <?php endif; ?>
      });
    });
    // ============================================
    // URL PARAMETER HANDLING (Registration Status)
    // ============================================
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('register_error')) {
      const error = urlParams.get('register_error');
      const registerSidebarEl = document.getElementById('registerSidebar');
      if (registerSidebarEl) {
        const registerSidebar = new bootstrap.Offcanvas(registerSidebarEl);
        registerSidebar.show();
        
        if (error === 'email_exists') {
          showMessage('registerMessage', 'Email already registered! Please login or use another email.', 'error');
        } else {
          showMessage('registerMessage', 'Registration failed. Please try again.', 'error');
        }
      }
      // Clean URL
      window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (urlParams.has('register_success')) {
      const loginSidebarEl = document.getElementById('loginSidebar');
      if (loginSidebarEl) {
        const loginSidebar = new bootstrap.Offcanvas(loginSidebarEl);
        loginSidebar.show();
        showMessage('loginMessage', 'Registration successful! Please login.', 'success');
      }
      // Clean URL
      window.history.replaceState({}, document.title, window.location.pathname);
    }

  });
</script>