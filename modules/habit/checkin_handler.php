<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/habit_model.php';
require_once __DIR__ . '/habit_ui.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    habit_set_flash('error', 'Choose an active habit before checking in.');
    header('Location: index.php');
    exit;
}

$habitId = (int) ($_POST['habit_id'] ?? 0);
if ($habitId <= 0) {
    habit_set_flash('error', 'The selected habit is invalid. Please refresh the page and try again.');
    header('Location: index.php');
    exit;
}

$result = habit_check_in($conn, (int) $logged_in_user_id, $habitId);
habit_set_flash($result['type'], $result['message']);
header('Location: index.php');
exit;
