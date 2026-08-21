<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';

$entries = getDiaryEntriesForUser($conn, $logged_in_user_id);
$load_error = $entries === false;

if ($load_error) {
    $entries = array();
}

if (empty($_SESSION['diary_delete_csrf_token'])) {
    $_SESSION['diary_delete_csrf_token'] = bin2hex(random_bytes(32));
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diary Journal</title>
    <link rel="stylesheet" href="../../assets/css/diary.css">
</head>
<body class="diary-page">
    <main class="diary-container">
        <header class="diary-list-header">
            <div>
                <p class="diary-eyebrow">Personal Journal</p>
                <h1>Diary Journal</h1>
                <p>Keep your thoughts, reflections, and daily moments in one place.</p>
            </div>
            <div class="diary-header-actions">
                <a class="diary-button diary-button-secondary" href="../../dashboard/dashboard.php">Back to Dashboard</a>
                <a class="diary-button" href="add.php">+ New Journal Entry</a>
            </div>
        </header>

        <?php if ($load_error): ?>
            <div class="diary-alert diary-alert-error" role="alert">
                Your journal entries could not be loaded right now. Please try again later.
            </div>
        <?php elseif (empty($entries)): ?>
            <section class="diary-empty-state">
                <h2>No journal entries yet</h2>
                <p>You haven't written any journal entries yet.</p>
                <a class="diary-button" href="add.php">Create Your First Entry</a>
            </section>
        <?php else: ?>
            <section class="diary-entry-list" aria-label="Journal entries">
                <?php foreach ($entries as $entry): ?>
                    <article class="diary-entry-card">
                        <div class="diary-entry-heading">
                            <div>
                                <h2><?php echo diaryEscape($entry['title']); ?></h2>
                                <p class="diary-entry-date">
                                    <?php echo diaryEscape(diaryDisplayDate($entry['entry_date'])); ?>
                                </p>
                            </div>
                            <span class="diary-mood"><?php echo diaryEscape($entry['mood']); ?></span>
                        </div>

                        <p class="diary-entry-preview">
                            <?php echo diaryEscape(diaryContentPreview($entry['content'])); ?>
                        </p>

                        <div class="diary-entry-actions">
                            <a href="view.php?diary_id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">View</a>
                            <a href="edit.php?diary_id=<?php echo rawurlencode((string) $entry['diary_id']); ?>">Edit</a>
                            <form action="delete_handler.php" method="post">
                                <input type="hidden" name="diary_id" value="<?php echo diaryEscape($entry['diary_id']); ?>">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo diaryEscape($_SESSION['diary_delete_csrf_token']); ?>"
                                >
                                <button class="diary-delete-button" type="submit">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
