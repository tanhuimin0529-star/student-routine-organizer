<?php
// Start or resume the session once, even when several shared files include
// this bootstrap during the same request.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
