<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_content.php';
require_once __DIR__ . '/diary_media_cleanup.php';

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

function diaryAddValidatedUploadBatchToken() {
    $batch_token = diaryPostString('upload_batch_token');

    if (
        preg_match('/\A[a-f0-9]{64}\z/D', $batch_token) !== 1
        || !isset($_SESSION['diary_upload_batches'][$batch_token])
        || !is_array($_SESSION['diary_upload_batches'][$batch_token])
    ) {
        return '';
    }

    return $batch_token;
}

function diaryAddRegisterUploadedPath($batch_token, $public_path, $user_id, $is_drawing) {
    $safe_path = $is_drawing
        ? diaryContentAllowedDrawingSrc($public_path, $user_id)
        : diaryContentAllowedImageSrc($public_path, $user_id);

    if ($safe_path === '') {
        return '';
    }

    if (!in_array($safe_path, $_SESSION['diary_upload_batches'][$batch_token], true)) {
        $_SESSION['diary_upload_batches'][$batch_token][] = $safe_path;
    }

    return $safe_path;
}

function diaryAddCleanupUploadBatchAfterCreate(
    $conn,
    $batch_token,
    $created_diary_id,
    $stored_content,
    $user_id
) {
    try {
        $registered_paths = isset($_SESSION['diary_upload_batches'][$batch_token])
            && is_array($_SESSION['diary_upload_batches'][$batch_token])
                ? $_SESSION['diary_upload_batches'][$batch_token]
                : array();
        $safe_batch_paths = array();

        foreach ($registered_paths as $registered_path) {
            $safe_path = diaryMediaValidatePublicPath($registered_path, $user_id);
            if ($safe_path !== '') {
                $safe_batch_paths[$safe_path] = true;
            }
        }

        $saved_path_lookup = array_fill_keys(
            diaryMediaExtractPathsFromRichContent($stored_content, $user_id),
            true
        );
        $unused_paths = array();

        foreach (array_keys($safe_batch_paths) as $safe_batch_path) {
            if (!isset($saved_path_lookup[$safe_batch_path])) {
                $unused_paths[] = $safe_batch_path;
            }
        }

        if (!empty($unused_paths)) {
            $user_entries = getDiaryEntriesForUser($conn, $user_id);

            // Fail closed when existing references cannot be checked.
            if (is_array($user_entries)) {
                $other_entry_path_lookup = array();

                foreach ($user_entries as $user_entry) {
                    if (
                        !isset($user_entry['diary_id'])
                        || (int) $user_entry['diary_id'] === (int) $created_diary_id
                        || !isset($user_entry['content'])
                    ) {
                        continue;
                    }

                    $other_paths = diaryMediaExtractPathsFromRichContent(
                        $user_entry['content'],
                        $user_id
                    );

                    foreach ($other_paths as $other_path) {
                        $other_entry_path_lookup[$other_path] = true;
                    }
                }

                foreach ($unused_paths as $unused_path) {
                    if (!isset($other_entry_path_lookup[$unused_path])) {
                        diaryMediaDeleteValidatedFile($unused_path, $user_id);
                    }
                }
            }
        }
    } catch (Throwable $cleanup_error) {
        // Cleanup is best-effort and must never undo a successful diary save.
    }

    unset($_SESSION['diary_upload_batches'][$batch_token]);
}

function diaryAddJsonResponse($status, $payload) {
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnToDiaryAdd(array('Please use the journal entry form to add an entry.'));
}

$is_image_upload = isset($_POST['diary_image_upload']) && $_POST['diary_image_upload'] === '1';
$is_drawing_upload = isset($_POST['diary_drawing_upload']) && $_POST['diary_drawing_upload'] === '1';

if ($is_image_upload || $is_drawing_upload) {
    $submitted_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $session_token = isset($_SESSION['diary_add_csrf_token'])
        ? $_SESSION['diary_add_csrf_token']
        : '';

    if ($session_token === '' || !hash_equals($session_token, $submitted_token)) {
        diaryAddJsonResponse(403, array(
            'ok' => false,
            'error' => 'Your form session expired. Refresh the page and try again.'
        ));
    }

    $upload_batch_token = diaryAddValidatedUploadBatchToken();
    if ($upload_batch_token === '') {
        diaryAddJsonResponse(403, array(
            'ok' => false,
            'error' => 'Your upload session expired. Refresh the page and try again.'
        ));
    }
    $upload_result = diaryImageStoreUploadedFile(
        isset($_FILES['diary_image']) ? $_FILES['diary_image'] : null,
        $logged_in_user_id,
        $is_drawing_upload ? 'Journal drawing' : 'Journal image',
        $is_drawing_upload ? 'image/png' : ''
    );

    if (!$upload_result['valid']) {
        diaryAddJsonResponse($upload_result['status'], array(
            'ok' => false,
            'error' => $upload_result['error']
        ));
    }

    $safe_uploaded_path = diaryAddRegisterUploadedPath(
        $upload_batch_token,
        $upload_result['src'],
        $logged_in_user_id,
        $is_drawing_upload
    );
    if ($safe_uploaded_path === '') {
        diaryAddJsonResponse(500, array(
            'ok' => false,
            'error' => 'The image could not be registered right now. Please try again.'
        ));
    }
    diaryAddJsonResponse(200, array(
        'ok' => true,
        'src' => $safe_uploaded_path,
        'alt' => $upload_result['alt']
    ));
}

$title = diaryPostString('title');
$submitted_content = diaryPostString('content');
$mood = diaryPostString('mood');
$entry_date = diaryPostString('entry_date');
$upload_batch_token = diaryAddValidatedUploadBatchToken();
$content_result = diaryContentPrepareForStorage($submitted_content, $logged_in_user_id);

$old = array(
    'title' => $title,
    'content' => $content_result['sanitized'],
    'upload_batch_token' => $upload_batch_token,
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

if ($upload_batch_token === '') {
    returnToDiaryAdd(
        array('Your upload session expired. Please review the entry and submit it again.'),
        $old
    );
}
$errors = array();
$valid_moods = array('Happy', 'Calm', 'Neutral', 'Sad', 'Stressed');

if ($title === '') {
    $errors[] = 'Title is required.';
} elseif (
    (function_exists('mb_strlen')
        ? mb_strlen($title, 'UTF-8')
        : strlen($title)) > 150
) {
    $errors[] = 'Title must be 150 characters or fewer.';
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
    returnToDiaryAdd($errors, $old);
}

// The owner comes only from the authenticated session guard.
$diary_id = createDiaryEntry(
    $conn,
    $logged_in_user_id,
    $title,
    $content_result['stored'],
    $mood,
    $entry_date
);

if ($diary_id === false) {
    returnToDiaryAdd(
        array('The journal entry could not be saved right now. Please try again.'),
        $old
    );
}

diaryAddCleanupUploadBatchAfterCreate(
    $conn,
    $upload_batch_token,
    $diary_id,
    $content_result['stored'],
    $logged_in_user_id
);

unset($_SESSION['diary_add_csrf_token'], $_SESSION['diary_add_errors'], $_SESSION['diary_add_old']);
header('Location: index.php', true, 303);
exit();
