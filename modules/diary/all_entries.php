<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/shared_navbar.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_content.php';
require_once __DIR__ . '/diary_navigation.php';

$delete_flash = isset($_SESSION['diary_delete_flash']) && is_array($_SESSION['diary_delete_flash'])
    ? $_SESSION['diary_delete_flash']
    : null;
unset($_SESSION['diary_delete_flash']);
$favorite_flash = isset($_SESSION['diary_favorite_flash']) && is_array($_SESSION['diary_favorite_flash'])
    ? $_SESSION['diary_favorite_flash']
    : null;
unset($_SESSION['diary_favorite_flash']);


$entries = getDiaryEntriesForUser($conn, $logged_in_user_id);
$load_error = $entries === false;

if ($load_error) {
    $entries = array();
}

$search_term = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search'])
    : '';
$search_error = diaryNavigationTextLength($search_term) > diaryNavigationSearchMaxLength()
    ? 'Search keyword must be 100 characters or fewer.'
    : '';
$validated_search_term = $search_error === '' ? $search_term : '';

$allowed_mood_filters = array('Happy', 'Calm', 'Neutral', 'Sad', 'Stressed');
$mood_filter_options = array(
    '' => array('label' => 'All', 'emoji' => '🌿'),
    'Happy' => array('label' => 'Happy', 'emoji' => '😊'),
    'Calm' => array('label' => 'Calm', 'emoji' => '😌'),
    'Neutral' => array('label' => 'Neutral', 'emoji' => '😐'),
    'Sad' => array('label' => 'Sad', 'emoji' => '😢'),
    'Stressed' => array('label' => 'Stressed', 'emoji' => '😣')
);
$requested_mood_filter = isset($_GET['mood']) && is_string($_GET['mood'])
    ? $_GET['mood']
    : '';
$mood_filter = in_array($requested_mood_filter, $allowed_mood_filters, true)
    ? $requested_mood_filter
    : '';
$allowed_weather_filters = array('Sunny', 'Cloudy', 'Rainy', 'Windy', 'Stormy', 'not-set');
$weather_filter_options = array(
    '' => array('label' => 'All Weather', 'emoji' => '🌦️'),
    'Sunny' => array('label' => 'Sunny', 'emoji' => '☀️'),
    'Cloudy' => array('label' => 'Cloudy', 'emoji' => '☁️'),
    'Rainy' => array('label' => 'Rainy', 'emoji' => '🌧️'),
    'Windy' => array('label' => 'Windy', 'emoji' => '💨'),
    'Stormy' => array('label' => 'Stormy', 'emoji' => '⛈️'),
    'not-set' => array('label' => 'Not set', 'emoji' => '—')
);
$requested_weather_filter = isset($_GET['weather']) && is_string($_GET['weather'])
    ? $_GET['weather']
    : '';
$weather_filter = in_array($requested_weather_filter, $allowed_weather_filters, true)
    ? $requested_weather_filter
    : '';

$allowed_sort_values = array('newest', 'oldest', 'updated');
$requested_sort = isset($_GET['sort']) && is_string($_GET['sort'])
    ? $_GET['sort']
    : 'newest';
$sort_value = in_array($requested_sort, $allowed_sort_values, true)
    ? $requested_sort
    : 'newest';

$favorites_only = isset($_GET['favorites'])
    && is_string($_GET['favorites'])
    && $_GET['favorites'] === '1';
$requested_page = isset($_GET['page']) && is_string($_GET['page'])
    ? $_GET['page']
    : '1';
$validated_page = filter_var(
    $requested_page,
    FILTER_VALIDATE_INT,
    array('options' => array('min_range' => 1))
);
$current_page = $validated_page === false ? 1 : (int) $validated_page;

if (empty($_SESSION['diary_delete_csrf_token'])) {
    $_SESSION['diary_delete_csrf_token'] = bin2hex(random_bytes(32));
}

if (empty($_SESSION['diary_favorite_csrf_token'])) {
    $_SESSION['diary_favorite_csrf_token'] = bin2hex(random_bytes(32));
}

$diary_css_version = filemtime(__DIR__ . '/../../assets/css/diary.css');
$diary_js_version = filemtime(__DIR__ . '/../../assets/js/diary.js');

function allEntriesEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function allEntriesFavoriteButton($entry, $csrf_token, $return_to) {
    $diary_id = isset($entry['diary_id']) ? (int) $entry['diary_id'] : 0;
    $is_favorite = isset($entry['is_favorite']) && (int) $entry['is_favorite'] === 1;
    $next_favorite = $is_favorite ? 0 : 1;
    $label = $is_favorite ? 'Remove from favorites' : 'Add to favorites';
    ?>
    <form class="diary-favorite-form" action="favorite_handler.php" method="post">
        <input type="hidden" name="diary_id" value="<?php echo allEntriesEscape($diary_id); ?>">
        <input type="hidden" name="favorite" value="<?php echo allEntriesEscape($next_favorite); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo allEntriesEscape($csrf_token); ?>">
        <input type="hidden" name="return_to" value="<?php echo allEntriesEscape($return_to); ?>">
        <button
            class="diary-favorite-button<?php echo $is_favorite ? ' is-favorite' : ''; ?>"
            type="submit"
            aria-label="<?php echo allEntriesEscape($label); ?>"
            aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>"
            title="<?php echo allEntriesEscape($label); ?>"
        ><?php echo $is_favorite ? '★' : '☆'; ?></button>
    </form>
    <?php
}

function allEntriesContentPreview($content, $limit = 180) {
    $content = diaryContentToPlainText($content);
    $content = trim(preg_replace('/\s+/', ' ', $content));

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($content, 'UTF-8') > $limit
            ? mb_substr($content, 0, $limit, 'UTF-8') . '...'
            : $content;
    }

    return strlen($content) > $limit
        ? substr($content, 0, $limit) . '...'
        : $content;
}

function allEntriesCardDate($entry_date) {
    $entry_date = (string) $entry_date;
    $date = DateTime::createFromFormat('!Y-m-d', $entry_date);

    return $date && $date->format('Y-m-d') === $entry_date
        ? strtoupper($date->format('M d'))
        : $entry_date;
}

function allEntriesMoodEmoji($mood) {
    $icons = array(
        'Happy' => '😊',
        'Calm' => '😌',
        'Neutral' => '😐',
        'Sad' => '😢',
        'Stressed' => '😣'
    );

    return isset($icons[$mood]) ? $icons[$mood] : '📝';
}

function allEntriesContainsSearch($value, $search_term) {
    $value = (string) $value;

    if (function_exists('mb_stripos')) {
        return mb_stripos($value, $search_term, 0, 'UTF-8') !== false;
    }

    return stripos($value, $search_term) !== false;
}

function allEntriesPaginationUrl($page, $parameters) {
    $safe_parameters = is_array($parameters) ? $parameters : array();
    $safe_page = max(1, (int) $page);
    unset($safe_parameters['page']);

    if ($safe_page > 1) {
        $safe_parameters['page'] = (string) $safe_page;
    }

    return diaryNavigationBuildReturnTo(
        'all_entries.php',
        $safe_parameters,
        'all-entries-heading'
    );
}

$filtered_entries = $entries;
$filters_active = $validated_search_term !== '' || $mood_filter !== '' || $weather_filter !== '' || $favorites_only;

if ($validated_search_term !== '') {
    $filtered_entries = array_values(array_filter($filtered_entries, function ($entry) use ($validated_search_term) {
        $title = isset($entry['title']) ? $entry['title'] : '';
        $content = isset($entry['content'])
            ? diaryContentToPlainText($entry['content'])
            : '';

        return allEntriesContainsSearch($title, $validated_search_term)
            || allEntriesContainsSearch($content, $validated_search_term);
    }));
}

if ($mood_filter !== '') {
    $filtered_entries = array_values(array_filter($filtered_entries, function ($entry) use ($mood_filter) {
        $entry_mood = isset($entry['mood']) ? $entry['mood'] : '';

        return $entry_mood === $mood_filter;
    }));
}

if ($weather_filter !== '') {
    $filtered_entries = array_values(array_filter($filtered_entries, function ($entry) use ($weather_filter) {
        $entry_weather = isset($entry['weather']) && is_string($entry['weather'])
            ? trim($entry['weather'])
            : '';

        return $weather_filter === 'not-set'
            ? $entry_weather === ''
            : $entry_weather === $weather_filter;
    }));
}

if ($favorites_only) {
    $filtered_entries = array_values(array_filter($filtered_entries, function ($entry) {
        return isset($entry['is_favorite']) && (int) $entry['is_favorite'] === 1;
    }));
}

