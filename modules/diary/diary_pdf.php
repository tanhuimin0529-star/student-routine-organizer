<?php
// Safe Diary-entry preparation for the future Dompdf export handler.
// This helper returns HTML only; it does not query entries, send headers, or
// create PDF output.

require_once __DIR__ . '/diary_content.php';
require_once __DIR__ . '/diary_media_cleanup.php';

function diaryPdfEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function diaryPdfAuthenticatedUserId($user_id) {
    $validated_user_id = filter_var(
        $user_id,
        FILTER_VALIDATE_INT,
        array('options' => array('min_range' => 1))
    );

    return $validated_user_id === false ? null : (int) $validated_user_id;
}

function diaryPdfDisplayDate($entry_date) {
    $entry_date = (string) $entry_date;
    $date = DateTime::createFromFormat('!Y-m-d', $entry_date);
    $date_errors = DateTime::getLastErrors();
    $has_errors = is_array($date_errors)
        && ($date_errors['warning_count'] > 0 || $date_errors['error_count'] > 0);

    return $date && !$has_errors && $date->format('Y-m-d') === $entry_date
        ? $date->format('F j, Y')
        : $entry_date;
}

function diaryPdfRemoveAllAttributes($element) {
    if (!($element instanceof DOMElement)) {
        return;
    }

    while ($element->attributes->length > 0) {
        $element->removeAttributeNode($element->attributes->item(0));
    }
}

function diaryPdfImageDataUri($public_src, $user_id, $is_drawing = false) {
    $safe_public_src = diaryMediaValidatePublicPath(
        $public_src,
        $user_id,
        $is_drawing
    );

    if ($safe_public_src === '') {
        return '';
    }

    $local_file = diaryMediaResolveLocalFile($safe_public_src, $user_id);
    if ($local_file === null) {
        return '';
    }

    $file_size = @filesize($local_file);
    if (
        $file_size === false
        || $file_size < 1
        || $file_size > diaryImageMaxUploadBytes()
    ) {
        return '';
    }

    $image_details = @getimagesize($local_file);
    if ($image_details === false || empty($image_details['mime'])) {
        return '';
    }

    $image_mime = strtolower((string) $image_details['mime']);
    $allowed_mimes = array('image/jpeg', 'image/png', 'image/webp');
    if (!in_array($image_mime, $allowed_mimes, true)) {
        return '';
    }

    if ($is_drawing && $image_mime !== 'image/png') {
        return '';
    }

    if (!function_exists('finfo_open')) {
        return '';
    }

    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    if ($file_info === false) {
        return '';
    }

    $detected_mime = strtolower((string) @finfo_file($file_info, $local_file));
    finfo_close($file_info);

    if ($detected_mime !== $image_mime) {
        return '';
    }

    $binary = @file_get_contents($local_file);
    if ($binary === false || $binary === '') {
        return '';
    }

    return 'data:' . $image_mime . ';base64,' . base64_encode($binary);
}

function diaryPdfDirectImageChild($figure) {
    if (!($figure instanceof DOMElement)) {
        return null;
    }

    foreach ($figure->childNodes as $child) {
        if (
            $child instanceof DOMElement
            && strtolower($child->nodeName) === 'img'
        ) {
            return $child;
        }
    }

    return null;
}

function diaryPdfNormalMediaWidth($media_element, $image_element) {
    $media_style = diaryContentParseInlineStyle($media_element->getAttribute('style'));
    $safe_width = diaryContentAllowedImageWidth(
        isset($media_style['width']) ? $media_style['width'] : ''
    );

    if ($safe_width === '') {
        $image_style = diaryContentParseInlineStyle($image_element->getAttribute('style'));
        $safe_width = diaryContentAllowedImageWidth(
            isset($image_style['width']) ? $image_style['width'] : ''
        );
    }

    if ($safe_width !== '') {
        return $safe_width;
    }

    $size = diaryContentAllowedImageSize($media_element->getAttribute('data-diary-size'));
    if ($size === '') {
        $size = diaryContentAllowedImageSize($image_element->getAttribute('data-diary-size'));
    }

    $size_widths = array(
        'small' => '25%',
        'medium' => '50%',
        'large' => '80%'
    );

    return isset($size_widths[$size]) ? $size_widths[$size] : '50%';
}

