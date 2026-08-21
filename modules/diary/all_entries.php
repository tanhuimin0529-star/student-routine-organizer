<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';

$entries = getDiaryEntriesForUser($conn, $logged_in_user_id);
$load_error = $entries === false;

if ($load_error) {
    $entries = array();
}

$search_term = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search'])
    : '';

$allowed_mood_filters = array('Happy', 'Calm', 'Neutral', 'Sad', 'Stressed');
$requested_mood_filter = isset($_GET['mood']) && is_string($_GET['mood'])
    ? $_GET['mood']
    : '';
$mood_filter = in_array($requested_mood_filter, $allowed_mood_filters, true)
    ? $requested_mood_filter
    : '';

if (empty($_SESSION['diary_delete_csrf_token'])) {
    $_SESSION['diary_delete_csrf_token'] = bin2hex(random_bytes(32));
}

$diary_css_version = filemtime(__DIR__ . '/../../assets/css/diary.css');

function allEntriesEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function allEntriesContentPreview($content, $limit = 180) {
    $content = trim(preg_replace('/\s+/', ' ', (string) $content));

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

if ($filters_active) {
    $filtered_entries = array_values(array_filter($entries, function ($entry) use ($search_term, $mood_filter) {
        $title = isset($entry['title']) ? $entry['title'] : '';
        $content = isset($entry['content']) ? $entry['content'] : '';
        $entry_mood = isset($entry['mood']) ? $entry['mood'] : '';

        $matches_search = $search_term === ''
            || allEntriesContainsSearch($title, $search_term)
            || allEntriesContainsSearch($content, $search_term);
        $matches_mood = $mood_filter === '' || $entry_mood === $mood_filter;

        return $matches_search && $matches_mood;
    }));
}

$result_count = count($filtered_entries);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Journal Entries</title>
    <link rel="stylesheet" href="../../assets/css/diary.css?v=<?php echo rawurlencode((string) $diary_css_version); ?>">
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

        <section class="diary-search-panel" aria-labelledby="diary-search-heading">
            <form class="diary-search-form" action="all_entries.php" method="get" role="search">
                <label id="diary-search-heading" for="search">Search journal entries</label>
                <div class="diary-search-controls diary-all-entries-search-controls">
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="<?php echo allEntriesEscape($search_term); ?>"
                        placeholder="Search by title or content"
                    >
                    <select id="mood" name="mood" aria-label="Filter by mood">
                        <option value="">All Moods</option>
                        <?php foreach ($allowed_mood_filters as $allowed_mood): ?>
                            <option
                                value="<?php echo allEntriesEscape($allowed_mood); ?>"
                                <?php echo $mood_filter === $allowed_mood ? 'selected' : ''; ?>
                            >
                                <?php echo allEntriesEscape($allowed_mood); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="diary-button" type="submit">Apply Filters</button>
                    <a class="diary-button diary-button-secondary" href="all_entries.php">Clear Filters</a>
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
