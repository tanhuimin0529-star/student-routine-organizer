<?php
// ===================================================================
// save_profile.php
// AJAX endpoint — saves a single fitness profile field
// Returns JSON: { "success": true/false, "message": "..." }
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "exercise_functions.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array("success" => false, "message" => "Invalid request method"));
    exit();
}

$field = isset($_POST['field']) ? trim($_POST['field']) : "";
$value = isset($_POST['value']) ? trim($_POST['value']) : "";

// Validate field is in allowed list
$allowed_fields = array(
    'height_cm', 'weight_kg', 'daily_calorie_goal', 'daily_step_goal',
    'current_steps', 'water_intake_ml', 'sleep_hours'
);

if (!in_array($field, $allowed_fields)) {
    echo json_encode(array("success" => false, "message" => "Invalid field"));
    exit();
}

// Validate value is numeric
if (!is_numeric($value)) {
    echo json_encode(array("success" => false, "message" => "Value must be a number"));
    exit();
}

$value = floatval($value);

// Field-specific validation
$errors = array();
if ($field === 'height_cm' && ($value < 50 || $value > 300)) {
    $errors[] = "Height must be between 50 and 300 cm";
}
if ($field === 'weight_kg' && ($value < 1 || $value > 500)) {
    $errors[] = "Weight must be between 1 and 500 kg";
}
if ($field === 'daily_calorie_goal' && ($value < 1 || $value > 10000)) {
    $errors[] = "Calorie goal must be between 1 and 10000";
}
if ($field === 'daily_step_goal' && ($value < 1 || $value > 100000)) {
    $errors[] = "Step goal must be between 1 and 100000";
}
if ($field === 'current_steps' && ($value < 0 || $value > 100000)) {
    $errors[] = "Steps must be between 0 and 100000";
}
if ($field === 'water_intake_ml' && ($value < 0 || $value > 10000)) {
    $errors[] = "Water intake must be between 0 and 10000 ml";
}
if ($field === 'sleep_hours' && ($value < 0 || $value > 24)) {
    $errors[] = "Sleep hours must be between 0 and 24";
}

if (!empty($errors)) {
    echo json_encode(array("success" => false, "message" => implode(", ", $errors)));
    exit();
}

$success = saveFitnessProfile($conn, $logged_in_user_id, $field, $value);

if ($success) {
    $labels = array(
        'height_cm'          => 'Height saved',
        'weight_kg'          => 'Weight saved',
        'daily_calorie_goal' => 'Calorie goal updated',
        'daily_step_goal'    => 'Step goal updated',
        'current_steps'      => 'Steps updated',
        'water_intake_ml'    => 'Water intake updated',
        'sleep_hours'        => 'Sleep hours saved'
    );
    $msg = isset($labels[$field]) ? $labels[$field] : "Saved!";
    echo json_encode(array("success" => true, "message" => $msg, "value" => $value));
} else {
    echo json_encode(array("success" => false, "message" => "Database error. Please try again."));
}
?>
