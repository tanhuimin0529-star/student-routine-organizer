<?php
// ===================================================================
// edit_exercise.php
// Lets a user update their own exercise record.
// The form is pre-filled with the existing values.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "exercise_functions.php";

$exercise_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Make sure the record exists and belongs to this user
$exercise = getExerciseById($conn, $exercise_id, $logged_in_user_id);
if (!$exercise) {
    header("Location: exercise_list.php");
    exit();
}

$errors = array();

// Pre-fill with existing values
$activity_type   = $exercise['activity_type'];
$duration        = $exercise['duration'];
$calories_burned = $exercise['calories_burned'];
$exercise_date   = $exercise['exercise_date'];
$notes           = $exercise['notes'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $activity_type   = trim($_POST['activity_type']);
    $duration        = trim($_POST['duration']);
    $calories_burned = trim($_POST['calories_burned']);
    $exercise_date   = trim($_POST['exercise_date']);
    $notes           = trim($_POST['notes']);

    $errors = validateExerciseInput($activity_type, $duration, $calories_burned, $exercise_date);

    if (count($errors) == 0) {

        $updated = updateExerciseRecord(
            $conn,
            $exercise_id,
            $logged_in_user_id,
            $activity_type,
            (int)$duration,
            (int)$calories_burned,
            $exercise_date,
            $notes
        );

        if ($updated) {
            header("Location: exercise_list.php?msg=updated");
            exit();
        } else {
            $errors[] = "Something went wrong while updating. Please try again.";
        }
    }
}

$page_title = "Edit Exercise";
require_once "../../includes/header.php";

// Load personal bests for sidebar
$personalBests = getPersonalBests($conn, $logged_in_user_id);
$stats         = getExerciseStats($conn, $logged_in_user_id);
?>

<!-- Page heading with back navigation -->
<div style="display:flex; align-items:center; gap:14px; margin-bottom:24px; flex-wrap:wrap;">
    <a href="dashboard.php" class="btn btn-secondary" style="font-size:12px; padding:7px 14px;">← Dashboard</a>
    <a href="exercise_list.php" class="btn btn-secondary" style="font-size:12px; padding:7px 14px;">📋 Records</a>
    <a href="exercise_details.php?id=<?php echo $exercise_id; ?>" class="btn btn-secondary" style="font-size:12px; padding:7px 14px;">👁 View</a>
</div>

<h1>✏️ Edit Workout</h1>
<p style="color:var(--gray-400); font-size:14px; margin-bottom:24px;">
    Editing record #<?php echo $exercise_id; ?> —
    <em><?php echo htmlspecialchars($exercise['activity_type']); ?></em>
    on <?php echo date('M d, Y', strtotime($exercise['exercise_date'])); ?>
</p>

<?php if (count($errors) > 0): ?>
    <div class="alert alert-error">
        <ul style="list-style:none;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- ── Two-column, two-row layout ────────────────────────────── -->
