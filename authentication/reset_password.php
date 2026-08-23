<?php
require_once __DIR__ . '/../includes/session_start.php';
require_once __DIR__ . '/../includes/theme_control.php';

if (isset($_SESSION['user_id'])) {
    $destination = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'
        ? '../admin/index.php'
        : '../dashboard/dashboard.php';
    header('Location: ' . $destination, true, 303);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/password_reset_functions.php';

if (empty($_SESSION['reset_password_csrf_token'])) {
    $_SESSION['reset_password_csrf_token'] = bin2hex(random_bytes(32));
}

$raw_token = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_token = isset($_POST['token']) && is_string($_POST['token'])
        ? trim($_POST['token'])
        : '';
} elseif (isset($_GET['token']) && is_string($_GET['token'])) {
    $raw_token = trim($_GET['token']);
}

$errors = array();
$token_record = findValidStudentPasswordResetToken($conn, $raw_token);
$token_is_valid = $token_record !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_is_valid) {
    $submitted_csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $session_csrf = isset($_SESSION['reset_password_csrf_token'])
        && is_string($_SESSION['reset_password_csrf_token'])
            ? $_SESSION['reset_password_csrf_token']
            : '';
    $new_password = isset($_POST['new_password']) && is_string($_POST['new_password'])
        ? $_POST['new_password']
        : '';
    $confirm_password = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
        ? $_POST['confirm_password']
        : '';

    if (
        $session_csrf === ''
        || $submitted_csrf === ''
        || !hash_equals($session_csrf, $submitted_csrf)
    ) {
        $errors[] = 'Your form session expired. Please try again.';
    }

    if ($new_password === '') {
        $errors[] = 'Please enter a new password.';
    } elseif (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters long.';
    }

    if ($confirm_password === '') {
        $errors[] = 'Please confirm your new password.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        if ($new_password_hash === false) {
            $errors[] = 'Your password could not be reset right now. Please try again.';
        } elseif (resetStudentPasswordWithToken($conn, $raw_token, $new_password_hash)) {
            unset($_SESSION['reset_password_csrf_token']);
            header('Location: login.php?msg=password_reset', true, 303);
            exit();
        } else {
            // The token may have expired or been consumed between validation and update.
            $token_is_valid = false;
        }
    }
}

function resetPasswordEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Student Routine Organizer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/system_ui.css">
</head>
<body class="page-reset-password system-ui-page">
<?php renderGlobalThemeControl(true); ?>

<div class="morph-bg" aria-hidden="true">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="blob blob-5"></div>
</div>

<main class="auth-wrapper">
    <section class="auth-card auth-card--recovery" aria-labelledby="reset-password-heading">
        <h1 id="reset-password-heading">Reset Password</h1>

        <?php if (!$token_is_valid): ?>
            <p class="auth-subtitle">Request a new link if this one can no longer be used.</p>
            <div class="alert alert-error" role="alert">
                This password reset link is invalid or has expired.
            </div>
            <p class="auth-switch"><a href="forgot_password.php">Request a New Reset Link</a></p>
            <p class="auth-switch"><a href="login.php">Back to Student Login</a></p>
        <?php else: ?>
            <p class="auth-subtitle">Choose a new password for your student account.</p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo resetPasswordEscape($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="reset_password.php">
                <input type="hidden" name="token" value="<?php echo resetPasswordEscape($raw_token); ?>">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo resetPasswordEscape($_SESSION['reset_password_csrf_token']); ?>"
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

                <label for="confirm-password">Confirm New Password</label>
                <input
                    type="password"
                    id="confirm-password"
                    name="confirm_password"
                    minlength="6"
                    autocomplete="new-password"
                    required
                >

                <p class="auth-help">Use at least 6 characters.</p>
                <button type="submit" class="btn-primary">Reset Password</button>
            </form>

            <p class="auth-switch"><a href="login.php">Back to Student Login</a></p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
