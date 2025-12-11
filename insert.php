<?php

include 'config.php';

$fname = $_POST["fname"];
$lname = $_POST["lname"];
$address = $_POST["address"];
$city = $_POST["city"];
$pin = $_POST["pin"];
$email = $_POST["email"];
$pwd = $_POST["pwd"];

// Hash the password for security (Recommended, though user didn't explicitly ask, it's best practice. 
// However, to stick strictly to the requested fix and avoid breaking existing login if it expects plain text (which verify.php seemed to support both), 
// I will keep it as is or check verify.php again. verify.php checks for $2y$ hash. 
// The previous code was inserting plain text. I should probably stick to the user's pattern unless I want to upgrade security.
// The user's prompt was about the duplicate error. I will stick to the previous insertion logic but wrap it in try-catch.
// Wait, the user said "after some user succsesful register he need to be navigate to the login page".
// Previously I implemented auto-login in the previous turn, but the user REVERTED it in Step 118/119.
// So I should NOT do auto-login. I should redirect to login page (or index with login sidebar open).

try {
    if($mysqli->query("INSERT INTO users (fname, lname, address, city, pin, email, password) VALUES('$fname', '$lname', '$address', '$city', $pin, '$email', '$pwd')")){
        // Success - Redirect to index with success param to open login sidebar
        header("location:index.php?register_success=1");
        exit;
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) { // Duplicate entry
        header("location:index.php?register_error=email_exists");
        exit;
    } else {
        // Other error
        header("location:index.php?register_error=unknown");
        exit;
    }
}

// Fallback
header("location:index.php");
?>
