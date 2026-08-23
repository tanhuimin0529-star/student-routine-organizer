<?php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/shared_navbar.php';

function profileSettingsEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function profileSettingsTextLength($value) {
    return function_exists('mb_strlen')
        ? mb_strlen((string) $value, 'UTF-8')
        : strlen((string) $value);
}

function profileSettingsLogDatabaseError($context, $exception) {
    $message = str_replace(array("\r", "\n"), ' ', $exception->getMessage());
    error_log(
        '[Profile settings] ' . $context
        . ' mysqli error ' . (int) $exception->getCode()
        . ': ' . $message
    );
}

function profileSettingsLoadUser($conn, $user_id, &$database_error) {
    $database_error = false;
    $stmt = null;

    try {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT user_id, name, email, password, role
             FROM users
             WHERE user_id = ?
             LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $user ?: null;
    } catch (mysqli_sql_exception $exception) {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }

        profileSettingsLogDatabaseError('load account', $exception);
        $database_error = true;
        return null;
    }
}

if (empty($_SESSION['profile_settings_csrf_token'])) {
    $_SESSION['profile_settings_csrf_token'] = bin2hex(random_bytes(32));
}

$profile_flash = isset($_SESSION['profile_settings_flash'])
    && is_array($_SESSION['profile_settings_flash'])
        ? $_SESSION['profile_settings_flash']
        : null;
unset($_SESSION['profile_settings_flash']);

$database_error = false;
$current_user = profileSettingsLoadUser(
    $conn,
    (int) $logged_in_user_id,
    $database_error
);

