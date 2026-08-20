<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/habit_model.php';
require_once __DIR__ . '/habit_ui.php';

$categories = habit_get_categories($conn);
$errors = $_SESSION['habit_form_errors'] ?? [];
$form = $_SESSION['habit_form_data'] ?? [];
unset($_SESSION['habit_form_errors'], $_SESSION['habit_form_data']);

$today = new DateTimeImmutable('today', new DateTimeZone('Asia/Shanghai'));

$form += [
    'habit_name' => '',
    'habit_description' => '',
    'category_id' => '',
    'target_frequency' => '1',
    'frequency_type' => 'Daily',
    'start_date' => $today->format('Y-m-d'),
    'status' => 'Active',
];

habit_render_head('Create habit', 'manage');
?>

<section class="form-shell">
    <form class="form-card card" action="add_handler.php" method="post" novalidate>
        <h1>Create a new habit</h1>
        <p class="form-card__intro">Start with one small routine. Required fields are marked clearly and any error will stay beside the field that needs attention.</p>

        <?php if ($errors): ?>
            <div class="validation-summary" role="alert">
                <strong>Please check the form.</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= habit_e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <input type="hidden" name="status" value="Active">
        <div class="form-grid">
            <label class="form-field form-field--full">
                <span>Habit name *</span>
                <input name="habit_name" type="text" maxlength="100" required value="<?= habit_form_value($form, 'habit_name') ?>" aria-invalid="<?= isset($errors['habit_name']) ? 'true' : 'false' ?>" <?= isset($errors['habit_name']) ? 'aria-describedby="habit_name-error"' : '' ?> placeholder="Example: Read 20 pages">
                <?php habit_render_field_error($errors, 'habit_name'); ?>
            </label>

            <label class="form-field">
                <span>Category *</span>
                <select name="category_id" required aria-invalid="<?= isset($errors['category_id']) ? 'true' : 'false' ?>" <?= isset($errors['category_id']) ? 'aria-describedby="category_id-error"' : '' ?>>
                    <option value="">Choose a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= habit_e($category['category_id']) ?>" <?= (string) $form['category_id'] === (string) $category['category_id'] ? 'selected' : '' ?>><?= habit_e($category['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php habit_render_field_error($errors, 'category_id'); ?>
            </label>

            <label class="form-field">
                <span>Start date *</span>
                <input name="start_date" type="date" min="<?= habit_e($today->format('Y-m-d')) ?>" required value="<?= habit_form_value($form, 'start_date') ?>" aria-invalid="<?= isset($errors['start_date']) ? 'true' : 'false' ?>" <?= isset($errors['start_date']) ? 'aria-describedby="start_date-error"' : '' ?>>
                <?php habit_render_field_error($errors, 'start_date'); ?>
            </label>

            <label class="form-field form-field--full">
                <span>Description</span>
                <textarea name="habit_description" maxlength="500" aria-invalid="<?= isset($errors['habit_description']) ? 'true' : 'false' ?>" <?= isset($errors['habit_description']) ? 'aria-describedby="habit_description-error"' : '' ?> placeholder="What will you do, and why does it matter?"><?= habit_form_value($form, 'habit_description') ?></textarea>
                <span class="field-hint">Optional, up to 500 characters.</span>
                <?php habit_render_field_error($errors, 'habit_description'); ?>
            </label>

            <label class="form-field">
                <span>Target frequency *</span>
                <input name="target_frequency" type="number" min="1" max="99" required value="<?= habit_form_value($form, 'target_frequency', '1') ?>" aria-invalid="<?= isset($errors['target_frequency']) ? 'true' : 'false' ?>" <?= isset($errors['target_frequency']) ? 'aria-describedby="target_frequency-error"' : '' ?>>
                <span class="field-hint">Daily habits must use 1 because check-in is once per day.</span>
                <?php habit_render_field_error($errors, 'target_frequency'); ?>
            </label>

            <label class="form-field">
                <span>Frequency type *</span>
                <select name="frequency_type" required aria-invalid="<?= isset($errors['frequency_type']) ? 'true' : 'false' ?>" <?= isset($errors['frequency_type']) ? 'aria-describedby="frequency_type-error"' : '' ?>>
                    <?php foreach (['Daily', 'Weekly', 'Monthly'] as $frequencyType): ?>
                        <option value="<?= habit_e($frequencyType) ?>" <?= $form['frequency_type'] === $frequencyType ? 'selected' : '' ?>><?= habit_e($frequencyType) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php habit_render_field_error($errors, 'frequency_type'); ?>
            </label>
        </div>

        <div class="form-actions">
            <a class="button button--quiet" href="index.php#manage">Cancel</a>
            <button class="button button--green" type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i>Create habit</button>
        </div>
    </form>
</section>

<?php habit_render_footer(); ?>
