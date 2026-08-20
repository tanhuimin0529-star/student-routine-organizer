<?php
// ===================================================================
// Session Guard
// Include this file at the very top of any protected module page.
// It makes sure only logged-in users can view the page.
// ===================================================================

// The shared guard safely starts the session and redirects guests to Login.
require_once __DIR__ . "/auth_guard.php";

// Save the logged-in user's id and role in short variables
// so every page can use them easily
$logged_in_user_id = $_SESSION['user_id'];
$logged_in_role     = isset($_SESSION['role']) ? $_SESSION['role'] : 'student';
?>
