<?php
// ===================================================================
// exercise_list.php
// Shows all exercise records that belong ONLY to the logged-in user.
// Features: search, date filter, activity filter, calorie filter,
//           duration filter, multi-sort, pagination (10/page),
//           glassmorphism table with hover animations.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "exercise_functions.php";

// ── Sort (with cookie memory) ────────────────────────────────
if (isset($_GET['sort'])) {
    setcookie("preferred_sort", $_GET['sort'], time() + (30 * 24 * 60 * 60));
    $sort = $_GET['sort'];
} elseif (isset($_COOKIE['preferred_sort'])) {
    $sort = $_COOKIE['preferred_sort'];
} else {
    $sort = "newest";
}

// ── Filters ──────────────────────────────────────────────────
$search           = isset($_GET['search'])      ? trim($_GET['search'])      : "";
$filter_date      = isset($_GET['filter_date']) ? trim($_GET['filter_date']) : "";
$filter_activity  = isset($_GET['filter_act'])  ? trim($_GET['filter_act'])  : "";
$filter_min_cal   = isset($_GET['min_cal'])     ? (int)$_GET['min_cal']     : 0;
$filter_max_cal   = isset($_GET['max_cal'])     ? (int)$_GET['max_cal']     : 99999;
$filter_min_dur   = isset($_GET['min_dur'])     ? (int)$_GET['min_dur']     : 0;
$filter_max_dur   = isset($_GET['max_dur'])     ? (int)$_GET['max_dur']     : 99999;

// ── Get data ──────────────────────────────────────────────────
$all_exercises = getExercisesForUser($conn, $logged_in_user_id, $search, $filter_date, $sort);

// Apply additional PHP-side filters
if ($filter_activity !== "") {
    $all_exercises = array_filter($all_exercises, function($r) use ($filter_activity) {
        return $r['activity_type'] === $filter_activity;
    });
    $all_exercises = array_values($all_exercises);
}

if ($filter_min_cal > 0 || $filter_max_cal < 99999) {
    $all_exercises = array_filter($all_exercises, function($r) use ($filter_min_cal, $filter_max_cal) {
        return $r['calories_burned'] >= $filter_min_cal && $r['calories_burned'] <= $filter_max_cal;
    });
    $all_exercises = array_values($all_exercises);
}

if ($filter_min_dur > 0 || $filter_max_dur < 99999) {
    $all_exercises = array_filter($all_exercises, function($r) use ($filter_min_dur, $filter_max_dur) {
        return $r['duration'] >= $filter_min_dur && $r['duration'] <= $filter_max_dur;
    });
    $all_exercises = array_values($all_exercises);
}

// ── Pagination ────────────────────────────────────────────────
$per_page    = 10;
$total       = count($all_exercises);
$total_pages = max(1, (int)ceil($total / $per_page));
$current_page = isset($_GET['page']) ? max(1, min($total_pages, (int)$_GET['page'])) : 1;
$offset       = ($current_page - 1) * $per_page;
$exercises    = array_slice($all_exercises, $offset, $per_page);

// ── Stats ─────────────────────────────────────────────────────
$stats = getExerciseStats($conn, $logged_in_user_id);

// ── Success/error message ─────────────────────────────────────
$success_message = "";
if (isset($_GET['msg'])) {
    $msgs = array(
        'added'   => '✅ Exercise added successfully!',
        'updated' => '✅ Exercise updated successfully!',
        'deleted' => '✅ Exercise record deleted.',
    );
    $success_message = $msgs[$_GET['msg']] ?? "";
}

// Build pagination URL helper
function pageUrl($page, $params) {
    $params['page'] = $page;
    return 'exercise_list.php?' . http_build_query($params);
}

$filter_params = array_filter(array(
    'search'      => $search,
    'filter_date' => $filter_date,
    'filter_act'  => $filter_activity,
    'min_cal'     => $filter_min_cal > 0   ? $filter_min_cal : null,
    'max_cal'     => $filter_max_cal < 99999 ? $filter_max_cal : null,
    'min_dur'     => $filter_min_dur > 0   ? $filter_min_dur : null,
    'max_dur'     => $filter_max_dur < 99999 ? $filter_max_dur : null,
    'sort'        => $sort,
), function($v) { return $v !== null && $v !== ""; });

$page_title = "Exercise List";
require_once "../../includes/header.php";
?>