<!-- Row 1: Form (left) sits next to Original Record + Personal Bests (right) -->
<!-- Row 2: Edit Tips (left) sits next to Danger Zone (right), same height -->
<div class="edit-two-col" style="display:grid; grid-template-columns:1fr 1fr; grid-template-rows:auto auto; gap:16px; align-items:stretch;">

    <!-- ── ROW 1 / LEFT — Edit Form ─────────────────────────── -->
    <div class="card form-card fade-in" style="grid-column:1; grid-row:1;">
        <form method="POST" action="edit_exercise.php?id=<?php echo $exercise_id; ?>" novalidate>

            <!-- Activity Type -->
            <label for="activity_type">Activity Type</label>
            <select name="activity_type" id="activity_type" required>
                <?php foreach ($activity_types as $type):
                    $icon = isset($activity_icons[$type]) ? $activity_icons[$type] : '🏃';
                ?>
                    <option value="<?php echo $type; ?>" <?php if ($activity_type == $type) echo 'selected'; ?>>
                        <?php echo $icon . ' ' . $type; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Duration and Calories side by side -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div>
                    <label for="duration">Duration (minutes)</label>
                    <input type="number" name="duration" id="duration" min="1" max="600"
                           value="<?php echo htmlspecialchars($duration); ?>" required>
                </div>
                <div>
                    <label for="calories_burned">Calories Burned</label>
                    <input type="number" name="calories_burned" id="calories_burned" min="0" max="9999"
                           value="<?php echo htmlspecialchars($calories_burned); ?>" required>
                </div>
            </div>

            <!-- Date -->
            <label for="exercise_date">Exercise Date</label>
            <input type="date" name="exercise_date" id="exercise_date"
                   value="<?php echo htmlspecialchars($exercise_date); ?>" required>

            <!-- Notes -->
            <label for="notes">Notes <span style="font-weight:400; text-transform:none; color:var(--gray-300);">(optional)</span></label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>

            <div class="form-buttons" style="margin-top:28px;">
                <button type="submit" class="btn btn-edit">💾 Update Workout</button>
                <a href="exercise_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- ── ROW 1 / RIGHT — Original Record + Personal Bests ──── -->
    <div style="grid-column:2; grid-row:1; display:flex; flex-direction:column; gap:16px;">

        <!-- Original record snapshot -->
        <div class="card fade-in delay-1" style="padding:20px 22px;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:12px;">📋 Original Record</div>
            <?php $origIcon = isset($activity_icons[$exercise['activity_type']]) ? $activity_icons[$exercise['activity_type']] : '🏃'; ?>
            <div style="text-align:center; padding:16px; background:rgba(167,139,250,0.08); border-radius:12px; margin-bottom:12px;">
                <div style="font-size:36px; margin-bottom:6px;"><?php echo $origIcon; ?></div>
                <div style="font-size:16px; font-weight:700; color:var(--gray-800);"><?php echo htmlspecialchars($exercise['activity_type']); ?></div>
            </div>
            <div style="display:flex; flex-direction:column; gap:0;">
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:11px; color:var(--gray-400);">DURATION</span>
                    <span style="font-size:13px; font-weight:700; color:var(--gray-800);"><?php echo $exercise['duration']; ?> min</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:11px; color:var(--gray-400);">CALORIES</span>
                    <span style="font-size:13px; font-weight:700; color:var(--purple-dark);">🔥 <?php echo number_format($exercise['calories_burned']); ?> kcal</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:11px; color:var(--gray-400);">DATE</span>
                    <span style="font-size:12px; font-weight:600; color:var(--gray-800);"><?php echo date('M d, Y', strtotime($exercise['exercise_date'])); ?></span>
                </div>
                <?php if ($exercise['notes']): ?>
                <div style="padding:8px 0;">
                    <span style="font-size:11px; color:var(--gray-400); display:block; margin-bottom:4px;">NOTES</span>
                    <span style="font-size:12px; color:var(--gray-600); line-height:1.4;"><?php echo htmlspecialchars($exercise['notes']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Personal Bests for context -->
        <div class="card fade-in delay-2" style="padding:20px 22px;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:12px;">🥇 Personal Bests</div>
            <div style="display:flex; flex-direction:column; gap:0;">
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:11px; color:var(--gray-400);">BEST CALORIES</span>
                    <span style="font-size:13px; font-weight:700; color:var(--purple-dark);">🔥 <?php echo $personalBests['highest_calories']; ?> kcal</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:11px; color:var(--gray-400);">LONGEST SESSION</span>
                    <span style="font-size:13px; font-weight:700; color:var(--gray-800);">⏱️ <?php echo $personalBests['longest_workout']; ?> min</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:11px; color:var(--gray-400);">FAVOURITE</span>
                    <span style="font-size:12px; font-weight:600; color:var(--gray-800);"><?php echo htmlspecialchars($personalBests['most_frequent']); ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:8px 0;">
                    <span style="font-size:11px; color:var(--gray-400);">TOTAL WORKOUTS</span>
                    <span style="font-size:13px; font-weight:700; color:var(--gray-800);"><?php echo $stats['total_workouts']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ROW 2 / LEFT — Edit Tips ─────────────────────────── -->
    <div class="card fade-in delay-3" style="grid-column:1; grid-row:2; padding:18px 20px; background:rgba(96,165,250,0.06);">
        <div style="font-size:12px; font-weight:700; color:var(--blue-dark); margin-bottom:8px;">💡 Edit Tips</div>
        <ul style="font-size:12px; color:var(--gray-500); line-height:1.8; padding-left:16px;">
            <li>Update only the fields that need changing.</li>
            <li>Calories can be refined after checking a fitness app.</li>
            <li>Use the notes field for workout quality details.</li>
            <li>Date can be backdated for missed log entries.</li>
        </ul>
    </div>

    <!-- ── ROW 2 / RIGHT — Danger Zone: delete ──────────────── -->
    <div class="card fade-in delay-2" style="grid-column:2; grid-row:2; padding:20px 24px; border-color:rgba(248,113,113,0.2); display:flex; align-items:center;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; width:100%;">
            <div>
                <div style="font-size:13px; font-weight:700; color:var(--red-dark);">⚠️ Danger Zone</div>
                <p style="font-size:12px; color:var(--gray-400); margin-top:4px;">Permanently delete this workout record.</p>
            </div>
            <a href="delete_exercise.php?id=<?php echo $exercise_id; ?>"
               class="btn btn-delete btn-small"
               onclick="return confirm('Are you sure? This cannot be undone.');">🗑 Delete</a>
        </div>
    </div>

</div><!-- end two-column -->

<!-- Responsive -->
<style>
@media (max-width: 900px) {
    .edit-two-col {
        grid-template-columns: 1fr !important;
        grid-template-rows: auto !important;
    }
}
</style>

<?php require_once "../../includes/footer.php"; ?>