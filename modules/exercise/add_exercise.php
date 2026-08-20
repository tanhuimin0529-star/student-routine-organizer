<?php
// ===================================================================
// add_exercise.php
// Shows the "Add Exercise" form and saves a new record for the
// currently logged-in user.
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../includes/cookie_consent.php";
require_once "../../config/database.php";
require_once "exercise_functions.php";

$errors = array();

// Cookie: remember the last activity type the user picked
$last_activity = optionalCookiesAllowed() && isset($_COOKIE['last_activity']) ? $_COOKIE['last_activity'] : "";

// Default form values
$activity_type   = $last_activity;
$duration        = "";
$calories_burned = "";
$exercise_date   = date("Y-m-d");
$notes           = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect and clean submitted values
    $activity_type   = trim($_POST['activity_type']);
    $duration        = trim($_POST['duration']);
    $calories_burned = trim($_POST['calories_burned']);
    $exercise_date   = trim($_POST['exercise_date']);
    $notes           = trim($_POST['notes']);

    $errors = validateExerciseInput($activity_type, $duration, $calories_burned, $exercise_date);

    if (count($errors) == 0) {

        $inserted = addExerciseRecord(
            $conn,
            $logged_in_user_id,
            $activity_type,
            (int)$duration,
            (int)$calories_burned,
            $exercise_date,
            $notes
        );

        if ($inserted) {
            setOptionalPreferenceCookie('last_activity', $activity_type);
            header("Location: exercise_list.php?msg=added");
            exit();
        } else {
            $errors[] = "Something went wrong while saving. Please try again.";
        }
    }
}

$page_title = "Add Exercise";
require_once "../../includes/header.php";

// Load stats and recent workouts for the sidebar
$stats    = getExerciseStats($conn, $logged_in_user_id);
$recent   = getRecentTimeline($conn, $logged_in_user_id);
$streak   = getExerciseStreak($conn, $logged_in_user_id);
?>

<!-- Page heading with back link -->
<div style="display:flex; align-items:center; gap:14px; margin-bottom:24px; flex-wrap:wrap;">
    <a href="dashboard.php" class="btn btn-secondary" style="font-size:12px; padding:7px 14px;">← Dashboard</a>
    <a href="exercise_list.php" class="btn btn-secondary" style="font-size:12px; padding:7px 14px;">📋 Records</a>
</div>

<h1>🏃 Log a Workout</h1>
<p style="color:var(--gray-400); font-size:14px; margin-bottom:24px;">
    Record today's exercise and keep your streak going!
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

