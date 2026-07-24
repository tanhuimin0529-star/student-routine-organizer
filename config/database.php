<?php
// ===================================================================
// Database Connection File
// This is the "Database Layer" of the three tier architecture.
// Every page that needs the database will include this file.
// ===================================================================

// Change these settings if your XAMPP setup is different
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "student_routine_organizer";

// Create the connection using mysqli (procedural style)
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection and show a friendly message instead of the real
// MySQL error, so we do not expose database details to the user
if (!$conn) {
    die("Sorry, we could not connect to the database right now. Please try again later.");
}
?>
