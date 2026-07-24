<?php
// ===================================================================
// Session Guard
// Include this file at the very top of every Exercise Tracker page.
// It makes sure only logged-in users can view the page.
// ===================================================================

// session_start() must run before any HTML output
session_start();

// If there is no user_id in the session, the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit();
}

// Save the logged-in user's id and role in short variables
// so every page can use them easily
$logged_in_user_id = $_SESSION['user_id'];
$logged_in_role     = isset($_SESSION['role']) ? $_SESSION['role'] : 'student';
?>
