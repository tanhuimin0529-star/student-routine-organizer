<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';

function returnToDiaryAdd($errors, $old = array()) {
    $_SESSION['diary_add_errors'] = $errors;
    $_SESSION['diary_add_old'] = $old;
    header('Location: add.php', true, 303);
    exit();
}

function diaryPostString($key) {
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? trim($_POST[$key])
        : '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnToDiaryAdd(array('Please use the journal entry form to add an entry.'));
}

$title = diaryPostString('title');
$content = diaryPostString('content');
$mood = diaryPostString('mood');
$entry_date = diaryPostString('entry_date');

$old = array(
    'title' => $title,
    'content' => $content,
    'mood' => $mood,
    'entry_date' => $entry_date
);

$submitted_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
$session_token = isset($_SESSION['diary_add_csrf_token']) ? $_SESSION['diary_add_csrf_token'] : '';

if ($session_token === '' || !is_string($submitted_token) || !hash_equals($session_token, $submitted_token)) {
    returnToDiaryAdd(
        array('Your form session expired. Please review the entry and submit it again.'),
        $old
    );
}

$errors = array();
$valid_moods = array('Happy', 'Calm', 'Neutral', 'Sad', 'Stressed');

if ($title === '') {
    $errors[] = 'Title is required.';
}

if ($content === '') {
    $errors[] = 'Content is required.';
}

if (!in_array($mood, $valid_moods, true)) {
    $errors[] = 'Please select a valid mood.';
}

$date = DateTime::createFromFormat('!Y-m-d', $entry_date);
$date_errors = DateTime::getLastErrors();
$date_has_errors = is_array($date_errors)
    && ($date_errors['warning_count'] > 0 || $date_errors['error_count'] > 0);

if (!$date || $date_has_errors || $date->format('Y-m-d') !== $entry_date) {
    $errors[] = 'Please enter a valid entry date.';
}

if (!empty($errors)) {
    returnToDiaryAdd($errors, $old);
}

// The owner comes only from the authenticated session guard.
$diary_id = createDiaryEntry(
    $conn,
    $logged_in_user_id,
    $title,
    $content,
    $mood,
    $entry_date
);

if ($diary_id === false) {
    returnToDiaryAdd(
        array('The journal entry could not be saved right now. Please try again.'),
        $old
    );
}

unset($_SESSION['diary_add_csrf_token'], $_SESSION['diary_add_errors'], $_SESSION['diary_add_old']);
header('Location: index.php', true, 303);
exit();
