<?php
// Shared rich-text handling for Diary content.
// All rich HTML must pass through this helper before storage or rendering.

function diaryContentRichMarker() {
    return '<!--DIARY_RICH_TEXT_V1-->';
}

function diaryContentMaxStorageBytes() {
    // Leave room below MySQL TEXT's 65,535-byte ceiling.
    return 60000;
}

function diaryImageMaxUploadBytes() {
    return 5 * 1024 * 1024;
}

function diaryImageApplicationBasePath() {
    $script_name = isset($_SERVER['SCRIPT_NAME']) && is_string($_SERVER['SCRIPT_NAME'])
        ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME'])
        : '';
    $diary_path_position = strpos($script_name, '/modules/diary/');

    if ($diary_path_position === false) {
        return '/student-routine-organizer';
    }

    return rtrim(substr($script_name, 0, $diary_path_position), '/');
}

function diaryImagePublicPrefix() {
    return diaryImageApplicationBasePath() . '/uploads/diary/';
}

function diaryImageStorageRoot() {
    return dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'diary';
}

function diaryContentAllowedImageSrc($value, $expected_user_id = null) {
    $src = trim((string) $value);
    $prefix = diaryImagePublicPrefix();
    $pattern = '#^'
        . preg_quote($prefix, '#')
        . 'user_([1-9][0-9]*)/[a-f0-9]{32}\.(?:jpe?g|png|webp)$#D';

    if (!preg_match($pattern, $src, $matches)) {
        return '';
    }

    if (
        $expected_user_id !== null
        && (int) $matches[1] !== (int) $expected_user_id
    ) {
        return '';
    }

    return $src;
}

function diaryContentAllowedImageAlt($value) {
    $alt = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $value);
    if ($alt === null) {
        return 'Journal image';
    }

    $alt = trim(preg_replace('/\s+/u', ' ', $alt));
    if ($alt === '') {
        return 'Journal image';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($alt, 0, 150, 'UTF-8');
    }

    return substr($alt, 0, 150);
}

function diaryContentAllowedImageCaption($value) {
    $caption = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $value);
    if ($caption === null) {
        return '';
    }

    $caption = trim(preg_replace('/\s+/u', ' ', $caption));
    if ($caption === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($caption, 0, 250, 'UTF-8');
    }

    return substr($caption, 0, 250);
}

function diaryContentAllowedImageSize($value) {
    $normalized = trim((string) $value);
    $allowed = array('small', 'medium', 'large');

    return in_array($normalized, $allowed, true) ? $normalized : '';
}

function diaryContentAllowedImageAlign($value) {
    $normalized = trim((string) $value);
    $allowed = array('left', 'center', 'right');

    return in_array($normalized, $allowed, true) ? $normalized : '';
}

function diaryContentAllowedImageWrap($value) {
    $normalized = trim((string) $value);
    $allowed = array('none', 'left', 'right');

    return in_array($normalized, $allowed, true) ? $normalized : '';
}

function diaryContentAllowedImageWidth($value) {
    $normalized = trim((string) $value);

    if (!preg_match('/^(?:100(?:\.0)?|[1-9][0-9]?(?:\.[0-9])?)%$/', $normalized)) {
        return '';
    }

    $numeric_width = (float) substr($normalized, 0, -1);
    if ($numeric_width < 10 || $numeric_width > 100) {
        return '';
    }

    $canonical_width = rtrim(rtrim(number_format($numeric_width, 1, '.', ''), '0'), '.');
    return $canonical_width . '%';
}

function diaryContentCanonicalDrawingNumber($value, $minimum, $maximum) {
    $normalized = trim((string) $value);

    if (!preg_match('/^-?(?:0|[1-9][0-9]{0,2})(?:\.[0-9])?$/', $normalized)) {
        return null;
    }

    $numeric_value = (float) $normalized;
    if ($numeric_value < $minimum || $numeric_value > $maximum) {
        return null;
    }

    $canonical_value = rtrim(
        rtrim(number_format($numeric_value, 1, '.', ''), '0'),
        '.'
    );

    return $canonical_value === '-0' ? '0' : $canonical_value;
}

