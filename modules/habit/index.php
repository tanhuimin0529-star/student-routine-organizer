<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/habit_model.php';
require_once __DIR__ . '/habit_ui.php';

date_default_timezone_set('Asia/Shanghai');

$today = new DateTimeImmutable('today');
$todayKey = $today->format('Y-m-d');
$allHabits = habit_get_habits_by_user($conn, (int) $logged_in_user_id);
$todayHabits = array_values(array_filter(
    $allHabits,
    static fn (array $habit): bool => $habit['status'] === 'Active' && $habit['start_date'] <= date('Y-m-d')
));
$stats = habit_get_stats($conn, (int) $logged_in_user_id);
$garden = habit_get_garden_state($conn, (int) $logged_in_user_id);
$rewardStates = habit_get_reward_states($conn, (int) $logged_in_user_id);
$flash = habit_take_flash();

$gardenView = $garden;

$weekStart = $today->modify('monday this week');
$weekEnd = $weekStart->modify('+6 days');
$completedDateSet = array_fill_keys(
    habit_get_completed_dates(
        $conn,
        (int) $logged_in_user_id,
        $weekStart->format('Y-m-d'),
        $weekEnd->format('Y-m-d')
    ),
    true
);
$weekDays = [];
for ($offset = 0; $offset < 7; $offset++) {
    $day = $weekStart->modify('+' . $offset . ' days');
    $key = $day->format('Y-m-d');
    $weekDays[] = [
        'short' => $day->format('D'),
        'date' => $day->format('M j'),
        'complete' => isset($completedDateSet[$key]),
        'today' => $key === $todayKey,
    ];
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = (string) ($_GET['status'] ?? 'All');
$sort = (string) ($_GET['sort'] ?? 'newest');
$allowedStatuses = ['All', 'Active', 'Paused', 'Completed', 'Archived'];
$allowedSorts = ['newest', 'oldest', 'az'];
$status = in_array($status, $allowedStatuses, true) ? $status : 'All';
$sort = in_array($sort, $allowedSorts, true) ? $sort : 'newest';
$filteredHabits = habit_get_habits_by_user(
    $conn,
    (int) $logged_in_user_id,
    $search,
    $status,
    $sort
);

$timeHour = (int) date('G');
$greeting = $timeHour < 12 ? 'Good morning' : ($timeHour < 18 ? 'Good afternoon' : 'Good evening');
$completedToday = $stats['completed_today'];
$activeToday = $stats['active_habits'];
$latestUnlocked = null;
foreach ($rewardStates as $rewardState) {
    if ($rewardState['unlocked']) {
        $latestUnlocked = $rewardState;
        break;
    }
}

habit_render_head('Home', 'home');
habit_render_flash($flash);
?>

<section class="home-grid" aria-label="Habit Garden home">
    <div class="today-column">
        <section class="greeting-card" aria-labelledby="greeting-title">
            <i class="bi bi-sun greeting-sun" aria-hidden="true"></i>
            <div>
                <h1 id="greeting-title"><?= habit_e($greeting) ?>, <?= habit_e(habit_current_user_name()) ?></h1>
                <p><?= habit_e($today->format('l, F j, Y')) ?></p>
            </div>
        </section>

        <section class="today-card card" aria-labelledby="today-heading">
            <div class="section-heading">
                <div>
                    <h2 id="today-heading">Today’s habits</h2>
                    <p>Small actions make the garden grow.</p>
                </div>
                <span class="date-highlight"><?= habit_e($today->format('D, M j')) ?></span>
            </div>

            <div class="week-strip" aria-label="This week’s completion history">
                <?php foreach ($weekDays as $day): ?>
                    <div class="week-day <?= $day['complete'] ? 'is-complete' : '' ?> <?= $day['today'] ? 'is-today' : '' ?>">
                        <span><?= habit_e($day['short']) ?></span>
                        <strong><?= habit_e($day['date']) ?></strong>
                        <i class="bi <?= $day['complete'] ? 'bi-check-circle-fill' : 'bi-circle' ?>" aria-hidden="true"></i>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$todayHabits): ?>
                <div class="empty-state">
                    <p>No active habits are scheduled yet.</p>
                    <a class="button button--green" href="add.php"><i class="bi bi-plus-lg" aria-hidden="true"></i>Add your first habit</a>
                </div>
            <?php else: ?>
                <div class="today-list">
                    <?php foreach (array_slice($todayHabits, 0, 5) as $habit): ?>
                        <article class="today-habit">
                            <img src="<?= habit_e(habit_category_art($habit)) ?>" alt="">
                            <div>
                                <h3><?= habit_e($habit['habit_name']) ?></h3>
                                <p><?= habit_e($habit['category_name'] ?: 'Routine') ?> · <?= habit_e($habit['target_frequency']) ?> per <?= habit_e(strtolower($habit['frequency_type'])) ?></p>
                            </div>
                            <?php if ((int) $habit['completed_today'] === 1): ?>
                                <span class="done-label"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Completed</span>
                            <?php else: ?>
                                <form action="checkin_handler.php" method="post">
                                    <input type="hidden" name="habit_id" value="<?= habit_e($habit['habit_id']) ?>">
                                    <button class="button button--small" type="submit">Complete</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="today-footer">
                <p><?= habit_e($completedToday) ?> of <?= habit_e($activeToday) ?> active habits completed today</p>
                <a class="button button--green button--small" href="add.php"><i class="bi bi-plus-lg" aria-hidden="true"></i>Add habit</a>
            </div>
        </section>
    </div>

    <section class="garden-card card" aria-labelledby="garden-heading">
        <div class="garden-card__copy">
            <h2 id="garden-heading">Your garden<br>is growing</h2>
            <p><?= habit_e($gardenView['label']) ?></p>
        </div>

        <div class="garden-floats">
            <div class="garden-float">
                <strong><?= habit_e($stats['longest_streak']) ?> day</strong>
                current best streak
            </div>
            <div class="garden-float garden-float--reward">
                <strong><?= habit_e($latestUnlocked['reward_name'] ?? 'First reward') ?></strong>
                <?= $latestUnlocked ? 'achievement unlocked' : 'waiting to bloom' ?>
            </div>
        </div>

        <img class="garden-card__art" src="<?= habit_e($gardenView['hero_asset']) ?>" alt="Habit Buddy tending the <?= habit_e(strtolower($gardenView['label'])) ?>">

        <div class="garden-card__actions">
            <a class="button button--quiet button--small" href="garden.php"><i class="bi bi-leaf" aria-hidden="true"></i>Open garden</a>
        </div>
    </section>
