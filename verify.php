<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }

// ---- DB CONNECTION ----
$dsn = 'mysql:host=localhost;dbname=sahan;charset=utf8mb4';
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
  header('Location: login.php');
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
  header('Location: login.php');
  exit;
}

// ---- LOOKUP USER ----
// Add `type` column (expects values 'admin' or 'user')
$stmt = $pdo->prepare('SELECT id, email, password, fname, type FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

// ---- VERIFY (still plaintext here, you should hash later) ----
$ok = $user && hash_equals((string)$user['password'], (string)$pwd);

if ($ok) {
  session_regenerate_id(true);
  $_SESSION['user_id']  = (int)$user['id'];
  $_SESSION['username'] = $user['email'];
  $_SESSION['name']     = $user['fname'] ?? null;
  $_SESSION['type']     = $user['type']; // Store user type

  // Choose redirect target
  $redirectUrl = ($user['type'] === 'admin') ? 'admin/dashboard.php' : 'index.php';

  if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    json_response(true, 'Login successful', $redirectUrl);
  }

  header("Location: $redirectUrl");
  exit;
}

// ---- FAIL ----
$msg = 'Email or password is incorrect.';
if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
  json_response(false, $msg);
}
$_SESSION['login_error'] = $msg;
header('Location: login.php');
exit;
?>
