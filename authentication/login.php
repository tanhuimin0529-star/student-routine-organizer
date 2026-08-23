<?php
// ===================================================================
// login.php
// Shows the login form and starts a session for a valid user.
// Styling lives in ../assets/css/auth.css (shared with register.php).
// ===================================================================

require_once __DIR__ . "/../includes/session_start.php";
require_once __DIR__ . "/../includes/theme_control.php";
require_once __DIR__ . "/../includes/cookie_consent.php";

// If already logged in, return each role to its own area.
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../dashboard/dashboard.php");
    }
    exit();
}

require_once "../config/database.php";
require_once "auth_functions.php";

$errors = array();

// -------------------------------------------------------------
// Cookie feature: remember the email address on this browser
// so the user does not have to retype it every time
// -------------------------------------------------------------
$email = optionalCookiesAllowed() && isset($_COOKIE['remembered_email']) ? $_COOKIE['remembered_email'] : "";

// Message shown after a successful registration
$success_message = "";
if (isset($_GET['msg']) && $_GET['msg'] == "registered") {
    $success_message = "Account created successfully. Please login.";
} elseif (isset($_GET['msg']) && $_GET['msg'] == "loggedout") {
    $success_message = "You have been logged out.";
} elseif (isset($_GET['msg']) && $_GET['msg'] === "password_reset") {
    $success_message = "Your password has been reset successfully. Please log in.";
}

$session_message = "";
if (isset($_GET['msg']) && $_GET['msg'] === "session_expired") {
    $session_message = "Your session has expired due to inactivity. Please log in again.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember_me']);

    if (trim($email) == "" || trim($password) == "") {
        $errors[] = "Please enter both email and password.";
    } else {

        $user = findUserByEmail($conn, $email);

        // password_verify() compares the typed password against the
        // hashed password stored in the database
        if ($user && password_verify($password, $user['password'])) {
            if (!isset($user['role']) || $user['role'] !== 'student') {
                $errors[] = "Administrator accounts must use the Admin Login page.";
            } else {
                // Replace the pre-login session id after authentication.
                session_regenerate_id(true);

                // Save the important details in the session
                $_SESSION['user_id']            = $user['user_id'];
                $_SESSION['name']               = $user['name'];
                $_SESSION['role']               = $user['role'];
                $_SESSION['auth_last_activity'] = time();

                // Remember the email only after optional cookies are accepted.
                if ($remember) {
                    if (optionalCookiesAllowed()) {
                        setOptionalPreferenceCookie('remembered_email', $email);
                    } elseif (getCookieConsentChoice() === null) {
                        // Keep the request server-side until the Dashboard choice.
                        $_SESSION['pending_remembered_email'] = $email;
                    } else {
                        unset($_SESSION['pending_remembered_email']);
                        clearOptionalPreferenceCookie('remembered_email');
                    }
                } else {
                    unset($_SESSION['pending_remembered_email']);
                    clearOptionalPreferenceCookie('remembered_email');
                }

                header("Location: ../dashboard/dashboard.php");
                exit();
            }
        } else {
            $errors[] = "Incorrect email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Routine Organizer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/system_ui.css">
</head>
<body class="page-login system-ui-page">
<?php renderGlobalThemeControl(true); ?>

<!-- Morphing blob background -->
<div class="morph-bg">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="blob blob-5"></div>
</div>


<div class="auth-wrapper auth-login-wrapper">
    <header class="auth-page-brand">
        <h1>Student Routine Organizer</h1>
        <p>Manage your daily routine in one place.</p>
    </header>
    <div class="auth-card auth-login-card">
        <h1>Welcome Back</h1>
        <p class="auth-subtitle">Login to your Student Routine Organizer account</p>

        <?php if ($success_message != "") { ?>
            <div class="alert alert-success" data-auth-auto-dismiss="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php } ?>

        <?php if ($session_message != "") { ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($session_message); ?></div>
        <?php } ?>

        <?php if (count($errors) > 0) { ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error) { ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <form method="POST" action="login.php">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <p class="auth-forgot-link"><a href="forgot_password.php">Forgot Password?</a></p>

            <label class="checkbox-label">
                <input type="checkbox" name="remember_me" <?php if ($email != "") echo "checked"; ?>>
                Remember my email on this browser
            </label>

            <button type="submit" class="btn-primary">Login</button>
        </form>

        <p class="auth-switch">Don't have an account? <a href="register.php">Register here</a></p>
        <p class="auth-switch">Administrator? <a href="admin_login.php">Admin Login</a></p>
    </div>
</div>

<script src="../assets/js/auth.js"></script>
</body>
</html>