<!-- ── Two-column layout ─────────────────────────────────────── -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:stretch;">

    <!-- ── LEFT — Form ──────────────────────────────────────── -->
    <div style="display:flex; flex-direction:column; gap:16px;">
        <div class="card form-card fade-in" style="flex:1;">
            <form method="POST" action="add_exercise.php" novalidate id="log-form">

                <!-- Activity Type -->
                <label for="activity_type">Activity Type</label>
                <select name="activity_type" id="activity_type" required>
                    <option value="">— Select Activity —</option>
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
                               value="<?php echo htmlspecialchars($duration); ?>"
                               placeholder="e.g. 30" required>
                    </div>
                    <div>
                        <label for="calories_burned">Calories Burned</label>
                        <input type="number" name="calories_burned" id="calories_burned" min="0" max="9999"
                               value="<?php echo htmlspecialchars($calories_burned); ?>"
                               placeholder="e.g. 250" required>
                    </div>
                </div>

                <!-- Date -->
                <label for="exercise_date">Exercise Date</label>
                <input type="date" name="exercise_date" id="exercise_date"
                       value="<?php echo htmlspecialchars($exercise_date); ?>" required>

                <!-- Notes -->
                <label for="notes">Notes <span style="font-weight:400; text-transform:none; color:var(--gray-300);">(optional)</span></label>
                <textarea name="notes" id="notes" rows="3"
                          placeholder="e.g. Felt great, beat my personal best…"><?php echo htmlspecialchars($notes); ?></textarea>

                <div class="form-buttons" style="margin-top:28px;">
                    <button type="submit" class="btn btn-primary btn-glow">💾 Save Workout</button>
                    <a href="exercise_list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Workout tips card — fills remaining left-column height -->
        <div class="card fade-in delay-3" style="padding:22px 24px;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:14px;">🏋️ Workout Tips</div>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span style="font-size:20px; flex-shrink:0;">💧</span>
                    <div>
                        <div style="font-size:12px; font-weight:700; color:var(--gray-800); margin-bottom:2px;">Stay Hydrated</div>
                        <div style="font-size:11px; color:var(--gray-400); line-height:1.5;">Drink at least 500ml of water before your workout and keep sipping throughout.</div>
                    </div>
                </div>
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span style="font-size:20px; flex-shrink:0;">🔥</span>
                    <div>
                        <div style="font-size:12px; font-weight:700; color:var(--gray-800); margin-bottom:2px;">Warm Up First</div>
                        <div style="font-size:11px; color:var(--gray-400); line-height:1.5;">5–10 minutes of light cardio raises your heart rate and reduces injury risk.</div>
                    </div>
                </div>
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span style="font-size:20px; flex-shrink:0;">😴</span>
                    <div>
                        <div style="font-size:12px; font-weight:700; color:var(--gray-800); margin-bottom:2px;">Rest & Recover</div>
                        <div style="font-size:11px; color:var(--gray-400); line-height:1.5;">Muscles grow during rest. Aim for 7–9 hours of sleep and at least one rest day per week.</div>
                    </div>
                </div>
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span style="font-size:20px; flex-shrink:0;">🥗</span>
                    <div>
                        <div style="font-size:12px; font-weight:700; color:var(--gray-800); margin-bottom:2px;">Fuel Properly</div>
                        <div style="font-size:11px; color:var(--gray-400); line-height:1.5;">Eat a balanced meal with protein and complex carbs 1–2 hours before training.</div>
                    </div>
                </div>
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span style="font-size:20px; flex-shrink:0;">📈</span>
                    <div>
                        <div style="font-size:12px; font-weight:700; color:var(--gray-800); margin-bottom:2px;">Progressive Overload</div>
                        <div style="font-size:11px; color:var(--gray-400); line-height:1.5;">Gradually increase intensity, duration, or reps each week to keep improving.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT — Sidebar ──────────────────────────────────── -->
    <div style="display:flex; flex-direction:column; gap:16px; height:100%;">

        <!-- Quick Stats -->
        <div class="card fade-in delay-1" style="padding:20px 22px;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:14px;">📊 Your Stats</div>
            <div style="display:flex; flex-direction:column; gap:0;">
                <div style="display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:12px; color:var(--gray-400);">Total Workouts</span>
                    <span style="font-size:14px; font-weight:700; color:var(--gray-800);"><?php echo $stats['total_workouts']; ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:12px; color:var(--gray-400);">Total Calories</span>
                    <span style="font-size:14px; font-weight:700; color:var(--purple-dark);"><?php echo number_format($stats['total_calories']); ?> kcal</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:12px; color:var(--gray-400);">Total Minutes</span>
                    <span style="font-size:14px; font-weight:700; color:var(--gray-800);"><?php echo $stats['total_duration']; ?> min</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:12px; color:var(--gray-400);">This Month</span>
                    <span style="font-size:14px; font-weight:700; color:var(--gray-800);"><?php echo $stats['monthly_count']; ?> workouts</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:9px 0;">
                    <span style="font-size:12px; color:var(--gray-400);">🔥 Current Streak</span>
                    <span style="font-size:14px; font-weight:700; color:<?php echo $streak > 0 ? 'var(--orange)' : 'var(--gray-400)'; ?>;">
                        <?php echo $streak > 0 ? $streak . ' day' . ($streak != 1 ? 's' : '') : 'None'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Calorie Estimates reference -->
        <div class="card fade-in delay-2" style="padding:20px 22px;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:12px;">💡 Calorie Reference</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px 16px;">
                <div style="display:flex; align-items:center; gap:8px; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:18px;">🏃</span>
                    <div><div style="font-size:12px; font-weight:600; color:var(--gray-800);">Jogging</div><div style="font-size:11px; color:var(--gray-400);">30 min ≈ 250 kcal</div></div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:18px;">🏋️</span>
                    <div><div style="font-size:12px; font-weight:600; color:var(--gray-800);">Gym</div><div style="font-size:11px; color:var(--gray-400);">60 min ≈ 400 kcal</div></div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:18px;">🏊</span>
                    <div><div style="font-size:12px; font-weight:600; color:var(--gray-800);">Swimming</div><div style="font-size:11px; color:var(--gray-400);">45 min ≈ 350 kcal</div></div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:18px;">🚴</span>
                    <div><div style="font-size:12px; font-weight:600; color:var(--gray-800);">Cycling</div><div style="font-size:11px; color:var(--gray-400);">45 min ≈ 320 kcal</div></div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:18px;">🧘</span>
                    <div><div style="font-size:12px; font-weight:600; color:var(--gray-800);">Yoga</div><div style="font-size:11px; color:var(--gray-400);">40 min ≈ 130 kcal</div></div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <span style="font-size:18px;">⚽</span>
                    <div><div style="font-size:12px; font-weight:600; color:var(--gray-800);">Football</div><div style="font-size:11px; color:var(--gray-400);">90 min ≈ 600 kcal</div></div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; padding:8px 0;">
                    <span style="font-size:18px;">🏸</span>
                    <div><div style="font-size:12px; font-weight:600; color:var(--gray-800);">Badminton</div><div style="font-size:11px; color:var(--gray-400);">45 min ≈ 310 kcal</div></div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; padding:8px 0;">
                    <span style="font-size:18px;">🚶</span>
                    <div><div style="font-size:12px; font-weight:600; color:var(--gray-800);">Walking</div><div style="font-size:11px; color:var(--gray-400);">40 min ≈ 150 kcal</div></div>
                </div>
            </div>
        </div>

        <!-- Calorie Estimator widget -->
        <div class="card fade-in delay-2" style="padding:20px 22px;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:10px;">⚡ Calorie Estimator</div>
            <p style="font-size:12px; color:var(--gray-400); margin-bottom:12px;">Pick activity and enter duration — we'll estimate calories for you.</p>
            <select id="est-activity" style="width:100%; padding:8px 12px; border-radius:8px; border:1.5px solid var(--gray-200); font-family:var(--font); font-size:13px; margin-bottom:8px; background:rgba(255,255,255,0.7);">
                <option value="">— Activity —</option>
                <option value="8.3">🏃 Jogging</option>
                <option value="3.5">🚶 Walking</option>
                <option value="7.5">🚴 Cycling</option>
                <option value="8.0">🏊 Swimming</option>
                <option value="6.0">🏋️ Gym</option>
                <option value="3.0">🧘 Yoga</option>
                <option value="7.0">⚽ Football</option>
                <option value="6.5">🏸 Badminton</option>
            </select>
            <div style="display:flex; gap:8px; align-items:center; margin-bottom:10px;">
                <input type="number" id="est-duration" placeholder="Minutes" min="1" max="600"
                       style="flex:1; padding:8px 12px; border-radius:8px; border:1.5px solid var(--gray-200); font-family:var(--font); font-size:13px; background:rgba(255,255,255,0.7);">
                <input type="number" id="est-weight" placeholder="kg" min="30" max="200" value="70"
                       style="width:70px; padding:8px 10px; border-radius:8px; border:1.5px solid var(--gray-200); font-family:var(--font); font-size:13px; background:rgba(255,255,255,0.7);">
            </div>
            <div id="est-result" style="text-align:center; font-size:22px; font-weight:800; color:var(--purple-dark); min-height:32px;">—</div>
            <p style="text-align:center; font-size:11px; color:var(--gray-400); margin-top:4px;">estimated kcal</p>
            <button onclick="applyEstimate()" class="btn btn-secondary" style="width:100%; margin-top:10px; font-size:12px; padding:8px;">Use This Value ↑</button>
        </div>

        <!-- Recent Workouts mini-list -->
        <?php if (!empty($recent)): ?>
        <div class="card fade-in delay-3" style="padding:20px 22px;">
            <div style="font-size:13px; font-weight:700; color:var(--gray-700); margin-bottom:10px;">⏰ Recent Workouts</div>
            <?php foreach (array_slice($recent, 0, 5) as $r):
                $ricon = isset($activity_icons[$r['activity_type']]) ? $activity_icons[$r['activity_type']] : '🏃';
            ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--gray-200);">
                    <div>
                        <span style="font-size:14px;"><?php echo $ricon; ?></span>
                        <span style="font-size:12px; color:var(--gray-700); font-weight:600; margin-left:4px;"><?php echo htmlspecialchars($r['activity_type']); ?></span>
                        <div style="font-size:11px; color:var(--gray-400); margin-top:2px;"><?php echo $r['relative_date']; ?> · <?php echo $r['duration']; ?> min</div>
                    </div>
                    <span style="font-size:12px; font-weight:700; color:var(--purple-dark);">🔥 <?php echo $r['calories_burned']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div><!-- end two-column -->