function diaryPdfNormalMediaClass($media_element, $image_element) {
    $wrap = diaryContentAllowedImageWrap($media_element->getAttribute('data-diary-wrap'));
    if ($wrap === '') {
        $wrap = diaryContentAllowedImageWrap($image_element->getAttribute('data-diary-wrap'));
    }

    if ($wrap === 'left' || $wrap === 'right') {
        return 'diary-pdf-media diary-pdf-wrap-' . $wrap;
    }

    $alignment = diaryContentAllowedImageAlign($media_element->getAttribute('data-diary-align'));
    if ($alignment === '') {
        $alignment = diaryContentAllowedImageAlign($image_element->getAttribute('data-diary-align'));
    }
    if ($alignment === '') {
        $alignment = 'center';
    }

    return 'diary-pdf-media diary-pdf-align-' . $alignment;
}

function diaryPdfPrepareDrawingFigure($figure, $image, $user_id) {
    $safe_x = diaryContentAllowedDrawingCoordinate($figure->getAttribute('data-diary-x'));
    $safe_y = diaryContentAllowedDrawingCoordinate($figure->getAttribute('data-diary-y'));
    $safe_width = diaryContentAllowedDrawingWidth($figure->getAttribute('data-diary-width'));
    $safe_rotation = diaryContentAllowedDrawingRotation(
        $figure->getAttribute('data-diary-rotation')
    );
    $data_uri = diaryPdfImageDataUri($image->getAttribute('src'), $user_id, true);

    if (
        $safe_x === null
        || $safe_y === null
        || $safe_width === null
        || $safe_rotation === null
        || $data_uri === ''
    ) {
        return false;
    }

    // Absolute x/y placement is unreliable when Dompdf paginates long entry
    // content. Approximate the horizontal position and keep the drawing in
    // normal flow so it remains visible and cannot overlap unrelated pages.
    $horizontal_class = (float) $safe_x < 34
        ? 'diary-pdf-align-left'
        : ((float) $safe_x > 66 ? 'diary-pdf-align-right' : 'diary-pdf-align-center');

    $alt = diaryContentAllowedImageAlt($image->getAttribute('alt'));
    diaryPdfRemoveAllAttributes($figure);
    diaryPdfRemoveAllAttributes($image);

    $figure->setAttribute(
        'class',
        'diary-pdf-media diary-pdf-drawing ' . $horizontal_class
    );
    $figure->setAttribute('style', 'width: ' . $safe_width . '%;');
    $image->setAttribute('src', $data_uri);
    $image->setAttribute('alt', $alt);

    if ((float) $safe_rotation !== 0.0) {
        // Dompdf currently understands transforms in supported contexts. If a
        // renderer ignores this declaration, the transparent PNG still falls
        // back to an unrotated, readable normal-flow image.
        $image->setAttribute(
            'style',
            'transform: rotate(' . $safe_rotation . 'deg); transform-origin: center center;'
        );
    }

    return true;
}

function diaryPdfPrepareNormalFigure($figure, $image, $user_id) {
    $data_uri = diaryPdfImageDataUri($image->getAttribute('src'), $user_id, false);
    if ($data_uri === '') {
        return false;
    }

    $width = diaryPdfNormalMediaWidth($figure, $image);
    $class = diaryPdfNormalMediaClass($figure, $image);
    $alt = diaryContentAllowedImageAlt($image->getAttribute('alt'));

    diaryPdfRemoveAllAttributes($figure);
    diaryPdfRemoveAllAttributes($image);

    $figure->setAttribute('class', $class);
    $figure->setAttribute('style', 'width: ' . $width . ';');
    $image->setAttribute('src', $data_uri);
    $image->setAttribute('alt', $alt);

    return true;
}

function diaryPdfPrepareStandaloneImage($image, $user_id) {
    $data_uri = diaryPdfImageDataUri($image->getAttribute('src'), $user_id, false);
    if ($data_uri === '') {
        return false;
    }

    $width = diaryPdfNormalMediaWidth($image, $image);
    $class = diaryPdfNormalMediaClass($image, $image);
    $alt = diaryContentAllowedImageAlt($image->getAttribute('alt'));

    diaryPdfRemoveAllAttributes($image);
    $image->setAttribute('class', $class . ' diary-pdf-standalone-image');
    $image->setAttribute('style', 'width: ' . $width . ';');
    $image->setAttribute('src', $data_uri);
    $image->setAttribute('alt', $alt);

    return true;
}

