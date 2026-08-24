<?php

/**
 * Return a canonical, internal Diary collection URL or a safe fallback.
 *
 * Collection return targets never include a host, scheme, filesystem path, or
 * unapproved query parameter. This keeps navigation context reusable across
 * View, Edit, Favorite, and Delete without creating an open redirect.
 */
function diaryNavigationSearchMaxLength() {
    return 100;
}

function diaryNavigationSanitizeReturnTo($value, $fallback = 'index.php') {
    $safe_value = diaryNavigationValidateCollectionTarget($value);
    if ($safe_value !== null) {
        return $safe_value;
    }

    $safe_fallback = diaryNavigationValidateCollectionTarget($fallback);
    return $safe_fallback !== null ? $safe_fallback : 'index.php';
}

function diaryNavigationBuildReturnTo($destination, $parameters = array(), $fragment = '') {
    if (!is_array($parameters)) {
        $parameters = array();
    }

    $candidate = (string) $destination;
    if (!empty($parameters)) {
        $candidate .= '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }
    if ($fragment !== '') {
        $candidate .= '#' . $fragment;
    }

    return diaryNavigationSanitizeReturnTo($candidate);
}

function diaryNavigationIsSequenceMode($value) {
    return is_string($value) && $value === '1';
}

function diaryNavigationViewUrl($diary_id, $return_to = 'index.php', $sequence_mode = false) {
    $safe_id = diaryNavigationPositiveId($diary_id);
    if ($safe_id === null) {
        return diaryNavigationSanitizeReturnTo($return_to);
    }

    $url = 'view.php?id=' . rawurlencode((string) $safe_id)
        . '&return_to=' . rawurlencode(diaryNavigationSanitizeReturnTo($return_to));

    return $sequence_mode === true ? $url . '&sequence=1' : $url;
}

function diaryNavigationEditUrl($diary_id, $return_to = 'index.php') {
    $safe_id = diaryNavigationPositiveId($diary_id);
    if ($safe_id === null) {
        return diaryNavigationSanitizeReturnTo($return_to);
    }

    return 'edit.php?id=' . rawurlencode((string) $safe_id)
        . '&return_to=' . rawurlencode(diaryNavigationSanitizeReturnTo($return_to));
}

/**
 * Favorite actions may return either to a collection or to the same View page.
 * The expected diary ID prevents a form from redirecting to a different entry.
 */
function diaryNavigationSanitizeActionTarget($value, $expected_diary_id = null) {
    $collection_target = diaryNavigationValidateCollectionTarget($value);
    if ($collection_target !== null) {
        return $collection_target;
    }

    if (!is_string($value) || strlen($value) > 4096 || diaryNavigationHasUnsafeCharacters($value)) {
        return 'index.php';
    }

    $parts = parse_url($value);
    if (!is_array($parts)
        || isset($parts['scheme'])
        || isset($parts['host'])
        || isset($parts['port'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['fragment'])
        || !isset($parts['path'])
        || $parts['path'] !== 'view.php'
    ) {
        return 'index.php';
    }

    $query = diaryNavigationParseFlatQuery(isset($parts['query']) ? $parts['query'] : '');
    if ($query === null || array_diff(array_keys($query), array('id', 'return_to', 'sequence')) !== array()) {
        return 'index.php';
    }
    if (!isset($query['id'], $query['return_to']) || !in_array(count($query), array(2, 3), true)) {
        return 'index.php';
    }

    $sequence_mode = isset($query['sequence']) && diaryNavigationIsSequenceMode($query['sequence']);
    if (isset($query['sequence']) && !$sequence_mode) {
        return 'index.php';
    }

    $safe_id = diaryNavigationPositiveId($query['id']);
    $expected_id = diaryNavigationPositiveId($expected_diary_id);
    if ($safe_id === null || $expected_id === null || $safe_id !== $expected_id) {
        return 'index.php';
    }

    $nested_return_to = diaryNavigationValidateCollectionTarget($query['return_to']);
    if ($nested_return_to === null) {
        return 'index.php';
    }

    return diaryNavigationViewUrl($safe_id, $nested_return_to, $sequence_mode);
}

function diaryNavigationEntriesForContext($entries, $return_to) {
    if (!is_array($entries)) {
        return array();
    }

    $safe_return_to = diaryNavigationSanitizeReturnTo($return_to);
    $parts = parse_url($safe_return_to);
    $query = diaryNavigationParseFlatQuery(
        is_array($parts) && isset($parts['query']) ? $parts['query'] : ''
    );
    $query = is_array($query) ? $query : array();
    $destination = is_array($parts) && isset($parts['path']) ? $parts['path'] : 'index.php';
    $fragment = is_array($parts) && isset($parts['fragment']) ? $parts['fragment'] : '';
    $filter_collection = $destination === 'all_entries.php' || $fragment === 'diary-filters';
    $date_collection = $fragment === 'selected-date-entries' && isset($query['date']);
    $favorites_collection = isset($query['favorites']) && $query['favorites'] === '1';
    $favorites_collection = $favorites_collection || $fragment === 'favorite-entries';

    $filtered = array_values(array_filter($entries, function ($entry) use (
        $query,
        $filter_collection,
        $date_collection,
        $favorites_collection
    ) {
        if ($date_collection
            && (!isset($entry['entry_date']) || $entry['entry_date'] !== $query['date'])
        ) {
            return false;
        }

        if ($favorites_collection
            && (!isset($entry['is_favorite']) || (int) $entry['is_favorite'] !== 1)
        ) {
            return false;
        }

        if (!$filter_collection) {
            return true;
        }

        if (isset($query['mood'])
            && (!isset($entry['mood']) || $entry['mood'] !== $query['mood'])
        ) {
            return false;
        }

        if (isset($query['weather'])) {
            $entry_weather = isset($entry['weather']) && is_string($entry['weather'])
                ? trim($entry['weather'])
                : '';
            if (($query['weather'] === 'not-set' && $entry_weather !== '')
                || ($query['weather'] !== 'not-set' && $entry_weather !== $query['weather'])
            ) {
                return false;
            }
        }

        if (isset($query['search'])) {
            $title = isset($entry['title']) ? (string) $entry['title'] : '';
            $content = isset($entry['content']) ? $entry['content'] : '';
            $visible_content = function_exists('diaryContentToPlainText')
                ? diaryContentToPlainText($content)
                : (string) $content;
            $search = $query['search'];
            $title_matches = function_exists('mb_stripos')
                ? mb_stripos($title, $search, 0, 'UTF-8') !== false
                : stripos($title, $search) !== false;
            $content_matches = function_exists('mb_stripos')
                ? mb_stripos($visible_content, $search, 0, 'UTF-8') !== false
                : stripos($visible_content, $search) !== false;

            if (!$title_matches && !$content_matches) {
                return false;
            }
        }

        return true;
    }));

    if ($filter_collection && isset($query['sort']) && $query['sort'] !== 'newest') {
        $sort_value = $query['sort'];
        usort($filtered, function ($first_entry, $second_entry) use ($sort_value) {
            $first_date = isset($first_entry['entry_date']) ? (string) $first_entry['entry_date'] : '';
            $second_date = isset($second_entry['entry_date']) ? (string) $second_entry['entry_date'] : '';
            $first_created = isset($first_entry['created_at']) ? (string) $first_entry['created_at'] : '';
            $second_created = isset($second_entry['created_at']) ? (string) $second_entry['created_at'] : '';

            if ($sort_value === 'updated') {
                $first_updated = isset($first_entry['updated_at']) ? (string) $first_entry['updated_at'] : '';
                $second_updated = isset($second_entry['updated_at']) ? (string) $second_entry['updated_at'] : '';
                $comparison = strcmp($second_updated, $first_updated);
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            if ($sort_value === 'oldest') {
                $comparison = strcmp($first_date, $second_date);
                return $comparison !== 0 ? $comparison : strcmp($first_created, $second_created);
            }

            $comparison = strcmp($second_date, $first_date);
            return $comparison !== 0 ? $comparison : strcmp($second_created, $first_created);
        });
    }

    if ($fragment === 'journal-entries-heading') {
        return array_slice($filtered, 0, 4);
    }

    if ($fragment === 'diary-today-corner') {
        return array_slice($filtered, 4);
    }

    return $filtered;
}

function diaryNavigationValidateCollectionTarget($value) {
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    if ($value === '' || strlen($value) > 2048 || diaryNavigationHasUnsafeCharacters($value)) {
        return null;
    }

    $parts = parse_url($value);
    if (!is_array($parts)
        || isset($parts['scheme'])
        || isset($parts['host'])
        || isset($parts['port'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || !isset($parts['path'])
    ) {
        return null;
    }

    $destination = $parts['path'];
    $allowed_parameters = array(
        'index.php' => array('search', 'mood', 'weather', 'sort', 'month', 'date', 'favorites'),
        'all_entries.php' => array('search', 'mood', 'weather', 'sort', 'favorites', 'page'),
        'memory_album.php' => array()
    );
    $allowed_fragments = array(
        'index.php' => array(
            '',
            'diary-today-corner',
            'diary-filters',
            'journal-calendar',
            'selected-date-entries',
            'monthly-reflection',
            'favorite-entries',
            'journal-entries-heading'
        ),
        'all_entries.php' => array('', 'diary-filters', 'all-entries-heading'),
        'memory_album.php' => array('')
    );

    if (!array_key_exists($destination, $allowed_parameters)) {
        return null;
    }

    $fragment = isset($parts['fragment']) ? $parts['fragment'] : '';
    if (!in_array($fragment, $allowed_fragments[$destination], true)) {
        return null;
    }

    $query = diaryNavigationParseFlatQuery(isset($parts['query']) ? $parts['query'] : '');
    if ($query === null || array_diff(array_keys($query), $allowed_parameters[$destination]) !== array()) {
        return null;
    }

    $canonical = array();
    foreach ($query as $key => $query_value) {
        if ($key === 'search') {
            $search = trim($query_value);
            if (
                $search === ''
                || diaryNavigationTextLength($search) > diaryNavigationSearchMaxLength()
            ) {
                return null;
            }
            $canonical['search'] = $search;
            continue;
        }

        if ($key === 'mood') {
            if (!in_array($query_value, array('Happy', 'Calm', 'Neutral', 'Sad', 'Stressed'), true)) {
                return null;
            }
            $canonical['mood'] = $query_value;
            continue;
        }

        if ($key === 'weather') {
            if (!in_array($query_value, array('Sunny', 'Cloudy', 'Rainy', 'Windy', 'Stormy', 'not-set'), true)) {
                return null;
            }
            $canonical['weather'] = $query_value;
            continue;
        }

        if ($key === 'sort') {
            if (!in_array($query_value, array('newest', 'oldest', 'updated'), true)) {
                return null;
            }
            $canonical['sort'] = $query_value;
            continue;
        }

        if ($key === 'month') {
            if (!diaryNavigationIsValidMonth($query_value)) {
                return null;
            }
            $canonical['month'] = $query_value;
            continue;
        }

        if ($key === 'date') {
            if (!diaryNavigationIsValidDate($query_value)) {
                return null;
            }
            $canonical['date'] = $query_value;
            continue;
        }

        if ($key === 'favorites') {
            if ($query_value !== '1') {
                return null;
            }
            $canonical['favorites'] = '1';
            continue;
        }

        if ($key === 'page') {
            $page = diaryNavigationPositiveId($query_value);
            if ($page === null) {
                return null;
            }
            if ($page > 1) {
                $canonical['page'] = (string) $page;
            }
            continue;
        }
    }

    if (isset($canonical['date'])) {
        if (!isset($canonical['month']) || substr($canonical['date'], 0, 7) !== $canonical['month']) {
            return null;
        }
    }

    $safe_target = $destination;
    if (!empty($canonical)) {
        $safe_target .= '?' . http_build_query($canonical, '', '&', PHP_QUERY_RFC3986);
    }
    if ($fragment !== '') {
        $safe_target .= '#' . $fragment;
    }

    return $safe_target;
}

function diaryNavigationParseFlatQuery($query) {
    if (!is_string($query) || strlen($query) > 1500 || diaryNavigationHasUnsafeCharacters($query)) {
        return null;
    }
    if ($query === '') {
        return array();
    }

    $values = array();
    foreach (explode('&', $query) as $component) {
        if ($component === '') {
            return null;
        }

        $pair = explode('=', $component, 2);
        $key = urldecode($pair[0]);
        $value = urldecode(isset($pair[1]) ? $pair[1] : '');

        if ($key === ''
            || array_key_exists($key, $values)
            || diaryNavigationHasUnsafeCharacters($key)
            || diaryNavigationHasUnsafeCharacters($value)
        ) {
            return null;
        }

        $values[$key] = $value;
    }

    return $values;
}

function diaryNavigationPositiveId($value) {
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (!is_string($value) && !is_numeric($value)) {
        return null;
    }

    $validated = filter_var(
        (string) $value,
        FILTER_VALIDATE_INT,
        array('options' => array('min_range' => 1))
    );

    return $validated === false ? null : (int) $validated;
}

function diaryNavigationIsValidMonth($value) {
    if (!is_string($value) || preg_match('/\A[1-9][0-9]{3}-(0[1-9]|1[0-2])\z/D', $value) !== 1) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m', $value);
    $errors = DateTimeImmutable::getLastErrors();
    $has_errors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

    return $date && !$has_errors && $date->format('Y-m') === $value;
}

function diaryNavigationIsValidDate($value) {
    if (!is_string($value)
        || preg_match('/\A[1-9][0-9]{3}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])\z/D', $value) !== 1
    ) {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    $has_errors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

    return $date && !$has_errors && $date->format('Y-m-d') === $value;
}

function diaryNavigationHasUnsafeCharacters($value) {
    return !is_string($value)
        || strpos($value, '\\') !== false
        || preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
}

function diaryNavigationTextLength($value) {
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);
}