usort($filtered_entries, function ($first_entry, $second_entry) use ($sort_value) {
    $first_entry_date = isset($first_entry['entry_date']) ? (string) $first_entry['entry_date'] : '';
    $second_entry_date = isset($second_entry['entry_date']) ? (string) $second_entry['entry_date'] : '';
    $first_created_at = isset($first_entry['created_at']) ? (string) $first_entry['created_at'] : '';
    $second_created_at = isset($second_entry['created_at']) ? (string) $second_entry['created_at'] : '';

    if ($sort_value === 'updated') {
        $first_updated_at = isset($first_entry['updated_at']) ? (string) $first_entry['updated_at'] : '';
        $second_updated_at = isset($second_entry['updated_at']) ? (string) $second_entry['updated_at'] : '';
        $updated_comparison = strcmp($second_updated_at, $first_updated_at);

        if ($updated_comparison !== 0) {
            return $updated_comparison;
        }

        $date_comparison = strcmp($second_entry_date, $first_entry_date);
        return $date_comparison !== 0
            ? $date_comparison
            : strcmp($second_created_at, $first_created_at);
    }

    if ($sort_value === 'oldest') {
        $date_comparison = strcmp($first_entry_date, $second_entry_date);
        return $date_comparison !== 0
            ? $date_comparison
            : strcmp($first_created_at, $second_created_at);
    }

    $date_comparison = strcmp($second_entry_date, $first_entry_date);
    return $date_comparison !== 0
        ? $date_comparison
        : strcmp($second_created_at, $first_created_at);
});

$result_count = count($filtered_entries);
$entries_per_page = 8;
$total_pages = max(1, (int) ceil($result_count / $entries_per_page));

if ($current_page > $total_pages) {
    $current_page = $total_pages;
}

