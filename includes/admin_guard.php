<?php
// Shared guard for every admin-only page.
require_once __DIR__ . "/session_start.php";

$application_directory = rawurlencode(basename(dirname(__DIR__)));

if (!isset($_SESSION['user_id'])) {
    header("Location: /" . $application_directory . "/authentication/admin_login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /" . $application_directory . "/dashboard/dashboard.php");
    exit();
}
?>
