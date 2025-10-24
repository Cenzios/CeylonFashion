<?php
if (session_id() == '' || !isset($_SESSION)) { session_start(); }
include_once '../config.php';

if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'admin') {
    exit('unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['type'])) {
    $id = (int)$_POST['id'];
    $type = $_POST['type'] === 'admin' ? 'admin' : 'user';
    
    $stmt = $mysqli->prepare("UPDATE users SET type=? WHERE id=?");
    $stmt->bind_param('si', $type, $id);
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'fail';
    }
    $stmt->close();
}
?>
