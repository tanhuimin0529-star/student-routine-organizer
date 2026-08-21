<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';

function diaryFavoriteReturnTarget($diary_id) {
    $requested_target = isset($_POST['return_to']) && is_string($_POST['return_to'])
        ? trim($_POST['return_to'])
        : '';

    if ($requested_target === 'index.php' || $requested_target === 'all_entries.php') {
        return $requested_target;
    }

    if ($diary_id !== false
        && preg_match('/\\Aview\\.php\\?id=([1-9][0-9]*)\\z/D', $requested_target, $matches) === 1
        && filter_var($matches[1], FILTER_VALIDATE_INT, array(
            'options' => array('min_range' => 1)
        )) === $diary_id
    ) {
        return 'view.php?id=' . rawurlencode((string) $diary_id);
    }

    return 'index.php';
}

function returnFromDiaryFavorite($diary_id, $message, $type = 'error') {
    $_SESSION['diary_favorite_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );

    header('Location: ' . diaryFavoriteReturnTarget($diary_id), true, 303);
    exit();
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnFromDiaryFavorite(false, 'Please use the journal favorite controls to update an entry.');
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
    returnFromDiaryFavorite(false, 'Journal entry is unavailable.');
}

$submitted_favorite = isset($_POST['favorite']) && is_string($_POST['favorite'])
    ? $_POST['favorite']
    : '';

if ($submitted_favorite !== '0' && $submitted_favorite !== '1') {
    returnFromDiaryFavorite($diary_id, 'Journal entry is unavailable.');
}

$submitted_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : '';
$session_token = isset($_SESSION['diary_favorite_csrf_token'])
    && is_string($_SESSION['diary_favorite_csrf_token'])
        ? $_SESSION['diary_favorite_csrf_token']
        : '';

if ($session_token === '' || !hash_equals($session_token, $submitted_token)) {
    returnFromDiaryFavorite($diary_id, 'Your form session expired. Please try again.');
}

// Rotate the token after a valid submission so it cannot be replayed.
unset($_SESSION['diary_favorite_csrf_token']);

// Verify availability without accepting an owner ID from the request.
$entry = getDiaryEntryById($conn, $diary_id, $logged_in_user_id);

if ($entry === false) {
    returnFromDiaryFavorite($diary_id, 'Favorite could not be updated right now. Please try again.');
}

if ($entry === null) {
    returnFromDiaryFavorite($diary_id, 'Journal entry is unavailable.');
}

$favorite = (int) $submitted_favorite;
$affected_rows = setDiaryEntryFavorite($conn, $diary_id, $logged_in_user_id, $favorite);

if ($affected_rows === false) {
    returnFromDiaryFavorite($diary_id, 'Favorite could not be updated right now. Please try again.');
}

$success_message = $favorite === 1
    ? 'Journal entry added to favorites.'
    : 'Journal entry removed from favorites.';

returnFromDiaryFavorite($diary_id, $success_message, 'success');
