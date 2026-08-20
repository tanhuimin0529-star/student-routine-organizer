<?php
// Role-aware application entry point.
require_once __DIR__ . "/includes/session_start.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: authentication/login.php");
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/index.php");
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
    header("Location: dashboard/dashboard.php");
    exit();
}

// End an invalid or unrecognized authenticated session safely.
header("Location: authentication/logout.php");
exit();
?>
