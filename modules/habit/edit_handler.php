<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/habit_model.php';
require_once __DIR__ . '/habit_ui.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    habit_set_flash('error', 'Use the edit form to update a habit.');
    header('Location: index.php#manage');
    exit;
}

$habitId = (int) ($_POST['habit_id'] ?? 0);
if ($habitId <= 0 || !habit_get_habit_by_id($conn, $habitId, (int) $logged_in_user_id)) {
    habit_set_flash('error', 'That habit was not found.');
    header('Location: index.php#manage');
    exit;
}

$input = [
    'habit_name' => trim((string) ($_POST['habit_name'] ?? '')),
    'habit_description' => trim((string) ($_POST['habit_description'] ?? '')),
    'category_id' => (int) ($_POST['category_id'] ?? 0),
    'target_frequency' => (int) ($_POST['target_frequency'] ?? 0),
    'frequency_type' => (string) ($_POST['frequency_type'] ?? ''),
    'start_date' => (string) ($_POST['start_date'] ?? ''),
    'status' => (string) ($_POST['status'] ?? ''),
];

$errors = habit_validate_payload($conn, $input);
if ($errors) {
    $_SESSION['habit_edit_errors'] = $errors;
    $_SESSION['habit_edit_data'] = $input;
    header('Location: edit.php?habit_id=' . $habitId);
    exit;
}

if (!habit_update($conn, $habitId, (int) $logged_in_user_id, $input)) {
    $_SESSION['habit_edit_errors'] = ['save' => 'The changes could not be saved. Please try again.'];
    $_SESSION['habit_edit_data'] = $input;
    header('Location: edit.php?habit_id=' . $habitId);
    exit;
}

habit_set_flash('success', 'Habit updated successfully.');
header('Location: index.php#manage');
exit;
