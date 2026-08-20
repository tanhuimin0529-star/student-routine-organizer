<?php
// ===================================================================
// logout.php
// Ends the user's session and sends them back to the login page.
// ===================================================================

require_once __DIR__ . "/../includes/session_start.php";

// Clear all session variables
$_SESSION = array();

// Remove the session cookie when PHP sessions use cookies
if (ini_get("session.use_cookies")) {
    $cookie_params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $cookie_params["path"],
        $cookie_params["domain"],
        $cookie_params["secure"],
        $cookie_params["httponly"]
    );
}

// Destroy the session itself
session_destroy();

header("Location: login.php?msg=loggedout");
exit();
?>