$details_errors = array();
$password_errors = array();
$submitted_name = $current_user !== null ? (string) $current_user['name'] : '';
$submitted_email = $current_user !== null ? (string) $current_user['email'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profile_action = isset($_POST['profile_action']) && is_string($_POST['profile_action'])
        ? $_POST['profile_action']
        : '';
    $submitted_csrf_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $session_csrf_token = isset($_SESSION['profile_settings_csrf_token'])
        && is_string($_SESSION['profile_settings_csrf_token'])
            ? $_SESSION['profile_settings_csrf_token']
            : '';

    $csrf_is_valid = $session_csrf_token !== ''
        && $submitted_csrf_token !== ''
        && hash_equals($session_csrf_token, $submitted_csrf_token);

    if ($profile_action === 'update_details') {
        $submitted_name = isset($_POST['name']) && is_string($_POST['name'])
            ? trim($_POST['name'])
            : '';
        $submitted_email = isset($_POST['email']) && is_string($_POST['email'])
            ? trim($_POST['email'])
            : '';

        if (!$csrf_is_valid) {
            $details_errors[] = 'Your form session expired. Please try again.';
        }

        if ($current_user === null) {
            $details_errors[] = 'Your account details could not be updated right now. Please try again.';
        }

        if ($submitted_name === '') {
            $details_errors[] = 'Please enter your name.';
        } elseif (profileSettingsTextLength($submitted_name) > 100) {
            $details_errors[] = 'Name must be 100 characters or fewer.';
        }

        if ($submitted_email === '') {
            $details_errors[] = 'Please enter your email.';
        } elseif (!filter_var($submitted_email, FILTER_VALIDATE_EMAIL)) {
            $details_errors[] = 'Please enter a valid email address.';
        } elseif (profileSettingsTextLength($submitted_email) > 100) {
            $details_errors[] = 'Email must be 100 characters or fewer.';
        }

        if (empty($details_errors)) {
            $stmt = null;

            try {
                $stmt = mysqli_prepare(
                    $conn,
                    'SELECT user_id
                     FROM users
                     WHERE email = ? AND user_id <> ?
                     LIMIT 1'
                );
                mysqli_stmt_bind_param($stmt, 'si', $submitted_email, $logged_in_user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $email_owner = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if ($email_owner) {
                    $details_errors[] = 'This email address is already used by another account.';
                }
            } catch (mysqli_sql_exception $exception) {
                if ($stmt instanceof mysqli_stmt) {
                    mysqli_stmt_close($stmt);
                }

                profileSettingsLogDatabaseError('check email uniqueness', $exception);
                $details_errors[] = 'Your account details could not be updated right now. Please try again.';
            }
        }

        if (empty($details_errors)) {
            $stmt = null;

            try {
                $stmt = mysqli_prepare(
                    $conn,
                    'UPDATE users
                     SET name = ?, email = ?
                     WHERE user_id = ?'
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    'ssi',
                    $submitted_name,
                    $submitted_email,
                    $logged_in_user_id
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $_SESSION['name'] = $submitted_name;
                $_SESSION['email'] = $submitted_email;

                $_SESSION['profile_settings_flash'] = array(
                    'type' => 'success',
                    'message' => 'Profile details updated successfully.'
                );
                $_SESSION['profile_settings_csrf_token'] = bin2hex(random_bytes(32));

                header('Location: profile.php', true, 303);
                exit();
            } catch (mysqli_sql_exception $exception) {
                if ($stmt instanceof mysqli_stmt) {
                    mysqli_stmt_close($stmt);
                }

                profileSettingsLogDatabaseError('update account details', $exception);

                if ((int) $exception->getCode() === 1062) {
                    $details_errors[] = 'This email address is already used by another account.';
                } else {
                    $details_errors[] = 'Your account details could not be updated right now. Please try again.';
                }
            }
        }
    } elseif ($profile_action === 'change_password') {
        $current_password = isset($_POST['current_password']) && is_string($_POST['current_password'])
            ? $_POST['current_password']
            : '';
        $new_password = isset($_POST['new_password']) && is_string($_POST['new_password'])
            ? $_POST['new_password']
            : '';
        $confirm_new_password = isset($_POST['confirm_new_password'])
            && is_string($_POST['confirm_new_password'])
                ? $_POST['confirm_new_password']
                : '';

        if (!$csrf_is_valid) {
            $password_errors[] = 'Your form session expired. Please try again.';
        }

        if ($current_user === null) {
            $password_errors[] = 'Your password could not be changed right now. Please try again.';
        }

        if ($current_password === '') {
            $password_errors[] = 'Please enter your current password.';
        }

        if ($new_password === '') {
            $password_errors[] = 'Please enter a new password.';
        } elseif (strlen($new_password) < 6) {
            $password_errors[] = 'New password must be at least 6 characters long.';
        }

        if ($confirm_new_password === '') {
            $password_errors[] = 'Please confirm your new password.';
        } elseif ($new_password !== $confirm_new_password) {
            $password_errors[] = 'New password and confirmation do not match.';
        }

        if (
            empty($password_errors)
            && !password_verify($current_password, $current_user['password'])
        ) {
            $password_errors[] = 'Current password is incorrect.';
        }

        if (empty($password_errors)) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            if ($new_password_hash === false) {
                $password_errors[] = 'Your password could not be changed right now. Please try again.';
            }
        }

        if (empty($password_errors)) {
            $stmt = null;

            try {
                $stmt = mysqli_prepare(
                    $conn,
                    'UPDATE users
                     SET password = ?
                     WHERE user_id = ?'
                );
                mysqli_stmt_bind_param($stmt, 'si', $new_password_hash, $logged_in_user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $_SESSION['profile_settings_flash'] = array(
                    'type' => 'success',
                    'message' => 'Password changed successfully.'
                );
                $_SESSION['profile_settings_csrf_token'] = bin2hex(random_bytes(32));

                header('Location: profile.php', true, 303);
                exit();
            } catch (mysqli_sql_exception $exception) {
                if ($stmt instanceof mysqli_stmt) {
                    mysqli_stmt_close($stmt);
                }

                profileSettingsLogDatabaseError('change password', $exception);
                $password_errors[] = 'Your password could not be changed right now. Please try again.';
            }
        }
    } else {
        $details_errors[] = 'Please use the Profile Settings forms to update your account.';
    }
}

$is_admin = $current_user !== null
    && isset($current_user['role'])
    && $current_user['role'] === 'admin';
$home_url = $is_admin ? '../admin/index.php' : 'dashboard.php';
$home_label = $is_admin ? 'Back to Admin Home' : 'Back to Home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Student Routine Organizer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/exercise.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <?php if (!$is_admin): ?>
        <?php renderSharedNavbarAssets('../'); ?>
    <?php endif; ?>
</head>
<body class="profile-page">
<div class="morph-bg" aria-hidden="true">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
</div>

<?php if ($is_admin): ?>
    <nav class="navbar">
        <div class="nav-brand">Student Routine Organizer</div>
        <div class="nav-links">
            <a href="<?php echo profileSettingsEscape($home_url); ?>"><?php echo profileSettingsEscape($home_label); ?></a>
            <?php renderGlobalThemeControl(); ?>
            <a href="../authentication/logout.php">Logout</a>
        </div>
    </nav>
<?php else: ?>
    <?php renderSharedStudentNavbar('../', '', 'profile'); ?>
<?php endif; ?>

<main class="page-wrapper profile-wrapper">
    <header class="profile-header">
        <div>
            <p class="profile-eyebrow">Account</p>
            <h1>Profile Settings</h1>
            <p>Review and update your account information securely.</p>
        </div>
        <a class="btn btn-secondary" href="<?php echo profileSettingsEscape($home_url); ?>">
            <?php echo profileSettingsEscape($home_label); ?>
        </a>
    </header>

    <?php if ($profile_flash !== null): ?>
        <div class="profile-alert profile-alert-success" role="status">
            <?php echo profileSettingsEscape(
                isset($profile_flash['message'])
                    ? $profile_flash['message']
                    : 'Profile updated successfully.'
            ); ?>
        </div>
    <?php endif; ?>

    <?php if ($database_error || $current_user === null): ?>
        <div class="profile-alert profile-alert-error" role="alert">
            Your account details could not be loaded right now. Please try again later.
        </div>
    <?php else: ?>
        <section class="profile-summary" aria-labelledby="profile-summary-heading">
            <div>
                <p class="profile-eyebrow">Signed-in account</p>
                <h2 id="profile-summary-heading"><?php echo profileSettingsEscape($current_user['name']); ?></h2>
                <p><?php echo profileSettingsEscape($current_user['email']); ?></p>
            </div>
            <div class="profile-role">
                <span>Role</span>
                <strong><?php echo profileSettingsEscape(ucfirst($current_user['role'])); ?></strong>
            </div>
        </section>

        <div class="profile-settings-grid">
            <section class="profile-card" aria-labelledby="profile-details-heading">
                <div class="profile-card-heading">
                    <span class="profile-card-icon" aria-hidden="true">👤</span>
                    <div>
                        <h2 id="profile-details-heading">Account Details</h2>
                        <p>Update the name and email used for your account.</p>
                    </div>
                </div>

                <?php if (!empty($details_errors)): ?>
                    <div class="profile-alert profile-alert-error" role="alert">
                        <ul>
                            <?php foreach ($details_errors as $error): ?>
                                <li><?php echo profileSettingsEscape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="profile.php" class="profile-form">
                    <input type="hidden" name="profile_action" value="update_details">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo profileSettingsEscape($_SESSION['profile_settings_csrf_token']); ?>"
                    >

                    <label for="profile-name">Name</label>
                    <input
                        type="text"
                        id="profile-name"
                        name="name"
                        maxlength="100"
                        autocomplete="name"
                        value="<?php echo profileSettingsEscape($submitted_name); ?>"
                        required
                    >

                    <label for="profile-email">Email</label>
                    <input
                        type="email"
                        id="profile-email"
                        name="email"
                        maxlength="100"
                        autocomplete="email"
                        value="<?php echo profileSettingsEscape($submitted_email); ?>"
                        required
                    >

                    <label for="profile-role">Role</label>
                    <input
                        type="text"
                        id="profile-role"
                        value="<?php echo profileSettingsEscape(ucfirst($current_user['role'])); ?>"
                        readonly
                        aria-readonly="true"
                    >
                    <p class="profile-help">Your account role cannot be changed here.</p>

                    <button class="btn btn-primary" type="submit">Save Profile</button>
                </form>
            </section>

            <section class="profile-card" aria-labelledby="profile-password-heading">
                <div class="profile-card-heading">
                    <span class="profile-card-icon" aria-hidden="true">🔒</span>
                    <div>
                        <h2 id="profile-password-heading">Change Password</h2>
                        <p>Confirm your current password before choosing a new one.</p>
                    </div>
                </div>

                <?php if (!empty($password_errors)): ?>
                    <div class="profile-alert profile-alert-error" role="alert">
                        <ul>
                            <?php foreach ($password_errors as $error): ?>
                                <li><?php echo profileSettingsEscape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="profile.php" class="profile-form">
                    <input type="hidden" name="profile_action" value="change_password">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo profileSettingsEscape($_SESSION['profile_settings_csrf_token']); ?>"
                    >

                    <label for="current-password">Current Password</label>
                    <input
                        type="password"
                        id="current-password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >

                    <label for="new-password">New Password</label>
                    <input
                        type="password"
                        id="new-password"
                        name="new_password"
                        minlength="6"
                        autocomplete="new-password"
                        required
                    >

                    <label for="confirm-new-password">Confirm New Password</label>
                    <input
                        type="password"
                        id="confirm-new-password"
                        name="confirm_new_password"
                        minlength="6"
                        autocomplete="new-password"
                        required
                    >

                    <p class="profile-help">Use at least 6 characters.</p>

                    <button class="btn btn-primary" type="submit">Change Password</button>
                </form>
            </section>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
