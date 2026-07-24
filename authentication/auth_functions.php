<?php
// ===================================================================
// auth_functions.php
// Business Logic Layer for the Authentication system.
// Holds validation rules and all database calls for
// registering, checking, and logging in users.
// ===================================================================

// -------------------------------------------------------------
// validateRegistrationInput()
// Checks the fields from the Register form.
// Returns an array of error messages. Empty array = no errors.
// -------------------------------------------------------------
function validateRegistrationInput($name, $email, $password, $confirm_password) {
    $errors = array();

    if (trim($name) == "") {
        $errors[] = "Please enter your name.";
    }

    if (trim($email) == "") {
        $errors[] = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Password and Confirm Password do not match.";
    }

    return $errors;
}


// -------------------------------------------------------------
// emailAlreadyExists()
// Checks if an email is already used by another account.
// -------------------------------------------------------------
function emailAlreadyExists($conn, $email) {

    $sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ? true : false;
}


// -------------------------------------------------------------
// registerUser()
// Hashes the password and inserts a new user with role "student".
// (Admin accounts are created directly in the database by staff,
// not through the public registration form.)
// -------------------------------------------------------------
function registerUser($conn, $name, $email, $password) {

    // Never store the plain password. password_hash() turns it into
    // a secure, one-way hash that we can later check with password_verify().
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed_password);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// findUserByEmail()
// Gets one user row by email (used during login).
// -------------------------------------------------------------
function findUserByEmail($conn, $email) {

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $user; // returns null if no user has this email
}
?>
