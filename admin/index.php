<?php
require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/theme_control.php";
require_once __DIR__ . "/admin_model.php";

function adminDashboardEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$counts = getAdminUserCounts($conn);
$recent_users = getRecentRegisteredUsers($conn, 5);
$total_users = max(0, (int) $counts['total_users']);
$student_percentage = $total_users > 0
    ? round(((int) $counts['student_users'] / $total_users) * 100, 1)
    : 0;
$admin_percentage = $total_users > 0
    ? round(((int) $counts['admin_users'] / $total_users) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Routine Organizer</title>
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="global-admin-page admin-dashboard-page">
<nav class="admin-topbar" aria-label="Admin navigation">
    <a class="admin-brand" href="index.php">Student Routine Organizer <span>Admin</span></a>
    <div class="admin-nav-actions">
        <a href="users.php">Registered Users</a>
        <a href="../dashboard/profile.php">Profile Settings</a>
        <?php renderGlobalThemeControl(); ?>
        <a class="admin-logout-link" href="../authentication/logout.php">Logout</a>
    </div>
</nav>

<main class="admin-dashboard-main">
    <header class="admin-welcome">
        <div>
            <p class="admin-eyebrow">Administration overview</p>
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo adminDashboardEscape($_SESSION['name']); ?>. Here is a quick overview of registered accounts.</p>
        </div>
        <a class="admin-button admin-button-primary" href="users.php">View Registered Users</a>
    </header>

    <section class="admin-stat-grid" aria-label="User totals">
        <article class="admin-stat-card admin-stat-card-total">
            <span class="admin-stat-icon" aria-hidden="true">👥</span>
            <div>
                <p>Registered Users</p>
                <strong><?php echo (int) $counts['total_users']; ?></strong>
            </div>
        </article>
        <article class="admin-stat-card">
            <span class="admin-stat-icon" aria-hidden="true">🎓</span>
            <div>
                <p>Students</p>
                <strong><?php echo (int) $counts['student_users']; ?></strong>
            </div>
        </article>
        <article class="admin-stat-card">
            <span class="admin-stat-icon" aria-hidden="true">🛡️</span>
            <div>
                <p>Administrators</p>
                <strong><?php echo (int) $counts['admin_users']; ?></strong>
            </div>
        </article>
    </section>

    <div class="admin-dashboard-grid">
        <section class="admin-panel admin-recent-panel" aria-labelledby="recent-registrations-heading">
            <div class="admin-panel-heading">
                <div>
                    <p class="admin-eyebrow">Latest accounts</p>
                    <h2 id="recent-registrations-heading">Recent Registrations</h2>
                </div>
                <span>Latest 5</span>
            </div>

            <?php if (empty($recent_users)): ?>
                <div class="admin-empty-state">No registered users are available right now.</div>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-recent-table">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <?php
                                $registration_time = strtotime((string) $user['created_at']);
                                $registration_date = $registration_time !== false
                                    ? date('M j, Y', $registration_time)
                                    : 'Not available';
                                $role = isset($user['role']) ? strtolower((string) $user['role']) : '';
                                ?>
                                <tr>
                                    <td class="admin-user-name"><?php echo adminDashboardEscape($user['name']); ?></td>
                                    <td><?php echo adminDashboardEscape($user['email']); ?></td>
                                    <td>
                                        <span class="admin-role-badge admin-role-<?php echo $role === 'admin' ? 'admin' : 'student'; ?>">
                                            <?php echo adminDashboardEscape(ucfirst($role)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo adminDashboardEscape($registration_date); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <aside class="admin-side-column">
            <section class="admin-panel admin-distribution-panel" aria-labelledby="user-distribution-heading">
                <div class="admin-panel-heading">
                    <div>
                        <p class="admin-eyebrow">Account roles</p>
                        <h2 id="user-distribution-heading">User Distribution</h2>
                    </div>
                </div>

                <div class="admin-distribution-item">
                    <div class="admin-distribution-label">
                        <span><i class="admin-dot admin-dot-student"></i>Students</span>
                        <strong><?php echo (int) $counts['student_users']; ?> · <?php echo $student_percentage; ?>%</strong>
                    </div>
                    <div class="admin-progress" aria-hidden="true">
                        <span class="admin-progress-student" style="width: <?php echo $student_percentage; ?>%"></span>
                    </div>
                </div>

                <div class="admin-distribution-item">
                    <div class="admin-distribution-label">
                        <span><i class="admin-dot admin-dot-admin"></i>Administrators</span>
                        <strong><?php echo (int) $counts['admin_users']; ?> · <?php echo $admin_percentage; ?>%</strong>
                    </div>
                    <div class="admin-progress" aria-hidden="true">
                        <span class="admin-progress-admin" style="width: <?php echo $admin_percentage; ?>%"></span>
                    </div>
                </div>
            </section>

            <section class="admin-panel admin-quick-panel" aria-labelledby="quick-actions-heading">
                <div class="admin-panel-heading">
                    <div>
                        <p class="admin-eyebrow">Shortcuts</p>
                        <h2 id="quick-actions-heading">Quick Actions</h2>
                    </div>
                </div>
                <div class="admin-quick-actions">
                    <a class="admin-action-card" href="users.php">
                        <span aria-hidden="true">👥</span>
                        <div><strong>View Registered Users</strong><small>Review student and admin accounts</small></div>
                    </a>
                    <a class="admin-action-card" href="../dashboard/profile.php">
                        <span aria-hidden="true">⚙️</span>
                        <div><strong>Profile Settings</strong><small>Update your account information</small></div>
                    </a>
                </div>
            </section>
        </aside>
    </div>
</main>
</body>
</html>