<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';

function diaryDeleteReturnTarget() {
    $requested_target = isset($_POST['return_to']) && is_string($_POST['return_to'])
        ? $_POST['return_to']
        : '';

    return $requested_target === 'all_entries.php'
        ? 'all_entries.php'
        : 'index.php';
}

function returnFromDiaryDelete($message, $type = 'error') {
    $_SESSION['diary_delete_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );

    header('Location: ' . diaryDeleteReturnTarget(), true, 303);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnFromDiaryDelete('Journal entry could not be deleted.');
}

$submitted_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
$session_token = isset($_SESSION['diary_delete_csrf_token'])
    ? $_SESSION['diary_delete_csrf_token']
    : '';

if ($session_token === '' || !hash_equals($session_token, $submitted_token)) {
    returnFromDiaryDelete('Your form session expired. Please try again.');
}

$submitted_id = isset($_POST['diary_id']) && is_string($_POST['diary_id'])
    ? trim($_POST['diary_id'])
    : '';
$diary_id = filter_var(
    $submitted_id,
    FILTER_VALIDATE_INT,
    array('options' => array('min_range' => 1))
);

if ($diary_id === false) {
    returnFromDiaryDelete('Journal entry could not be deleted.');
}

// Rotate the token after every valid submission so it cannot be replayed.
unset($_SESSION['diary_delete_csrf_token']);

// Confirm ownership before attempting the user-scoped model deletion.
$entry = getDiaryEntryById($conn, $diary_id, $logged_in_user_id);

if ($entry === false) {
    returnFromDiaryDelete('Journal entry could not be deleted right now. Please try again.');
}

if ($entry === null) {
    returnFromDiaryDelete('Journal entry could not be deleted.');
}

$deleted_rows = deleteDiaryEntry($conn, $diary_id, $logged_in_user_id);

if ($deleted_rows !== 1) {
    returnFromDiaryDelete('Journal entry could not be deleted right now. Please try again.');
}

returnFromDiaryDelete('Journal entry deleted successfully.', 'success');
