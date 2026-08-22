<?php
// Safe path extraction and single-file cleanup helpers for uploaded Diary media.
// Callers must pass the authenticated session user ID. Nothing in this helper
// scans upload directories or decides which files are unreferenced.

require_once __DIR__ . '/diary_content.php';

function diaryMediaCleanupUserId($user_id) {
    $validated_user_id = filter_var(
        $user_id,
        FILTER_VALIDATE_INT,
        array('options' => array('min_range' => 1))
    );

    return $validated_user_id === false ? null : (int) $validated_user_id;
}

/**
 * Validate one Diary media public path for the authenticated user.
 *
 * @return string Canonical approved public path, or an empty string.
 */
function diaryMediaValidatePublicPath($public_path, $user_id, $is_drawing = false) {
    $validated_user_id = diaryMediaCleanupUserId($user_id);
    if ($validated_user_id === null || !is_string($public_path)) {
        return '';
    }

    return $is_drawing
        ? diaryContentAllowedDrawingSrc($public_path, $validated_user_id)
        : diaryContentAllowedImageSrc($public_path, $validated_user_id);
}

/**
 * Extract unique uploaded media paths from a stored trusted rich-text entry.
 *
 * Legacy/plain content is deliberately ignored. Rich HTML is sanitized again
 * for the authenticated user before any img elements are inspected.
 *
 * @return array Unique canonical public upload paths in document order.
 */
function diaryMediaExtractPathsFromRichContent($stored_content, $user_id) {
    $validated_user_id = diaryMediaCleanupUserId($user_id);
    if (
        $validated_user_id === null
        || !is_string($stored_content)
        || !diaryContentIsRich($stored_content)
    ) {
        return array();
    }

    $sanitized_html = diaryContentSanitizeRichHtml(
        diaryContentRichBody($stored_content),
        $validated_user_id
    );
    $parsed = diaryContentParseHtmlFragment($sanitized_html);

    if ($parsed === null) {
        return array();
    }

    $unique_paths = array();
    $images = $parsed[1]->getElementsByTagName('img');

    foreach ($images as $image) {
        $parent = $image->parentNode;
        $is_drawing = $parent instanceof DOMElement
            && strtolower($parent->nodeName) === 'figure'
            && $parent->getAttribute('data-diary-object') === 'drawing';
        $safe_path = diaryMediaValidatePublicPath(
            $image->getAttribute('src'),
            $validated_user_id,
            $is_drawing
        );

        if ($safe_path !== '') {
            $unique_paths[$safe_path] = true;
        }
    }

    return array_keys($unique_paths);
}

function diaryMediaCleanupComparablePath($path) {
    $normalized = rtrim(str_replace('\\', '/', (string) $path), '/');

    return DIRECTORY_SEPARATOR === '\\'
        ? strtolower($normalized)
        : $normalized;
}

/**
 * Resolve an approved public Diary upload path to one local regular file.
 *
 * The returned path is for internal server use only and must never be shown to
 * users. Missing files, links, traversal attempts, and ownership mismatches
 * all fail closed by returning null.
 *
 * @return string|null Canonical local file path, or null when unsafe/missing.
 */
function diaryMediaResolveLocalFile($public_path, $user_id) {
    $validated_user_id = diaryMediaCleanupUserId($user_id);
    if ($validated_user_id === null) {
        return null;
    }

    $safe_public_path = diaryMediaValidatePublicPath(
        $public_path,
        $validated_user_id,
        false
    );
    if ($safe_public_path === '') {
        return null;
    }

    $last_slash = strrpos($safe_public_path, '/');
    $filename = $last_slash === false
        ? ''
        : substr($safe_public_path, $last_slash + 1);

    if (!preg_match('/\A[a-f0-9]{32}\.(?:jpe?g|png|webp)\z/D', $filename)) {
        return null;
    }

    $storage_root = diaryImageStorageRoot();
    $user_directory = $storage_root
        . DIRECTORY_SEPARATOR . 'user_' . $validated_user_id;
    $candidate = $user_directory . DIRECTORY_SEPARATOR . $filename;

    // Refuse links at every application-controlled directory/file boundary.
    if (
        is_link($storage_root)
        || is_link($user_directory)
        || is_link($candidate)
    ) {
        return null;
    }

    $storage_root_real = realpath($storage_root);
    $user_directory_real = realpath($user_directory);
    $candidate_real = realpath($candidate);

    if (
        $storage_root_real === false
        || $user_directory_real === false
        || $candidate_real === false
        || !is_file($candidate_real)
        || is_link($candidate_real)
    ) {
        return null;
    }

    $storage_root_comparable = diaryMediaCleanupComparablePath($storage_root_real);
    $user_parent_comparable = diaryMediaCleanupComparablePath(dirname($user_directory_real));
    $user_directory_comparable = diaryMediaCleanupComparablePath($user_directory_real);
    $candidate_parent_comparable = diaryMediaCleanupComparablePath(dirname($candidate_real));

    if (
        $user_parent_comparable !== $storage_root_comparable
        || $candidate_parent_comparable !== $user_directory_comparable
    ) {
        return null;
    }

    return $candidate_real;
}

/**
 * Best-effort deletion of one already selected and validated Diary media path.
 *
 * This helper does not determine whether a file is unreferenced. Callers must
 * perform that comparison first. Failure is returned without emitting paths,
 * warnings, or unlink details to the response.
 */
function diaryMediaDeleteValidatedFile($public_path, $user_id) {
    $local_file = diaryMediaResolveLocalFile($public_path, $user_id);
    if ($local_file === null) {
        return false;
    }

    return @unlink($local_file);
}