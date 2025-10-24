<?php
// lib/user.php
function current_user_id(mysqli $db) {
    // If your login code sets $_SESSION['user_id'], just return it
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    // Fallbacks if older code still stores email or username
    if (!empty($_SESSION['email'])) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $_SESSION['email']);
    } elseif (!empty($_SESSION['username'])) {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $_SESSION['username']);
    } else {
        return 0; // guest
    }

    $stmt->execute();
    $stmt->bind_result($uid);
    if ($stmt->fetch()) {
        $_SESSION['user_id'] = (int)$uid; // cache it
        $stmt->close();
        return (int)$uid;
    }
    $stmt->close();
    return 0;
}
