<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/theme_control.php";
require_once __DIR__ . "/admin_model.php";

$counts = getAdminUserCounts($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Routine Organizer</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; color: #1f2937; background: #f3f4f6; }
        nav { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; color: white; background: #4f46e5; }
        nav a { margin-left: 16px; color: white; text-decoration: none; }
        main { max-width: 960px; margin: 32px auto; padding: 0 20px; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin: 24px 0; }
        .card { padding: 20px; border-radius: 10px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .card strong { display: block; margin-top: 8px; font-size: 28px; }
        .button { display: inline-block; padding: 10px 16px; border-radius: 6px; color: white; background: #4f46e5; text-decoration: none; }
    </style>
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
</head>
<body class="global-admin-page">
<nav>
    <strong>Student Routine Organizer — Admin</strong>
    <div>
        <a href="users.php">Registered Users</a>
        <a href="../dashboard/profile.php">Profile Settings</a>
        <?php renderGlobalThemeControl(); ?>
        <a href="../authentication/logout.php">Logout</a>
    </div>
</nav>

<main>
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>.</p>

    <div class="cards">
        <div class="card">Registered users<strong><?php echo $counts['total_users']; ?></strong></div>
        <div class="card">Students<strong><?php echo $counts['student_users']; ?></strong></div>
        <div class="card">Administrators<strong><?php echo $counts['admin_users']; ?></strong></div>
    </div>

    <a class="button" href="users.php">View registered users</a>
</main>
</body>
</html>
