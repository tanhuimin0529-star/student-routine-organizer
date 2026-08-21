<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_content.php';

function returnToDiaryEdit($diary_id, $errors, $old = array()) {
    $_SESSION['diary_edit_errors'] = $errors;
    $_SESSION['diary_edit_old'] = $old;
    $_SESSION['diary_edit_id'] = $diary_id === false ? null : (int) $diary_id;

    $location = $diary_id === false
        ? 'edit.php'
        : 'edit.php?id=' . rawurlencode((string) $diary_id);

    header('Location: ' . $location, true, 303);
    exit();
}

function diaryEditPostString($key) {
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? trim($_POST[$key])
        : '';
}

function diaryEditJsonResponse($status, $payload) {
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php', true, 303);
    exit();
}

$submitted_id = diaryEditPostString('id');
$diary_id = filter_var(
    $submitted_id,
    FILTER_VALIDATE_INT,
    array('options' => array('min_range' => 1))
);

if (isset($_POST['diary_image_upload']) && $_POST['diary_image_upload'] === '1') {
    $submitted_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $session_token = isset($_SESSION['diary_edit_csrf_token'])
        ? $_SESSION['diary_edit_csrf_token']
        : '';

    if ($session_token === '' || !hash_equals($session_token, $submitted_token)) {
        diaryEditJsonResponse(403, array(
            'ok' => false,
            'error' => 'Your form session expired. Refresh the page and try again.'
        ));
    }

    if ($diary_id === false) {
        diaryEditJsonResponse(404, array(
            'ok' => false,
            'error' => 'Journal entry not found.'
        ));
    }

    $upload_entry = getDiaryEntryById($conn, $diary_id, $logged_in_user_id);
    if ($upload_entry === null) {
        diaryEditJsonResponse(404, array(
            'ok' => false,
            'error' => 'Journal entry not found.'
        ));
    }
    if ($upload_entry === false) {
        diaryEditJsonResponse(500, array(
            'ok' => false,
            'error' => 'The image could not be uploaded right now. Please try again.'
        ));
    }

    $upload_result = diaryImageStoreUploadedFile(
        isset($_FILES['diary_image']) ? $_FILES['diary_image'] : null,
        $logged_in_user_id
    );

    if (!$upload_result['valid']) {
        diaryEditJsonResponse($upload_result['status'], array(
            'ok' => false,
            'error' => $upload_result['error']
        ));
    }

    diaryEditJsonResponse(200, array(
        'ok' => true,
        'src' => $upload_result['src'],
        'alt' => $upload_result['alt']
    ));
}

$title = diaryEditPostString('title');
$submitted_content = diaryEditPostString('content');
$mood = diaryEditPostString('mood');
$entry_date = diaryEditPostString('entry_date');
$content_result = diaryContentPrepareForStorage($submitted_content, $logged_in_user_id);

$old = array(
    'title' => $title,
    'content' => $content_result['sanitized'],
    'mood' => $mood,
    'entry_date' => $entry_date
);

if ($diary_id === false) {
    returnToDiaryEdit(false, array('Journal entry not found.'), $old);
}

$submitted_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
$session_token = isset($_SESSION['diary_edit_csrf_token'])
    ? $_SESSION['diary_edit_csrf_token']
    : '';

if ($session_token === '' || !hash_equals($session_token, $submitted_token)) {
    returnToDiaryEdit(
        $diary_id,
        array('Your form session expired. Please review the entry and submit it again.'),
        $old
    );
}

$existing_entry = getDiaryEntryById($conn, $diary_id, $logged_in_user_id);

if ($existing_entry === null) {
    returnToDiaryEdit($diary_id, array('Journal entry not found.'), $old);
}

if ($existing_entry === false) {
    returnToDiaryEdit(
        $diary_id,
        array('The journal entry could not be updated right now. Please try again.'),
        $old
    );
}

$errors = array();
$valid_moods = array('Happy', 'Calm', 'Neutral', 'Sad', 'Stressed');

if ($title === '') {
    $errors[] = 'Title is required.';
}

if (!$content_result['valid']) {
    $errors[] = $content_result['error'];
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
    returnToDiaryEdit($diary_id, $errors, $old);
}

$updated = updateDiaryEntry(
    $conn,
    $diary_id,
    $logged_in_user_id,
    $title,
    $content_result['stored'],
    $mood,
    $entry_date
);

if ($updated === false) {
    returnToDiaryEdit(
        $diary_id,
        array('The journal entry could not be updated right now. Please try again.'),
        $old
    );
}

unset(
    $_SESSION['diary_edit_csrf_token'],
    $_SESSION['diary_edit_errors'],
    $_SESSION['diary_edit_old'],
    $_SESSION['diary_edit_id']
);

header('Location: view.php?id=' . rawurlencode((string) $diary_id), true, 303);
exit();
