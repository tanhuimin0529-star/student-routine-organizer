<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_content.php';
require_once __DIR__ . '/diary_reflection_content.php';

$delete_flash = isset($_SESSION['diary_delete_flash']) && is_array($_SESSION['diary_delete_flash'])
    ? $_SESSION['diary_delete_flash']
    : null;
unset($_SESSION['diary_delete_flash']);
$favorite_flash = isset($_SESSION['diary_favorite_flash']) && is_array($_SESSION['diary_favorite_flash'])
    ? $_SESSION['diary_favorite_flash']
    : null;
unset($_SESSION['diary_favorite_flash']);
$reflection_flash = isset($_SESSION['diary_reflection_flash']) && is_array($_SESSION['diary_reflection_flash'])
    ? $_SESSION['diary_reflection_flash']
    : null;
unset($_SESSION['diary_reflection_flash']);


$entries = getDiaryEntriesForUser($conn, $logged_in_user_id);
$load_error = $entries === false;

if ($load_error) {
    $entries = array();
}

$entry_count = count($entries);
$latest_entry_date = $entry_count > 0 ? $entries[0]['entry_date'] : null;
$recent_entries = array_slice($entries, 0, 4);
$favorite_entries = array_slice(array_values(array_filter($entries, function ($entry) {
    return isset($entry['is_favorite']) && (int) $entry['is_favorite'] === 1;
})), 0, 4);
$search_term = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search'])
    : '';
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
$allowed_sort_values = array('newest', 'oldest', 'updated');
$requested_sort = isset($_GET['sort']) && is_string($_GET['sort'])
    ? $_GET['sort']
    : 'newest';
$sort_value = in_array($requested_sort, $allowed_sort_values, true)
    ? $requested_sort
    : 'newest';

$current_calendar_month = new DateTimeImmutable('first day of this month');
$calendar_month = $current_calendar_month;
$requested_month = isset($_GET['month']) && is_string($_GET['month'])
    ? $_GET['month']
    : '';

if (preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/', $requested_month)) {
    $requested_calendar_month = DateTimeImmutable::createFromFormat('!Y-m', $requested_month);
    $requested_month_errors = DateTimeImmutable::getLastErrors();
    $requested_month_has_errors = is_array($requested_month_errors)
        && ($requested_month_errors['warning_count'] > 0 || $requested_month_errors['error_count'] > 0);

    if (
        $requested_calendar_month
        && !$requested_month_has_errors
        && $requested_calendar_month->format('Y-m') === $requested_month
    ) {
        $calendar_month = $requested_calendar_month;
    }
}

$calendar_month_name = $calendar_month->format('F');
$calendar_year = $calendar_month->format('Y');
$calendar_days_in_month = (int) $calendar_month->format('t');
$calendar_leading_blanks = (int) $calendar_month->format('N') - 1;
$calendar_used_cells = $calendar_leading_blanks + $calendar_days_in_month;
$calendar_trailing_blanks = (7 - ($calendar_used_cells % 7)) % 7;
$calendar_weekdays = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
$previous_calendar_month = $calendar_month->modify('-1 month');
$next_calendar_month = $calendar_month->modify('+1 month');

$entries_by_date = array();
foreach ($entries as $entry) {
    if (!isset($entry['entry_date']) || !is_string($entry['entry_date'])) {
        continue;
    }

    $entry_date_key = $entry['entry_date'];
    if (!isset($entries_by_date[$entry_date_key])) {
        $entries_by_date[$entry_date_key] = array();
    }

    $entries_by_date[$entry_date_key][] = $entry;
}
$monthly_mood_counts = array(
    'Happy' => 0,
    'Calm' => 0,
    'Neutral' => 0,
    'Sad' => 0,
    'Stressed' => 0
);
$monthly_mood_colors = array(
    'Happy' => '#e5b94c',
    'Calm' => '#7ea38a',
    'Neutral' => '#9a927f',
    'Sad' => '#7196b5',
    'Stressed' => '#c27878'
);
$displayed_month_key = $calendar_month->format('Y-m');
$reflection_month = $calendar_month->format('Y-m-01');
$monthly_reflection = getDiaryMonthlyReflection(
    $conn,
    $logged_in_user_id,
    $reflection_month
);
$reflection_load_error = $monthly_reflection === false;
$reflection_exists = is_array($monthly_reflection);
$reflection_editor_content = $reflection_exists
    ? diaryReflectionContentSanitize($monthly_reflection['content'])
    : '';

if (!$reflection_exists) {
    $monthly_reflection = null;
}

foreach ($entries as $entry) {
    $entry_date = isset($entry['entry_date']) && is_string($entry['entry_date'])
        ? $entry['entry_date']
        : '';
    $entry_mood = isset($entry['mood']) && is_string($entry['mood'])
        ? $entry['mood']
        : '';

    if (substr($entry_date, 0, 7) === $displayed_month_key
        && array_key_exists($entry_mood, $monthly_mood_counts)
    ) {
        $monthly_mood_counts[$entry_mood]++;
    }
}

