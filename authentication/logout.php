<?php
// ===================================================================
// logout.php
// Ends the user's session and sends them back to the login page.
// ===================================================================

session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session itself
session_destroy();

header("Location: login.php?msg=loggedout");
exit();
?>
