<?php
// ===================================================================
// register.php
// Shows the registration form and creates a new student account.
// ===================================================================

require_once __DIR__ . "/../includes/session_start.php";
require_once __DIR__ . "/../includes/theme_control.php";

// If already logged in, no need to register again
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

require_once "../config/database.php";
require_once "auth_functions.php";

$errors = array();

// Values to re-show in the form if validation fails
$name  = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name             = trim($_POST['name']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = validateRegistrationInput($name, $email, $password, $confirm_password);

    // Only check the database for a duplicate email if the basic
    // fields already passed validation
    if (count($errors) == 0 && emailAlreadyExists($conn, $email)) {
        $errors[] = "An account with this email already exists. Please login instead.";
    }

    if (count($errors) == 0) {
        $registered = registerUser($conn, $name, $email, $password);

        if ($registered) {
            header("Location: login.php?msg=registered");
            exit();
        } else {
            $errors[] = "Something went wrong while creating your account. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Routine Organizer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/system_ui.css">
</head>
<body class="page-register system-ui-page">
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
        <h1>Create Account</h1>
        <p class="auth-subtitle">Join Student Routine Organizer</p>

        <?php if (count($errors) > 0) { ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error) { ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <form method="POST" action="register.php">
            <label for="name">Full Name</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" minlength="6" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" minlength="6" required>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

</body>
</html>
