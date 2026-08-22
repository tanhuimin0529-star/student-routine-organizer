<?php
// Read-only helpers for building a Diary Memory Album from images already
// referenced by authenticated, user-owned rich-text diary entries.

require_once __DIR__ . '/diary_media_cleanup.php';

/**
 * Validate an entry date and return its YYYY-MM album key.
 *
 * @return string Empty when the date is not a real YYYY-MM-DD value.
 */
function diaryMemoryAlbumMonthKey($entry_date) {
    if (!is_string($entry_date)) {
        return '';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $entry_date);
    $errors = DateTimeImmutable::getLastErrors();

    if (
        $date === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $date->format('Y-m-d') !== $entry_date
    ) {
        return '';
    }

    return $date->format('Y-m');
}

/**
 * Return true when an image belongs to a sanitized floating drawing figure.
 */
function diaryMemoryAlbumImageIsDrawing($image) {
    if (!($image instanceof DOMElement)) {
        return false;
    }

    $ancestor = $image->parentNode;

    while ($ancestor instanceof DOMElement) {
        if (
            strtolower($ancestor->nodeName) === 'figure'
            && $ancestor->getAttribute('data-diary-object') === 'drawing'
        ) {
            return true;
        }

        $ancestor = $ancestor->parentNode;
    }

    return false;
}

/**
 * Extract the plain-text caption attached to one normal sanitized image.
 */
function diaryMemoryAlbumImageCaption($image) {
    if (!($image instanceof DOMElement)) {
        return '';
    }

    $figure = $image->parentNode;
    if (
        !($figure instanceof DOMElement)
        || strtolower($figure->nodeName) !== 'figure'
        || $figure->getAttribute('data-diary-object') === 'drawing'
    ) {
        return '';
    }

    foreach ($figure->childNodes as $child) {
        if (
            $child instanceof DOMElement
            && strtolower($child->nodeName) === 'figcaption'
        ) {
            return diaryContentAllowedImageCaption($child->textContent);
        }
    }

    return '';
}

/**
 * Extract normal uploaded photos from one authenticated user's diary entry.
 *
 * The entry must contain the trusted rich-text marker and must carry the same
 * user_id as the authenticated user. Images are returned in document order.
 * When $verify_existing_file is true, missing or unsafe local files are
 * skipped, but resolved filesystem paths are never returned.
 *
 * @return array Album photo records for this entry.
 */
function diaryMemoryAlbumExtractEntryPhotos($entry, $user_id, $verify_existing_file = true) {
    $validated_user_id = diaryMediaCleanupUserId($user_id);

    if (
        $validated_user_id === null
        || !is_array($entry)
        || !isset($entry['user_id'])
        || (int) $entry['user_id'] !== $validated_user_id
        || !isset($entry['content'])
        || !is_string($entry['content'])
        || !diaryContentIsRich($entry['content'])
    ) {
        return array();
    }

    $diary_id = isset($entry['diary_id'])
        ? filter_var(
            $entry['diary_id'],
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        )
        : false;
    $entry_date = isset($entry['entry_date']) && is_string($entry['entry_date'])
        ? $entry['entry_date']
        : '';

    if ($diary_id === false || diaryMemoryAlbumMonthKey($entry_date) === '') {
        return array();
    }

    $sanitized_html = diaryContentSanitizeRichHtml(
        diaryContentRichBody($entry['content']),
        $validated_user_id
    );
    $parsed = diaryContentParseHtmlFragment($sanitized_html);

    if ($parsed === null) {
        return array();
    }

    $photos = array();
    $images = $parsed[1]->getElementsByTagName('img');

    foreach ($images as $image) {
        if (diaryMemoryAlbumImageIsDrawing($image)) {
            continue;
        }

        $safe_public_path = diaryMediaValidatePublicPath(
            $image->getAttribute('src'),
            $validated_user_id,
            false
        );

        if ($safe_public_path === '') {
            continue;
        }

        if (
            $verify_existing_file
            && diaryMediaResolveLocalFile($safe_public_path, $validated_user_id) === null
        ) {
            continue;
        }

        $photos[] = array(
            'image_path' => $safe_public_path,
            'caption' => diaryMemoryAlbumImageCaption($image),
            'diary_id' => (int) $diary_id,
            'title' => isset($entry['title']) ? (string) $entry['title'] : '',
            'mood' => isset($entry['mood']) ? (string) $entry['mood'] : '',
            'entry_date' => $entry_date
        );
    }

    return $photos;
}

/**
 * Extract normal Diary photos from an already-loaded user-owned entry array.
 *
 * Input entry order and each entry's image document order are preserved.
 *
 * @return array Flat album photo list.
 */
function diaryMemoryAlbumExtractPhotos($entries, $user_id, $verify_existing_file = true) {
    if (!is_array($entries)) {
        return array();
    }

    $photos = array();

    foreach ($entries as $entry) {
        $entry_photos = diaryMemoryAlbumExtractEntryPhotos(
            $entry,
            $user_id,
            $verify_existing_file
        );

        foreach ($entry_photos as $photo) {
            $photos[] = $photo;
        }
    }

    return $photos;
}

/**
 * Group album photos by entry month, newest month first.
 *
 * Photo order inside each month is not changed.
 *
 * @return array Associative array keyed by YYYY-MM.
 */
function diaryMemoryAlbumGroupPhotosByMonth($photos) {
    if (!is_array($photos)) {
        return array();
    }

    $grouped_photos = array();

    foreach ($photos as $photo) {
        if (!is_array($photo) || !isset($photo['entry_date'])) {
            continue;
        }

        $month_key = diaryMemoryAlbumMonthKey($photo['entry_date']);
        if ($month_key === '') {
            continue;
        }

        if (!isset($grouped_photos[$month_key])) {
            $grouped_photos[$month_key] = array();
        }

        $grouped_photos[$month_key][] = $photo;
    }

    krsort($grouped_photos, SORT_STRING);

    return $grouped_photos;
}

