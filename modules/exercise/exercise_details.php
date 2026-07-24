<?php
// ===================================================================
// exercise_details.php
// Shows every field for ONE exercise record.
// Only works if the record belongs to the logged-in user.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "exercise_functions.php";

$exercise_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$exercise = getExerciseById($conn, $exercise_id, $logged_in_user_id);

// If no record was found (wrong id, or belongs to someone else) — go back
if (!$exercise) {
    header("Location: exercise_list.php");
    exit();
}

$icon = isset($activity_icons[$exercise['activity_type']]) ? $activity_icons[$exercise['activity_type']] : '🏃';

// Sidebar data: recent 5 exercises of the same type
$all = getExercisesForUser($conn, $logged_in_user_id, $exercise['activity_type'], '', 'newest');
$sameType = array_values(array_filter($all, function($r) use ($exercise_id) {
    return $r['exercise_id'] != $exercise_id;
}));
$sameType = array_slice($sameType, 0, 5);

$bests  = getPersonalBests($conn, $logged_in_user_id);
$streak = getExerciseStreak($conn, $logged_in_user_id);

$page_title = "Exercise Details";
require_once "../../includes/header.php";
?>

<!-- Back navigation -->
<div style="display:flex; align-items:center; gap:14px; margin-bottom:24px; flex-wrap:wrap;">
    <a href="dashboard.php" class="btn btn-secondary" style="font-size:12px; padding:7px 14px;">← Dashboard</a>
    <a href="exercise_list.php" class="btn btn-secondary" style="font-size:12px; padding:7px 14px;">📋 Records</a>
    <a href="edit_exercise.php?id=<?php echo $exercise_id; ?>" class="btn btn-edit btn-small" style="font-size:12px; padding:7px 14px;">✏️ Edit</a>
</div>

<h1><?php echo $icon; ?> Workout Details</h1>
<p style="color:var(--gray-400); font-size:14px; margin-bottom:24px;">
    Record #<?php echo $exercise['exercise_id']; ?> · Added <?php echo date('M d, Y', strtotime($exercise['created_at'])); ?>
</p>

