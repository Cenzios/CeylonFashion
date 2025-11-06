<?php
if (session_id() == '' || !isset($_SESSION)) {
  session_start();
}

include 'config.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
  header("Location: login.php");
  exit;
}

$id = (int)$_SESSION['id'];

// ✅ Use null coalescing to avoid undefined key warnings
$fname   = $_POST['fname']   ?? '';
$lname   = $_POST['lname']   ?? '';
$address = $_POST['address'] ?? '';
$city    = $_POST['city']    ?? '';
$pin     = $_POST['pin']     ?? '';
$email   = $_POST['email']   ?? '';
$pwd     = $_POST['pwd']     ?? '';

// ✅ Update each field only if not empty
if ($fname !== '') {
  $stmt = $mysqli->prepare("UPDATE users SET fname = ? WHERE id = ?");
  $stmt->bind_param("si", $fname, $id);
  $stmt->execute();
}

if ($lname !== '') {
  $stmt = $mysqli->prepare("UPDATE users SET lname = ? WHERE id = ?");
  $stmt->bind_param("si", $lname, $id);
  $stmt->execute();
}

if ($address !== '') {
  $stmt = $mysqli->prepare("UPDATE users SET address = ? WHERE id = ?");
  $stmt->bind_param("si", $address, $id);
  $stmt->execute();
}

if ($city !== '') {
  $stmt = $mysqli->prepare("UPDATE users SET city = ? WHERE id = ?");
  $stmt->bind_param("si", $city, $id);
  $stmt->execute();
}

if ($pin !== '') {
  $stmt = $mysqli->prepare("UPDATE users SET pin = ? WHERE id = ?");
  $stmt->bind_param("si", $pin, $id);
  $stmt->execute();
}

if ($email !== '') {
  $stmt = $mysqli->prepare("UPDATE users SET email = ? WHERE id = ?");
  $stmt->bind_param("si", $email, $id);
  $stmt->execute();

  // ✅ Update session email too
  $_SESSION['username'] = $email;
}

if ($pwd !== '') {
  // Optional: Hash password (if you want)
  // $hashed = password_hash($pwd, PASSWORD_DEFAULT);
  $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
  $stmt->bind_param("si", $pwd, $id);
  $stmt->execute();
}

// ✅ Close prepared statement & redirect
if (isset($stmt)) {
  $stmt->close();
}

header("Location: success.php");
exit;
?>