<script>
// Calorie estimator widget
var MET = {
    '8.3': 8.3, // Jogging
    '3.5': 3.5, // Walking
    '7.5': 7.5, // Cycling
    '8.0': 8.0, // Swimming
    '6.0': 6.0, // Gym
    '3.0': 3.0, // Yoga
    '7.0': 7.0, // Football
    '6.5': 6.5  // Badminton
};

function updateEstimate() {
    var act = document.getElementById('est-activity').value;
    var dur = parseFloat(document.getElementById('est-duration').value);
    var wt  = parseFloat(document.getElementById('est-weight').value) || 70;
    var res = document.getElementById('est-result');

    if (act && dur > 0) {
        var kcal = Math.round(MET[act] * 3.5 * wt / 200 * dur);
        res.textContent = kcal;
    } else {
        res.textContent = '—';
    }
}

document.getElementById('est-activity').addEventListener('change', updateEstimate);
document.getElementById('est-duration').addEventListener('input', updateEstimate);
document.getElementById('est-weight').addEventListener('input', updateEstimate);

function applyEstimate() {
    var res = document.getElementById('est-result').textContent;
    if (res !== '—') {
        document.getElementById('calories_burned').value = res;
        showToast('Calorie estimate applied! ✅', 'success');
    }
}
</script>

<!-- Responsive: stack columns on mobile -->
<style>
@media (max-width: 900px) {
    div[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once "../../includes/footer.php"; ?>

