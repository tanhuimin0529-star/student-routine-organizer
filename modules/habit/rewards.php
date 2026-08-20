<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/habit_model.php';
require_once __DIR__ . '/habit_ui.php';

$rewardStates = habit_get_reward_states($conn, (int) $logged_in_user_id);
$stats = habit_get_stats($conn, (int) $logged_in_user_id);
$flash = habit_take_flash();
$earnedCount = count(array_filter($rewardStates, static fn (array $reward): bool => (bool) $reward['unlocked']));

habit_render_head('Rewards', 'rewards');
habit_render_flash($flash);
?>

<section class="page-hero">
    <div>
        <h1>Garden achievements</h1>
        <p>Achievements reflect your current check-in history. Build a consecutive-day streak or reach a total check-in milestone to unlock them.</p>
    </div>
    <div class="hero-stat">
        <strong><?= habit_e($earnedCount) ?> / <?= habit_e(count($rewardStates)) ?></strong>
        <span>achievements earned</span>
    </div>
</section>

<?php if (!$rewardStates): ?>
    <div class="empty-state">No badge definitions were found. Add Badge records to the badge_types table to show achievements here.</div>
<?php else: ?>
    <section class="reward-grid" aria-label="All Habit Garden achievements">
        <?php foreach ($rewardStates as $reward): ?>
            <?php
            $unlocked = $reward['unlocked'];
            $requirement = max(1, (int) $reward['requirement']);
            $progress = (int) $reward['progress'];
            $percent = min(100, (int) round($progress / $requirement * 100));
            ?>
            <article class="reward-card card <?= $unlocked ? '' : 'is-locked' ?>">
                <img class="reward-art" src="<?= habit_e(habit_reward_art($reward)) ?>" alt="">
                <span class="status-chip status-chip--<?= $unlocked ? 'Active' : 'Archived' ?>">
                    Tier <?= habit_e($reward['tree_tier']) ?> achievement
                </span>
                <h2><?= habit_e($reward['reward_name']) ?></h2>
                <p><?= habit_e($reward['reward_description']) ?></p>

                <div class="reward-progress">
                    <div class="reward-progress__meta">
                        <span><?= habit_e($progress) ?> / <?= habit_e($requirement) ?></span>
                        <span class="reward-progress__state">
                            <i class="bi <?= $unlocked ? 'bi-check-circle-fill' : 'bi-lock' ?>" aria-hidden="true"></i>
                            <?= $unlocked ? 'Earned' : 'Locked' ?>
                        </span>
                    </div>
                    <div class="progress-track" aria-label="Achievement progress: <?= habit_e($percent) ?> percent">
                        <div class="progress-fill" style="width: <?= habit_e($percent) ?>%"></div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<section class="insight-grid insight-grid--two" aria-label="Reward calculation summary">
    <article class="insight-card card">
        <i class="bi bi-fire" aria-hidden="true"></i>
        <h3>Longest current streak</h3>
        <strong><?= habit_e($stats['longest_streak']) ?></strong>
        <p>Used for streak-based achievements.</p>
    </article>
    <article class="insight-card card">
        <i class="bi bi-check2-circle" aria-hidden="true"></i>
        <h3>Total check-ins</h3>
        <strong><?= habit_e($stats['total_checkins']) ?></strong>
        <p>Used for account-wide achievement milestones.</p>
    </article>
</section>

<?php habit_render_footer(); ?>
