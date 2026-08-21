<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_content.php';

$entry = null;
$database_error = false;
$requested_id = isset($_GET['id']) && is_string($_GET['id'])
    ? $_GET['id']
    : (isset($_GET['diary_id']) && is_string($_GET['diary_id']) ? $_GET['diary_id'] : '');
$diary_id = filter_var(
    $requested_id,
    FILTER_VALIDATE_INT,
    array('options' => array('min_range' => 1))
);

if ($diary_id !== false) {
    $entry = getDiaryEntryById($conn, $diary_id, $logged_in_user_id);

    if ($entry === false) {
        $database_error = true;
        $entry = null;
    }
}

if ($entry !== null && empty($_SESSION['diary_delete_csrf_token'])) {
    $_SESSION['diary_delete_csrf_token'] = bin2hex(random_bytes(32));
}

$previous_entry_id = null;
$next_entry_id = null;

if ($entry !== null) {
    $user_entries = getDiaryEntriesForUser($conn, $logged_in_user_id);

    if (is_array($user_entries)) {
        $current_position = null;

        foreach ($user_entries as $position => $user_entry) {
            if (isset($user_entry['diary_id']) && (int) $user_entry['diary_id'] === (int) $entry['diary_id']) {
                $current_position = $position;
                break;
            }
        }

        if ($current_position !== null) {
            $older_position = $current_position + 1;
            $newer_position = $current_position - 1;

            if (isset($user_entries[$older_position]['diary_id'])) {
                $previous_entry_id = $user_entries[$older_position]['diary_id'];
            }

            if ($newer_position >= 0 && isset($user_entries[$newer_position]['diary_id'])) {
                $next_entry_id = $user_entries[$newer_position]['diary_id'];
            }
        }
    }
}

$diary_css_version = filemtime(__DIR__ . '/../../assets/css/diary.css');
$diary_js_version = filemtime(__DIR__ . '/../../assets/js/diary.js');

function diaryViewEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function diaryViewDisplayDate($entry_date) {
    $entry_date = (string) $entry_date;
    $date = DateTime::createFromFormat('!Y-m-d', $entry_date);

    return $date && $date->format('Y-m-d') === $entry_date
        ? $date->format('F j, Y')
        : $entry_date;
}

function diaryViewMoodEmoji($mood) {
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
    <title>View Journal Entry</title>
    <link rel="stylesheet" href="../../assets/css/diary.css?v=<?php echo rawurlencode((string) $diary_css_version); ?>">
    <script src="../../assets/js/diary.js?v=<?php echo rawurlencode((string) $diary_js_version); ?>" defer></script>
</head>
<body class="diary-page diary-reader-page">
    <main class="diary-container diary-reader-container">
        <?php if ($database_error): ?>
            <header class="diary-page-header">
                <p class="diary-eyebrow">Diary Entry</p>
            </header>
            <div class="diary-alert diary-alert-error" role="alert">
                This journal entry could not be loaded right now. Please try again later.
            </div>
            <a class="diary-button diary-button-secondary" href="index.php">Back to Diary</a>
        <?php elseif ($entry === null): ?>
            <header class="diary-page-header">
                <p class="diary-eyebrow">Diary Entry</p>
            </header>
            <section class="diary-empty-state">
                <h2>Journal entry not found.</h2>
                <p>The entry may not exist or may not be available to your account.</p>
                <a class="diary-button" href="index.php">Back to Diary</a>
            </section>
        <?php else: ?>
            <nav class="diary-reader-nav" aria-label="Diary navigation">
                <a href="index.php">← Back to Diary Home</a>
                <span>Personal Journal</span>
            </nav>

            <div class="diary-book-shell">
                <article class="diary-reading-page">
                    <span class="diary-paper-binding" aria-hidden="true"></span>
                    <span class="diary-page-corner" aria-hidden="true"></span>
                    <header class="diary-reading-header">
                        <p class="diary-reading-label">Personal Journal</p>
                        <h1><?php echo diaryViewEscape($entry['title']); ?></h1>
                        <div class="diary-reading-meta">
                            <time class="diary-reading-meta-item" datetime="<?php echo diaryViewEscape($entry['entry_date']); ?>">
                                <span class="diary-meta-label">Date</span>
                                <?php echo diaryViewEscape(diaryViewDisplayDate($entry['entry_date'])); ?>
                            </time>
                            <span class="diary-reading-meta-item">
                                <span class="diary-meta-label">Mood</span>
                                <span aria-hidden="true"><?php echo diaryViewEscape(diaryViewMoodEmoji($entry['mood'])); ?></span>
                                <?php echo diaryViewEscape($entry['mood']); ?>
                            </span>
                        </div>
                    </header>

                    <div class="diary-reading-content">
                        <?php echo diaryContentRenderSafeHtml($entry['content']); ?>
                    </div>

                    <div class="diary-reading-page-mark">— Personal Journal —</div>
                </article>

                <nav class="diary-entry-sequence-navigation" aria-label="Previous and next journal entries">
                    <?php if ($previous_entry_id !== null): ?>
                        <a
                            class="diary-entry-sequence-link diary-entry-sequence-previous"
                            href="view.php?id=<?php echo rawurlencode((string) $previous_entry_id); ?>"
                            aria-label="Previous journal entry"
                        >←</a>
                    <?php else: ?>
                        <span
                            class="diary-entry-sequence-link diary-entry-sequence-previous is-disabled"
                            aria-label="No previous journal entry"
                            aria-disabled="true"
                        >←</span>
                    <?php endif; ?>

                    <?php if ($next_entry_id !== null): ?>
                        <a
                            class="diary-entry-sequence-link diary-entry-sequence-next"
                            href="view.php?id=<?php echo rawurlencode((string) $next_entry_id); ?>"
                            aria-label="Next journal entry"
                        >→</a>
                    <?php else: ?>
                        <span
                            class="diary-entry-sequence-link diary-entry-sequence-next is-disabled"
                            aria-label="No next journal entry"
                            aria-disabled="true"
                        >→</span>
                    <?php endif; ?>
                </nav>
            </div>

            <footer class="diary-reader-external-actions">
                <nav class="diary-reading-actions" aria-label="Journal entry actions">
                    <a class="diary-action-button diary-action-secondary" href="index.php">Back to Diary Home</a>
                    <a class="diary-action-button diary-action-primary" href="edit.php?id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Edit Entry</a>
                    <form action="delete_handler.php" method="post">
                        <input type="hidden" name="diary_id" value="<?php echo diaryViewEscape($entry['diary_id']); ?>">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo diaryViewEscape($_SESSION['diary_delete_csrf_token']); ?>"
                        >
                        <button class="diary-action-button diary-action-delete" type="submit">Delete</button>
                    </form>
                </nav>
            </footer>
        <?php endif; ?>
    </main>
</body>
</html>