function diaryContentAllowedDrawingCoordinate($value) {
    return diaryContentCanonicalDrawingNumber($value, 0, 100);
}

function diaryContentAllowedDrawingWidth($value) {
    return diaryContentCanonicalDrawingNumber($value, 10, 100);
}

function diaryContentAllowedDrawingRotation($value) {
    return diaryContentCanonicalDrawingNumber($value, -180, 180);
}

function diaryContentAllowedDrawingSrc($value, $expected_user_id = null) {
    $safe_src = diaryContentAllowedImageSrc($value, $expected_user_id);

    return $safe_src !== '' && substr($safe_src, -4) === '.png'
        ? $safe_src
        : '';
}
function diaryImageStoreUploadedFile($file, $user_id, $default_alt = 'Journal image', $required_mime = '') {
    $failure = array(
        'valid' => false,
        'src' => '',
        'alt' => '',
        'error' => 'Please choose a valid JPG, PNG, or WebP image.',
        'status' => 422
    );
    $user_id = filter_var(
        $user_id,
        FILTER_VALIDATE_INT,
        array('options' => array('min_range' => 1))
    );

    if ($user_id === false || !is_array($file)) {
        return $failure;
    }

    $default_alt = diaryContentAllowedImageAlt($default_alt);
    $required_mime = is_string($required_mime) ? trim($required_mime) : '';

    $upload_error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
    if ($upload_error !== UPLOAD_ERR_OK) {
        switch ($upload_error) {
            case UPLOAD_ERR_PARTIAL:
                $failure['error'] = 'The image upload was incomplete. Please try again.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $failure['error'] = 'No image was selected.';
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $failure['error'] = 'The image is too large.';
                break;
            default:
                $failure['error'] = 'Image upload failed. Please try again.';
        }
        return $failure;
    }

    $temporary_path = isset($file['tmp_name']) && is_string($file['tmp_name'])
        ? $file['tmp_name']
        : '';
    $file_size = isset($file['size']) ? (int) $file['size'] : 0;

    if ($temporary_path === '' || !is_uploaded_file($temporary_path)) {
        $failure['error'] = 'Image upload failed. Please try again.';
        return $failure;
    }

    if ($file_size < 1) {
        $failure['error'] = 'The selected image is invalid or corrupted.';
        return $failure;
    }

    if ($file_size > diaryImageMaxUploadBytes()) {
        $failure['error'] = 'The image is too large.';
        return $failure;
    }

    if (!class_exists('finfo')) {
        $failure['error'] = 'Image uploads are unavailable right now. Please try again later.';
        $failure['status'] = 500;
        return $failure;
    }

    $mime_to_extension = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    );
    $file_info = new finfo(FILEINFO_MIME_TYPE);
    $detected_mime = $file_info->file($temporary_path);
    $image_info = @getimagesize($temporary_path);

    if (!is_string($detected_mime) || !isset($mime_to_extension[$detected_mime])) {
        return $failure;
    }

    if ($required_mime !== '' && $detected_mime !== $required_mime) {
        $failure['error'] = 'The drawing could not be processed as a PNG.';
        return $failure;
    }

    if (
        !is_array($image_info)
        || !isset($image_info['mime'])
        || $image_info['mime'] !== $detected_mime
        || empty($image_info[0])
        || empty($image_info[1])
    ) {
        $failure['error'] = 'The selected image is invalid or corrupted.';
        return $failure;
    }

    $user_directory = diaryImageStorageRoot()
        . DIRECTORY_SEPARATOR . 'user_' . $user_id;

    if (
        (!is_dir($user_directory) && !mkdir($user_directory, 0755, true))
        || !is_writable($user_directory)
    ) {
        $failure['error'] = 'The image could not be stored right now. Please try again.';
        $failure['status'] = 500;
        return $failure;
    }

    try {
        $safe_filename = bin2hex(random_bytes(16))
            . '.' . $mime_to_extension[$detected_mime];
    } catch (Exception $exception) {
        $failure['error'] = 'The image could not be stored right now. Please try again.';
        $failure['status'] = 500;
        return $failure;
    }

    $destination = $user_directory . DIRECTORY_SEPARATOR . $safe_filename;
    if (!move_uploaded_file($temporary_path, $destination)) {
        $failure['error'] = 'The image could not be stored right now. Please try again.';
        $failure['status'] = 500;
        return $failure;
    }

    @chmod($destination, 0644);

    return array(
        'valid' => true,
        'src' => diaryImagePublicPrefix()
            . 'user_' . $user_id . '/' . $safe_filename,
        'alt' => $default_alt,
        'error' => '',
        'status' => 200
    );
}

