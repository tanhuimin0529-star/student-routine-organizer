<?php
// ===================================================================
// hash_seed_passwords.php
//
// ONE-TIME USE ONLY.
//
// The sample rows inserted by database/exercise_schema.sql store
// "password123" as plain text (so the SQL file works everywhere).
// The real login system checks hashed passwords with
// password_verify(), so this script converts those three sample
// accounts to a proper password_hash() value.
//
// HOW TO USE:
//   1. Import database/exercise_schema.sql in phpMyAdmin first.
//   2. Visit this file once in your browser, e.g.
//      http://localhost/student-routine-organizer/authentication/hash_seed_passwords.php
//   3. Delete this file (or at least stop using it) afterwards —
//      running it again just re-hashes the same accounts, which is
//      harmless, but it should not stay on a live/shared server.
// ===================================================================

require_once "../config/database.php";

$sample_emails = array("ali@example.com", "siti@example.com", "admin@example.com");
$plain_password = "password123";
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

$updated_count = 0;

foreach ($sample_emails as $email) {
    $sql = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
    if (mysqli_stmt_execute($stmt)) {
        $updated_count++;
    }
    mysqli_stmt_close($stmt);
}

echo "<p>Done. Updated $updated_count sample account(s).</p>";
echo "<p>You can now login with any of the sample emails and the password: <strong>password123</strong></p>";
echo "<p><strong>Please delete this file now.</strong></p>";
?>
