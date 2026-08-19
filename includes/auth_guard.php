<?php
// Shared authentication guard for dashboard and module pages.
require_once __DIR__ . "/session_start.php";

if (!isset($_SESSION['user_id'])) {
    // The application is served from its project directory under htdocs.
    $application_directory = rawurlencode(basename(dirname(__DIR__)));
    header("Location: /" . $application_directory . "/authentication/login.php");
    exit();
}
?>
