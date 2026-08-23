<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/theme_control.php";
require_once __DIR__ . "/admin_model.php";

$users = getAllRegisteredUsers($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Users - Student Routine Organizer</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; color: #1f2937; background: #f3f4f6; }
        nav { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; color: white; background: #4f46e5; }
        nav a { margin-left: 16px; color: white; text-decoration: none; }
        main { max-width: 960px; margin: 32px auto; padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; overflow: hidden; background: white; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        th, td { padding: 12px 14px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #eef2ff; }
        .empty { padding: 24px; text-align: center; }
    </style>
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
</head>
<body class="global-admin-page">
<nav>
    <strong>Student Routine Organizer — Admin</strong>
    <div>
        <a href="index.php">Admin Dashboard</a>
        <a href="../authentication/logout.php">Logout</a>
        <?php renderGlobalThemeControl(); ?>
    </div>
</nav>

<main>
    <h1>Registered Users</h1>
    <p>This page is read-only. Delete and deactivation actions are not enabled.</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($users) === 0) { ?>
            <tr><td class="empty" colspan="4">No registered users found.</td></tr>
        <?php } else { ?>
            <?php foreach ($users as $user) { ?>
                <tr>
                    <td><?php echo (int)$user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                </tr>
            <?php } ?>
        <?php } ?>
        </tbody>
    </table>
</main>
</body>
</html>
