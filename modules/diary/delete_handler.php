<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_media_cleanup.php';
require_once __DIR__ . '/diary_navigation.php';

function diaryDeleteReturnTarget() {
    $requested_target = isset($_POST['return_to']) && is_string($_POST['return_to'])
        ? $_POST['return_to']
        : '';

    return diaryNavigationSanitizeReturnTo($requested_target);
}

function diaryDeleteSequenceRequested() {
    return isset($_POST['sequence'])
        && is_string($_POST['sequence'])
        && diaryNavigationIsSequenceMode($_POST['sequence']);
}

function returnFromDiaryDelete(
    $message,
    $type = 'error',
    $redirect_target = null,
    $expected_diary_id = null
) {
    $_SESSION['diary_delete_flash'] = array(
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message
    );

    $safe_target = $redirect_target === null
        ? diaryDeleteReturnTarget()
        : diaryNavigationSanitizeActionTarget($redirect_target, $expected_diary_id);

    header('Location: ' . $safe_target, true, 303);
    exit();
}

function diaryDeleteSequenceDestination($conn, $diary_id, $user_id, $return_to) {
    $entries = getDiaryEntriesForUser($conn, $user_id);
    if (!is_array($entries)) {
        return null;
    }

    $entries = diaryNavigationEntriesForContext($entries, $return_to);
    $current_position = null;

    foreach ($entries as $position => $candidate) {
        if (isset($candidate['diary_id']) && (int) $candidate['diary_id'] === (int) $diary_id) {
            $current_position = $position;
            break;
        }
    }

    if ($current_position === null) {
        return null;
    }

    $next_position = $current_position - 1;
    $previous_position = $current_position + 1;
    $adjacent_id = $next_position >= 0 && isset($entries[$next_position]['diary_id'])
        ? diaryNavigationPositiveId($entries[$next_position]['diary_id'])
        : null;

    if ($adjacent_id === null && isset($entries[$previous_position]['diary_id'])) {
        $adjacent_id = diaryNavigationPositiveId($entries[$previous_position]['diary_id']);
    }

    if ($adjacent_id === null) {
        return null;
    }

    return array(
        'diary_id' => $adjacent_id,
        'url' => diaryNavigationViewUrl($adjacent_id, $return_to, true)
    );
}

function diaryDeleteCleanupUnreferencedMedia($conn, $deleted_entry_paths, $user_id) {
    try {
        $safe_deleted_path_lookup = array();

        foreach ($deleted_entry_paths as $deleted_entry_path) {
            $safe_path = diaryMediaValidatePublicPath($deleted_entry_path, $user_id);
            if ($safe_path !== '') {
                $safe_deleted_path_lookup[$safe_path] = true;
            }
        }

        if (empty($safe_deleted_path_lookup)) {
            return;
        }

        $remaining_entries = getDiaryEntriesForUser($conn, $user_id);
        if (!is_array($remaining_entries)) {
            // Fail closed when remaining references cannot be checked.
            return;
        }

        $remaining_path_lookup = array();

        foreach ($remaining_entries as $remaining_entry) {
            if (!isset($remaining_entry['content'])) {
                continue;
            }

            $remaining_paths = diaryMediaExtractPathsFromRichContent(
                $remaining_entry['content'],
                $user_id
            );

            foreach ($remaining_paths as $remaining_path) {
                $remaining_path_lookup[$remaining_path] = true;
            }
        }

        foreach (array_keys($safe_deleted_path_lookup) as $deleted_path) {
            if (!isset($remaining_path_lookup[$deleted_path])) {
                diaryMediaDeleteValidatedFile($deleted_path, $user_id);
            }
        }
    } catch (Throwable $cleanup_error) {
        // Cleanup is best-effort and must never undo a successful diary deletion.
    }
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

$sequence_destination = diaryDeleteSequenceRequested()
    ? diaryDeleteSequenceDestination(
        $conn,
        $diary_id,
        $logged_in_user_id,
        diaryDeleteReturnTarget()
    )
    : null;

$deleted_entry_media_paths = array();

try {
    $entry_content = isset($entry['content']) && is_string($entry['content'])
        ? $entry['content']
        : '';
    $deleted_entry_media_paths = diaryMediaExtractPathsFromRichContent(
        $entry_content,
        $logged_in_user_id
    );
} catch (Throwable $cleanup_error) {
    // The database deletion can proceed even when media extraction is unavailable.
}

$deleted_rows = deleteDiaryEntry($conn, $diary_id, $logged_in_user_id);

if ($deleted_rows !== 1) {
    returnFromDiaryDelete('Journal entry could not be deleted right now. Please try again.');
}

diaryDeleteCleanupUnreferencedMedia(
    $conn,
    $deleted_entry_media_paths,
    $logged_in_user_id
);

if (is_array($sequence_destination)) {
    returnFromDiaryDelete(
        'Journal entry deleted successfully.',
        'success',
        $sequence_destination['url'],
        $sequence_destination['diary_id']
    );
}

returnFromDiaryDelete('Journal entry deleted successfully.', 'success');
