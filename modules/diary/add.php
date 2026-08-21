<?php
require_once __DIR__ . '/../../includes/session_check.php';

if (empty($_SESSION['diary_add_csrf_token'])) {
    $_SESSION['diary_add_csrf_token'] = bin2hex(random_bytes(32));
}

$errors = isset($_SESSION['diary_add_errors']) && is_array($_SESSION['diary_add_errors'])
    ? $_SESSION['diary_add_errors']
    : array();
$old = isset($_SESSION['diary_add_old']) && is_array($_SESSION['diary_add_old'])
    ? $_SESSION['diary_add_old']
    : array();

unset($_SESSION['diary_add_errors'], $_SESSION['diary_add_old']);

function diaryFormValue($old, $key, $default = '') {
    $value = isset($old[$key]) ? $old[$key] : $default;
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$moods = array('Happy', 'Calm', 'Neutral', 'Sad', 'Stressed');
$selected_mood = isset($old['mood']) ? $old['mood'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Journal Entry</title>
    <link rel="stylesheet" href="../../assets/css/diary.css">
</head>
<body class="diary-page">
    <main class="diary-container">
        <header class="diary-page-header">
            <p class="diary-eyebrow">Diary Journal</p>
            <h1>Add Journal Entry</h1>
            <p>Record your thoughts and how you are feeling today.</p>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="diary-alert diary-alert-error" role="alert">
                <p>Please correct the following:</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="diary-form" action="add_handler.php" method="post">
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['diary_add_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"
            >

            <div class="diary-form-group">
                <label for="title">Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="150"
                    required
                    value="<?php echo diaryFormValue($old, 'title'); ?>"
                >
            </div>

            <div class="diary-form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" rows="10" required><?php echo diaryFormValue($old, 'content'); ?></textarea>
            </div>

            <div class="diary-form-group">
                <label for="mood">Mood</label>
                <select id="mood" name="mood" required>
                    <option value="">Select a mood</option>
                    <?php foreach ($moods as $mood): ?>
                        <option value="<?php echo htmlspecialchars($mood, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $selected_mood === $mood ? ' selected' : ''; ?>>
                            <?php echo htmlspecialchars($mood, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="diary-form-group">
                <label for="entry_date">Entry Date</label>
                <input
                    type="date"
                    id="entry_date"
                    name="entry_date"
                    required
                    value="<?php echo diaryFormValue($old, 'entry_date', date('Y-m-d')); ?>"
                >
            </div>

            <div class="diary-form-actions">
                <button type="submit">Save Entry</button>
                <a href="index.php">Cancel / Back</a>
            </div>
        </form>
    </main>
</body>
</html>