function diaryPdfPrepareRichBodyHtml($sanitized_html, $user_id) {
    $parsed = diaryContentParseHtmlFragment($sanitized_html);
    if ($parsed === null) {
        return '';
    }

    $document = $parsed[0];
    $body = $parsed[1];
    $figures = array();

    foreach ($body->getElementsByTagName('figure') as $figure) {
        $figures[] = $figure;
    }

    foreach ($figures as $figure) {
        $image = diaryPdfDirectImageChild($figure);
        $is_drawing = $figure->getAttribute('data-diary-object') === 'drawing';
        $prepared = $image !== null && ($is_drawing
            ? diaryPdfPrepareDrawingFigure($figure, $image, $user_id)
            : diaryPdfPrepareNormalFigure($figure, $image, $user_id));

        if (!$prepared && $figure->parentNode !== null) {
            $figure->parentNode->removeChild($figure);
        }
    }

    $images = array();
    foreach ($body->getElementsByTagName('img') as $image) {
        $images[] = $image;
    }

    foreach ($images as $image) {
        $parent = $image->parentNode;
        if ($parent instanceof DOMElement && strtolower($parent->nodeName) === 'figure') {
            continue;
        }

        if (!diaryPdfPrepareStandaloneImage($image, $user_id) && $parent !== null) {
            $parent->removeChild($image);
        }
    }

    $rendered = '';
    foreach ($body->childNodes as $child_node) {
        $rendered .= $document->saveHTML($child_node);
    }

    return trim($rendered);
}

function diaryPdfPrepareStoredContent($stored_content, $user_id) {
    $stored_content = (string) $stored_content;

    if (!diaryContentIsRich($stored_content)) {
        return diaryContentLegacyToSafeHtml($stored_content);
    }

    $sanitized_html = diaryContentSanitizeRichHtml(
        diaryContentRichBody($stored_content),
        $user_id
    );

    return diaryPdfPrepareRichBodyHtml($sanitized_html, $user_id);
}

function diaryPdfStylesheet() {
    $stylesheet_path = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR . 'assets'
        . DIRECTORY_SEPARATOR . 'css'
        . DIRECTORY_SEPARATOR . 'diary_pdf.css';
    $stylesheet = @file_get_contents($stylesheet_path);

    return $stylesheet === false ? '' : $stylesheet;
}

/**
 * Prepare a complete, self-contained HTML document for one owned Diary entry.
 *
 * @return array{valid: bool, html: string, error: string}
 */
function diaryPdfPrepareEntryDocument($entry, $authenticated_user_id) {
    $failure = array(
        'valid' => false,
        'html' => '',
        'error' => 'Journal entry not found.'
    );
    $user_id = diaryPdfAuthenticatedUserId($authenticated_user_id);

    if (
        $user_id === null
        || !is_array($entry)
        || !isset($entry['user_id'])
        || (int) $entry['user_id'] !== $user_id
        || !isset($entry['diary_id'])
        || filter_var(
            $entry['diary_id'],
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        ) === false
    ) {
        return $failure;
    }

    $title = isset($entry['title']) ? (string) $entry['title'] : 'Journal Entry';
    $mood = isset($entry['mood']) ? (string) $entry['mood'] : '';
    $entry_date = isset($entry['entry_date']) ? (string) $entry['entry_date'] : '';
    $stored_content = isset($entry['content']) ? (string) $entry['content'] : '';
    $safe_content = diaryPdfPrepareStoredContent($stored_content, $user_id);
    $stylesheet = diaryPdfStylesheet();

    $html = '<!DOCTYPE html>'
        . '<html lang="en"><head><meta charset="UTF-8">'
        . '<title>' . diaryPdfEscape($title) . ' - Personal Journal</title>'
        . '<style>' . $stylesheet . '</style>'
        . '</head><body>'
        . '<main class="diary-pdf-sheet">'
        . '<header class="diary-pdf-header">'
        . '<p class="diary-pdf-label">Personal Journal</p>'
        . '<h1>' . diaryPdfEscape($title) . '</h1>'
        . '<table class="diary-pdf-meta" role="presentation"><tr>'
        . '<td><span>Date</span><strong>'
        . diaryPdfEscape(diaryPdfDisplayDate($entry_date))
        . '</strong></td>'
        . '<td><span>Mood</span><strong>' . diaryPdfEscape($mood) . '</strong></td>'
        . '</tr></table>'
        . '</header>'
        . '<section class="diary-pdf-content">' . $safe_content . '</section>'
        . '<footer class="diary-pdf-footer">Personal Journal</footer>'
        . '</main></body></html>';

    return array(
        'valid' => true,
        'html' => $html,
        'error' => ''
    );
}
?>
