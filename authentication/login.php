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
</head>
<body class="page-login">
<?php renderGlobalThemeControl(true); ?>

<!-- Morphing blob background -->
<div class="morph-bg">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="blob blob-5"></div>
</div>

<!-- Decorative floating student-life icons -->
<div class="bg-scene">

    <!-- Graduation cap -->
    <svg class="float-icon icon-cap" viewBox="0 0 24 24" fill="currentColor">
        <polygon points="12,3 22,8 12,13 2,8" />
        <path d="M6 10.5 V15 C6 17 9 18.5 12 18.5 C15 18.5 18 17 18 15 V10.5" fill="none" stroke="currentColor" stroke-width="1.4" />
        <line x1="22" y1="8" x2="22" y2="14" stroke="currentColor" stroke-width="1.4" />
    </svg>

    <!-- Open book -->
    <svg class="float-icon icon-book" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <path d="M2 5 C5 3.5 9 3.5 12 5 C15 3.5 19 3.5 22 5 V18 C19 16.5 15 16.5 12 18 C9 16.5 5 16.5 2 18 Z" />
        <line x1="12" y1="5" x2="12" y2="18" />
    </svg>

    <!-- Pencil -->
    <svg class="float-icon icon-pencil" viewBox="0 0 24 24" fill="currentColor">
        <rect x="10.5" y="2" width="3" height="16" rx="1" transform="rotate(20 12 10)" />
        <polygon points="9,17 15,17 12,22" transform="rotate(20 12 10)" />
    </svg>

    <!-- Coffee cup -->
    <svg class="float-icon icon-cup" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <path d="M4 8 H17 V15 C17 18 14.5 20 10.5 20 C6.5 20 4 18 4 15 Z" />
        <path d="M17 9.5 H19 C20.5 9.5 21.5 11 20.5 12.5 C20 13.3 19 13.5 17.5 13.3" />
        <line x1="8" y1="4" x2="8" y2="6.5" />
        <line x1="12" y1="3.5" x2="12" y2="6" />
    </svg>

    <!-- Apple -->
    <svg class="float-icon icon-apple" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 8.5 C9 6.5 5 8 5 12.5 C5 16.5 8 20 11 20 C11.7 20 12.3 19.8 13 19.8 C13.7 19.8 14.3 20 15 20 C18 20 21 16.5 21 12.5 C21 8 17 6.5 14 8.5" />
        <path d="M12 8.5 C12 6.5 13 5 15 4.5" fill="none" stroke="currentColor" stroke-width="1.4" />
    </svg>

    <!-- Light bulb (idea) -->
    <svg class="float-icon icon-bulb" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <circle cx="12" cy="10" r="6" />
        <line x1="9.5" y1="19" x2="14.5" y2="19" />
        <line x1="10" y1="21" x2="14" y2="21" />
        <line x1="12" y1="16" x2="12" y2="19" />
    </svg>

    <!-- Backpack -->
    <svg class="float-icon icon-backpack" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <rect x="5" y="8" width="14" height="13" rx="3" />
        <path d="M9 8 V5 C9 3.5 10.3 2.5 12 2.5 C13.7 2.5 15 3.5 15 5 V8" />
        <line x1="9" y1="13" x2="15" y2="13" />
    </svg>

</div>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Welcome Back</h1>
        <p class="auth-subtitle">Login to Student Routine Organizer</p>

        <?php if ($success_message != "") { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
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

</body>
</html>
