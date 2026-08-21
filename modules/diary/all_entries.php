<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_content.php';

$delete_flash = isset($_SESSION['diary_delete_flash']) && is_array($_SESSION['diary_delete_flash'])
    ? $_SESSION['diary_delete_flash']
    : null;
unset($_SESSION['diary_delete_flash']);

$entries = getDiaryEntriesForUser($conn, $logged_in_user_id);
$load_error = $entries === false;

if ($load_error) {
    $entries = array();
}

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

if (empty($_SESSION['diary_delete_csrf_token'])) {
    $_SESSION['diary_delete_csrf_token'] = bin2hex(random_bytes(32));
}

$diary_css_version = filemtime(__DIR__ . '/../../assets/css/diary.css');
$diary_js_version = filemtime(__DIR__ . '/../../assets/js/diary.js');

function allEntriesEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

$filtered_entries = $entries;
$filters_active = $search_term !== '' || $mood_filter !== '';

if ($search_term !== '') {
    $filtered_entries = array_values(array_filter($filtered_entries, function ($entry) use ($search_term) {
        $title = isset($entry['title']) ? $entry['title'] : '';
        $content = isset($entry['content'])
            ? diaryContentToPlainText($entry['content'])
            : '';

        return allEntriesContainsSearch($title, $search_term)
            || allEntriesContainsSearch($content, $search_term);
    }));
}

if ($mood_filter !== '') {
    $filtered_entries = array_values(array_filter($filtered_entries, function ($entry) use ($mood_filter) {
        $entry_mood = isset($entry['mood']) ? $entry['mood'] : '';

        return $entry_mood === $mood_filter;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Journal Entries</title>
    <link rel="stylesheet" href="../../assets/css/diary.css?v=<?php echo rawurlencode((string) $diary_css_version); ?>">
    <script src="../../assets/js/diary.js?v=<?php echo rawurlencode((string) $diary_js_version); ?>" defer></script>
</head>
<body class="diary-page diary-home-page diary-all-entries-page">
    <main class="diary-container">
        <section class="diary-home-hero diary-all-entries-hero">
            <header class="diary-list-header">
                <div class="diary-title-group">
                    <p class="diary-eyebrow">Personal Journal</p>
                    <h1>All Journal Entries</h1>
                    <p>Browse every thought, reflection, and memory in your journal.</p>
                </div>
                <div class="diary-header-actions">
                    <a class="diary-button diary-button-secondary" href="index.php">Back to Diary Home</a>
                    <a class="diary-button diary-new-entry-button" href="add.php">+ New Journal Entry</a>
                </div>
            </header>
        </section>

        <?php if ($delete_flash !== null): ?>
            <?php $delete_flash_type = isset($delete_flash['type']) && $delete_flash['type'] === 'success' ? 'success' : 'error'; ?>
            <div
                class="diary-alert diary-alert-<?php echo allEntriesEscape($delete_flash_type); ?>"
                role="<?php echo $delete_flash_type === 'success' ? 'status' : 'alert'; ?>"
            >
                <?php echo allEntriesEscape(isset($delete_flash['message']) ? $delete_flash['message'] : 'Journal entry could not be deleted.'); ?>
            </div>
        <?php endif; ?>

        <section class="diary-search-panel" aria-labelledby="diary-search-heading">
            <form class="diary-search-form" action="all_entries.php" method="get" role="search">
                <label id="diary-search-heading" for="search">Search journal entries</label>
                <?php if ($mood_filter !== ''): ?>
                    <input type="hidden" name="mood" value="<?php echo allEntriesEscape($mood_filter); ?>">
                <?php endif; ?>
                <div class="diary-search-controls diary-all-entries-search-controls">
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="<?php echo allEntriesEscape($search_term); ?>"
                        placeholder="Search by title or content"
                    >

                    <button class="diary-button" type="submit">Search</button>
                    <a class="diary-button diary-button-secondary diary-all-entries-clear" href="all_entries.php">Clear Filters</a>
                </div>
                <div class="diary-all-entries-filter-row">
                    <div class="diary-mood-filter-toolbar" role="group" aria-label="Filter journal entries by mood">
                    <?php foreach ($mood_filter_options as $mood_value => $mood_option): ?>
                        <?php
                        $mood_link_parameters = array();
                        if ($search_term !== '') {
                            $mood_link_parameters['search'] = $search_term;
                        }
                        if ($mood_value !== '') {
                            $mood_link_parameters['mood'] = $mood_value;
                        }
                        if ($sort_value !== 'newest') {
                            $mood_link_parameters['sort'] = $sort_value;
                        }
                        $mood_link_url = 'all_entries.php';
                        if (!empty($mood_link_parameters)) {
                            $mood_link_url .= '?' . http_build_query($mood_link_parameters);
                        }
                        $mood_is_active = $mood_filter === $mood_value;
                        ?>
                        <a
                            class="diary-mood-filter-option<?php echo $mood_is_active ? ' is-active' : ''; ?>"
                            href="<?php echo allEntriesEscape($mood_link_url); ?>"
                            <?php echo $mood_is_active ? 'aria-current="true"' : ''; ?>
                        >
                            <span class="diary-mood-filter-emoji" aria-hidden="true"><?php echo allEntriesEscape($mood_option['emoji']); ?></span>
                            <span><?php echo allEntriesEscape($mood_option['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
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
            <section class="diary-library" aria-labelledby="all-entries-heading">
                <div class="diary-section-heading">
                    <div>
                        <p class="diary-eyebrow">Your Collection</p>
                        <h2 id="all-entries-heading">Complete Journal Collection</h2>
                    </div>
                    <p class="diary-result-count">
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
                            <a class="diary-button diary-button-secondary" href="all_entries.php">Clear Filters</a>
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
                        <?php foreach ($filtered_entries as $entry): ?>
                            <article class="diary-journal-card">
                                <header class="diary-card-meta">
                                    <span class="diary-card-mood">
                                        <span aria-hidden="true"><?php echo allEntriesEscape(allEntriesMoodEmoji($entry['mood'])); ?></span>
                                        <?php echo allEntriesEscape($entry['mood']); ?>
                                    </span>
                                    <time datetime="<?php echo allEntriesEscape($entry['entry_date']); ?>">
                                        <?php echo allEntriesEscape(allEntriesCardDate($entry['entry_date'])); ?>
                                    </time>
                                </header>

                                <h3><?php echo allEntriesEscape($entry['title']); ?></h3>
                                <p class="diary-entry-preview">
                                    <?php echo allEntriesEscape(allEntriesContentPreview($entry['content'])); ?>
                                </p>

                                <footer class="diary-card-actions">
                                    <a class="diary-action-button diary-action-primary" href="view.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Read Entry</a>
                                    <a class="diary-action-button diary-action-secondary" href="edit.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Edit</a>
                                    <form action="delete_handler.php" method="post">
                                        <input type="hidden" name="diary_id" value="<?php echo allEntriesEscape($entry['diary_id']); ?>">
                                        <input type="hidden" name="return_to" value="all_entries.php">
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo allEntriesEscape($_SESSION['diary_delete_csrf_token']); ?>"
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
    </main>
</body>
</html>
