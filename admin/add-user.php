<?php
session_name('ADMIN_SESSION');
session_start();

// ---- Redirect if not logged in or not admin ----
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit;
}

// Database connection
$dsn = 'mysql:host=localhost;dbname=ceylon_fashion;charset=utf8mb4';
$user = 'root';
$pass = '';
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];
try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Throwable $e) {
  exit('Database connection failed.');
}

// ---- Check if admin ----
$stmt = $pdo->prepare("SELECT type, fname FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();

if (!$userData || $userData['type'] !== 'admin') {
  header('Location: ../index.php');
  exit;
}

// ---- Add User Logic ----
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fname = trim($_POST['fname']);
  $lname = trim($_POST['lname']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $pwd_confirm = trim($_POST['pwd_confirm'] ?? '');
  $type = $_POST['type'];

  // Server-side validation (same rules as user registration)
  $errors = [];

  // First Name
  if (!$fname) {
    $errors[] = 'First Name is required';
  } elseif (!preg_match('/^[A-Za-z]+$/', $fname)) {
    $errors[] = 'First Name must contain only letters';
  } elseif (strlen($fname) < 2) {
    $errors[] = 'First Name must be at least 2 characters long';
  } elseif (strlen($fname) > 50) {
    $errors[] = 'First Name cannot exceed 50 characters';
  }

  // Last Name
  if (!$lname) {
    $errors[] = 'Last Name is required';
  } elseif (!preg_match('/^[A-Za-z]+$/', $lname)) {
    $errors[] = 'Last Name must contain only letters';
  } elseif (strlen($lname) < 2) {
    $errors[] = 'Last Name must be at least 2 characters long';
  } elseif (strlen($lname) > 50) {
    $errors[] = 'Last Name cannot exceed 50 characters';
  }

  // Email
  if (!$email) {
    $errors[] = 'Email is required';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address';
  } else {
    // Check for duplicate email
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
      $errors[] = 'This email is already registered';
    }
  }

  // Password
  if (!$password) {
    $errors[] = 'Password is required';
  } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    $errors[] = 'Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character';
  }

  // Confirm Password
  if (!$pwd_confirm) {
    $errors[] = 'Confirm Password is required';
  } elseif ($password !== $pwd_confirm) {
    $errors[] = 'Passwords do not match';
  }

  if (empty($errors)) {
    try {
      $stmt = $pdo->prepare("INSERT INTO users (fname, lname, email, password, type)
                             VALUES (?, ?, ?, ?, ?)");
      if ($stmt->execute([$fname, $lname, $email, $password, $type])) {
        $success = "✅ User added successfully!";
      } else {
        $error = "❌ Failed to add user.";
      }
    } catch (Throwable $e) {
      $error = "❌ Database error: " . $e->getMessage();
    }
  } else {
    $error = "❌ " . implode('<br>❌ ', $errors);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add User - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f8f9fa; font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
    .sidebar { width: 240px; position: fixed; left:0; top:0; bottom:0; background:#430160ff; color:#fff; padding-top:20px; }
    .sidebar a { display:block; padding:12px 18px; color:#cfd8dc; text-decoration:none; }
    .sidebar a.active { background:#007bff; color:#fff; }
    .sidebar a:hover { background: #5a1b88; color: #fff; }
    .main { margin-left:240px; padding:28px; min-height:100vh; }
    .form-container { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); max-width: 700px; margin: auto; }

    /* Validation Styles (same as user registration) */
    .invalid-feedback-custom {
      color: red !important;
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
    .password-wrapper {
      position: relative;
    }
    .password-wrapper .form-control.is-invalid-custom {
      background-position: right 2.5rem center;
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
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h4 class="text-center mb-3">Ceylon Fashion</h4>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="products.php">📦 Products</a>
    <a href="orders.php">🧾 Orders</a>
    <a href="users.php" class="active">👥 Users</a>
    <a href="reports.php">📊 Reports</a>
    <hr style="border-color: rgba(255,255,255,.06)">
    <a href="logout.php" class="text-danger" onclick="return confirm('Are you sure you want to logout?');">🚪 Logout</a>
  </div>

  <!-- Main Content -->
  <main class="main">
    <div class="container-fluid">
      <h2 class="fw-bold mb-3">Add New User</h2>
      <p class="text-muted mb-4">Fill out the form below to add a new user to your system.</p>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" class="form-container" id="addUserForm">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">First Name <span class="text-danger">*</span></label>
            <input type="text" name="fname" id="adminFname" class="form-control" required>
            <div class="invalid-feedback-custom"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Last Name <span class="text-danger">*</span></label>
            <input type="text" name="lname" id="adminLname" class="form-control" required>
            <div class="invalid-feedback-custom"></div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" id="adminEmail" class="form-control" required>
            <div class="invalid-feedback-custom"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <div class="password-wrapper">
              <input type="password" name="password" id="adminPassword" class="form-control" required>
              <button type="button" class="btn-toggle-pass" onclick="togglePassword('adminPassword', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="invalid-feedback-custom"></div>
            <small class="text-muted">Min 8 chars, uppercase, lowercase, number & special character</small>
          </div>

          <div class="col-md-6">
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <div class="password-wrapper">
              <input type="password" name="pwd_confirm" id="adminPwdConfirm" class="form-control" required>
              <button type="button" class="btn-toggle-pass" onclick="togglePassword('adminPwdConfirm', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="invalid-feedback-custom"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label">User Type</label>
            <select name="type" class="form-select">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>

        <div class="text-center mt-4">
          <button type="submit" class="btn btn-primary px-4" id="addUserSubmitBtn">
            <span id="addUserBtnText">Add User</span>
            <span id="addUserSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
          </button>
          <a href="users.php" class="btn btn-outline-secondary ms-2">Cancel</a>
        </div>
      </form>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  // Toggle password visibility
  function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('bi-eye');
      icon.classList.add('bi-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('bi-eye-slash');
      icon.classList.add('bi-eye');
    }
  }

  // Client-side Validation (same rules as user registration)
  document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('addUserForm');
      const fnameInput = document.getElementById('adminFname');
      const lnameInput = document.getElementById('adminLname');
      const emailInput = document.getElementById('adminEmail');
      const pwdInput = document.getElementById('adminPassword');
      const pwdConfirmInput = document.getElementById('adminPwdConfirm');

      const validateField = (input, validator) => {
          const value = input.value.trim();
          const errorMessage = validator(value);
          let errorDiv;
          if (input.parentElement.classList.contains('password-wrapper')) {
              errorDiv = input.parentElement.nextElementSibling;
          } else {
              errorDiv = input.nextElementSibling;
          }

          if (errorMessage) {
              input.classList.add('is-invalid-custom');
              if (errorDiv && errorDiv.classList.contains('invalid-feedback-custom')) {
                  errorDiv.textContent = errorMessage;
                  errorDiv.style.display = 'block';
              }
              return false;
          } else {
              input.classList.remove('is-invalid-custom');
              if (errorDiv && errorDiv.classList.contains('invalid-feedback-custom')) {
                  errorDiv.style.display = 'none';
              }
              return true;
          }
      };

      // Same validators as user registration
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
              if (!value) return 'Password is required';
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

      // Live validation on input and blur
      inputs.forEach(({ input, validator }) => {
          if (input) {
              input.addEventListener('input', () => validateField(input, validator));
              input.addEventListener('blur', () => validateField(input, validator));
          }
      });

      // Re-validate confirm password when main password changes
      if (pwdInput) {
          pwdInput.addEventListener('input', () => {
              if (pwdConfirmInput.value) validateField(pwdConfirmInput, validators.confirmPassword);
          });
      }

      // Form submit validation
      if (form) {
          form.addEventListener('submit', function(e) {
              let isValid = true;
              inputs.forEach(({ input, validator }) => {
                  if (input && !validateField(input, validator)) {
                      isValid = false;
                  }
              });

              if (!isValid) {
                  e.preventDefault();
              } else {
                  // Show loading state
                  const submitBtn = document.getElementById('addUserSubmitBtn');
                  const btnText = document.getElementById('addUserBtnText');
                  const spinner = document.getElementById('addUserSpinner');

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
</body>
</html>