$page_offset = ($current_page - 1) * $entries_per_page;
$page_entries = array_slice($filtered_entries, $page_offset, $entries_per_page);
$all_entries_navigation_parameters = array();
if ($validated_search_term !== '') {
    $all_entries_navigation_parameters['search'] = $validated_search_term;
}
if ($mood_filter !== '') {
    $all_entries_navigation_parameters['mood'] = $mood_filter;
}
if ($weather_filter !== '') {
    $all_entries_navigation_parameters['weather'] = $weather_filter;
}
if ($sort_value !== 'newest') {
    $all_entries_navigation_parameters['sort'] = $sort_value;
}
if ($favorites_only) {
    $all_entries_navigation_parameters['favorites'] = '1';
}
if ($current_page > 1) {
    $all_entries_navigation_parameters['page'] = (string) $current_page;
}
$all_entries_context = diaryNavigationBuildReturnTo(
    'all_entries.php',
    $all_entries_navigation_parameters,
    'diary-filters'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Journal Entries</title>
    <script src="../../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../../assets/css/theme.css">
    <?php renderSharedNavbarAssets('../../'); ?>
    <link rel="stylesheet" href="../../assets/css/diary.css?v=<?php echo rawurlencode((string) $diary_css_version); ?>">
    <script src="../../assets/js/diary.js?v=<?php echo rawurlencode((string) $diary_js_version); ?>" defer></script>
</head>
<body class="diary-page diary-home-page diary-all-entries-page">

    <?php renderIntegratedModuleHeader('../../', 'diary'); ?>
    <main class="diary-container">
        <section class="diary-home-hero diary-all-entries-hero">
            <header class="diary-list-header">
                <div class="diary-title-group">
                    <p class="diary-eyebrow">Personal Journal</p>
                    <h1>All Journal Entries</h1>
                    <p>Browse every thought, reflection, and memory in your journal.</p>
                </div>
            </header>
        </section>

        <?php if ($delete_flash !== null): ?>
            <?php $delete_flash_type = isset($delete_flash['type']) && $delete_flash['type'] === 'success' ? 'success' : 'error'; ?>
            <div
                class="diary-alert diary-alert-<?php echo allEntriesEscape($delete_flash_type); ?>"
                data-diary-flash="<?php echo allEntriesEscape($delete_flash_type); ?>"
                role="<?php echo $delete_flash_type === 'success' ? 'status' : 'alert'; ?>"
            >
                <?php echo allEntriesEscape(isset($delete_flash['message']) ? $delete_flash['message'] : 'Journal entry could not be deleted.'); ?>
            </div>
        <?php endif; ?>

        <?php if ($favorite_flash !== null): ?>
            <?php $favorite_flash_type = isset($favorite_flash['type']) && $favorite_flash['type'] === 'success' ? 'success' : 'error'; ?>
            <div
                class="diary-alert diary-alert-<?php echo allEntriesEscape($favorite_flash_type); ?>"
                data-diary-flash="<?php echo allEntriesEscape($favorite_flash_type); ?>"
                role="<?php echo $favorite_flash_type === 'success' ? 'status' : 'alert'; ?>"
            >
                <?php echo allEntriesEscape(isset($favorite_flash['message']) ? $favorite_flash['message'] : 'Favorite could not be updated right now. Please try again.'); ?>
            </div>
        <?php endif; ?>

        <section class="diary-search-panel" id="diary-filters" aria-labelledby="diary-search-heading">
            <?php if ($search_error !== ''): ?>
                <div class="diary-alert diary-alert-error" role="alert">
                    <?php echo allEntriesEscape($search_error); ?>
                </div>
            <?php endif; ?>
            <form class="diary-search-form" action="all_entries.php#diary-filters" method="get" role="search">
                <label id="diary-search-heading" for="search">Search journal entries</label>
                <?php if ($favorites_only): ?>
                    <input type="hidden" name="favorites" value="1">
                <?php endif; ?>
                <input type="hidden" name="mood" value="<?php echo allEntriesEscape($mood_filter); ?>">
                <input type="hidden" name="weather" value="<?php echo allEntriesEscape($weather_filter); ?>">

                <div class="diary-search-controls diary-all-entries-search-controls">
                    <input
                        type="search"
                        id="search"
                        name="search"
                        maxlength="100"
                        value="<?php echo allEntriesEscape($search_term); ?>"
                        placeholder="Search by title or content"
                    >

                    <button class="diary-button" type="submit">Search</button>
                    <a class="diary-button diary-button-secondary diary-all-entries-clear" href="all_entries.php#diary-filters">Clear Filters</a>
                </div>
                <div class="diary-all-entries-filter-row">
                    <div class="diary-filter-toolbar-groups">
                        <div class="diary-filter-group">
                            <span class="diary-filter-group-label">Mood</span>
                            <div class="diary-mood-filter-toolbar" role="group" aria-label="Filter journal entries by mood">
                            <?php foreach ($mood_filter_options as $mood_value => $mood_option): ?>
                                <?php $mood_is_active = $mood_filter === $mood_value; ?>
                                <button
                                    class="diary-mood-filter-option<?php echo $mood_is_active ? ' is-active' : ''; ?>"
                                    type="submit"
                                    name="mood"
                                    value="<?php echo allEntriesEscape($mood_value); ?>"
                                    aria-pressed="<?php echo $mood_is_active ? 'true' : 'false'; ?>"
                                >
                                    <span class="diary-mood-filter-emoji" aria-hidden="true"><?php echo allEntriesEscape($mood_option['emoji']); ?></span>
                                    <span><?php echo allEntriesEscape($mood_option['label']); ?></span>
                                </button>
                            <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="diary-filter-group diary-weather-filter-group">
                            <span class="diary-filter-group-label">Weather</span>
                            <div class="diary-mood-filter-toolbar diary-weather-filter-toolbar" role="group" aria-label="Filter journal entries by weather">
                            <?php foreach ($weather_filter_options as $weather_value => $weather_option): ?>
                                <?php $weather_is_active = $weather_filter === $weather_value; ?>
                                <button
                                    class="diary-mood-filter-option diary-weather-filter-option<?php echo $weather_is_active ? ' is-active' : ''; ?>"
                                    type="submit"
                                    name="weather"
                                    value="<?php echo allEntriesEscape($weather_value); ?>"
                                    aria-pressed="<?php echo $weather_is_active ? 'true' : 'false'; ?>"
                                >
                                    <span class="diary-mood-filter-emoji" aria-hidden="true"><?php echo allEntriesEscape($weather_option['emoji']); ?></span>
                                    <span><?php echo allEntriesEscape($weather_option['label']); ?></span>
                                </button>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <label class="diary-all-entries-sort-control" for="sort">
                        <span>Sort</span>
                        <select id="sort" name="sort" aria-label="Sort journal entries">
                            <option value="newest"<?php echo $sort_value === 'newest' ? ' selected' : ''; ?>>Newest Entry</option>
                            <option value="oldest"<?php echo $sort_value === 'oldest' ? ' selected' : ''; ?>>Oldest Entry</option>
                            <option value="updated"<?php echo $sort_value === 'updated' ? ' selected' : ''; ?>>Recently Updated</option>
                        </select>
                    </label>
                </div>
            </form>
        </section>

        <?php if ($load_error): ?>
            <div class="diary-alert diary-alert-error" role="alert">
                Your journal entries could not be loaded right now. Please try again later.
            </div>
        <?php else: ?>
            <section
                class="diary-library"
                aria-labelledby="all-entries-heading"
                data-diary-result-count="<?php echo allEntriesEscape($result_count); ?>"
                data-diary-current-page="<?php echo allEntriesEscape($current_page); ?>"
                data-diary-total-pages="<?php echo allEntriesEscape($total_pages); ?>"
                data-diary-entries-per-page="<?php echo allEntriesEscape($entries_per_page); ?>"
            >
                <div class="diary-section-heading">
                    <div>
                        <p class="diary-eyebrow">Your Collection</p>
                        <h2 id="all-entries-heading">Complete Journal Collection</h2>
                    </div>
                    <p
                        class="diary-result-count"
                        data-diary-count-singular="<?php echo $filters_active ? 'Matching Entry' : 'Entry'; ?>"
                        data-diary-count-plural="<?php echo $filters_active ? 'Matching Entries' : 'Entries'; ?>"
                    >
                        <?php echo allEntriesEscape($result_count); ?>
                        <?php if ($filters_active): ?>
                            <?php echo $result_count === 1 ? 'Matching Entry' : 'Matching Entries'; ?>
                        <?php else: ?>
                            <?php echo $result_count === 1 ? 'Entry' : 'Entries'; ?>
                        <?php endif; ?>
                    </p>
                </div>

                <?php if (empty($filtered_entries)): ?>
                    <?php if ($filters_active): ?>
                        <section class="diary-empty-state diary-search-empty">
                            <span class="diary-empty-icon" aria-hidden="true">🔎</span>
                            <h2>No journal entries found.</h2>
                            <p>Try different filters or clear them to see your complete collection.</p>
                            <a class="diary-button diary-button-secondary" href="all_entries.php#diary-filters">Clear Filters</a>
                        </section>
                    <?php else: ?>
                        <section class="diary-empty-state diary-empty-journal">
                            <span class="diary-empty-icon" aria-hidden="true">📖</span>
                            <h2>Your journal is waiting for its first story.</h2>
                            <p>Write about your day, thoughts, goals, or memories.</p>
                            <a class="diary-button diary-new-entry-button" href="add.php">Write My First Entry</a>
                        </section>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="diary-entry-list">
                        <?php foreach ($page_entries as $entry): ?>
                            <article class="diary-journal-card">
                                <?php allEntriesFavoriteButton($entry, $_SESSION['diary_favorite_csrf_token'], $all_entries_context); ?>
                                <header class="diary-card-meta">
                                    <span class="diary-card-mood">
                                        <span aria-hidden="true"><?php echo allEntriesEscape(allEntriesMoodEmoji($entry['mood'])); ?></span>
                                        <?php echo allEntriesEscape($entry['mood']); ?>
                                    </span>
                                    <time datetime="<?php echo allEntriesEscape($entry['entry_date']); ?>">
                                        <?php echo allEntriesEscape(allEntriesCardDate($entry['entry_date'])); ?>
                                    </time>
                                </header>

                                <h3><a class="diary-card-link" href="<?php echo allEntriesEscape(diaryNavigationViewUrl($entry['diary_id'], $all_entries_context)); ?>"><?php echo allEntriesEscape($entry['title']); ?></a></h3>
                                <p class="diary-entry-preview">
                                    <?php echo allEntriesEscape(allEntriesContentPreview($entry['content'])); ?>
                                </p>
                                <span class="diary-card-read-cue" aria-hidden="true">Read journal →</span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <nav class="diary-pagination" aria-label="Journal entry pages">
                            <?php if ($current_page > 1): ?>
                                <a class="diary-pagination-link diary-pagination-direction" href="<?php echo allEntriesEscape(allEntriesPaginationUrl($current_page - 1, $all_entries_navigation_parameters)); ?>">← Previous</a>
                            <?php else: ?>
                                <span class="diary-pagination-link diary-pagination-direction is-disabled" aria-disabled="true">← Previous</span>
                            <?php endif; ?>

                            <div class="diary-pagination-pages">
                                <?php for ($page_number = 1; $page_number <= $total_pages; $page_number++): ?>
                                    <?php if ($page_number === $current_page): ?>
                                        <span class="diary-pagination-link is-current" aria-current="page"><?php echo allEntriesEscape($page_number); ?></span>
                                    <?php else: ?>
                                        <a class="diary-pagination-link" href="<?php echo allEntriesEscape(allEntriesPaginationUrl($page_number, $all_entries_navigation_parameters)); ?>"><?php echo allEntriesEscape($page_number); ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>

                            <?php if ($current_page < $total_pages): ?>
                                <a class="diary-pagination-link diary-pagination-direction" href="<?php echo allEntriesEscape(allEntriesPaginationUrl($current_page + 1, $all_entries_navigation_parameters)); ?>">Next →</a>
                            <?php else: ?>
                                <span class="diary-pagination-link diary-pagination-direction is-disabled" aria-disabled="true">Next →</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