function diaryContentIsRich($content) {
    $content = (string) $content;
    $marker = diaryContentRichMarker();

    return strncmp($content, $marker, strlen($marker)) === 0;
}

function diaryContentRichBody($content) {
    $content = (string) $content;

    return diaryContentIsRich($content)
        ? substr($content, strlen(diaryContentRichMarker()))
        : '';
}

function diaryContentParseHtmlFragment($html) {
    if (!class_exists('DOMDocument')) {
        return null;
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previous_error_setting = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . (string) $html . '</body></html>',
        LIBXML_NONET
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous_error_setting);

    if (!$loaded) {
        return null;
    }

    $body_nodes = $document->getElementsByTagName('body');
    $body = $body_nodes->length > 0 ? $body_nodes->item(0) : null;

    return $body ? array($document, $body) : null;
}

function diaryContentParseInlineStyle($style) {
    $declarations = array();

    foreach (explode(';', (string) $style) as $declaration) {
        $parts = explode(':', $declaration, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $property = strtolower(trim($parts[0]));
        $value = trim($parts[1]);

        if ($property !== '' && $value !== '') {
            $declarations[$property] = $value;
        }
    }

    return $declarations;
}

function diaryContentAllowedFontFamily($value) {
    $normalized = strtolower(trim(str_replace(array('"', "'"), '', (string) $value)));
    $allowed = array(
        'arial' => 'Arial',
        'georgia' => 'Georgia',
        'times new roman' => 'Times New Roman',
        'verdana' => 'Verdana',
        'courier new' => 'Courier New'
    );

    return isset($allowed[$normalized]) ? $allowed[$normalized] : '';
}

function diaryContentAllowedFontSize($value) {
    $normalized = strtolower(trim((string) $value));
    $allowed = array('12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px');

    return in_array($normalized, $allowed, true) ? $normalized : '';
}

function diaryContentAllowedTextColor($value) {
    $normalized = strtolower(preg_replace('/\s+/', '', trim((string) $value)));
    $allowed = array(
        '#342e28' => '#342e28',
        'rgb(52,46,40)' => '#342e28',
        '#667653' => '#667653',
        'rgb(102,118,83)' => '#667653',
        '#82694f' => '#82694f',
        'rgb(130,105,79)' => '#82694f',
        '#a44743' => '#a44743',
        'rgb(164,71,67)' => '#a44743',
        '#315a7d' => '#315a7d',
        'rgb(49,90,125)' => '#315a7d'
    );

    return isset($allowed[$normalized]) ? $allowed[$normalized] : '';
}

function diaryContentAllowedHighlightColor($value) {
    $normalized = strtolower(preg_replace('/\s+/', '', trim((string) $value)));
    $allowed = array(
        '#fff1a8' => '#fff1a8',
        'rgb(255,241,168)' => '#fff1a8',
        'rgba(255,241,168,1)' => '#fff1a8',
        '#dce9ce' => '#dce9ce',
        'rgb(220,233,206)' => '#dce9ce',
        'rgba(220,233,206,1)' => '#dce9ce',
        '#f6d3bd' => '#f6d3bd',
        'rgb(246,211,189)' => '#f6d3bd',
        'rgba(246,211,189,1)' => '#f6d3bd',
        '#d8e7f3' => '#d8e7f3',
        'rgb(216,231,243)' => '#d8e7f3',
        'rgba(216,231,243,1)' => '#d8e7f3'
    );

    return isset($allowed[$normalized]) ? $allowed[$normalized] : '';
}

function diaryContentUsesUnderlineStyle($source_element) {
    $declarations = diaryContentParseInlineStyle($source_element->getAttribute('style'));
    $underline_sources = array();

    if (isset($declarations['text-decoration-line'])) {
        $underline_sources[] = $declarations['text-decoration-line'];
    }
    if (isset($declarations['text-decoration'])) {
        $underline_sources[] = $declarations['text-decoration'];
    }

    foreach ($underline_sources as $underline_source) {
        $tokens = preg_split('/\s+/', strtolower(trim((string) $underline_source)));
        if (is_array($tokens) && in_array('underline', $tokens, true)) {
            return true;
        }
    }

    return false;
}

function diaryContentAllowedTextAlign($value) {
    $normalized = strtolower(trim((string) $value));
    $allowed = array('left', 'center', 'right', 'justify');

    return in_array($normalized, $allowed, true) ? $normalized : '';
}

function diaryContentBuildSafeStyle($source_element, $output_tag) {
    $declarations = diaryContentParseInlineStyle($source_element->getAttribute('style'));
    $safe_styles = array();

    if ($output_tag === 'span') {
        $font_family_source = isset($declarations['font-family'])
            ? $declarations['font-family']
            : $source_element->getAttribute('face');
        $font_family = diaryContentAllowedFontFamily($font_family_source);
        $font_size = diaryContentAllowedFontSize(
            isset($declarations['font-size']) ? $declarations['font-size'] : ''
        );
        $text_color_source = isset($declarations['color'])
            ? $declarations['color']
            : $source_element->getAttribute('color');
        $text_color = diaryContentAllowedTextColor($text_color_source);
        $highlight_source = isset($declarations['background-color'])
            ? $declarations['background-color']
            : (isset($declarations['background']) ? $declarations['background'] : '');
        $highlight_color = diaryContentAllowedHighlightColor($highlight_source);

        if ($font_family !== '') {
            $safe_styles[] = 'font-family: ' . $font_family;
        }
        if ($font_size !== '') {
            $safe_styles[] = 'font-size: ' . $font_size;
        }
        if ($text_color !== '') {
            $safe_styles[] = 'color: ' . $text_color;
        }
        if ($highlight_color !== '') {
            $safe_styles[] = 'background-color: ' . $highlight_color;
        }
    }

    if ($output_tag === 'p') {
        $alignment_source = isset($declarations['text-align'])
            ? $declarations['text-align']
            : $source_element->getAttribute('align');
        $text_align = diaryContentAllowedTextAlign($alignment_source);

        if ($text_align !== '') {
            $safe_styles[] = 'text-align: ' . $text_align;
        }
    }

    return implode('; ', $safe_styles);
}

function diaryContentAppendSanitizedNode($parent_node, $sanitized_node) {
    if (
        $sanitized_node->nodeType === XML_DOCUMENT_FRAG_NODE
        && !$sanitized_node->hasChildNodes()
    ) {
        return;
    }

    $parent_node->appendChild($sanitized_node);
}

function diaryContentSanitizeNode($source_node, $clean_document, $image_user_id = null) {
    if ($source_node->nodeType === XML_TEXT_NODE) {
        return $clean_document->createTextNode($source_node->nodeValue);
    }

    if ($source_node->nodeType !== XML_ELEMENT_NODE) {
        return $clean_document->createDocumentFragment();
    }

    $source_tag = strtolower($source_node->nodeName);
    $removed_tags = array(
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'form', 'input', 'button', 'link', 'meta', 'video', 'audio',
        'source', 'track', 'canvas', 'template'
    );

    if (in_array($source_tag, $removed_tags, true)) {
        return $clean_document->createDocumentFragment();
    }

    $normalization_map = array(
        'div' => 'p',
        'strike' => 's',
        'ins' => 'u',
        'font' => 'span'
    );
    $output_tag = isset($normalization_map[$source_tag])
        ? $normalization_map[$source_tag]
        : $source_tag;
    $allowed_tags = array(
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li', 'span', 'blockquote', 'figure', 'figcaption', 'img'
    );

    if (!in_array($output_tag, $allowed_tags, true)) {
        $unwrapped = $clean_document->createDocumentFragment();

        foreach ($source_node->childNodes as $child_node) {
            diaryContentAppendSanitizedNode(
                $unwrapped,
                diaryContentSanitizeNode($child_node, $clean_document, $image_user_id)
            );
        }

        return $unwrapped;
    }

    // Captions are accepted only as plain text inside an approved image figure.
    // A standalone figcaption is discarded so it cannot be used as a generic
    // container to bypass the normal rich-text allow-list.
    if ($output_tag === 'figcaption') {
        return $clean_document->createDocumentFragment();
    }

    if ($output_tag === 'figure') {
        $source_image = null;
        $source_caption = null;

        foreach ($source_node->childNodes as $figure_child) {
            if ($figure_child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $figure_child_tag = strtolower($figure_child->nodeName);
            if ($figure_child_tag === 'img' && $source_image === null) {
                $source_image = $figure_child;
            } elseif ($figure_child_tag === 'figcaption' && $source_caption === null) {
                $source_caption = $figure_child;
            }
        }

        if ($source_image === null) {
            return $clean_document->createDocumentFragment();
        }

        if ($source_node->getAttribute('data-diary-object') === 'drawing') {
            $safe_src = diaryContentAllowedDrawingSrc(
                $source_image->getAttribute('src'),
                $image_user_id
            );
            $safe_x = diaryContentAllowedDrawingCoordinate(
                $source_node->getAttribute('data-diary-x')
            );
            $safe_y = diaryContentAllowedDrawingCoordinate(
                $source_node->getAttribute('data-diary-y')
            );
            $safe_width = diaryContentAllowedDrawingWidth(
                $source_node->getAttribute('data-diary-width')
            );
            $safe_rotation = diaryContentAllowedDrawingRotation(
                $source_node->getAttribute('data-diary-rotation')
            );

            if (
                $safe_src === ''
                || $safe_x === null
                || $safe_y === null
                || $safe_width === null
                || $safe_rotation === null
            ) {
                return $clean_document->createDocumentFragment();
            }

            $clean_drawing = $clean_document->createElement('figure');
            $clean_drawing->setAttribute('data-diary-object', 'drawing');
            $clean_drawing->setAttribute('data-diary-x', $safe_x);
            $clean_drawing->setAttribute('data-diary-y', $safe_y);
            $clean_drawing->setAttribute('data-diary-width', $safe_width);
            $clean_drawing->setAttribute('data-diary-rotation', $safe_rotation);

            $clean_drawing_image = $clean_document->createElement('img');
            $clean_drawing_image->setAttribute('src', $safe_src);
            $clean_drawing_image->setAttribute(
                'alt',
                diaryContentAllowedImageAlt($source_image->getAttribute('alt'))
            );
            $clean_drawing->appendChild($clean_drawing_image);

            return $clean_drawing;
        }

        $clean_image = diaryContentSanitizeNode(
            $source_image,
            $clean_document,
            $image_user_id
        );

        if (!($clean_image instanceof DOMElement) || strtolower($clean_image->nodeName) !== 'img') {
            return $clean_document->createDocumentFragment();
        }

        $clean_figure = $clean_document->createElement('figure');
        $safe_size = diaryContentAllowedImageSize(
            $source_node->getAttribute('data-diary-size')
        );
        $safe_alignment = diaryContentAllowedImageAlign(
            $source_node->getAttribute('data-diary-align')
        );
        $safe_wrap = diaryContentAllowedImageWrap(
            $source_node->getAttribute('data-diary-wrap')
        );

        // Accept the previous standalone-image representation as a fallback so
        // existing saved entries can be wrapped without losing their layout.
        if ($safe_size === '') {
            $safe_size = diaryContentAllowedImageSize(
                $source_image->getAttribute('data-diary-size')
            );
        }
        if ($safe_alignment === '') {
            $safe_alignment = diaryContentAllowedImageAlign(
                $source_image->getAttribute('data-diary-align')
            );
        }
        if ($safe_wrap === '') {
            $safe_wrap = diaryContentAllowedImageWrap(
                $source_image->getAttribute('data-diary-wrap')
            );
        }

        if ($safe_size !== '') {
            $clean_figure->setAttribute('data-diary-size', $safe_size);
        }
        if ($safe_alignment !== '') {
            $clean_figure->setAttribute('data-diary-align', $safe_alignment);
        }
        if ($safe_wrap !== '') {
            $clean_figure->setAttribute('data-diary-wrap', $safe_wrap);
        }

        $figure_style = diaryContentParseInlineStyle($source_node->getAttribute('style'));
        $safe_width = diaryContentAllowedImageWidth(
            isset($figure_style['width']) ? $figure_style['width'] : ''
        );
        if ($safe_width === '') {
            $image_style = diaryContentParseInlineStyle($source_image->getAttribute('style'));
            $safe_width = diaryContentAllowedImageWidth(
                isset($image_style['width']) ? $image_style['width'] : ''
            );
        }
        if ($safe_width !== '') {
            $clean_figure->setAttribute('style', 'width: ' . $safe_width);
        }

        $clean_image->removeAttribute('data-diary-size');
        $clean_image->removeAttribute('data-diary-align');
        $clean_image->removeAttribute('data-diary-wrap');
        $clean_image->removeAttribute('style');
        $clean_figure->appendChild($clean_image);

        if ($source_caption !== null) {
            $safe_caption = diaryContentAllowedImageCaption($source_caption->textContent);
            if ($safe_caption !== '') {
                $clean_caption = $clean_document->createElement('figcaption');
                $clean_caption->appendChild($clean_document->createTextNode($safe_caption));
                $clean_figure->appendChild($clean_caption);
            }
        }

        return $clean_figure;
    }

    if ($output_tag === 'img') {
        $safe_src = diaryContentAllowedImageSrc(
            $source_node->getAttribute('src'),
            $image_user_id
        );

        if ($safe_src === '') {
            return $clean_document->createDocumentFragment();
        }

        $clean_image = $clean_document->createElement('img');
        $clean_image->setAttribute('src', $safe_src);
        $clean_image->setAttribute(
            'alt',
            diaryContentAllowedImageAlt($source_node->getAttribute('alt'))
        );

        $safe_size = diaryContentAllowedImageSize(
            $source_node->getAttribute('data-diary-size')
        );
        $safe_alignment = diaryContentAllowedImageAlign(
            $source_node->getAttribute('data-diary-align')
        );
        $safe_wrap = diaryContentAllowedImageWrap(
            $source_node->getAttribute('data-diary-wrap')
        );

        if ($safe_size !== '') {
            $clean_image->setAttribute('data-diary-size', $safe_size);
        }
        if ($safe_alignment !== '') {
            $clean_image->setAttribute('data-diary-align', $safe_alignment);
        }
        if ($safe_wrap !== '') {
            $clean_image->setAttribute('data-diary-wrap', $safe_wrap);
        }

        $image_style = diaryContentParseInlineStyle($source_node->getAttribute('style'));
        $safe_width = diaryContentAllowedImageWidth(
            isset($image_style['width']) ? $image_style['width'] : ''
        );

        if ($safe_width !== '') {
            $clean_image->setAttribute('style', 'width: ' . $safe_width);
        }

        return $clean_image;
    }

    $clean_element = $clean_document->createElement($output_tag);
    $safe_style = diaryContentBuildSafeStyle($source_node, $output_tag);

    if ($safe_style !== '') {
        $clean_element->setAttribute('style', $safe_style);
    }

    foreach ($source_node->childNodes as $child_node) {
        diaryContentAppendSanitizedNode(
            $clean_element,
            diaryContentSanitizeNode($child_node, $clean_document, $image_user_id)
        );
    }

    // Browsers may represent underline as a span style. Store one approved,
    // predictable representation instead of allowing text-decoration in CSS.
    if ($output_tag === 'span' && diaryContentUsesUnderlineStyle($source_node)) {
        $underline_element = $clean_document->createElement('u');

        if ($clean_element->hasAttributes()) {
            $underline_element->appendChild($clean_element);
        } else {
            while ($clean_element->hasChildNodes()) {
                $underline_element->appendChild($clean_element->firstChild);
            }
        }

        return $underline_element;
    }

    return $clean_element;
}

function diaryContentSanitizeRichHtml($html, $image_user_id = null) {
    $parsed = diaryContentParseHtmlFragment($html);
    if ($parsed === null) {
        return '';
    }

    $source_body = $parsed[1];
    $clean_document = new DOMDocument('1.0', 'UTF-8');
    $clean_container = $clean_document->createElement('div');
    $clean_document->appendChild($clean_container);

    foreach ($source_body->childNodes as $source_node) {
        diaryContentAppendSanitizedNode(
            $clean_container,
            diaryContentSanitizeNode($source_node, $clean_document, $image_user_id)
        );
    }

    $sanitized = '';
    foreach ($clean_container->childNodes as $clean_node) {
        $sanitized .= $clean_document->saveHTML($clean_node);
    }

    return trim($sanitized);
}

function diaryContentCollectPlainText($node, &$plain_text) {
    if ($node->nodeType === XML_TEXT_NODE) {
        $plain_text .= $node->nodeValue;
        return;
    }

    if ($node->nodeType !== XML_ELEMENT_NODE) {
        return;
    }

    $tag = strtolower($node->nodeName);
    $block_tags = array('p', 'div', 'li', 'ul', 'ol', 'blockquote', 'figure', 'figcaption');

    if ($tag === 'br') {
        $plain_text .= "\n";
        return;
    }

    if (in_array($tag, $block_tags, true) && $plain_text !== '' && substr($plain_text, -1) !== "\n") {
        $plain_text .= "\n";
    }

    foreach ($node->childNodes as $child_node) {
        diaryContentCollectPlainText($child_node, $plain_text);
    }

    if (in_array($tag, $block_tags, true) && substr($plain_text, -1) !== "\n") {
        $plain_text .= "\n";
    }
}

function diaryContentPlainTextFromSanitizedHtml($html) {
    $parsed = diaryContentParseHtmlFragment($html);
    if ($parsed === null) {
        return '';
    }

    $plain_text = '';
    foreach ($parsed[1]->childNodes as $child_node) {
        diaryContentCollectPlainText($child_node, $plain_text);
    }

    return trim(html_entity_decode($plain_text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function diaryContentHasMeaningfulText($sanitized_html) {
    $plain_text = diaryContentPlainTextFromSanitizedHtml($sanitized_html);
    $visible_text = preg_replace('/[\s\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}]+/u', '', $plain_text);

    if ($visible_text !== null && $visible_text !== '') {
        return true;
    }

    $parsed = diaryContentParseHtmlFragment($sanitized_html);
    if ($parsed !== null && $parsed[1]->getElementsByTagName('img')->length > 0) {
        return true;
    }

    return false;
}

function diaryContentPrepareForStorage($submitted_html, $image_user_id = null) {
    $result = array(
        'valid' => false,
        'sanitized' => '',
        'stored' => '',
        'error' => 'Content is required.'
    );

    if (!is_string($submitted_html)) {
        return $result;
    }

    if (strlen($submitted_html) > diaryContentMaxStorageBytes()) {
        $result['error'] = 'Content is too long. Please shorten the journal entry.';
        return $result;
    }

    $sanitized = diaryContentSanitizeRichHtml($submitted_html, $image_user_id);
    $result['sanitized'] = $sanitized;

    if (!diaryContentHasMeaningfulText($sanitized)) {
        return $result;
    }

    $stored = diaryContentRichMarker() . $sanitized;
    if (strlen($stored) > diaryContentMaxStorageBytes()) {
        $result['error'] = 'Content is too long. Please shorten the journal entry.';
        return $result;
    }

    $result['valid'] = true;
    $result['stored'] = $stored;
    $result['error'] = '';

    return $result;
}

function diaryContentToPlainText($stored_content) {
    $stored_content = (string) $stored_content;

    if (!diaryContentIsRich($stored_content)) {
        return $stored_content;
    }

    $sanitized = diaryContentSanitizeRichHtml(diaryContentRichBody($stored_content));
    return diaryContentPlainTextFromSanitizedHtml($sanitized);
}

function diaryContentLegacyToSafeHtml($plain_text) {
    return nl2br(htmlspecialchars((string) $plain_text, ENT_QUOTES, 'UTF-8'), false);
}

function diaryContentAddDrawingPresentationStyles($sanitized_html) {
    $parsed = diaryContentParseHtmlFragment($sanitized_html);
    if ($parsed === null) {
        return '';
    }

    $document = $parsed[0];
    $body = $parsed[1];
    $figures = $body->getElementsByTagName('figure');

    foreach ($figures as $figure) {
        if ($figure->getAttribute('data-diary-object') !== 'drawing') {
            continue;
        }

        $safe_x = diaryContentAllowedDrawingCoordinate(
            $figure->getAttribute('data-diary-x')
        );
        $safe_y = diaryContentAllowedDrawingCoordinate(
            $figure->getAttribute('data-diary-y')
        );
        $safe_width = diaryContentAllowedDrawingWidth(
            $figure->getAttribute('data-diary-width')
        );
        $safe_rotation = diaryContentAllowedDrawingRotation(
            $figure->getAttribute('data-diary-rotation')
        );

        if (
            $safe_x === null
            || $safe_y === null
            || $safe_width === null
            || $safe_rotation === null
        ) {
            continue;
        }

        // These declarations are generated only from the closed numeric
        // allow-list above. Submitted style attributes are never copied.
        $figure->setAttribute(
            'style',
            'left: ' . $safe_x . '%; '
            . 'top: ' . $safe_y . '%; '
            . 'width: ' . $safe_width . '%; '
            . 'transform: translate(-50%, -50%) rotate(' . $safe_rotation . 'deg)'
        );
    }

    $rendered = '';
    foreach ($body->childNodes as $child_node) {
        $rendered .= $document->saveHTML($child_node);
    }

    return trim($rendered);
}

function diaryContentRenderSafeHtml($stored_content, $image_user_id = null) {
    $stored_content = (string) $stored_content;

    if (!diaryContentIsRich($stored_content)) {
        return diaryContentLegacyToSafeHtml($stored_content);
    }

    $sanitized = diaryContentSanitizeRichHtml(
        diaryContentRichBody($stored_content),
        $image_user_id
    );

    return diaryContentAddDrawingPresentationStyles($sanitized);
}
?>
