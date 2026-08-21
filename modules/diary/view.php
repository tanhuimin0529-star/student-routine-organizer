<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';

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

$diary_css_version = filemtime(__DIR__ . '/../../assets/css/diary.css');

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
                <a href="index.php">← Back to Diary</a>
                <span>Diary Entry</span>
            </nav>

            <article class="diary-reading-page">
                <span class="diary-paper-binding" aria-hidden="true"></span>
                <header class="diary-reading-header">
                    <p class="diary-reading-label">Diary Entry</p>
                    <h1><?php echo diaryViewEscape($entry['title']); ?></h1>
                    <div class="diary-reading-meta">
                        <time datetime="<?php echo diaryViewEscape($entry['entry_date']); ?>">
                            <?php echo diaryViewEscape(diaryViewDisplayDate($entry['entry_date'])); ?>
                        </time>
                        <span aria-hidden="true">•</span>
                        <span>
                            <span aria-hidden="true"><?php echo diaryViewEscape(diaryViewMoodEmoji($entry['mood'])); ?></span>
                            <?php echo diaryViewEscape($entry['mood']); ?>
                        </span>
                    </div>
                </header>

                <div class="diary-reading-content">
                    <?php echo nl2br(diaryViewEscape($entry['content'])); ?>
                </div>

                <footer class="diary-reading-footer">
                    <span class="diary-end-mark">End of entry</span>
                    <nav class="diary-reading-actions" aria-label="Journal entry actions">
                        <a class="diary-action-button diary-action-secondary" href="index.php">Back to Diary</a>
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
            </article>
        <?php endif; ?>
    </main>
</body>
</html>
