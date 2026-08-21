<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';

$requested_id = isset($_GET['id']) && is_string($_GET['id'])
    ? $_GET['id']
    : (isset($_GET['diary_id']) && is_string($_GET['diary_id']) ? $_GET['diary_id'] : '');
$diary_id = filter_var(
    $requested_id,
    FILTER_VALIDATE_INT,
    array('options' => array('min_range' => 1))
);

$entry = null;
$database_error = false;

if ($diary_id !== false) {
    $entry = getDiaryEntryById($conn, $diary_id, $logged_in_user_id);

    if ($entry === false) {
        $database_error = true;
        $entry = null;
    }
}

$flash_errors = isset($_SESSION['diary_edit_errors']) && is_array($_SESSION['diary_edit_errors'])
    ? $_SESSION['diary_edit_errors']
    : array();
$flash_old = isset($_SESSION['diary_edit_old']) && is_array($_SESSION['diary_edit_old'])
    ? $_SESSION['diary_edit_old']
    : array();
$flash_id = isset($_SESSION['diary_edit_id']) ? $_SESSION['diary_edit_id'] : null;

unset($_SESSION['diary_edit_errors'], $_SESSION['diary_edit_old'], $_SESSION['diary_edit_id']);

$use_flash = $diary_id !== false
    && $flash_id !== null
    && (int) $flash_id === (int) $diary_id;
$errors = $use_flash ? $flash_errors : array();
$form_values = $entry !== null ? $entry : array();

if ($entry !== null && $use_flash) {
    $form_values = array_replace($form_values, $flash_old);
}

if (empty($_SESSION['diary_edit_csrf_token'])) {
    $_SESSION['diary_edit_csrf_token'] = bin2hex(random_bytes(32));
}

function diaryEditEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function diaryEditFormValue($values, $key) {
    return diaryEditEscape(isset($values[$key]) ? $values[$key] : '');
}

$moods = array('Happy', 'Calm', 'Neutral', 'Sad', 'Stressed');
$selected_mood = isset($form_values['mood']) ? $form_values['mood'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Journal Entry</title>
    <link rel="stylesheet" href="../../assets/css/diary.css">
</head>
<body class="diary-page">
    <main class="diary-container">
        <header class="diary-page-header">
            <p class="diary-eyebrow">Diary Journal</p>
            <h1>Edit Journal Entry</h1>
        </header>

        <?php if ($database_error): ?>
            <div class="diary-alert diary-alert-error" role="alert">
                This journal entry could not be loaded right now. Please try again later.
            </div>
            <a class="diary-button diary-button-secondary" href="index.php">Back to Diary</a>
        <?php elseif ($entry === null): ?>
            <section class="diary-empty-state">
                <h2>Journal entry not found.</h2>
                <p>The entry may not exist or may not be available to your account.</p>
                <a class="diary-button" href="index.php">Back to Diary</a>
            </section>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="diary-alert diary-alert-error" role="alert">
                    <p>Please correct the following:</p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo diaryEditEscape($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="diary-form" action="edit_handler.php" method="post">
                <input type="hidden" name="id" value="<?php echo diaryEditEscape($diary_id); ?>">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo diaryEditEscape($_SESSION['diary_edit_csrf_token']); ?>"
                >

                <div class="diary-form-group">
                    <label for="title">Title</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="150"
                        required
                        value="<?php echo diaryEditFormValue($form_values, 'title'); ?>"
                    >
                </div>

                <div class="diary-form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="10" required><?php echo diaryEditFormValue($form_values, 'content'); ?></textarea>
                </div>

                <div class="diary-form-group">
                    <label for="mood">Mood</label>
                    <select id="mood" name="mood" required>
                        <option value="">Select a mood</option>
                        <?php foreach ($moods as $mood): ?>
                            <option value="<?php echo diaryEditEscape($mood); ?>"<?php echo $selected_mood === $mood ? ' selected' : ''; ?>>
                                <?php echo diaryEditEscape($mood); ?>
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
                        value="<?php echo diaryEditFormValue($form_values, 'entry_date'); ?>"
                    >
                </div>

                <div class="diary-form-actions">
                    <button type="submit">Save Changes</button>
                    <a href="view.php?id=<?php echo rawurlencode((string) $diary_id); ?>">Cancel / Back</a>
                </div>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