<!-- ── Two-column layout ─────────────────────────────────────── -->
<div style="display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start;">

    <!-- ── LEFT — Detail Card ───────────────────────────────── -->
    <div>
        <div class="card detail-card fade-in">

            <!-- Hero stat row -->
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; text-align:center;">
                <div style="background:rgba(167,139,250,0.1); border-radius:var(--radius-sm); padding:16px;">
                    <div style="font-size:28px; font-weight:800; color:var(--purple-dark);">
                        <?php echo $exercise['calories_burned']; ?>
                    </div>
                    <div style="font-size:11px; color:var(--gray-400); text-transform:uppercase; letter-spacing:0.5px; margin-top:4px;">kcal burned</div>
                </div>
                <div style="background:rgba(34,211,238,0.1); border-radius:var(--radius-sm); padding:16px;">
                    <div style="font-size:28px; font-weight:800; color:var(--cyan-dark);">
                        <?php echo $exercise['duration']; ?>
                    </div>
                    <div style="font-size:11px; color:var(--gray-400); text-transform:uppercase; letter-spacing:0.5px; margin-top:4px;">minutes</div>
                </div>
                <div style="background:rgba(52,211,153,0.1); border-radius:var(--radius-sm); padding:16px;">
                    <div style="font-size:24px; font-weight:800; color:var(--green-dark);">
                        <?php echo date('M d', strtotime($exercise['exercise_date'])); ?>
                    </div>
                    <div style="font-size:11px; color:var(--gray-400); text-transform:uppercase; letter-spacing:0.5px; margin-top:4px;"><?php echo date('Y', strtotime($exercise['exercise_date'])); ?></div>
                </div>
            </div>

            <!-- Detail rows -->
            <div style="display:flex; flex-direction:column; gap:0;">
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(229,231,235,0.3);">
                    <span style="font-size:12px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:0.5px;">Activity</span>
                    <span style="font-size:14px; font-weight:700; color:var(--gray-800);">
                        <?php echo $icon; ?> <?php echo htmlspecialchars($exercise['activity_type']); ?>
                    </span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(229,231,235,0.3);">
                    <span style="font-size:12px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:0.5px;">Duration</span>
                    <span style="font-size:14px; font-weight:700; color:var(--gray-800);"><?php echo htmlspecialchars($exercise['duration']); ?> minutes</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(229,231,235,0.3);">
                    <span style="font-size:12px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:0.5px;">Calories Burned</span>
                    <span style="font-size:14px; font-weight:700; color:var(--purple-dark);">🔥 <?php echo number_format($exercise['calories_burned']); ?> kcal</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(229,231,235,0.3);">
                    <span style="font-size:12px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:0.5px;">Date</span>
                    <span style="font-size:14px; font-weight:700; color:var(--gray-800);">
                        <?php echo date('l, F j, Y', strtotime($exercise['exercise_date'])); ?>
                    </span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(229,231,235,0.3);">
                    <span style="font-size:12px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:0.5px;">Notes</span>
                    <span style="font-size:14px; color:var(--gray-600); max-width:280px; text-align:right; line-height:1.5;">
                        <?php echo $exercise['notes'] ? htmlspecialchars($exercise['notes']) : '<em style="color:var(--gray-300);">No notes added.</em>'; ?>
                    </span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid rgba(229,231,235,0.3);">
                    <span style="font-size:12px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:0.5px;">Added On</span>
                    <span style="font-size:13px; color:var(--gray-400);">
                        <?php echo date('M d, Y H:i', strtotime($exercise['created_at'])); ?>
                    </span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0;">
                    <span style="font-size:12px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:0.5px;">Last Updated</span>
                    <span style="font-size:13px; color:var(--gray-400);">
                        <?php echo date('M d, Y H:i', strtotime($exercise['updated_at'])); ?>
                    </span>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="form-buttons" style="margin-top:24px; border-top:1px solid rgba(229,231,235,0.3); padding-top:20px;">
                <a href="edit_exercise.php?id=<?php echo $exercise['exercise_id']; ?>" class="btn btn-edit">✏️ Edit</a>
                <a href="delete_exercise.php?id=<?php echo $exercise['exercise_id']; ?>"
                   class="btn btn-delete"
                   onclick="return confirm('Delete this exercise record? This cannot be undone.');">🗑 Delete</a>
                <a href="exercise_list.php" class="btn btn-secondary">← Back to List</a>
            </div>
        </div>
    </div>

    <!-- ── RIGHT — Sidebar ──────────────────────────────────── -->
    <div style="display:flex; flex-direction:column; gap:16px;">

        <!-- Compare vs Personal Bests -->
        <div class="card fade-in delay-1" style="padding:20px 22px;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:12px;">🥇 vs Personal Best</div>
            <!-- Calories comparison -->
            <div style="margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--gray-500); margin-bottom:5px;">
                    <span>This workout</span>
                    <span><?php echo $exercise['calories_burned']; ?> kcal</span>
                </div>
                <?php $calPct = $bests['highest_calories'] > 0 ? min(100, round($exercise['calories_burned'] / $bests['highest_calories'] * 100)) : 0; ?>
                <div style="height:8px; background:var(--gray-200); border-radius:4px; overflow:hidden;">
                    <div style="height:100%; width:<?php echo $calPct; ?>%; background:linear-gradient(90deg, var(--purple), var(--blue)); border-radius:4px; transition:width 1s ease;"></div>
                </div>
                <div style="text-align:right; font-size:11px; color:var(--gray-400); margin-top:3px;">
                    Best: <?php echo $bests['highest_calories']; ?> kcal (<?php echo $calPct; ?>%)
                </div>
            </div>
            <!-- Duration comparison -->
            <div>
                <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--gray-500); margin-bottom:5px;">
                    <span>This duration</span>
                    <span><?php echo $exercise['duration']; ?> min</span>
                </div>
                <?php $durPct = $bests['longest_workout'] > 0 ? min(100, round($exercise['duration'] / $bests['longest_workout'] * 100)) : 0; ?>
                <div style="height:8px; background:var(--gray-200); border-radius:4px; overflow:hidden;">
                    <div style="height:100%; width:<?php echo $durPct; ?>%; background:linear-gradient(90deg, var(--cyan), var(--green)); border-radius:4px; transition:width 1s ease;"></div>
                </div>
                <div style="text-align:right; font-size:11px; color:var(--gray-400); margin-top:3px;">
                    Best: <?php echo $bests['longest_workout']; ?> min (<?php echo $durPct; ?>%)
                </div>
            </div>
        </div>

        <!-- Streak -->
        <div class="card fade-in delay-2" style="padding:20px 22px; text-align:center;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:8px;">🔥 Current Streak</div>
            <div style="font-size:40px; font-weight:800; color:<?php echo $streak > 0 ? 'var(--orange)' : 'var(--gray-300)'; ?>;">
                <?php echo $streak; ?>
            </div>
            <div style="font-size:12px; color:var(--gray-400); margin-top:4px;">
                day<?php echo $streak != 1 ? 's' : ''; ?> in a row
            </div>
            <?php if ($streak == 0): ?>
                <a href="add_exercise.php" class="btn btn-primary" style="margin-top:10px; font-size:12px; padding:8px 16px;">Log Today →</a>
            <?php endif; ?>
        </div>

        <!-- Same activity — recent history -->
        <?php if (!empty($sameType)): ?>
        <div class="card fade-in delay-3" style="padding:20px 22px;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:10px;">
                <?php echo $icon; ?> Other <?php echo htmlspecialchars($exercise['activity_type']); ?> Sessions
            </div>
            <?php foreach ($sameType as $r): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <div>
                        <div style="font-size:12px; font-weight:600; color:var(--gray-800);"><?php echo date('M d, Y', strtotime($r['exercise_date'])); ?></div>
                        <div style="font-size:11px; color:var(--gray-400);"><?php echo $r['duration']; ?> min</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:13px; font-weight:700; color:var(--purple-dark);">🔥 <?php echo $r['calories_burned']; ?></div>
                        <a href="exercise_details.php?id=<?php echo $r['exercise_id']; ?>" style="font-size:10px; color:var(--blue-dark); text-decoration:none;">View →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card fade-in delay-3" style="padding:20px 22px; text-align:center;">
            <div style="font-size:32px; margin-bottom:8px; opacity:0.3;"><?php echo $icon; ?></div>
            <p style="font-size:12px; color:var(--gray-400);">No other <?php echo htmlspecialchars($exercise['activity_type']); ?> sessions yet.</p>
            <a href="add_exercise.php" class="btn btn-secondary" style="margin-top:8px; font-size:11px; padding:6px 14px;">Log One Now</a>
        </div>
        <?php endif; ?>
    </div>
</div><!-- end two-column -->

<!-- Responsive -->
<style>
@media (max-width: 900px) {
    div[style*="grid-template-columns:1fr 300px"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once "../../includes/footer.php"; ?>
