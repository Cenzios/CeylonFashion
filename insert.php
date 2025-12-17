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

    header("Location: index.php?register_success=1");
    exit;

} catch (mysqli_sql_exception $e) {

    if ($e->getCode() == 1062) {
        header("Location: index.php?register_error=email_exists");
    } else {
        header("Location: index.php?register_error=unknown");
    }
    exit;
}
