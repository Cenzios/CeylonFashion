<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }

if (!isset($_SESSION["username"])) {
    header("location: index.php");
    exit;
}

if (isset($_SESSION["type"]) && $_SESSION["type"] === "admin") {
    header("location: admin/dashboard.php");
    exit;
}

include 'config.php';

$username = $_SESSION['username'];
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['pwd']);
    
    // Validation
    $errors = [];
    
    if (empty($fname)) $errors[] = 'First name is required';
    if (empty($lname)) $errors[] = 'Last name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'danger';
    } else {
        // Check if email is already taken by another user
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND email != ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = 'Email is already registered to another account';
            $messageType = 'danger';
            $stmt->close();
        } else {
            $stmt->close();
            
            // Update user details
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET fname=?, lname=?, email=?, password=? WHERE email=?");
                $stmt->bind_param("sssss", $fname, $lname, $email, $hashed_password, $username);
            } else {
                // Update without changing password
                $stmt = $mysqli->prepare("UPDATE users SET fname=?, lname=?, email=? WHERE email=?");
                $stmt->bind_param("ssss", $fname, $lname, $email, $username);
            }
            
            if ($stmt->execute()) {
                // Update session variables
                $_SESSION['username'] = $email;
                $_SESSION['first_name'] = $fname;
                $_SESSION['last_name'] = $lname;
                $_SESSION['email'] = $email;
                
                $username = $email; // Update for re-fetching
                $message = 'Profile updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update profile. Please try again.';
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }
}

// Fetch current user data
$stmt = $mysqli->prepare("SELECT id, fname, lname, email, type, created FROM users WHERE email=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("location: logout.php");
    exit;
}

// Get order statistics
$stmt = $mysqli->prepare("SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_spent
    FROM orders WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$orderStats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="account-container">
    <div class="container">
        <!-- User Profile Header -->
        <div class="user-profile-header">
            <div class="profile-avatar">
                <?php 
                $initials = strtoupper(substr($user['fname'], 0, 1) . substr($user['lname'], 0, 1));
                echo $initials;
                ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                <!-- <span class="badge bg-primary">
                    <?php echo ucfirst($user['type']); ?>
                </span> -->
            </div>
            <!-- <div class="profile-actions">
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div> -->
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs account-tabs" id="accountTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                    <i class="bi bi-person-circle"></i> Profile Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">
                    <i class="bi bi-graph-up"></i> Account Statistics
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="accountTabContent">
            <!-- Profile Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="section-card">
                    <div class="section-header">
                        <h4><i class="bi bi-person-circle"></i> Profile Information</h4>
                        <p class="text-muted">Update your account details and personal information</p>
                    </div>

                    <form method="POST" class="profile-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fname" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="fname" name="fname" 
                                       value="<?php echo htmlspecialchars($user['fname']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lname" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="lname" name="lname" 
                                       value="<?php echo htmlspecialchars($user['lname']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3"><i class="bi bi-shield-lock"></i> Change Password</h5>
                        <p class="text-muted small">Leave blank if you don't want to change your password</p>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pwd" class="form-label">New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" class="form-control" id="pwd" name="pwd" minlength="6">
                                    <button type="button" class="btn-toggle-pass" onclick="togglePassword('pwd', this)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                <small class="text-muted">At least 6 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="pwd_confirm" class="form-label">Confirm New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" class="form-control" id="pwd_confirm" name="pwd_confirm" minlength="6">
                                    <button type="button" class="btn-toggle-pass" onclick="togglePassword('pwd_confirm', this)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Tab -->
            <div class="tab-pane fade" id="stats" role="tabpanel">
                <div class="section-card">
                    <div class="section-header">
                        <h4><i class="bi bi-graph-up"></i> Account Statistics</h4>
                        <p class="text-muted">Overview of your account activity</p>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: #e3f2fd;">
                                    <i class="bi bi-box-seam" style="color: #1976d2;"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo (int)$orderStats['total_orders']; ?></h3>
                                    <p>Total Orders</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: #e8f5e9;">
                                    <i class="bi bi-check-circle" style="color: #388e3c;"></i>
                                </div>
                                <div class="stat-details">
                                    <h3><?php echo (int)$orderStats['paid_orders']; ?></h3>
                                    <p>Completed Orders</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: #f3e5f5;">
                                    <i class="bi bi-currency-dollar" style="color: #7b1fa2;"></i>
                                </div>
                                <div class="stat-details">
                                    <h3>Rs. <?php echo number_format((float)$orderStats['total_spent'], 2); ?></h3>
                                    <p>Total Spent</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card mt-4">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-info-circle-fill text-primary fs-3"></i>
                            <div>
                                <h6 class="mb-1">Account Details</h6>
                                <p class="mb-0 text-muted">
                                    Registered date: <strong><?php echo date('F j, Y', strtotime($user['created'])); ?></strong>
                                </p>
                                <p class="mb-0 text-muted">
                                    Account Type: <strong><?php echo ucfirst($user['type']); ?></strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
body {
    background: #f8f9fa;
}

.account-container {
    padding: 30px 0;
    min-height: calc(100vh - 200px);
}

/* User Profile Header */
.user-profile-header {
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 25px;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: bold;
    flex-shrink: 0;
}

.profile-info {
    flex-grow: 1;
}

.profile-info h2 {
    margin: 0 0 5px 0;
    color: #430160;
    font-weight: 700;
    font-size: 28px;
}

.profile-info p {
    margin: 0 0 10px 0;
    color: #666;
}

.profile-actions {
    display: flex;
    gap: 10px;
}

/* Navigation Tabs */
.account-tabs {
    background: white;
    border-radius: 8px 8px 0 0;
    padding: 10px 10px 0 10px;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 0;
}

.account-tabs .nav-link {
    color: #666;
    border: none;
    border-radius: 8px 8px 0 0;
    padding: 12px 24px;
    font-weight: 600;
    transition: all 0.2s;
}

.account-tabs .nav-link:hover {
    color: #430160;
    background: #f5f5f5;
}

.account-tabs .nav-link.active {
    color: #430160;
    background: white;
    border-bottom: 3px solid #430160;
}

.account-tabs .nav-link i {
    margin-right: 8px;
    font-size: 18px;
}

/* Tab Content */
.tab-content {
    background: white;
    border-radius: 0 0 8px 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.section-card {
    background: white;
}

.section-header {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.section-header h4 {
    color: #430160;
    font-weight: 600;
    margin-bottom: 5px;
}

.section-header h4 i {
    margin-right: 8px;
}

.profile-form .form-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.profile-form .form-control {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px 14px;
}

.profile-form .form-control:focus {
    border-color: #430160;
    box-shadow: 0 0 0 0.2rem rgba(67, 1, 96, 0.1);
}

/* Statistics Cards */
.stat-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.stat-details h3 {
    margin: 0;
    color: #430160;
    font-size: 28px;
    font-weight: 700;
}

.stat-details p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.info-card {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
}

.info-card h6 {
    color: #430160;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .user-profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-info {
        text-align: center;
    }
    
    .account-tabs .nav-link {
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .tab-content {
        padding: 20px 15px;
    }
    
    .stat-card {
        margin-bottom: 15px;
    }
}

/* Password Toggle Styles */
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
</style>

<script>
// Password confirmation validation
document.querySelector('.profile-form').addEventListener('submit', function(e) {
    const pwd = document.getElementById('pwd').value;
    const pwdConfirm = document.getElementById('pwd_confirm').value;
    
    if (pwd && pwd !== pwdConfirm) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
});
</script>

<?php include 'includes/scripts.php'; ?>
</body>
</html>