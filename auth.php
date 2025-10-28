<?php
session_start();

// Session timeout - 30 minutes
$timeout = 1800;

// Check if session exists
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: index.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Verify user still exists in database
require_once 'db.php';
$user_id = $_SESSION['user_id'];
$check = $conn->query("SELECT user_id FROM users WHERE user_id = $user_id");
if ($check->num_rows == 0) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>