$monthly_mood_total = array_sum($monthly_mood_counts);
$monthly_mood_percentages = array();
$monthly_mood_chart_segments = array();
$monthly_mood_chart_position = 0.0;

foreach ($monthly_mood_counts as $mood => $count) {
    $percentage = $monthly_mood_total > 0
        ? ($count / $monthly_mood_total) * 100
        : 0;
    $monthly_mood_percentages[$mood] = $percentage;

    if ($count > 0) {
        $segment_start = $monthly_mood_chart_position;
        $monthly_mood_chart_position += ($count / $monthly_mood_total) * 360;
        $monthly_mood_chart_segments[] = $monthly_mood_colors[$mood]
            . ' ' . number_format($segment_start, 4, '.', '') . 'deg '
            . number_format($monthly_mood_chart_position, 4, '.', '') . 'deg';
    }
}

$monthly_mood_chart = $monthly_mood_total > 0
    ? 'conic-gradient(' . implode(', ', $monthly_mood_chart_segments) . ')'
    : '';
$monthly_mood_maximum = $monthly_mood_total > 0 ? max($monthly_mood_counts) : 0;
$monthly_most_common_moods = array();

if ($monthly_mood_maximum > 0) {
    foreach ($monthly_mood_counts as $mood => $count) {
        if ($count === $monthly_mood_maximum) {
            $monthly_most_common_moods[] = $mood;
        }
    }
}

$selected_date = null;
$selected_date_entries = array();
$requested_date = isset($_GET['date']) && is_string($_GET['date'])
    ? $_GET['date']
    : '';

if (preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $requested_date)) {
    $requested_calendar_date = DateTimeImmutable::createFromFormat('!Y-m-d', $requested_date);
    $requested_date_errors = DateTimeImmutable::getLastErrors();
    $requested_date_has_errors = is_array($requested_date_errors)
        && ($requested_date_errors['warning_count'] > 0 || $requested_date_errors['error_count'] > 0);

    if (
        $requested_calendar_date
        && !$requested_date_has_errors
        && $requested_calendar_date->format('Y-m-d') === $requested_date
        && $requested_calendar_date->format('Y-m') === $calendar_month->format('Y-m')
    ) {
        $selected_date = $requested_date;
        $selected_date_entries = isset($entries_by_date[$selected_date])
            ? $entries_by_date[$selected_date]
            : array();
    }
}

if (empty($_SESSION['diary_delete_csrf_token'])) {
    $_SESSION['diary_delete_csrf_token'] = bin2hex(random_bytes(32));
}


if (empty($_SESSION['diary_favorite_csrf_token'])) {
    $_SESSION['diary_favorite_csrf_token'] = bin2hex(random_bytes(32));
}

if (empty($_SESSION['diary_reflection_csrf_token'])) {
    $_SESSION['diary_reflection_csrf_token'] = bin2hex(random_bytes(32));
}
$diary_css_version = filemtime(__DIR__ . '/../../assets/css/diary.css');
$diary_js_version = filemtime(__DIR__ . '/../../assets/js/diary.js');

function diaryEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function diaryContentPreview($content, $limit = 180) {

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

function diaryFavoriteButton($entry, $return_to, $csrf_token) {
    $diary_id = isset($entry['diary_id']) ? (int) $entry['diary_id'] : 0;
    $is_favorite = isset($entry['is_favorite']) && (int) $entry['is_favorite'] === 1;
    $next_favorite = $is_favorite ? 0 : 1;
    $label = $is_favorite ? 'Remove from favorites' : 'Add to favorites';
    ?>
    <form class="diary-favorite-form" action="favorite_handler.php" method="post">
        <input type="hidden" name="diary_id" value="<?php echo diaryEscape($diary_id); ?>">
        <input type="hidden" name="favorite" value="<?php echo diaryEscape($next_favorite); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo diaryEscape($csrf_token); ?>">
        <input type="hidden" name="return_to" value="<?php echo diaryEscape($return_to); ?>">
        <button
            class="diary-favorite-button<?php echo $is_favorite ? ' is-favorite' : ''; ?>"
            type="submit"
            aria-label="<?php echo diaryEscape($label); ?>"
            aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>"
            title="<?php echo diaryEscape($label); ?>"
        ><?php echo $is_favorite ? '★' : '☆'; ?></button>
    </form>
    <?php
}

function diaryDisplayDate($entry_date) {
    $date = DateTime::createFromFormat('!Y-m-d', (string) $entry_date);
    return $date && $date->format('Y-m-d') === $entry_date
        ? $date->format('F j, Y')
        : (string) $entry_date;
}

function diaryCardDate($entry_date) {
    $entry_date = (string) $entry_date;
    $date = DateTime::createFromFormat('!Y-m-d', $entry_date);

    return $date && $date->format('Y-m-d') === $entry_date
        ? strtoupper($date->format('M d'))
        : $entry_date;
}

function diaryMoodEmoji($mood) {
    $icons = array(
        'Happy' => '😊',
        'Calm' => '😌',
        'Neutral' => '😐',
        'Sad' => '😢',
        'Stressed' => '😣'
    );

    return isset($icons[$mood]) ? $icons[$mood] : '📝';
}
function diaryPercentageLabel($percentage) {
    return rtrim(rtrim(number_format((float) $percentage, 1, '.', ''), '0'), '.') . '%';
}

function diaryContainsSearch($value, $search_term) {
    $value = (string) $value;

    if (function_exists('mb_stripos')) {
        return mb_stripos($value, $search_term, 0, 'UTF-8') !== false;
    }

    return stripos($value, $search_term) !== false;
}

$filter_results = array();
$filters_active = $search_term !== '' || $mood_filter !== '' || $sort_value !== 'newest';

if ($filters_active) {
    $filter_results = array_values(array_filter($entries, function ($entry) use ($search_term, $mood_filter) {
        $title = isset($entry['title']) ? $entry['title'] : '';
        $content = isset($entry['content'])
            ? diaryContentToPlainText($entry['content'])
            : '';
        $entry_mood = isset($entry['mood']) ? $entry['mood'] : '';

        $matches_search = $search_term === ''
            || diaryContainsSearch($title, $search_term)
            || diaryContainsSearch($content, $search_term);
        $matches_mood = $mood_filter === '' || $entry_mood === $mood_filter;

        return $matches_search && $matches_mood;
    }));

    usort($filter_results, function ($first_entry, $second_entry) use ($sort_value) {
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
}

$clear_search_parameters = array('month' => $calendar_month->format('Y-m'));
if ($selected_date !== null) {
    $clear_search_parameters['date'] = $selected_date;
}
$clear_search_url = 'index.php?' . http_build_query($clear_search_parameters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diary Journal</title>
    <link rel="stylesheet" href="../../assets/css/diary.css?v=<?php echo rawurlencode((string) $diary_css_version); ?>">
    <script src="../../assets/js/diary.js?v=<?php echo rawurlencode((string) $diary_js_version); ?>" defer></script>
</head>
<body class="diary-page diary-home-page">
    <main class="diary-container">
        <section class="diary-home-hero">
            <header class="diary-list-header">
                <div class="diary-title-group">
                    <p class="diary-eyebrow">Personal Journal</p>
                    <h1>Diary Journal</h1>
                    <p>Keep your thoughts, reflections, and daily moments in one place.</p>
                </div>
                <div class="diary-header-actions">
                    <a class="diary-button diary-button-secondary" href="../../dashboard/dashboard.php">Back to Dashboard</a>
                    <a class="diary-button diary-button-secondary diary-favorites-jump" href="#favorite-entries">★ Favorites</a>
                    <a class="diary-button diary-new-entry-button" href="add.php">+ New Journal Entry</a>
                </div>
            </header>

            <?php if ($delete_flash !== null): ?>
                <?php $delete_flash_type = isset($delete_flash['type']) && $delete_flash['type'] === 'success' ? 'success' : 'error'; ?>
                <div
                    class="diary-alert diary-alert-<?php echo diaryEscape($delete_flash_type); ?>"
                    role="<?php echo $delete_flash_type === 'success' ? 'status' : 'alert'; ?>"
                >
                    <?php echo diaryEscape(isset($delete_flash['message']) ? $delete_flash['message'] : 'Journal entry could not be deleted.'); ?>
                </div>
            <?php endif; ?>

            <?php if ($favorite_flash !== null): ?>
                <?php $favorite_flash_type = isset($favorite_flash['type']) && $favorite_flash['type'] === 'success' ? 'success' : 'error'; ?>
                <div
                    class="diary-alert diary-alert-<?php echo diaryEscape($favorite_flash_type); ?>"
                    role="<?php echo $favorite_flash_type === 'success' ? 'status' : 'alert'; ?>"
                >
                    <?php echo diaryEscape(isset($favorite_flash['message']) ? $favorite_flash['message'] : 'Favorite could not be updated right now. Please try again.'); ?>
                </div>
            <?php endif; ?>

            <?php if (!$load_error): ?>
                <div class="diary-summary" aria-label="Journal summary">
                    <div class="diary-summary-item">
                        <span class="diary-summary-icon" aria-hidden="true">📚</span>
                        <div>
                            <span class="diary-summary-label">Total Entries</span>
                            <strong><?php echo diaryEscape($entry_count); ?> <?php echo $entry_count === 1 ? 'Entry' : 'Entries'; ?></strong>
                        </div>
                    </div>
                    <div class="diary-summary-item">
                        <span class="diary-summary-icon" aria-hidden="true">✒️</span>
                        <div>
                            <span class="diary-summary-label">Latest Entry Date</span>
                            <?php if ($latest_entry_date !== null): ?>
                                <strong>Last written: <?php echo diaryEscape(diaryDisplayDate($latest_entry_date)); ?></strong>
                            <?php else: ?>
                                <strong>Your first page starts here</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="diary-search-panel" aria-labelledby="diary-home-search-heading">
            <form class="diary-search-form" action="index.php" method="get" role="search">
                <label id="diary-home-search-heading" for="search">Search journal entries</label>
                <input type="hidden" name="month" value="<?php echo diaryEscape($calendar_month->format('Y-m')); ?>">
                <?php if ($selected_date !== null): ?>
                    <input type="hidden" name="date" value="<?php echo diaryEscape($selected_date); ?>">
                <?php endif; ?>
                <?php if ($mood_filter !== ''): ?>
                    <input type="hidden" name="mood" value="<?php echo diaryEscape($mood_filter); ?>">
                <?php endif; ?>
                <div class="diary-search-controls diary-home-search-controls">
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="<?php echo diaryEscape($search_term); ?>"
                        placeholder="Search by title or content"
                    >
                    <button class="diary-button" type="submit">Search</button>
                    <a class="diary-button diary-button-secondary" href="<?php echo diaryEscape($clear_search_url); ?>">Clear Filters</a>
                </div>
                <div class="diary-all-entries-filter-row">
                    <div class="diary-mood-filter-toolbar" role="group" aria-label="Filter journal entries by mood">
                    <?php foreach ($mood_filter_options as $mood_value => $mood_option): ?>
                        <?php
                        $mood_link_parameters = array('month' => $calendar_month->format('Y-m'));
                        if ($selected_date !== null) {
                            $mood_link_parameters['date'] = $selected_date;
                        }
                        if ($search_term !== '') {
                            $mood_link_parameters['search'] = $search_term;
                        }
                        if ($mood_value !== '') {
                            $mood_link_parameters['mood'] = $mood_value;
                        }
                        if ($sort_value !== 'newest') {
                            $mood_link_parameters['sort'] = $sort_value;
                        }
                        $mood_link_url = 'index.php?' . http_build_query($mood_link_parameters);
                        $mood_is_active = $mood_filter === $mood_value;
                        ?>
                        <a
                            class="diary-mood-filter-option<?php echo $mood_is_active ? ' is-active' : ''; ?>"
                            href="<?php echo diaryEscape($mood_link_url); ?>"
                            <?php echo $mood_is_active ? 'aria-current="true"' : ''; ?>
                        >
                            <span class="diary-mood-filter-emoji" aria-hidden="true"><?php echo diaryEscape($mood_option['emoji']); ?></span>
                            <span><?php echo diaryEscape($mood_option['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                    </div>
                    <label class="diary-all-entries-sort-control" for="sort">
                        <span>Sort</span>
                        <select id="sort" name="sort" aria-label="Sort journal search results">
                            <option value="newest"<?php echo $sort_value === 'newest' ? ' selected' : ''; ?>>Newest Entry</option>
                            <option value="oldest"<?php echo $sort_value === 'oldest' ? ' selected' : ''; ?>>Oldest Entry</option>
                            <option value="updated"<?php echo $sort_value === 'updated' ? ' selected' : ''; ?>>Recently Updated</option>
                        </select>
                    </label>
                </div>
            </form>
        </section>

        <?php if ($filters_active && !$load_error): ?>
            <section class="diary-search-results-section" aria-labelledby="diary-search-results-heading">
                <div class="diary-section-heading">
                    <div>
                        <p class="diary-eyebrow">Search</p>
                        <h2 id="diary-search-results-heading">Search Results</h2>
                    </div>
                    <?php if (!empty($filter_results)): ?>
                        <p><?php echo diaryEscape(count($filter_results)); ?> <?php echo count($filter_results) === 1 ? 'entry' : 'entries'; ?></p>
                    <?php endif; ?>
                </div>

                <?php if (empty($filter_results)): ?>
                    <div class="diary-selected-date-empty">
                        <p>No journal entries found.</p>
                        <a class="diary-button diary-button-secondary" href="<?php echo diaryEscape($clear_search_url); ?>">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <div class="diary-entry-list diary-search-entry-list">
                        <?php foreach ($filter_results as $entry): ?>
                            <article class="diary-journal-card">
                                <?php diaryFavoriteButton($entry, 'index.php', $_SESSION['diary_favorite_csrf_token']); ?>
                                <header class="diary-card-meta">
                                    <span class="diary-card-mood">
                                        <span aria-hidden="true"><?php echo diaryEscape(diaryMoodEmoji($entry['mood'])); ?></span>
                                        <?php echo diaryEscape($entry['mood']); ?>
                                    </span>
                                    <time datetime="<?php echo diaryEscape($entry['entry_date']); ?>">
                                        <?php echo diaryEscape(diaryCardDate($entry['entry_date'])); ?>
                                    </time>
                                </header>

                                <h3><?php echo diaryEscape($entry['title']); ?></h3>
                                <p class="diary-entry-preview">
                                    <?php echo diaryEscape(diaryContentPreview($entry['content'])); ?>
                                </p>

                                <footer class="diary-card-actions">
                                    <a class="diary-action-button diary-action-primary" href="view.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Read Entry</a>
                                    <a class="diary-action-button diary-action-secondary" href="edit.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Edit</a>
                                    <form action="delete_handler.php" method="post">
                                        <input type="hidden" name="diary_id" value="<?php echo diaryEscape($entry['diary_id']); ?>">
                                        <input type="hidden" name="return_to" value="index.php">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo diaryEscape($_SESSION['diary_delete_csrf_token']); ?>"
                                        >
                                        <button class="diary-action-button diary-action-delete" type="submit">Delete</button>
                                    </form>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section id="journal-calendar" class="diary-calendar-section" aria-labelledby="journal-calendar-heading">
            <header class="diary-calendar-header">
                <div>
                    <p class="diary-eyebrow">Monthly Overview</p>
                    <h2 id="journal-calendar-heading">Journal Calendar</h2>
                </div>
                <nav class="diary-calendar-navigation" aria-label="Calendar month navigation">
                    <a
                        class="diary-calendar-nav-button"
                        href="index.php?month=<?php echo rawurlencode($previous_calendar_month->format('Y-m')); ?><?php echo $search_term !== '' ? '&amp;search=' . rawurlencode($search_term) : ''; ?><?php echo $mood_filter !== '' ? '&amp;mood=' . rawurlencode($mood_filter) : ''; ?>#journal-calendar"
                        aria-label="Previous month: <?php echo diaryEscape($previous_calendar_month->format('F Y')); ?>"
                    >
                        ← <?php echo diaryEscape($previous_calendar_month->format('F')); ?>
                    </a>
                    <p class="diary-calendar-month" aria-current="date">
                        <span><?php echo diaryEscape($calendar_month_name); ?></span>
                        <strong><?php echo diaryEscape($calendar_year); ?></strong>
                    </p>
                    <a
                        class="diary-calendar-nav-button"
                        href="index.php?month=<?php echo rawurlencode($next_calendar_month->format('Y-m')); ?><?php echo $search_term !== '' ? '&amp;search=' . rawurlencode($search_term) : ''; ?><?php echo $mood_filter !== '' ? '&amp;mood=' . rawurlencode($mood_filter) : ''; ?>#journal-calendar"
                        aria-label="Next month: <?php echo diaryEscape($next_calendar_month->format('F Y')); ?>"
                    >
                        <?php echo diaryEscape($next_calendar_month->format('F')); ?> →
                    </a>
                </nav>
            </header>

            <div class="diary-calendar-grid" aria-label="<?php echo diaryEscape($calendar_month_name . ' ' . $calendar_year); ?> calendar">
                <?php foreach ($calendar_weekdays as $weekday): ?>
                    <div class="diary-calendar-weekday"><?php echo diaryEscape($weekday); ?></div>
                <?php endforeach; ?>

                <?php for ($blank = 0; $blank < $calendar_leading_blanks; $blank++): ?>
                    <div class="diary-calendar-empty" aria-hidden="true"></div>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $calendar_days_in_month; $day++): ?>
                    <?php $calendar_date = $calendar_month->setDate((int) $calendar_year, (int) $calendar_month->format('m'), $day); ?>
                    <?php $calendar_date_key = $calendar_date->format('Y-m-d'); ?>
                    <?php $calendar_entry_count = isset($entries_by_date[$calendar_date_key]) ? count($entries_by_date[$calendar_date_key]) : 0; ?>
                    <?php $calendar_day_label = $calendar_date->format('F j, Y') . ($calendar_entry_count > 0 ? ', ' . $calendar_entry_count . ($calendar_entry_count === 1 ? ' journal entry' : ' journal entries') : ''); ?>
                    <div
                        class="diary-calendar-day<?php echo $calendar_entry_count > 0 ? ' diary-calendar-day-has-entries' : ''; ?><?php echo $selected_date === $calendar_date_key ? ' diary-calendar-day-selected' : ''; ?>"
                    >
                        <a
                            class="diary-calendar-day-link"
                            href="index.php?month=<?php echo rawurlencode($calendar_month->format('Y-m')); ?>&amp;date=<?php echo rawurlencode($calendar_date_key); ?><?php echo $search_term !== '' ? '&amp;search=' . rawurlencode($search_term) : ''; ?><?php echo $mood_filter !== '' ? '&amp;mood=' . rawurlencode($mood_filter) : ''; ?>#selected-date-entries"
                            aria-label="<?php echo diaryEscape($calendar_day_label); ?>"
                            <?php echo $selected_date === $calendar_date_key ? 'aria-current="date"' : ''; ?>
                        >
                            <time datetime="<?php echo diaryEscape($calendar_date->format('Y-m-d')); ?>">
                                <?php echo diaryEscape($day); ?>
                            </time>
                            <?php if ($calendar_entry_count > 0): ?>
                                <span class="diary-calendar-entry-indicator" aria-hidden="true">
                                    <span class="diary-calendar-entry-dot"></span>
                                    <?php if ($calendar_entry_count > 1): ?>
                                        <span class="diary-calendar-entry-count"><?php echo diaryEscape($calendar_entry_count); ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endfor; ?>

                <?php for ($blank = 0; $blank < $calendar_trailing_blanks; $blank++): ?>
                    <div class="diary-calendar-empty" aria-hidden="true"></div>
                <?php endfor; ?>
            </div>
        </section>
        <?php if (!$load_error): ?>
            <section class="diary-mood-summary-section" aria-labelledby="monthly-mood-summary-heading">
                <div class="diary-section-heading">
                    <div>
                        <p class="diary-eyebrow">Monthly Reflection</p>
                        <h2 id="monthly-mood-summary-heading">Monthly Mood Summary</h2>
                    </div>
                    <p><?php echo diaryEscape($calendar_month->format('F Y')); ?></p>
                </div>

                <?php if ($monthly_mood_total === 0): ?>
                    <div class="diary-mood-summary-empty">
                        <p>No mood data for this month.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $monthly_mood_chart_label_parts = array();
                    foreach ($monthly_mood_counts as $mood => $count) {
                        $monthly_mood_chart_label_parts[] = $mood . ': ' . $count
                            . ' (' . diaryPercentageLabel($monthly_mood_percentages[$mood]) . ')';
                    }
                    ?>
                    <div class="diary-mood-summary-layout">
                        <div class="diary-mood-chart-wrap">
                            <div
                                class="diary-mood-chart"
                                role="img"
                                aria-label="<?php echo diaryEscape($calendar_month->format('F Y') . ' mood distribution. ' . implode(', ', $monthly_mood_chart_label_parts)); ?>"
                                style="--diary-mood-chart: <?php echo diaryEscape($monthly_mood_chart); ?>;"
                            ></div>
                            <strong><?php echo diaryEscape($monthly_mood_total); ?></strong>
                            <span><?php echo $monthly_mood_total === 1 ? 'Entry' : 'Entries'; ?></span>
                        </div>

                        <ul class="diary-mood-summary-list" aria-label="Monthly mood counts and percentages">
                            <?php foreach ($monthly_mood_counts as $mood => $count): ?>
                                <li>
                                    <span class="diary-mood-summary-name">
                                        <span aria-hidden="true"><?php echo diaryEscape(diaryMoodEmoji($mood)); ?></span>
                                        <?php echo diaryEscape($mood); ?>
                                    </span>
                                    <span class="diary-mood-summary-values">
                                        <strong><?php echo diaryEscape($count); ?></strong>
                                        <span><?php echo diaryEscape(diaryPercentageLabel($monthly_mood_percentages[$mood])); ?></span>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="diary-most-common-mood">
                        <span>Most Common Mood</span>
                        <div>
                            <?php foreach ($monthly_most_common_moods as $mood): ?>
                                <strong>
                                    <span aria-hidden="true"><?php echo diaryEscape(diaryMoodEmoji($mood)); ?></span>
                                    <?php echo diaryEscape($mood); ?>
                                </strong>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>


        <section id="monthly-reflection" class="diary-reflection-section" aria-labelledby="monthly-reflection-heading">
            <div class="diary-section-heading diary-reflection-heading">
                <div>
                    <p class="diary-eyebrow">Pause and Look Back</p>
                    <h2 id="monthly-reflection-heading">Monthly Reflection</h2>
                </div>
                <p><?php echo diaryEscape($calendar_month->format('F Y')); ?></p>
            </div>

            <p class="diary-reflection-intro">
                Capture what stood out, what you learned, and what you want to carry into the next month.
            </p>

            <?php if ($reflection_flash !== null): ?>
                <?php $reflection_flash_type = isset($reflection_flash['type']) && $reflection_flash['type'] === 'success' ? 'success' : 'error'; ?>
                <div
                    class="diary-alert diary-alert-<?php echo diaryEscape($reflection_flash_type); ?> diary-reflection-flash"
                    role="<?php echo $reflection_flash_type === 'success' ? 'status' : 'alert'; ?>"
                >
                    <?php echo diaryEscape(isset($reflection_flash['message']) ? $reflection_flash['message'] : 'Your monthly reflection could not be saved right now. Please try again.'); ?>
                </div>
            <?php endif; ?>

            <?php if ($reflection_load_error): ?>
                <div class="diary-alert diary-alert-error diary-reflection-flash" role="alert">
                    Your monthly reflection could not be loaded right now. You can still try saving it again.
                </div>
            <?php endif; ?>

            <form class="diary-reflection-form" action="reflection_handler.php" method="post" data-diary-reflection-editor>
                <input type="hidden" name="month" value="<?php echo diaryEscape($displayed_month_key); ?>">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo diaryEscape($_SESSION['diary_reflection_csrf_token']); ?>"
                >
                <input
                    type="hidden"
                    name="content"
                    value="<?php echo diaryEscape($reflection_editor_content); ?>"
                    data-reflection-content
                >

                <div class="diary-reflection-editor-shell">
                    <div class="diary-reflection-toolbar" role="toolbar" aria-label="Monthly reflection formatting">
                        <div class="diary-reflection-tool-group" aria-label="Text formatting">
                            <button type="button" data-reflection-command="bold" aria-label="Bold" title="Bold"><strong>B</strong></button>
                            <button type="button" data-reflection-command="italic" aria-label="Italic" title="Italic"><em>I</em></button>
                            <button type="button" data-reflection-command="underline" aria-label="Underline" title="Underline"><u>U</u></button>
                        </div>
                        <div class="diary-reflection-tool-group" aria-label="Lists">
                            <button type="button" data-reflection-command="insertUnorderedList" aria-label="Bullet list" title="Bullet list">• List</button>
                            <button type="button" data-reflection-command="insertOrderedList" aria-label="Numbered list" title="Numbered list">1. List</button>
                        </div>
                        <div class="diary-reflection-tool-group" aria-label="Emoji">
                            <button
                                type="button"
                                data-reflection-emoji-toggle
                                aria-label="Insert emoji"
                                title="Insert emoji"
                                aria-expanded="false"
                                aria-controls="diary-reflection-emoji-picker"
                            >☺ Emoji</button>
                        </div>
                    </div>

                    <div
                        class="diary-reflection-emoji-picker"
                        id="diary-reflection-emoji-picker"
                        data-reflection-emoji-picker
                        aria-label="Choose an emoji"
                        hidden
                    >
                        <?php foreach (array('😀', '😂', '🥰', '😭', '😴', '❤️', '⭐', '🌸', '☕', '🎓', '👍', '🎉', '🌈', '💭', '🍀') as $emoji): ?>
                            <button type="button" data-reflection-emoji="<?php echo diaryEscape($emoji); ?>" aria-label="Insert <?php echo diaryEscape($emoji); ?> emoji"><?php echo diaryEscape($emoji); ?></button>
                        <?php endforeach; ?>
                    </div>

                    <div
                        class="diary-reflection-editor"
                        contenteditable="true"
                        role="textbox"
                        aria-label="Monthly reflection content"
                        aria-multiline="true"
                        aria-required="true"
                        data-reflection-surface
                        data-placeholder="Write your reflection for <?php echo diaryEscape($calendar_month->format('F Y')); ?>..."
                    ><?php echo $reflection_editor_content; // Sanitized by the strict reflection allow-list. ?></div>
                </div>

                <div class="diary-reflection-actions">
                    <button class="diary-button diary-new-entry-button" type="submit">
                        <?php echo $reflection_exists ? 'Update Reflection' : 'Save Reflection'; ?>
                    </button>
                </div>
            </form>
        </section>

        <?php if ($selected_date !== null && !$load_error): ?>
            <section id="selected-date-entries" class="diary-selected-date-section" aria-labelledby="selected-date-heading">
                <div class="diary-section-heading">
                    <div>
                        <p class="diary-eyebrow">Selected Date</p>
                        <h2 id="selected-date-heading">Entries for <?php echo diaryEscape(diaryDisplayDate($selected_date)); ?></h2>
                    </div>
                    <?php if (!empty($selected_date_entries)): ?>
                        <p><?php echo diaryEscape(count($selected_date_entries)); ?> <?php echo count($selected_date_entries) === 1 ? 'entry' : 'entries'; ?></p>
                    <?php endif; ?>
                </div>

                <?php if (empty($selected_date_entries)): ?>
                    <div class="diary-selected-date-empty">
                        <p>No journal entries for this date.</p>
                        <a class="diary-button diary-new-entry-button" href="add.php">+ Write an Entry</a>
                    </div>
                <?php else: ?>
                    <div class="diary-entry-list diary-selected-entry-list">
                        <?php foreach ($selected_date_entries as $entry): ?>
                            <article class="diary-journal-card diary-selected-entry-card">
                                <?php diaryFavoriteButton($entry, 'index.php', $_SESSION['diary_favorite_csrf_token']); ?>
                                <header class="diary-card-meta">
                                    <span class="diary-card-mood">
                                        <span aria-hidden="true"><?php echo diaryEscape(diaryMoodEmoji($entry['mood'])); ?></span>
                                        <?php echo diaryEscape($entry['mood']); ?>
                                    </span>
                                    <time datetime="<?php echo diaryEscape($entry['entry_date']); ?>">
                                        <?php echo diaryEscape(diaryCardDate($entry['entry_date'])); ?>
                                    </time>
                                </header>

                                <h3><?php echo diaryEscape($entry['title']); ?></h3>
                                <p class="diary-entry-preview">
                                    <?php echo diaryEscape(diaryContentPreview($entry['content'])); ?>
                                </p>

                                <footer class="diary-card-actions">
                                    <a class="diary-action-button diary-action-primary" href="view.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Read Entry</a>
                                    <a class="diary-action-button diary-action-secondary" href="edit.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Edit</a>
                                    <form action="delete_handler.php" method="post">
                                        <input type="hidden" name="diary_id" value="<?php echo diaryEscape($entry['diary_id']); ?>">
                                        <input type="hidden" name="return_to" value="index.php">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo diaryEscape($_SESSION['diary_delete_csrf_token']); ?>"
                                        >
                                        <button class="diary-action-button diary-action-delete" type="submit">Delete</button>
                                    </form>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (!$load_error): ?>
            <section id="favorite-entries" class="diary-library diary-favorites-section" aria-labelledby="favorite-entries-heading">
                <div class="diary-section-heading">
                    <div>
                        <p class="diary-eyebrow">Pinned Pages</p>
                        <h2 id="favorite-entries-heading">★ Favorites</h2>
                    </div>
                    <p>Your favorite journal memories</p>
                </div>

                <?php if (empty($favorite_entries)): ?>
                    <div class="diary-favorites-empty">
                        <p>No favorite journal entries yet.</p>
                    </div>
                <?php else: ?>
                    <div class="diary-entry-list diary-favorite-entry-list">
                        <?php foreach ($favorite_entries as $entry): ?>
                            <article class="diary-journal-card diary-favorite-entry-card">
                                <?php diaryFavoriteButton($entry, 'index.php', $_SESSION['diary_favorite_csrf_token']); ?>
                                <header class="diary-card-meta">
                                    <span class="diary-card-mood">
                                        <span aria-hidden="true"><?php echo diaryEscape(diaryMoodEmoji($entry['mood'])); ?></span>
                                        <?php echo diaryEscape($entry['mood']); ?>
                                    </span>
                                    <time datetime="<?php echo diaryEscape($entry['entry_date']); ?>">
                                        <?php echo diaryEscape(diaryCardDate($entry['entry_date'])); ?>
                                    </time>
                                </header>

                                <h3><?php echo diaryEscape($entry['title']); ?></h3>
                                <p class="diary-entry-preview">
                                    <?php echo diaryEscape(diaryContentPreview($entry['content'])); ?>
                                </p>

                                <footer class="diary-card-actions">
                                    <a class="diary-action-button diary-action-primary" href="view.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Read Entry</a>
                                    <a class="diary-action-button diary-action-secondary" href="edit.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Edit</a>
                                    <form action="delete_handler.php" method="post">
                                        <input type="hidden" name="diary_id" value="<?php echo diaryEscape($entry['diary_id']); ?>">
                                        <input type="hidden" name="return_to" value="index.php">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo diaryEscape($_SESSION['diary_delete_csrf_token']); ?>"
                                        >
                                        <button class="diary-action-button diary-action-delete" type="submit">Delete</button>
                                    </form>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($load_error): ?>
            <div class="diary-alert diary-alert-error" role="alert">
                Your journal entries could not be loaded right now. Please try again later.
            </div>
        <?php elseif (empty($entries)): ?>
            <section class="diary-empty-state diary-empty-journal">
                <span class="diary-empty-icon" aria-hidden="true">📖</span>
                <h2>Your journal is waiting for its first story.</h2>
                <p>Write about your day, thoughts, goals, or memories.</p>
                <a class="diary-button diary-new-entry-button" href="add.php">Write My First Entry</a>
            </section>
        <?php else: ?>
            <section class="diary-library" aria-labelledby="journal-entries-heading">
                <div class="diary-section-heading">
                    <div>
                        <p class="diary-eyebrow">Your Collection</p>
                        <h2 id="journal-entries-heading">Recent Entries</h2>
                    </div>
                    <p>Your latest thoughts and memories</p>
                </div>

                <div class="diary-entry-list">
                    <?php foreach ($recent_entries as $entry): ?>
                        <article class="diary-journal-card">
                            <?php diaryFavoriteButton($entry, 'index.php', $_SESSION['diary_favorite_csrf_token']); ?>
                            <header class="diary-card-meta">
                                <span class="diary-card-mood">
                                    <span aria-hidden="true"><?php echo diaryEscape(diaryMoodEmoji($entry['mood'])); ?></span>
                                    <?php echo diaryEscape($entry['mood']); ?>
                                </span>
                                <time datetime="<?php echo diaryEscape($entry['entry_date']); ?>">
                                    <?php echo diaryEscape(diaryCardDate($entry['entry_date'])); ?>
                                </time>
                            </header>

                            <h3><?php echo diaryEscape($entry['title']); ?></h3>
                            <p class="diary-entry-preview">
                                <?php echo diaryEscape(diaryContentPreview($entry['content'])); ?>
                            </p>

                            <footer class="diary-card-actions">
                                <a class="diary-action-button diary-action-primary" href="view.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Read Entry</a>
                                <a class="diary-action-button diary-action-secondary" href="edit.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Edit</a>
                                <form action="delete_handler.php" method="post">
                                    <input type="hidden" name="diary_id" value="<?php echo diaryEscape($entry['diary_id']); ?>">
                                    <input type="hidden" name="return_to" value="index.php">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo diaryEscape($_SESSION['diary_delete_csrf_token']); ?>"
                                    >
                                    <button class="diary-action-button diary-action-delete" type="submit">Delete</button>
                                </form>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="diary-collection-footer">
                    <a class="diary-button diary-button-secondary" href="all_entries.php">View All Entries</a>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
