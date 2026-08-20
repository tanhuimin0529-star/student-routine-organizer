<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/habit_model.php';
require_once __DIR__ . '/habit_ui.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    habit_set_flash('error', 'Use the create habit form to add a routine.');
    header('Location: add.php');
    exit;
}

$input = [
    'habit_name' => trim((string) ($_POST['habit_name'] ?? '')),
    'habit_description' => trim((string) ($_POST['habit_description'] ?? '')),
    'category_id' => (int) ($_POST['category_id'] ?? 0),
    'target_frequency' => (int) ($_POST['target_frequency'] ?? 0),
    'frequency_type' => (string) ($_POST['frequency_type'] ?? ''),
    'start_date' => (string) ($_POST['start_date'] ?? ''),
    'status' => 'Active',
];

$errors = habit_validate_payload($conn, $input, true);
if ($errors) {
    $_SESSION['habit_form_errors'] = $errors;
    $_SESSION['habit_form_data'] = $input;
    header('Location: add.php');
    exit;
}

if (!habit_create($conn, (int) $logged_in_user_id, $input)) {
    $_SESSION['habit_form_errors'] = ['save' => 'The habit could not be saved. Please try again.'];
    $_SESSION['habit_form_data'] = $input;
    header('Location: add.php');
    exit;
}

habit_set_flash('success', 'Habit created. It is ready for today’s check-in list.');
header('Location: index.php');
exit;
