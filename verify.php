<?php
if (session_id() == '' || !isset($_SESSION)) { 
  session_start(); 
}

require_once 'config.php';
if (file_exists('lib/guest-cart.php')) {
    require_once 'lib/guest-cart.php';
}

// ---- DB CONNECTION ----
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
  http_response_code(500);
  exit('Database connection error.');
}

// ---- HELPERS ----
function json_response($ok, $msg = '', $redirect = 'index.php') {
  header('Content-Type: application/json');
  echo json_encode(['ok' => $ok, 'message' => $msg, 'redirect' => $redirect]);
  exit;
}

// ---- METHOD CHECK ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

// ---- CSRF CHECK ----
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
  if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    json_response(false, 'Security check failed. Please refresh and try again.');
  }
  $_SESSION['login_error'] = 'Security check failed. Please try again.';
  header('Location: index.php');
  exit;
}

// ---- INPUTS ----
$email = trim($_POST['username'] ?? '');
$pwd   = $_POST['pwd'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pwd === '') {
  $msg = 'Invalid email or password.';
  if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    json_response(false, $msg);
  }
  $_SESSION['login_error'] = $msg;
  header('Location: index.php');
  exit;
}

// ---- LOOKUP USER ----
$stmt = $pdo->prepare('SELECT id, email, password, fname, lname, type FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

// ---- VERIFY PASSWORD ----
// Check if user exists first
if (!$user) {
  $msg = 'Email or password is incorrect.';
  if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    json_response(false, $msg);
  }
  $_SESSION['login_error'] = $msg;
  header('Location: index.php');
  exit;
}

// Check if password starts with $2y$ (bcrypt hash)
if (strpos($user['password'], '$2y$') === 0) {
  // Use password_verify for hashed passwords
  $passwordMatch = password_verify($pwd, $user['password']);
} else {
  // Use direct comparison for plain text passwords (temporary - should be removed in production)
  $passwordMatch = hash_equals((string)$user['password'], (string)$pwd);
}

if ($passwordMatch) {
  // ---- CHECK IF USER IS ADMIN ----
  // Admins should not be able to login through the main page login form
  if (isset($user['type']) && $user['type'] === 'admin') {
    $msg = 'Admins must login through the admin login page. Please use the admin panel login.';
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
      json_response(false, $msg);
    }
    $_SESSION['login_error'] = $msg;
    header('Location: index.php');
    exit;
  }

  // ---- SUCCESS: Login user (regular users only) ----
  session_regenerate_id(true);
  $_SESSION['user_id']  = (int)$user['id'];
  $_SESSION['username'] = $user['email'];
  $_SESSION['name']     = trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? ''));
  $_SESSION['type']     = $user['type'] ?? 'user';

  // Migrate Guest Data
  if (function_exists('migrateGuestData') && isset($mysqli)) {
      migrateGuestData($mysqli, (int)$user['id']);
  }

  // Regular users are redirected to index page or the requested redirect URL
  $redirectUrl = $_POST['redirect_url'] ?? 'index.php';
  if (empty($redirectUrl)) {
    $redirectUrl = 'index.php';
  }

  if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    json_response(true, 'Login successful', $redirectUrl);
  }

  header("Location: $redirectUrl");
  exit;
}

// ---- FAIL: Invalid credentials ----
$msg = 'Email or password is incorrect.';
if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
  json_response(false, $msg);
}
$_SESSION['login_error'] = $msg;
header('Location: index.php');
exit;
?>