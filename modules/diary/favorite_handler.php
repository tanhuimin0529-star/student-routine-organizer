<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_content.php';
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

function diaryFavoriteContainsSearch($value, $search_term) {
    if (function_exists('mb_stripos')) {
        return mb_stripos((string) $value, $search_term, 0, 'UTF-8') !== false;
    }

    return stripos((string) $value, $search_term) !== false;
}

function diaryFavoriteFilteredResultCount($conn, $user_id, $return_target) {
    $parts = parse_url($return_target);

    if (!is_array($parts)
        || !isset($parts['path'])
        || $parts['path'] !== 'all_entries.php'
    ) {
        return null;
    }

    $query = array();
    parse_str(isset($parts['query']) ? $parts['query'] : '', $query);

    if (!isset($query['favorites']) || $query['favorites'] !== '1') {
        return null;
    }

    $search_term = isset($query['search']) && is_string($query['search'])
        ? trim($query['search'])
        : '';
    $allowed_moods = array('Happy', 'Calm', 'Neutral', 'Sad', 'Stressed');
    $mood = isset($query['mood'])
        && is_string($query['mood'])
        && in_array($query['mood'], $allowed_moods, true)
            ? $query['mood']
            : '';
    $allowed_weather_filters = array('Sunny', 'Cloudy', 'Rainy', 'Windy', 'Stormy', 'not-set');
    $weather = isset($query['weather'])
        && is_string($query['weather'])
        && in_array($query['weather'], $allowed_weather_filters, true)
            ? $query['weather']
            : '';

    $entries = getDiaryEntriesForUser($conn, $user_id);
    if (!is_array($entries)) {
        return null;
    }

    $matching_count = 0;

    foreach ($entries as $candidate) {
        if (!isset($candidate['is_favorite'])
            || (int) $candidate['is_favorite'] !== 1
        ) {
            continue;
        }

        if ($mood !== ''
            && (!isset($candidate['mood']) || $candidate['mood'] !== $mood)
        ) {
            continue;
        }

        $candidate_weather = isset($candidate['weather']) && is_string($candidate['weather'])
            ? trim($candidate['weather'])
            : '';
        if (($weather === 'not-set' && $candidate_weather !== '')
            || ($weather !== '' && $weather !== 'not-set' && $candidate_weather !== $weather)
        ) {
            continue;
        }

        if ($search_term !== '') {
            $title = isset($candidate['title']) ? $candidate['title'] : '';
            $content = isset($candidate['content'])
                ? diaryContentToPlainText($candidate['content'])
                : '';

            if (!diaryFavoriteContainsSearch($title, $search_term)
                && !diaryFavoriteContainsSearch($content, $search_term)
            ) {
                continue;
            }
        }

        $matching_count++;
    }

    return $matching_count;
}

function returnFromDiaryFavorite(
    $diary_id,
    $message,
    $type = 'error',
    $favorite_state = null,
    $error_status = 400,
    $result_count = null
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

        if (is_int($result_count) && $result_count >= 0) {
            $payload['result_count'] = $result_count;
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
$return_target = diaryFavoriteReturnTarget($diary_id);
$result_count = diaryFavoriteIsAjaxRequest()
    ? diaryFavoriteFilteredResultCount($conn, $logged_in_user_id, $return_target)
    : null;

returnFromDiaryFavorite(
    $diary_id,
    $success_message,
    'success',
    $favorite,
    400,
    $result_count
);