<!-- Page heading -->
<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
    <div>
        <h1>📋 My Exercise Records</h1>
        <p style="color:var(--gray-400); font-size:14px; margin-top:4px;">
            <?php echo $total; ?> record<?php echo $total != 1 ? 's' : ''; ?> found
        </p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="add_exercise.php" class="btn btn-primary">+ Log Workout</a>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<!-- ── Quick Stats Row ──────────────────────────────────────── -->
<div class="stats-container fade-in">
    <div class="stat-card">
        <span class="stat-number counter" data-target="<?php echo $stats['total_workouts']; ?>"><?php echo $stats['total_workouts']; ?></span>
        <span class="stat-label">Total Workouts</span>
    </div>
    <div class="stat-card">
        <span class="stat-number counter" data-target="<?php echo $stats['total_calories']; ?>"><?php echo $stats['total_calories']; ?></span>
        <span class="stat-label">Total Calories</span>
    </div>
    <div class="stat-card">
        <span class="stat-number counter" data-target="<?php echo $stats['total_duration']; ?>"><?php echo $stats['total_duration']; ?></span>
        <span class="stat-label">Total Minutes</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?php echo htmlspecialchars($stats['most_frequent']); ?></span>
        <span class="stat-label">Most Frequent</span>
    </div>
    <div class="stat-card">
        <span class="stat-number counter" data-target="<?php echo $stats['monthly_count']; ?>"><?php echo $stats['monthly_count']; ?></span>
        <span class="stat-label">This Month</span>
    </div>
</div>

<!-- ── Search / Filter / Sort Bar ──────────────────────────── -->
<form method="GET" action="exercise_list.php" class="search-bar fade-in delay-1">
    <!-- Text search -->
    <input type="text" name="search" placeholder="🔍 Search activity or notes…"
           value="<?php echo htmlspecialchars($search); ?>" style="min-width:160px;">

    <!-- Date filter -->
    <input type="date" name="filter_date"
           value="<?php echo htmlspecialchars($filter_date); ?>"
           title="Filter by date">

    <!-- Activity type filter -->
    <select name="filter_act" title="Filter by activity">
        <option value="">All Activities</option>
        <?php foreach ($activity_types as $type): ?>
            <option value="<?php echo $type; ?>" <?php if ($filter_activity === $type) echo 'selected'; ?>>
                <?php echo $type; ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Calorie range -->
    <input type="number" name="min_cal" min="0" max="9999" placeholder="Min cal"
           value="<?php echo $filter_min_cal > 0 ? $filter_min_cal : ''; ?>"
           style="width:110px;" title="Minimum calories">
    <input type="number" name="max_cal" min="0" max="9999" placeholder="Max cal"
           value="<?php echo $filter_max_cal < 99999 ? $filter_max_cal : ''; ?>"
           style="width:110px;" title="Maximum calories">

    <!-- Duration range -->
    <input type="number" name="min_dur" min="0" max="999" placeholder="Min min"
           value="<?php echo $filter_min_dur > 0 ? $filter_min_dur : ''; ?>"
           style="width:110px;" title="Minimum duration (minutes)">
    <input type="number" name="max_dur" min="0" max="999" placeholder="Max min"
           value="<?php echo $filter_max_dur < 99999 ? $filter_max_dur : ''; ?>"
           style="width:110px;" title="Maximum duration (minutes)">


    <!-- Sort -->
    <select name="sort" title="Sort order">
        <option value="newest"   <?php if ($sort === 'newest')   echo 'selected'; ?>>Newest First</option>
        <option value="oldest"   <?php if ($sort === 'oldest')   echo 'selected'; ?>>Oldest First</option>
        <option value="calories" <?php if ($sort === 'calories') echo 'selected'; ?>>Highest Calories</option>
        <option value="duration" <?php if ($sort === 'duration') echo 'selected'; ?>>Longest Duration</option>
        <option value="activity" <?php if ($sort === 'activity') echo 'selected'; ?>>Activity Name</option>
    </select>

    <button type="submit" class="btn btn-primary">Apply</button>
    <a href="exercise_list.php" class="btn btn-secondary">Reset</a>
</form>

