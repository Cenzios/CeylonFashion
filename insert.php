<?php
include 'config.php';

$fname  = $_POST["fname"];
$lname  = $_POST["lname"];
$email  = $_POST["email"];
$pwd    = $_POST["pwd"];

// Enable MySQLi exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $stmt = $mysqli->prepare(
        "INSERT INTO users (fname, lname, email, password, type)
         VALUES (?, ?, ?, ?, 'user')"
    );
    $stmt->bind_param("ssss", $fname, $lname, $email, $pwd);
    $stmt->execute();
    $new_user_id = $mysqli->insert_id;

    // Start session if not started
    if (session_id() == '' || !isset($_SESSION)) { session_start(); }
    
    // REMOVED AUTO-LOGIN Logic here
    // User must login manually to retrieve guest cart data (handled in verify.php)

    // Redirect logic

    // Redirect logic
    $redirect_url = isset($_POST['redirect_url']) && !empty($_POST['redirect_url']) ? $_POST['redirect_url'] : 'index.php';

    // Append success param
    if (strpos($redirect_url, '?') !== false) {
        $redirect_url .= '&register_success=1';
    } else {
        $redirect_url .= '?register_success=1';
    }
    
    header("Location: " . $redirect_url);
    exit;

} catch (mysqli_sql_exception $e) {

    if ($e->getCode() == 1062) {
        $error_code = "email_exists";
    } else {
        $error_code = "unknown";
    }

    // Determine redirect URL
    $redirect_url = isset($_POST['redirect_url']) && !empty($_POST['redirect_url']) ? $_POST['redirect_url'] : 'index.php';
    
    // Append error param
    if (strpos($redirect_url, '?') !== false) {
        $redirect_url .= '&register_error=' . $error_code;
    } else {
        $redirect_url .= '?register_error=' . $error_code;
    }

    header("Location: " . $redirect_url);
    exit;
}
