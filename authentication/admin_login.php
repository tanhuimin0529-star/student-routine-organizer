<?php
// Separate login entry point for existing users whose role is "admin".
require_once __DIR__ . "/../includes/session_start.php";
require_once __DIR__ . "/../includes/theme_control.php";

// Preserve the existing session destinations for users who are already signed in.
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../dashboard/dashboard.php");
    }
    exit();
}

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/auth_functions.php";

$errors = array();
$email = "";

$success_message = "";
if (isset($_GET['msg']) && $_GET['msg'] === 'password_reset') {
    $success_message = "Your administrator password has been reset successfully. Please log in.";
}

$session_message = "";
if (isset($_GET['msg']) && $_GET['msg'] === 'session_expired') {
    $session_message = "Your session has expired due to inactivity. Please log in again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? $_POST['password'] : "";

    if ($email === "" || trim($password) === "") {
        $errors[] = "Please enter both email and password.";
    } else {
        $user = findUserByEmail($conn, $email);

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = "Incorrect email or password.";
        } elseif (!isset($user['role']) || $user['role'] !== 'admin') {
            $errors[] = "This account does not have administrator access.";
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id']            = $user['user_id'];
            $_SESSION['name']               = $user['name'];
            $_SESSION['role']               = $user['role'];
            $_SESSION['auth_last_activity'] = time();

            header("Location: ../admin/index.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Student Routine Organizer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
</head>
<body class="page-login">
<?php renderGlobalThemeControl(true); ?>

<div class="morph-bg">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="blob blob-5"></div>
</div>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Admin Login</h1>
        <p class="auth-subtitle">Administrator access only</p>

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

        <form method="POST" action="admin_login.php">
            <label for="email">Admin Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <p class="auth-forgot-link"><a href="admin_forgot_password.php">Forgot Password?</a></p>

            <button type="submit" class="btn-primary">Login as Admin</button>
        </form>

        <p class="auth-switch">Student user? <a href="login.php">Return to Student Login</a></p>
    </div>
</div>

</body>
</html>
