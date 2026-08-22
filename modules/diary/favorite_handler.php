<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_navigation.php';

function diaryFavoriteReturnTarget($diary_id) {
    $requested_target = isset($_POST['return_to']) && is_string($_POST['return_to'])
        ? $_POST['return_to']
        : '';

    return diaryNavigationSanitizeActionTarget($requested_target, $diary_id);
}

function diaryFavoriteIsAjaxRequest() {
    $requested_with = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && is_string($_SERVER['HTTP_X_REQUESTED_WITH'])
            ? trim($_SERVER['HTTP_X_REQUESTED_WITH'])
            : '';
    $accept = isset($_SERVER['HTTP_ACCEPT']) && is_string($_SERVER['HTTP_ACCEPT'])
        ? $_SERVER['HTTP_ACCEPT']
        : '';

    return strcasecmp($requested_with, 'XMLHttpRequest') === 0
        || stripos($accept, 'application/json') !== false;
}

function returnFromDiaryFavorite(
    $diary_id,
    $message,
    $type = 'error',
    $favorite_state = null,
    $error_status = 400
) {
    $success = $type === 'success';

    if (diaryFavoriteIsAjaxRequest()) {
        http_response_code($success ? 200 : (int) $error_status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');

        $payload = array(
            'success' => $success,
            'message' => (string) $message
        );

        if ($favorite_state === 0 || $favorite_state === 1) {
            $payload['favorite'] = $favorite_state;
        }

        if (isset($_SESSION['diary_favorite_csrf_token'])
            && is_string($_SESSION['diary_favorite_csrf_token'])
            && $_SESSION['diary_favorite_csrf_token'] !== ''
        ) {
            $payload['csrf_token'] = $_SESSION['diary_favorite_csrf_token'];
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    $_SESSION['diary_favorite_flash'] = array(
        'type' => $success ? 'success' : 'error',
        'message' => (string) $message
    );

    header('Location: ' . diaryFavoriteReturnTarget($diary_id), true, 303);
    exit();
}

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (diaryFavoriteIsAjaxRequest()) {
        header('Allow: POST');
    }
    returnFromDiaryFavorite(
        false,
        'Please use the journal favorite controls to update an entry.',
        'error',
        null,
        405
    );
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
    returnFromDiaryFavorite(
        $diary_id,
        'Your form session expired. Please try again.',
        'error',
        null,
        403
    );
}

// Rotate the token after a valid submission and return it to AJAX clients.
$_SESSION['diary_favorite_csrf_token'] = bin2hex(random_bytes(32));

// Verify availability without accepting an owner ID from the request.
$entry = getDiaryEntryById($conn, $diary_id, $logged_in_user_id);

if ($entry === false) {
    returnFromDiaryFavorite(
        $diary_id,
        'Favorite could not be updated right now. Please try again.',
        'error',
        null,
        500
    );
}

if ($entry === null) {
    returnFromDiaryFavorite(
        $diary_id,
        'Journal entry is unavailable.',
        'error',
        null,
        404
    );
}

$favorite = (int) $submitted_favorite;
$affected_rows = setDiaryEntryFavorite($conn, $diary_id, $logged_in_user_id, $favorite);

if ($affected_rows === false) {
    returnFromDiaryFavorite(
        $diary_id,
        'Favorite could not be updated right now. Please try again.',
        'error',
        null,
        500
    );
}

$success_message = $favorite === 1
    ? 'Journal entry added to favorites.'
    : 'Journal entry removed from favorites.';

returnFromDiaryFavorite($diary_id, $success_message, 'success', $favorite);
