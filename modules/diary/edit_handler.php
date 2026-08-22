<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_content.php';
require_once __DIR__ . '/diary_media_cleanup.php';
require_once __DIR__ . '/diary_navigation.php';

function returnToDiaryEdit($diary_id, $errors, $old = array()) {
    $return_to = diaryNavigationSanitizeReturnTo(
        isset($old['return_to']) && is_string($old['return_to'])
            ? $old['return_to']
            : ''
    );
    $old['return_to'] = $return_to;

    $_SESSION['diary_edit_errors'] = $errors;
    $_SESSION['diary_edit_old'] = $old;
    $_SESSION['diary_edit_id'] = $diary_id === false ? null : (int) $diary_id;

    $location = $diary_id === false
        ? 'edit.php?return_to=' . rawurlencode($return_to)
        : diaryNavigationEditUrl($diary_id, $return_to);

    header('Location: ' . $location, true, 303);
    exit();
}

function diaryEditPostString($key) {
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? trim($_POST[$key])
        : '';
}

function diaryEditValidatedUploadBatchToken() {
    $batch_token = diaryEditPostString('upload_batch_token');

    if (
        preg_match('/\A[a-f0-9]{64}\z/D', $batch_token) !== 1
        || !isset($_SESSION['diary_upload_batches'][$batch_token])
        || !is_array($_SESSION['diary_upload_batches'][$batch_token])
    ) {
        return '';
    }

    return $batch_token;
}

function diaryEditRegisterUploadedPath($batch_token, $public_path, $user_id, $is_drawing) {
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

function diaryEditCleanupUploadBatchAfterUpdate(
    $conn,
    $batch_token,
    $updated_diary_id,
    $old_stored_content,
    $new_stored_content,
    $user_id
) {
    try {
        $cleanup_candidate_lookup = array();

        foreach (diaryMediaExtractPathsFromRichContent($old_stored_content, $user_id) as $old_path) {
            $cleanup_candidate_lookup[$old_path] = true;
        }

        $registered_paths = isset($_SESSION['diary_upload_batches'][$batch_token])
            && is_array($_SESSION['diary_upload_batches'][$batch_token])
                ? $_SESSION['diary_upload_batches'][$batch_token]
                : array();

        foreach ($registered_paths as $registered_path) {
            $safe_path = diaryMediaValidatePublicPath($registered_path, $user_id);
            if ($safe_path !== '') {
                $cleanup_candidate_lookup[$safe_path] = true;
            }
        }

        $new_paths = diaryMediaExtractPathsFromRichContent($new_stored_content, $user_id);
        foreach ($new_paths as $new_path) {
            unset($cleanup_candidate_lookup[$new_path]);
        }

        if (!empty($cleanup_candidate_lookup)) {
            $user_entries = getDiaryEntriesForUser($conn, $user_id);

            // Fail closed when existing references cannot be checked.
            if (is_array($user_entries)) {
                $other_entry_path_lookup = array();

                foreach ($user_entries as $user_entry) {
                    if (
                        !isset($user_entry['diary_id'])
                        || (int) $user_entry['diary_id'] === (int) $updated_diary_id
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

                foreach (array_keys($cleanup_candidate_lookup) as $cleanup_candidate) {
                    if (!isset($other_entry_path_lookup[$cleanup_candidate])) {
                        diaryMediaDeleteValidatedFile($cleanup_candidate, $user_id);
                    }
                }
            }
        }
    } catch (Throwable $cleanup_error) {
        // Cleanup is best-effort and must never undo a successful diary update.
    }

    unset($_SESSION['diary_upload_batches'][$batch_token]);
}

function diaryEditJsonResponse($status, $payload) {
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['diary_delete_flash'] = array(
        'type' => 'error',
        'message' => 'Please use the Edit Journal Entry form to update an entry.'
    );
    header('Location: index.php', true, 303);
    exit();
}

$submitted_id = diaryEditPostString('id');
$diary_id = filter_var(
    $submitted_id,
    FILTER_VALIDATE_INT,
    array('options' => array('min_range' => 1))
);

$is_image_upload = isset($_POST['diary_image_upload']) && $_POST['diary_image_upload'] === '1';
$is_drawing_upload = isset($_POST['diary_drawing_upload']) && $_POST['diary_drawing_upload'] === '1';

if ($is_image_upload || $is_drawing_upload) {
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
    $upload_batch_token = diaryEditValidatedUploadBatchToken();
    if ($upload_batch_token === '') {
        diaryEditJsonResponse(403, array(
            'ok' => false,
            'error' => 'Your upload session expired. Refresh the page and try again.'
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
        $logged_in_user_id,
        $is_drawing_upload ? 'Journal drawing' : 'Journal image',
        $is_drawing_upload ? 'image/png' : ''
    );

    if (!$upload_result['valid']) {
        diaryEditJsonResponse($upload_result['status'], array(
            'ok' => false,
            'error' => $upload_result['error']
        ));
    }

    $safe_uploaded_path = diaryEditRegisterUploadedPath(
        $upload_batch_token,
        $upload_result['src'],
        $logged_in_user_id,
        $is_drawing_upload
    );

    if ($safe_uploaded_path === '') {
        diaryEditJsonResponse(500, array(
            'ok' => false,
            'error' => 'The image could not be uploaded right now. Please try again.'
        ));
    }

    diaryEditJsonResponse(200, array(
        'ok' => true,
        'src' => $safe_uploaded_path,
        'alt' => $upload_result['alt']
    ));
}

$return_to = diaryNavigationSanitizeReturnTo(diaryEditPostString('return_to'));
$title = diaryEditPostString('title');
$submitted_content = diaryEditPostString('content');
$mood = diaryEditPostString('mood');
$entry_date = diaryEditPostString('entry_date');
$upload_batch_token = diaryEditValidatedUploadBatchToken();
$content_result = diaryContentPrepareForStorage($submitted_content, $logged_in_user_id);

$old = array(
    'return_to' => $return_to,
    'title' => $title,
    'content' => $content_result['sanitized'],
    'upload_batch_token' => $upload_batch_token,
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

if ($upload_batch_token === '') {
    returnToDiaryEdit(
        $diary_id,
        array('Your upload session expired. Please review the entry and submit it again.'),
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

$existing_stored_content = isset($existing_entry['content'])
    && is_string($existing_entry['content'])
        ? $existing_entry['content']
        : '';

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

diaryEditCleanupUploadBatchAfterUpdate(
    $conn,
    $upload_batch_token,
    $diary_id,
    $existing_stored_content,
    $content_result['stored'],
    $logged_in_user_id
);

unset(
    $_SESSION['diary_edit_csrf_token'],
    $_SESSION['diary_edit_errors'],
    $_SESSION['diary_edit_old'],
    $_SESSION['diary_edit_id']
);

header('Location: ' . diaryNavigationViewUrl($diary_id, $return_to), true, 303);
exit();
