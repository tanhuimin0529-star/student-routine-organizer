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
require_once __DIR__ . '/admin_password_reset_functions.php';
require_once __DIR__ . '/password_reset_mailer.php';

if (empty($_SESSION['admin_forgot_password_csrf_token'])) {
    $_SESSION['admin_forgot_password_csrf_token'] = bin2hex(random_bytes(32));
}

$email = '';
$errors = array();
$generic_message = '';
$demo_reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) && is_string($_POST['email'])
        ? trim($_POST['email'])
        : '';
    $submitted_csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $session_csrf = isset($_SESSION['admin_forgot_password_csrf_token'])
        && is_string($_SESSION['admin_forgot_password_csrf_token'])
            ? $_SESSION['admin_forgot_password_csrf_token']
            : '';

    if (
        $session_csrf === ''
        || $submitted_csrf === ''
        || !hash_equals($session_csrf, $submitted_csrf)
    ) {
        $errors[] = 'Your form session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $result = createAdminPasswordResetToken($conn, $email);
        $generic_message = 'If an administrator account with that email exists, a password reset link has been sent.';

        if ($result['success'] && is_string($result['token'])) {
            sendAdminPasswordResetEmail($email, $result['token']);
        }

        if (
            $result['success']
            && is_string($result['token'])
            && passwordResetIsLocalDevelopment()
        ) {
            $demo_reset_link = 'admin_reset_password.php?token=' . rawurlencode($result['token']);
        }

        $email = '';
        $_SESSION['admin_forgot_password_csrf_token'] = bin2hex(random_bytes(32));
    }
}

function adminForgotPasswordEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Forgot Password - Student Routine Organizer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
</head>
<body class="page-forgot-password">
<?php renderGlobalThemeControl(true); ?>

<div class="morph-bg" aria-hidden="true">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="blob blob-5"></div>
</div>

<main class="auth-wrapper">
    <section class="auth-card auth-card--recovery" aria-labelledby="admin-forgot-password-heading">
        <h1 id="admin-forgot-password-heading">Admin Password Recovery</h1>
        <p class="auth-subtitle">Enter your administrator email to receive a secure reset link.</p>

        <?php if ($generic_message !== ''): ?>
            <div class="alert alert-success" role="status">
                <?php echo adminForgotPasswordEscape($generic_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo adminForgotPasswordEscape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($demo_reset_link !== ''): ?>
            <aside class="development-reset-link" aria-label="Local development admin reset link">
                <strong>Local XAMPP admin demo link</strong>
                <p>Development fallback only. This link is shown only on localhost.</p>
                <a href="<?php echo adminForgotPasswordEscape($demo_reset_link); ?>">Open Admin Password Reset</a>
            </aside>
        <?php endif; ?>

        <form method="post" action="admin_forgot_password.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo adminForgotPasswordEscape($_SESSION['admin_forgot_password_csrf_token']); ?>"
            >

            <label for="admin-forgot-email">Admin Email</label>
            <input
                type="email"
                id="admin-forgot-email"
                name="email"
                maxlength="100"
                autocomplete="email"
                value="<?php echo adminForgotPasswordEscape($email); ?>"
                required
            >

            <button type="submit" class="btn-primary">Send Admin Reset Link</button>
        </form>

        <p class="auth-switch"><a href="admin_login.php">Back to Admin Login</a></p>
    </section>
</main>
</body>
</html>