</section>

<section class="summary-grid" aria-label="Progress and achievements">
    <article class="progress-card card">
        <h2>Total progress</h2>
        <div class="progress-main">
            <div class="progress-number"><?= habit_e($stats['today_progress']) ?>%</div>
            <div class="progress-copy">
                <strong><?= habit_e($stats['completed_today']) ?> / <?= habit_e($stats['active_habits']) ?> habits</strong>
                <span>completed today</span>
                <div class="progress-track" aria-label="Today’s progress: <?= habit_e($stats['today_progress']) ?> percent">
                    <div class="progress-fill" style="width: <?= habit_e($stats['today_progress']) ?>%"></div>
                </div>
            </div>
        </div>
        <div class="metric-row">
            <div><strong><?= habit_e($stats['weekly_consistency']) ?>%</strong><span>weekly consistency</span></div>
            <div><strong><?= habit_e($stats['total_checkins']) ?></strong><span>all-time check-ins</span></div>
            <div><strong><?= habit_e($stats['unique_days']) ?></strong><span>active days</span></div>
        </div>
    </article>

    <article class="achievement-card card">
        <div class="achievement-head">
            <div>
                <h2>Achievements</h2>
                <p class="handwritten">Your consistency becomes a collection of garden memories.</p>
            </div>
            <a class="text-link" href="rewards.php">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if (!$rewardStates): ?>
            <div class="empty-state">Achievement definitions are not available yet.</div>
        <?php else: ?>
            <div class="achievement-list">
                <?php foreach (array_slice($rewardStates, 0, 3) as $reward): ?>
                    <?php $unlocked = $reward['unlocked']; ?>
                    <article class="achievement-item <?= $unlocked ? '' : 'is-locked' ?>">
                        <img src="<?= habit_e(habit_reward_art($reward)) ?>" alt="">
                        <div>
                            <h3><?= habit_e($reward['reward_name']) ?></h3>
                            <p><?= habit_e($reward['reward_description']) ?></p>
                            <span class="achievement-state">
                                <i class="bi <?= $unlocked ? 'bi-check-circle-fill' : 'bi-lock' ?>" aria-hidden="true"></i>
                                <?= $unlocked ? 'Earned' : habit_e($reward['progress'] . ' / ' . $reward['requirement']) ?>
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="manage-panel card" id="manage" aria-labelledby="manage-heading">
    <div class="manage-head">
        <div>
            <h2 id="manage-heading">Manage habits</h2>
            <p>Search, edit, pause, complete, or remove routines from one place.</p>
        </div>
        <a class="button button--green" href="add.php"><i class="bi bi-plus-lg" aria-hidden="true"></i>Create habit</a>
    </div>

    <form class="filter-form" method="get" action="index.php#manage">
        <input type="search" name="search" placeholder="Search habits" value="<?= habit_e($search) ?>" aria-label="Search habits">
        <select name="status" aria-label="Filter by status">
            <?php foreach ($allowedStatuses as $statusOption): ?>
                <option value="<?= habit_e($statusOption) ?>" <?= $statusOption === $status ? 'selected' : '' ?>><?= habit_e($statusOption) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort" aria-label="Sort habits">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
            <option value="az" <?= $sort === 'az' ? 'selected' : '' ?>>A to Z</option>
        </select>
        <button class="button" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Apply</button>
        <a class="button button--quiet" href="index.php#manage">Reset</a>
    </form>

    <?php if (!$filteredHabits): ?>
        <div class="empty-state">No habits match this view. Try another search or create a new habit.</div>
    <?php else: ?>
        <div class="habit-table">
            <?php foreach ($filteredHabits as $habit): ?>
                <?php $streak = habit_get_current_streak($conn, (int) $habit['habit_id']); ?>
                <article class="habit-row">
                    <div class="habit-row__name">
                        <img src="<?= habit_e(habit_category_art($habit)) ?>" alt="">
                        <div>
                            <h3><?= habit_e($habit['habit_name']) ?></h3>
                            <p><?= habit_e($habit['category_name'] ?: 'Routine') ?></p>
                        </div>
                    </div>
                    <div><span class="status-chip status-chip--<?= habit_e($habit['status']) ?>"><?= habit_e($habit['status']) ?></span></div>
                    <div class="habit-row__detail"><?= habit_e($habit['target_frequency']) ?> per <?= habit_e(strtolower($habit['frequency_type'])) ?></div>
                    <div class="habit-row__detail"><?= habit_e($streak) ?> day streak</div>
                    <div class="habit-row__actions">
                        <a class="button button--quiet button--small" href="edit.php?habit_id=<?= habit_e($habit['habit_id']) ?>"><i class="bi bi-pencil" aria-hidden="true"></i>Edit</a>
                        <form action="delete_handler.php" method="post" onsubmit="return confirm('Delete this habit and its check-in history? This cannot be undone.');">
                            <input type="hidden" name="habit_id" value="<?= habit_e($habit['habit_id']) ?>">
                            <button class="button button--danger button--small" type="submit"><i class="bi bi-trash3" aria-hidden="true"></i>Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php habit_render_footer(); ?>
