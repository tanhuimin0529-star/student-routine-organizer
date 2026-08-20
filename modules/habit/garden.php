<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/habit_model.php';
require_once __DIR__ . '/habit_ui.php';

$garden = habit_get_garden_state($conn, (int) $logged_in_user_id);
$stats = $garden['stats'];
$view = $garden;
$flash = habit_take_flash();

$displayDays = $stats['unique_days'];
$displayStreak = $stats['longest_streak'];
$displayCheckins = $stats['total_checkins'];

$milestones = [
    ['days' => 0, 'title' => 'Seed', 'copy' => 'The garden is ready for your first completed day.'],
    ['days' => 1, 'title' => 'New sprout', 'copy' => 'One active day brings the first sprout above the soil.'],
    ['days' => 3, 'title' => 'Growing garden', 'copy' => 'Three different active days turn effort into visible growth.'],
    ['days' => 7, 'title' => 'Flourishing garden', 'copy' => 'Seven active days reveal the full garden scene.'],
];

habit_render_head('Garden', 'garden');
habit_render_flash($flash);
?>

<section class="page-hero">
    <div>
        <h1>Your garden</h1>
        <p>Every day with at least one completed habit waters this little space. The garden grows from active days, so completing many habits on one day cannot skip the journey.</p>
    </div>
    <div class="hero-stat">
        <strong><?= habit_e($stats['unique_days']) ?></strong>
        <span>real active days</span>
    </div>
</section>

<section class="garden-stage" aria-labelledby="stage-heading">
    <div class="garden-stage__copy">
        <span class="status-chip status-chip--Active">Garden level</span>
        <h2 id="stage-heading"><?= habit_e($view['label']) ?></h2>
        <p><?= habit_e($view['description']) ?></p>
        <div class="stage-progress">
            <?php if ($view['next_target'] === null): ?>
                <strong>Full garden reached</strong>
                <span>Keep completing habits to maintain your consistency.</span>
            <?php else: ?>
                <strong>Next milestone: <?= habit_e($view['next_target']) ?> active days</strong>
                <span><?= habit_e($displayDays) ?> of <?= habit_e($view['next_target']) ?> active days completed</span>
                <div class="progress-track" aria-label="Garden milestone progress">
                    <div class="progress-fill" style="width: <?= habit_e($view['progress']) ?>%"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <img class="garden-stage__art" src="<?= habit_e($view['asset']) ?>" alt="<?= habit_e($view['label']) ?> with Habit Buddy">
</section>

<section class="insight-grid" aria-label="Garden statistics">
    <article class="insight-card card">
        <i class="bi bi-calendar-check" aria-hidden="true"></i>
        <h3>Active days</h3>
        <strong><?= habit_e($displayDays) ?></strong>
        <p>Different calendar days with at least one completed habit.</p>
    </article>
    <article class="insight-card card">
        <i class="bi bi-fire" aria-hidden="true"></i>
        <h3>Best current streak</h3>
        <strong><?= habit_e($displayStreak) ?></strong>
        <p>Consecutive days maintained on the same habit.</p>
    </article>
    <article class="insight-card card">
        <i class="bi bi-check2-circle" aria-hidden="true"></i>
        <h3>Total check-ins</h3>
        <strong><?= habit_e($displayCheckins) ?></strong>
        <p>All completed habit check-ins recorded by the database.</p>
    </article>
</section>

<section class="milestone-panel card" aria-labelledby="milestone-heading">
    <div class="section-heading">
        <div>
            <h2 id="milestone-heading">Garden milestones</h2>
            <p>Each milestone is reached through completed habits on different calendar days.</p>
        </div>
        <a class="text-link" href="index.php"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back home</a>
    </div>
    <div class="milestone-grid">
        <?php foreach ($milestones as $milestone): ?>
            <?php $reached = $displayDays >= $milestone['days']; ?>
            <article class="milestone <?= $reached ? '' : 'is-locked' ?>">
                <strong><?= habit_e($milestone['days']) ?> active day<?= $milestone['days'] === 1 ? '' : 's' ?></strong>
                <span><?= habit_e($milestone['title']) ?></span>
                <small><?= habit_e($milestone['copy']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php habit_render_footer(); ?>
