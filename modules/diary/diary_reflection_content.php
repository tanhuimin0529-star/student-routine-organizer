<?php
// Strict rich-text handling for Diary monthly reflections.
// This intentionally excludes the images, drawings, styles, and metadata
// supported by full Diary journal entries.

function diaryReflectionContentMaxBytes() {
    return 30000;
}

function diaryReflectionContentParseHtmlFragment($html) {
    if (!class_exists('DOMDocument')) {
        return null;
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previous_error_setting = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
        . (string) $html
        . '</body></html>',
        LIBXML_NONET
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous_error_setting);

    if (!$loaded) {
        return null;
    }

    $body_nodes = $document->getElementsByTagName('body');
    $body = $body_nodes->item(0);

    return $body ? array($document, $body) : null;
}

function diaryReflectionContentAppendNode($parent, $node) {
    if ($node->nodeType === XML_DOCUMENT_FRAG_NODE && !$node->hasChildNodes()) {
        return;
    }

    $parent->appendChild($node);
}

function diaryReflectionContentSanitizeNode($source_node, $clean_document) {
    if ($source_node->nodeType === XML_TEXT_NODE) {
        return $clean_document->createTextNode($source_node->nodeValue);
    }

    if ($source_node->nodeType !== XML_ELEMENT_NODE) {
        return $clean_document->createDocumentFragment();
    }

    $source_tag = strtolower($source_node->nodeName);
    $discard_with_contents = array(
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'form', 'input', 'button', 'link', 'meta', 'video', 'audio',
        'source', 'track', 'canvas', 'template', 'img', 'figure', 'figcaption'
    );

    if (in_array($source_tag, $discard_with_contents, true)) {
        return $clean_document->createDocumentFragment();
    }

    // contenteditable commonly creates div blocks. Normalize those blocks to
    // an allowed paragraph while still emitting only the strict allow-list.
    $output_tag = $source_tag === 'div' ? 'p' : $source_tag;
    $allowed_tags = array('p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li');

    if (!in_array($output_tag, $allowed_tags, true)) {
        // Keep safe text from unsupported formatting (including links), but
        // discard the unsupported element and all of its attributes.
        $fragment = $clean_document->createDocumentFragment();
        foreach ($source_node->childNodes as $child_node) {
            diaryReflectionContentAppendNode(
                $fragment,
                diaryReflectionContentSanitizeNode($child_node, $clean_document)
            );
        }

        return $fragment;
    }

    $clean_element = $clean_document->createElement($output_tag);
    foreach ($source_node->childNodes as $child_node) {
        diaryReflectionContentAppendNode(
            $clean_element,
            diaryReflectionContentSanitizeNode($child_node, $clean_document)
        );
    }

    // No attributes are copied. This removes styles, classes, IDs, event
    // handlers, data attributes, URLs, and every other submitted attribute.
    return $clean_element;
}

function diaryReflectionContentSanitize($html) {
    $parsed = diaryReflectionContentParseHtmlFragment($html);
    if ($parsed === null) {
        return '';
    }

    $source_body = $parsed[1];
    $clean_document = new DOMDocument('1.0', 'UTF-8');
    $clean_container = $clean_document->createElement('div');
    $clean_document->appendChild($clean_container);

    foreach ($source_body->childNodes as $source_node) {
        diaryReflectionContentAppendNode(
            $clean_container,
            diaryReflectionContentSanitizeNode($source_node, $clean_document)
        );
    }

    $sanitized = '';
    foreach ($clean_container->childNodes as $clean_node) {
        $sanitized .= $clean_document->saveHTML($clean_node);
    }

    return trim($sanitized);
}

function diaryReflectionContentHasMeaningfulText($sanitized_html) {
    $parsed = diaryReflectionContentParseHtmlFragment($sanitized_html);
    if ($parsed === null) {
        return false;
    }

    $visible_text = $parsed[1]->textContent;
    $visible_text = preg_replace(
        '/[\s\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}]+/u',
        '',
        $visible_text
    );

    return $visible_text !== null && $visible_text !== '';
}

function diaryReflectionContentValidate($submitted_html) {
    $result = array(
        'valid' => false,
        'sanitized' => '',
        'error' => 'Please write something in your monthly reflection.'
    );
    $submitted_html = is_string($submitted_html) ? $submitted_html : '';

    if (strlen($submitted_html) > diaryReflectionContentMaxBytes()) {
        $result['error'] = 'Your monthly reflection is too long. Please shorten it.';
        return $result;
    }

    if (!class_exists('DOMDocument')) {
        $result['error'] = 'Your monthly reflection could not be processed right now. Please try again.';
        return $result;
    }

    $sanitized = diaryReflectionContentSanitize($submitted_html);
    if (!diaryReflectionContentHasMeaningfulText($sanitized)) {
        return $result;
    }

    if (strlen($sanitized) > diaryReflectionContentMaxBytes()) {
        $result['error'] = 'Your monthly reflection is too long. Please shorten it.';
        return $result;
    }

    $result['valid'] = true;
    $result['sanitized'] = $sanitized;
    $result['error'] = '';

    return $result;
}