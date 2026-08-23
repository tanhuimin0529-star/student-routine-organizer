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
</head>
<body class="page-register">
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
    <svg class="float-icon icon-cap" viewBox="0 0 24 24" fill="currentColor">
        <polygon points="12,3 22,8 12,13 2,8" />
        <path d="M6 10.5 V15 C6 17 9 18.5 12 18.5 C15 18.5 18 17 18 15 V10.5" fill="none" stroke="currentColor" stroke-width="1.4" />
        <line x1="22" y1="8" x2="22" y2="14" stroke="currentColor" stroke-width="1.4" />
    </svg>
    <svg class="float-icon icon-book" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <path d="M2 5 C5 3.5 9 3.5 12 5 C15 3.5 19 3.5 22 5 V18 C19 16.5 15 16.5 12 18 C9 16.5 5 16.5 2 18 Z" />
        <line x1="12" y1="5" x2="12" y2="18" />
    </svg>
    <svg class="float-icon icon-pencil" viewBox="0 0 24 24" fill="currentColor">
        <rect x="10.5" y="2" width="3" height="16" rx="1" transform="rotate(20 12 10)" />
        <polygon points="9,17 15,17 12,22" transform="rotate(20 12 10)" />
    </svg>
    <svg class="float-icon icon-cup" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <path d="M4 8 H17 V15 C17 18 14.5 20 10.5 20 C6.5 20 4 18 4 15 Z" />
        <path d="M17 9.5 H19 C20.5 9.5 21.5 11 20.5 12.5 C20 13.3 19 13.5 17.5 13.3" />
        <line x1="8" y1="4" x2="8" y2="6.5" />
        <line x1="12" y1="3.5" x2="12" y2="6" />
    </svg>
    <svg class="float-icon icon-apple" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 8.5 C9 6.5 5 8 5 12.5 C5 16.5 8 20 11 20 C11.7 20 12.3 19.8 13 19.8 C13.7 19.8 14.3 20 15 20 C18 20 21 16.5 21 12.5 C21 8 17 6.5 14 8.5" />
        <path d="M12 8.5 C12 6.5 13 5 15 4.5" fill="none" stroke="currentColor" stroke-width="1.4" />
    </svg>
    <svg class="float-icon icon-bulb" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <circle cx="12" cy="10" r="6" />
        <line x1="9.5" y1="19" x2="14.5" y2="19" />
        <line x1="10" y1="21" x2="14" y2="21" />
        <line x1="12" y1="16" x2="12" y2="19" />
    </svg>
    <svg class="float-icon icon-backpack" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
        <rect x="5" y="8" width="14" height="13" rx="3" />
        <path d="M9 8 V5 C9 3.5 10.3 2.5 12 2.5 C13.7 2.5 15 3.5 15 5 V8" />
        <line x1="9" y1="13" x2="15" y2="13" />
    </svg>
</div>

<div class="auth-wrapper">
    <div class="auth-card">
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
