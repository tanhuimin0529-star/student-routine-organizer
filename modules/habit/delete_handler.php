<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/habit_model.php';
require_once __DIR__ . '/habit_ui.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    habit_set_flash('error', 'Use the delete button beside a habit to remove it.');
    header('Location: index.php#manage');
    exit;
}

$habitId = (int) ($_POST['habit_id'] ?? 0);
if ($habitId <= 0 || !habit_get_habit_by_id($conn, $habitId, (int) $logged_in_user_id)) {
    habit_set_flash('error', 'That habit was not found.');
    header('Location: index.php#manage');
    exit;
}

if (!habit_delete($conn, $habitId, (int) $logged_in_user_id)) {
    habit_set_flash('error', 'The habit could not be deleted. Please try again.');
    header('Location: index.php#manage');
    exit;
}

habit_set_flash('success', 'Habit and its check-in history were deleted.');
header('Location: index.php#manage');
exit;
