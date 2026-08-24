<?php
require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/theme_control.php';
require_once __DIR__ . '/admin_model.php';

function adminUsersEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminUsersTextLength($value) {
    return function_exists('mb_strlen')
        ? mb_strlen((string) $value, 'UTF-8')
        : strlen((string) $value);
}

function adminUsersRegistrationDate($value) {
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? 'Not available' : date('F j, Y', $timestamp);
}

$search = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search'])
    : '';
$role = isset($_GET['role']) && is_string($_GET['role'])
    ? $_GET['role']
    : '';
$errors = array();

if (adminUsersTextLength($search) > 100) {
    $errors[] = 'Search must be 100 characters or fewer.';
}

if (!in_array($role, array('', 'student', 'admin'), true)) {
    $role = '';
}

$users = empty($errors) ? getRegisteredUsers($conn, $search, $role) : array();

if (empty($_SESSION['admin_user_delete_csrf_token'])) {
    $_SESSION['admin_user_delete_csrf_token'] = bin2hex(random_bytes(32));
}

$flash = isset($_SESSION['admin_users_flash']) && is_array($_SESSION['admin_users_flash'])
    ? $_SESSION['admin_users_flash']
    : null;
unset($_SESSION['admin_users_flash']);

$filters_active = $search !== '' || $role !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Users - Student Routine Organizer</title>
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin_users.css">
    <link rel="stylesheet" href="../assets/css/system_ui.css">
    <script src="../assets/js/admin.js" defer></script>
</head>
<body class="global-admin-page admin-dashboard-page admin-users-page system-ui-page">
<nav class="admin-topbar" aria-label="Admin navigation">
    <a class="admin-brand" href="index.php">Student Routine Organizer <span>Admin</span></a>
    <div class="admin-nav-actions">
        <a class="admin-nav-current" href="users.php" aria-current="page">Registered Users</a>
        <a href="../dashboard/profile.php">Profile Settings</a>
        <?php renderGlobalThemeControl(); ?>
        <a class="admin-logout-link" href="../authentication/logout.php">Logout</a>
    </div>
</nav>

<main class="admin-dashboard-main admin-users-main">
    <header class="admin-users-heading">
        <div>
            <p class="admin-eyebrow">Account management</p>
            <h1>Registered Users</h1>
            <p>Search registered accounts, filter by role, or remove an account when required.</p>
        </div>
        <a class="admin-button admin-button-secondary" href="index.php">Back to Admin Dashboard</a>
    </header>

    <?php if ($flash): ?>
        <div
            class="admin-alert admin-alert-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>"
            role="<?php echo $flash['type'] === 'success' ? 'status' : 'alert'; ?>"
        >
            <?php echo adminUsersEscape(isset($flash['message']) ? $flash['message'] : ''); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="admin-alert admin-alert-error" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?php echo adminUsersEscape($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="admin-panel admin-users-panel" aria-labelledby="registered-users-table-heading">
        <form class="admin-user-filters" method="get" action="users.php" role="search">
            <div class="admin-filter-field admin-search-field">
                <label for="admin-user-search">Search users</label>
                <input
                    id="admin-user-search"
                    type="search"
                    name="search"
                    value="<?php echo adminUsersEscape($search); ?>"
                    maxlength="100"
                    placeholder="Search by name or email"
                >
            </div>

            <div class="admin-filter-field admin-role-filter">
                <label for="admin-role-filter">Role</label>
                <select id="admin-role-filter" name="role">
                    <option value=""<?php echo $role === '' ? ' selected' : ''; ?>>All Users</option>
                    <option value="student"<?php echo $role === 'student' ? ' selected' : ''; ?>>Student</option>
                    <option value="admin"<?php echo $role === 'admin' ? ' selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <div class="admin-filter-actions">
                <button class="admin-button admin-button-primary" type="submit">Search</button>
                <a class="admin-button admin-button-secondary" href="users.php">Clear</a>
            </div>
        </form>

        <div class="admin-panel-heading admin-users-table-heading">
            <div>
                <p class="admin-eyebrow">User directory</p>
                <h2 id="registered-users-table-heading">
                    <?php echo $filters_active ? 'Matching Users' : 'All Registered Users'; ?>
                </h2>
            </div>
            <span><?php echo count($users); ?> result<?php echo count($users) === 1 ? '' : 's'; ?></span>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-recent-table admin-users-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Registration Date</th>
                        <th scope="col" class="admin-action-column">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td class="admin-users-empty" colspan="5">
                            <?php echo $filters_active ? 'No users found.' : 'No registered users found.'; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $user_id = (int) $user['user_id'];
                        $user_role = strtolower((string) $user['role']);
                        $is_current_admin = $user_id === (int) $_SESSION['user_id'];
                        ?>
                        <tr>
                            <td class="admin-user-name"><?php echo adminUsersEscape($user['name']); ?></td>
                            <td><?php echo adminUsersEscape($user['email']); ?></td>
                            <td>
                                <span class="admin-role-badge admin-role-<?php echo $user_role === 'admin' ? 'admin' : 'student'; ?>">
                                    <?php echo adminUsersEscape(ucfirst($user_role)); ?>
                                </span>
                            </td>
                            <td><?php echo adminUsersEscape(adminUsersRegistrationDate($user['created_at'])); ?></td>
                            <td class="admin-action-column">
                                <?php if ($is_current_admin): ?>
                                    <span class="admin-current-account">Current account</span>
                                <?php else: ?>
                                    <form
                                        class="admin-delete-form"
                                        method="post"
                                        action="delete_user_handler.php"
                                        data-admin-delete-form
                                    >
                                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo adminUsersEscape($_SESSION['admin_user_delete_csrf_token']); ?>"
                                        >
                                        <input type="hidden" name="search" value="<?php echo adminUsersEscape($search); ?>">
                                        <input type="hidden" name="role" value="<?php echo adminUsersEscape($role); ?>">
                                        <button
                                            class="admin-delete-button"
                                            type="submit"
                                            aria-label="Delete <?php echo adminUsersEscape($user['name']); ?>"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