<!-- ── Exercise Table ───────────────────────────────────────── -->
<div class="card fade-in delay-2" style="padding:24px; overflow-x:auto;">

    <?php if (count($exercises) == 0): ?>
        <div class="empty-state" style="padding:48px 20px;">
            <span class="empty-icon" style="font-size:60px; display:block; margin-bottom:16px; opacity:0.4;">🏃</span>
            <div class="empty-title" style="font-size:18px; font-weight:600; color:var(--gray-600); margin-bottom:8px;">
                <?php echo (count($all_exercises) == 0 && !$search && !$filter_date) ? 'No exercises yet!' : 'No matches found'; ?>
            </div>
            <p class="empty-desc" style="font-size:13px; color:var(--gray-400); max-width:320px; margin:0 auto 20px;">
                <?php echo (count($all_exercises) == 0 && !$search && !$filter_date)
                    ? 'Start your fitness journey by logging your very first workout.'
                    : 'Try adjusting your search terms or clearing the filters.'; ?>
            </p>
            <?php if (!$search && !$filter_date && !$filter_activity): ?>
                <a href="add_exercise.php" class="btn btn-primary btn-glow">+ Log Your First Workout</a>
            <?php else: ?>
                <a href="exercise_list.php" class="btn btn-secondary">Clear Filters</a>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <table class="exercise-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Activity</th>
                    <th>Duration</th>
                    <th>Calories</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exercises as $i => $row):
                    global $activity_icons;
                    $icon = isset($activity_icons[$row['activity_type']]) ? $activity_icons[$row['activity_type']] : '🏃';
                ?>
                    <tr>
                        <td style="color:var(--gray-400); font-size:12px;"><?php echo $offset + $i + 1; ?></td>
                        <td>
                            <span style="margin-right:6px;"><?php echo $icon; ?></span>
                            <?php echo htmlspecialchars($row['activity_type']); ?>
                        </td>
                        <td>
                            <span style="font-weight:600;"><?php echo htmlspecialchars($row['duration']); ?></span>
                            <span style="font-size:11px; color:var(--gray-400);"> min</span>
                        </td>
                        <td>
                            <span style="font-weight:700; color:var(--purple-dark);"><?php echo number_format($row['calories_burned']); ?></span>
                            <span style="font-size:11px; color:var(--gray-400);"> kcal</span>
                        </td>
                        <td style="white-space:nowrap; font-size:13px;">
                            <?php echo date('D, M d Y', strtotime($row['exercise_date'])); ?>
                        </td>
                        <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; color:var(--gray-400);">
                            <?php echo $row['notes'] ? htmlspecialchars(mb_strimwidth($row['notes'], 0, 45, '…')) : '<em>—</em>'; ?>
                        </td>
                        <td class="actions">
                            <a href="exercise_details.php?id=<?php echo $row['exercise_id']; ?>"
                               class="btn btn-small btn-secondary" title="View details">👁 View</a>
                            <a href="edit_exercise.php?id=<?php echo $row['exercise_id']; ?>"
                               class="btn btn-small btn-edit" title="Edit">✏️ Edit</a>
                            <a href="delete_exercise.php?id=<?php echo $row['exercise_id']; ?>"
                               class="btn btn-small btn-delete" title="Delete"
                               onclick="return confirm('Delete this exercise record? This cannot be undone.');">🗑</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ── Pagination ───────────────────────────────────── -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination" style="margin-top:20px;">
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo htmlspecialchars(pageUrl(1, $filter_params)); ?>" title="First">«</a>
                    <a href="<?php echo htmlspecialchars(pageUrl($current_page - 1, $filter_params)); ?>" title="Previous">‹</a>
                <?php else: ?>
                    <span class="disabled">«</span>
                    <span class="disabled">‹</span>
                <?php endif; ?>

                <?php
                $range = 2;
                $start = max(1, $current_page - $range);
                $end   = min($total_pages, $current_page + $range);
                if ($start > 1): ?><span>…</span><?php endif;
                for ($p = $start; $p <= $end; $p++): ?>
                    <?php if ($p == $current_page): ?>
                        <span class="active"><?php echo $p; ?></span>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars(pageUrl($p, $filter_params)); ?>"><?php echo $p; ?></a>
                    <?php endif; ?>
                <?php endfor;
                if ($end < $total_pages): ?><span>…</span><?php endif; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo htmlspecialchars(pageUrl($current_page + 1, $filter_params)); ?>" title="Next">›</a>
                    <a href="<?php echo htmlspecialchars(pageUrl($total_pages, $filter_params)); ?>" title="Last">»</a>
                <?php else: ?>
                    <span class="disabled">›</span>
                    <span class="disabled">»</span>
                <?php endif; ?>

                <span style="font-size:12px; color:var(--gray-400); margin-left:8px;">
                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                    &nbsp;(<?php echo $total; ?> total)
                </span>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once "../../includes/footer.php"; ?>