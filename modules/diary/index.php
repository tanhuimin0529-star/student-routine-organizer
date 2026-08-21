<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';

$entries = getDiaryEntriesForUser($conn, $logged_in_user_id);
$load_error = $entries === false;

if ($load_error) {
    $entries = array();
}

$entry_count = count($entries);
$latest_entry_date = $entry_count > 0 ? $entries[0]['entry_date'] : null;
$recent_entries = array_slice($entries, 0, 4);

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

$diary_css_version = filemtime(__DIR__ . '/../../assets/css/diary.css');

function diaryEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function diaryContentPreview($content, $limit = 180) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diary Journal</title>
    <link rel="stylesheet" href="../../assets/css/diary.css?v=<?php echo rawurlencode((string) $diary_css_version); ?>">
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
                    <a class="diary-button diary-new-entry-button" href="add.php">+ New Journal Entry</a>
                </div>
            </header>

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

        <section id="journal-calendar" class="diary-calendar-section" aria-labelledby="journal-calendar-heading">
            <header class="diary-calendar-header">
                <div>
                    <p class="diary-eyebrow">Monthly Overview</p>
                    <h2 id="journal-calendar-heading">Journal Calendar</h2>
                </div>
                <nav class="diary-calendar-navigation" aria-label="Calendar month navigation">
                    <a
                        class="diary-calendar-nav-button"
                        href="index.php?month=<?php echo rawurlencode($previous_calendar_month->format('Y-m')); ?>#journal-calendar"
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
                        href="index.php?month=<?php echo rawurlencode($next_calendar_month->format('Y-m')); ?>#journal-calendar"
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
                            href="index.php?month=<?php echo rawurlencode($calendar_month->format('Y-m')); ?>&amp;date=<?php echo rawurlencode($calendar_date_key); ?>#selected-date-entries"
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
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
