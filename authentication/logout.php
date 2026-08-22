<?php
// ===================================================================
// logout.php
// Ends the user's session and sends them back to the login page.
// ===================================================================

require_once __DIR__ . "/../includes/session_start.php";

// Clear all authenticated session data.
$_SESSION = array();

// Expire the session cookie with the same active parameters used to create it.
if (ini_get("session.use_cookies")) {
    $cookie_params = session_get_cookie_params();
    $expired_cookie = array(
        'expires' => time() - 42000,
        'path' => $cookie_params['path'],
        'secure' => $cookie_params['secure'],
        'httponly' => $cookie_params['httponly'],
        'samesite' => isset($cookie_params['samesite'])
            ? $cookie_params['samesite']
            : 'Lax'
    );

    if ($cookie_params['domain'] !== '') {
        $expired_cookie['domain'] = $cookie_params['domain'];
    }

    setcookie(session_name(), "", $expired_cookie);
}

// Destroy the server-side session itself.
session_destroy();

header("Location: login.php?msg=loggedout");
exit();
?>
