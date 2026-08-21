<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';

function returnToDiaryIndex() {
    header('Location: index.php', true, 303);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnToDiaryIndex();
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
    returnToDiaryIndex();
}

$submitted_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
$session_token = isset($_SESSION['diary_delete_csrf_token'])
    ? $_SESSION['diary_delete_csrf_token']
    : '';

if ($session_token === '' || !hash_equals($session_token, $submitted_token)) {
    returnToDiaryIndex();
}

// Rotate the token after every valid submission so it cannot be replayed.
unset($_SESSION['diary_delete_csrf_token']);

// Confirm ownership before attempting the user-scoped model deletion.
$entry = getDiaryEntryById($conn, $diary_id, $logged_in_user_id);

if (!is_array($entry)) {
    returnToDiaryIndex();
}

$deleted_rows = deleteDiaryEntry($conn, $diary_id, $logged_in_user_id);

if ($deleted_rows !== 1) {
    returnToDiaryIndex();
}

returnToDiaryIndex();
