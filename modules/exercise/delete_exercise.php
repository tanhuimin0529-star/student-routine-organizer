<?php
// ===================================================================
// delete_exercise.php
// Deletes an exercise record after the user confirms in the browser
// (the confirm() popup is triggered from exercise_list.php and
// exercise_details.php). This file only runs the actual delete.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "exercise_functions.php";

$exercise_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Make sure the record exists and belongs to this user before deleting
$exercise = getExerciseById($conn, $exercise_id, $logged_in_user_id);

if ($exercise) {
    deleteExerciseRecord($conn, $exercise_id, $logged_in_user_id);
}

// Whether it worked or the record was not found, go back to the list
header("Location: exercise_list.php?msg=deleted");
exit();
?>
