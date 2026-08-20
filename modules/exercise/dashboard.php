<?php
// ===================================================================
// dashboard.php  — Fitness Dashboard (Exercise Tracker Module)
// Premium fitness dashboard with live data from MySQL
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "exercise_functions.php";

// ── Gather all data ───────────────────────────────────────────
$profile       = getFitnessProfile($conn, $logged_in_user_id);
$dashStats     = getDashboardStats($conn, $logged_in_user_id);
$todayStats    = getTodayStats($conn, $logged_in_user_id);
$bmi           = calculateBMI($profile['weight_kg'], $profile['height_cm']);
$streak        = getExerciseStreak($conn, $logged_in_user_id);
$longestStreak = getLongestStreak($conn, $logged_in_user_id);
$personalBests = getPersonalBests($conn, $logged_in_user_id);
$timeline      = getRecentTimeline($conn, $logged_in_user_id);
$heatmapData   = getHeatmapData($conn, $logged_in_user_id);
$weeklyData    = getWeeklyCalories($conn, $logged_in_user_id);
$monthlyTrend  = getMonthlyTrend($conn, $logged_in_user_id);
$distribution  = getExerciseDistribution($conn, $logged_in_user_id);
$durationTrend = getDurationTrend($conn, $logged_in_user_id);
$calTrend      = getCaloriesTrend($conn, $logged_in_user_id);

// Auto-award achievements
checkAchievements($conn, $logged_in_user_id);
$achievements  = getAchievements($conn, $logged_in_user_id);
$allBadges     = getAllPossibleBadges();
$earnedNames   = array_column($achievements, 'badge_name');

// ── Calorie progress ─────────────────────────────────────────
$calorieGoal   = max(1, (int)$profile['daily_calorie_goal']);
$todayCal      = (int)$todayStats['total_calories'];
$calPct        = min(100, round(($todayCal / $calorieGoal) * 100));
$calRemaining  = max(0, $calorieGoal - $todayCal);
$calRingColor  = $calPct >= 100 ? '#34D399' : ($calPct >= 70 ? '#FB923C' : '#F87171');

// ── Steps progress ────────────────────────────────────────────
$stepGoal      = max(1, (int)$profile['daily_step_goal']);
$currentSteps  = (int)$profile['current_steps'];
$stepPct       = min(100, round(($currentSteps / $stepGoal) * 100));

// ── Water intake ──────────────────────────────────────────────
$waterMl       = (int)$profile['water_intake_ml'];
$waterGlasses  = floor($waterMl / 250);

// ── Sleep quality label ───────────────────────────────────────
$sleepHours = (float)$profile['sleep_hours'];
if ($sleepHours >= 7) {
    $sleepLabel = "😴 Great Sleep!"; $sleepClass = "quality-good";
} elseif ($sleepHours >= 5) {
    $sleepLabel = "😐 Could Be Better"; $sleepClass = "quality-ok";
} elseif ($sleepHours > 0) {
    $sleepLabel = "😟 Need More Sleep"; $sleepClass = "quality-poor";
} else {
    $sleepLabel = "— Not Recorded"; $sleepClass = "quality-ok";
}

// ── Build chart JSON ──────────────────────────────────────────
$weekLabels    = json_encode(array_keys($weeklyData));
$weekValues    = json_encode(array_values($weeklyData));

$monthLabels   = json_encode(array_column($monthlyTrend, 'date'));
$monthValues   = json_encode(array_column($monthlyTrend, 'calories'));

$distLabels    = json_encode(array_column($distribution, 'type'));
$distValues    = json_encode(array_column($distribution, 'count'));

$durLabels     = json_encode(array_column($durationTrend, 'date'));
$durValues     = json_encode(array_column($durationTrend, 'duration'));

$calTrendLbls  = json_encode(array_column($calTrend, 'date'));
$calTrendVals  = json_encode(array_column($calTrend, 'calories'));

$heatmapJson   = json_encode($heatmapData);

// ── User name ─────────────────────────────────────────────────
$userName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Athlete';

$page_title = "Fitness Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Your personal fitness dashboard — track workouts, calories, steps, BMI and more.">
    <title>Fitness Dashboard — Exercise Tracker</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="../../assets/css/exercise.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>

<!-- Blob backgrounds -->
<div class="morph-bg">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="blob blob-5"></div>
    <div class="blob blob-6"></div>
</div>

<!-- Confetti canvas (injected by JS when needed) -->

<!-- Navbar -->
<nav class="navbar">
    <div class="nav-brand">Student Routine Organizer</div>
    <div class="nav-links">
        <a href="../../dashboard/dashboard.php">Home</a>
        <a href="dashboard.php" class="active">Fitness Dashboard</a>
        <a href="exercise_list.php">My Records</a>
        <a href="export_report.php" target="_blank">Export PDF</a>
        <a href="../../authentication/logout.php">Logout</a>
    </div>
    <button class="dark-mode-toggle" title="Toggle dark mode">🌙</button>
</nav>

<div class="page-wrapper">

    <!-- ── Page Header ─────────────────────────── -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 id="greeting-heading">👋 Hello, <?php echo $userName; ?>!</h1>
            <p style="color:var(--gray-400); font-size:14px; margin-top:4px;">
                <?php echo date('l, F j, Y'); ?> &nbsp;·&nbsp;
                <?php if ($streak > 0): ?>
                    🔥 You're on a <strong><?php echo $streak; ?>-day streak!</strong>
                <?php else: ?>
                    Start your streak by logging a workout today!
                <?php endif; ?>
            </p>
        </div>
        <a href="add_exercise.php" class="btn btn-primary btn-glow">
            + Log Workout
        </a>
    </div>

    <?php if ($calPct >= 100): ?>
    <!-- Goal Achieved Banner -->
    <div id="goal-celebration" class="dash-card accent-green fade-in" style="text-align:center; padding:24px; margin-bottom:var(--gap-lg);">
        <span style="font-size:40px; display:block; margin-bottom:8px; animation: bounce 1s ease infinite;">🎉</span>
        <strong style="font-size:20px; color:var(--green-dark);">Daily Calorie Goal Achieved!</strong>
        <p style="color:var(--gray-500); margin-top:6px; font-size:14px;">You burned <?php echo number_format($todayCal); ?> kcal today — goal was <?php echo number_format($calorieGoal); ?> kcal.</p>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════
         ROW 1 — Key Daily Metrics
    ════════════════════════════════════════════════════════ -->
    <div class="dashboard-grid fade-in delay-1">

        <!-- Calories Burned Today -->
        <div class="dash-card accent-purple">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">🔥</div>
                    <div class="card-title">Calories Burned Today</div>
                </div>
                <div class="tooltip-wrapper">
                    <span style="cursor:help; color:var(--gray-300); font-size:16px;">ⓘ</span>
                    <span class="tooltip-text">Total calories burned from all workouts today</span>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                <!-- Circular ring -->
                <div class="progress-ring-container ring-lg" id="calorie-ring">
                    <svg class="progress-ring" width="140" height="140" viewBox="0 0 140 140">
                        <circle class="progress-ring-bg" cx="70" cy="70" r="58"/>
                        <circle class="progress-ring-fill" cx="70" cy="70" r="58"
                                style="stroke:<?php echo $calRingColor; ?>"/>
                    </svg>
                    <div class="progress-ring-text">
                        <span class="ring-value counter" data-target="<?php echo $calPct; ?>"><?php echo $calPct; ?></span>
                        <span class="ring-label">%</span>
                    </div>
                </div>
                <!-- Stats -->
                <div style="flex:1; min-width:120px;">
                    <div class="big-stat">
                        <span class="counter" data-target="<?php echo $todayCal; ?>"><?php echo $todayCal; ?></span>
                        <span class="unit">kcal</span>
                    </div>
                    <div class="stat-meta" style="margin-top:10px;">
                        <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:4px;">
                            <span>Goal: <?php echo number_format($calorieGoal); ?> kcal</span>
                            <span style="color:<?php echo $calRingColor; ?>; font-weight:700;"><?php echo $calPct; ?>%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" id="cal-bar" style="width:0%; background:linear-gradient(90deg, <?php echo $calRingColor; ?>, <?php echo $calRingColor; ?>aa);"></div>
                        </div>
                        <p style="font-size:12px; color:var(--gray-400); margin-top:6px;">
                            <?php echo $calRemaining > 0 ? number_format($calRemaining).' kcal remaining' : '🎉 Goal reached!'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Calorie Goal Editor -->
        <div class="dash-card accent-blue">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">🎯</div>
                    <div class="card-title">Daily Calorie Goal</div>
                </div>
                <div class="tooltip-wrapper">
                    <span style="cursor:help; color:var(--gray-300); font-size:16px;">ⓘ</span>
                    <span class="tooltip-text">Set your personal daily calorie-burn target</span>
                </div>
            </div>
            <div class="big-stat">
                <?php echo number_format($calorieGoal); ?>
                <span class="unit">kcal / day</span>
            </div>
            <p style="font-size:12px; color:var(--gray-400); margin:8px 0 14px;">Adjust your target below and hit Save:</p>
            <div class="inline-edit">
                <input type="number" id="calorie-goal-input" min="1" max="10000"
                       value="<?php echo $calorieGoal; ?>" placeholder="e.g. 500">
                <button class="save-btn" id="save-calorie-goal">Save</button>
            </div>
            <p style="font-size:11px; color:var(--gray-300); margin-top:8px;">Default: 500 kcal</p>
        </div>

        <!-- Steps Tracker -->
        <div class="dash-card accent-cyan">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">👟</div>
                    <div class="card-title">Steps Tracker</div>
                </div>
                <div class="tooltip-wrapper">
                    <span style="cursor:help; color:var(--gray-300); font-size:16px;">ⓘ</span>
                    <span class="tooltip-text">Track today's steps vs. your goal. Resets daily.</span>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <div class="progress-ring-container ring-md" id="steps-ring">
                    <svg class="progress-ring" width="110" height="110" viewBox="0 0 110 110">
                        <circle class="progress-ring-bg" cx="55" cy="55" r="44"/>
                        <circle class="progress-ring-fill" cx="55" cy="55" r="44"
                                style="stroke:var(--cyan);"/>
                    </svg>
                    <div class="progress-ring-text">
                        <span class="ring-value counter" data-target="<?php echo $stepPct; ?>"><?php echo $stepPct; ?></span>
                        <span class="ring-label">%</span>
                    </div>
                </div>
                <div style="flex:1; min-width:100px;">
                    <div style="font-size:22px; font-weight:800; color:var(--gray-800);">
                        <span class="counter" data-target="<?php echo $currentSteps; ?>"><?php echo $currentSteps; ?></span>
                    </div>
                    <p style="font-size:12px; color:var(--gray-400);">of <?php echo number_format($stepGoal); ?> steps</p>
                    <div class="progress-bar-container" style="margin-top:8px;">
                        <div class="progress-bar-fill" id="step-bar"
                             style="width:0%; background:linear-gradient(90deg, var(--cyan), var(--blue));"></div>
                    </div>
                    <p style="font-size:11px; color:var(--gray-400); margin-top:4px;">
                        <?php echo max(0, $stepGoal - $currentSteps); ?> steps to goal
                    </p>
                    <div style="margin-top:10px; display:flex; gap:6px; flex-wrap:wrap;">
                        <input type="number" id="steps-input" placeholder="Today's steps" min="0" max="100000"
                               value="<?php echo $currentSteps; ?>"
                               style="width:100px; padding:7px 10px; border-radius:8px; border:1.5px solid var(--gray-200); font-family:var(--font); font-size:12px; background:rgba(255,255,255,0.6);">
                        <input type="number" id="steps-goal-input" placeholder="Goal" min="1" max="100000"
                               value="<?php echo $stepGoal; ?>"
                               style="width:80px; padding:7px 10px; border-radius:8px; border:1.5px solid var(--gray-200); font-family:var(--font); font-size:12px; background:rgba(255,255,255,0.6);">
                        <button class="save-btn" id="save-steps">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ROW 2 — Body, BMI, Summaries
    ════════════════════════════════════════════════════════ -->
    <div class="dashboard-grid fade-in delay-2">

        <!-- Weight & Height + BMI -->
        <div class="dash-card accent-green">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">⚖️</div>
                    <div class="card-title">Body Metrics</div>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; color:var(--gray-400); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Weight (kg)</label>
                    <input type="number" id="weight-input" step="0.1" min="1" max="500"
                           value="<?php echo $profile['weight_kg']; ?>"
                           style="width:100%; padding:10px; border-radius:8px; border:1.5px solid var(--gray-200); font-family:var(--font); font-size:15px; font-weight:700; text-align:center; background:rgba(255,255,255,0.6);">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:600; color:var(--gray-400); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Height (cm)</label>
                    <input type="number" id="height-input" step="0.1" min="50" max="300"
                           value="<?php echo $profile['height_cm']; ?>"
                           style="width:100%; padding:10px; border-radius:8px; border:1.5px solid var(--gray-200); font-family:var(--font); font-size:15px; font-weight:700; text-align:center; background:rgba(255,255,255,0.6);">
                </div>
            </div>
            <button class="save-btn" id="save-body" style="width:100%;">Save Body Metrics</button>
        </div>

        <!-- BMI Calculator -->
        <div class="dash-card accent-orange">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">📊</div>
                    <div class="card-title">BMI Calculator</div>
                </div>
                <div class="tooltip-wrapper">
                    <span style="cursor:help; color:var(--gray-300); font-size:16px;">ⓘ</span>
                    <span class="tooltip-text">Body Mass Index = weight(kg) ÷ height(m)²</span>
                </div>
            </div>
            <?php if ($bmi['value'] > 0): ?>
                <div class="bmi-display">
                    <div class="bmi-value <?php echo $bmi['class']; ?>">
                        <?php echo $bmi['value']; ?>
                    </div>
                    <span class="bmi-category <?php echo $bmi['class']; ?>">
                        <?php echo $bmi['category']; ?>
                    </span>
                    <div class="bmi-range">Healthy range: 18.5 – 24.9</div>
                </div>
                <!-- BMI scale bar -->
                <div style="margin-top:12px;">
                    <div style="display:flex; justify-content:space-between; font-size:10px; color:var(--gray-400); margin-bottom:4px;">
                        <span>Under</span><span>Normal</span><span>Over</span><span>Obese</span>
                    </div>
                    <div style="height:8px; border-radius:4px; background:linear-gradient(90deg, #60A5FA 0%, #34D399 30%, #FB923C 65%, #F87171 100%); position:relative;">
                        <?php
                            $bmiVal = min(40, max(10, $bmi['value']));
                            $bmiPos = round(($bmiVal - 10) / 30 * 100);
                        ?>
                        <div style="position:absolute; top:50%; left:<?php echo $bmiPos; ?>%; transform:translate(-50%,-50%); width:14px; height:14px; background:#fff; border-radius:50%; border:2px solid var(--gray-800); box-shadow:0 2px 6px rgba(0,0,0,0.2);"></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding:20px;">
                    <span class="empty-icon" style="font-size:36px;">📏</span>
                    <p class="empty-desc">Enter your weight and height to calculate BMI.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Workout Summary (all-time) -->
        <div class="dash-card accent-purple">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">🏆</div>
                    <div class="card-title">Workout Summary</div>
                </div>
            </div>
            <div class="stat-row">
                <span class="stat-row-label">Total Workouts</span>
                <span class="stat-row-value counter" data-target="<?php echo $dashStats['total_workouts']; ?>"><?php echo $dashStats['total_workouts']; ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-row-label">Total Minutes</span>
                <span class="stat-row-value counter" data-target="<?php echo $dashStats['total_duration']; ?>"><?php echo $dashStats['total_duration']; ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-row-label">Total Calories</span>
                <span class="stat-row-value counter" data-target="<?php echo $dashStats['total_calories']; ?>"><?php echo $dashStats['total_calories']; ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-row-label">Avg Duration</span>
                <span class="stat-row-value"><?php echo $dashStats['avg_duration']; ?> min</span>
            </div>
            <div class="stat-row">
                <span class="stat-row-label">Avg / Week</span>
                <span class="stat-row-value"><?php echo $dashStats['avg_workouts_week']; ?> workouts</span>
            </div>
            <div class="stat-row">
                <span class="stat-row-label">Favourite</span>
                <span class="stat-row-value"><?php echo htmlspecialchars($dashStats['most_frequent']); ?></span>
            </div>
            <div class="stat-row">
                <span class="stat-row-label">Longest Session</span>
                <span class="stat-row-value"><?php echo $dashStats['longest_workout']; ?> min</span>
            </div>
            <div class="stat-row">
                <span class="stat-row-label">Shortest Session</span>
                <span class="stat-row-value"><?php echo $dashStats['shortest_workout']; ?> min</span>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ROW 3 — Today's Summary + Personal Best + Streak
    ════════════════════════════════════════════════════════ -->
    <div class="dashboard-grid fade-in delay-3">

        <!-- Today's Exercise Summary -->
        <div class="dash-card accent-cyan">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">📅</div>
                    <div class="card-title">Today's Summary</div>
                </div>
            </div>
            <?php if ($todayStats['workout_count'] == 0): ?>
                <div class="empty-state" style="padding:24px 10px;">
                    <span class="empty-icon" style="font-size:40px;">🏃</span>
                    <p class="empty-title" style="font-size:14px;">No workouts yet today</p>
                    <a href="add_exercise.php" class="btn btn-primary" style="margin-top:8px; font-size:12px; padding:8px 16px;">Log One Now</a>
                </div>
            <?php else: ?>
                <div class="quick-stats">
                    <div class="quick-stat-item">
                        <span class="qs-value counter" data-target="<?php echo $todayStats['workout_count']; ?>"><?php echo $todayStats['workout_count']; ?></span>
                        <span class="qs-label">Workouts</span>
                    </div>
                    <div class="quick-stat-item">
                        <span class="qs-value counter" data-target="<?php echo $todayStats['total_minutes']; ?>"><?php echo $todayStats['total_minutes']; ?></span>
                        <span class="qs-label">Minutes</span>
                    </div>
                    <div class="quick-stat-item">
                        <span class="qs-value counter" data-target="<?php echo $todayStats['total_calories']; ?>"><?php echo $todayStats['total_calories']; ?></span>
                        <span class="qs-label">Calories</span>
                    </div>
                    <div class="quick-stat-item">
                        <span class="qs-value" style="font-size:14px;"><?php echo htmlspecialchars($todayStats['most_frequent']); ?></span>
                        <span class="qs-label">Top Activity</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Personal Best -->
        <div class="dash-card accent-blue">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">🥇</div>
                    <div class="card-title">Personal Best</div>
                </div>
            </div>
            <div class="personal-best-grid">
                <div class="pb-item">
                    <span class="pb-icon">🔥</span>
                    <div class="pb-value counter" data-target="<?php echo $personalBests['highest_calories']; ?>"><?php echo $personalBests['highest_calories']; ?></div>
                    <div class="pb-label">Best Calories</div>
                </div>
                <div class="pb-item">
                    <span class="pb-icon">⏱️</span>
                    <div class="pb-value counter" data-target="<?php echo $personalBests['longest_workout']; ?>"><?php echo $personalBests['longest_workout']; ?></div>
                    <div class="pb-label">Longest (min)</div>
                </div>
                <div class="pb-item">
                    <span class="pb-icon">📆</span>
                    <div class="pb-value" style="font-size:12px; font-weight:600;"><?php echo htmlspecialchars($personalBests['most_active_day']); ?></div>
                    <div class="pb-label">Most Active Day</div>
                </div>
                <div class="pb-item">
                    <span class="pb-icon">🏅</span>
                    <div class="pb-value" style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($personalBests['most_frequent']); ?></div>
                    <div class="pb-label">Favourite</div>
                </div>
            </div>
        </div>

        <!-- Exercise Streak -->
        <div class="dash-card accent-red">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">🔥</div>
                    <div class="card-title">Exercise Streak</div>
                </div>
                <div class="tooltip-wrapper">
                    <span style="cursor:help; color:var(--gray-300); font-size:16px;">ⓘ</span>
                    <span class="tooltip-text">Consecutive days with at least one workout logged</span>
                </div>
            </div>
            <div class="streak-display">
                <?php if ($streak > 0): ?>
                    <span class="streak-fire">🔥</span>
                    <div class="streak-number counter" data-target="<?php echo $streak; ?>"><?php echo $streak; ?></div>
                    <div class="streak-label">Day<?php echo $streak != 1 ? 's' : ''; ?> Streak</div>
                    <p style="font-size:12px; color:var(--gray-400); margin-top:8px;">Best ever: <?php echo $longestStreak; ?> days</p>
                <?php else: ?>
                    <span style="font-size:40px; display:block; margin-bottom:8px; opacity:0.3;">🔥</span>
                    <div class="streak-number" style="font-size:36px; opacity:0.3;">0</div>
                    <div class="streak-label">No active streak</div>
                    <a href="add_exercise.php" class="btn btn-primary" style="margin-top:12px; font-size:12px; padding:8px 16px;">Start Today!</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ROW 4 — Weekly Progress Chart (full width)
    ════════════════════════════════════════════════════════ -->
    <div class="section-title fade-in delay-1">
        <span class="section-icon">📈</span>
        <h2>Weekly Progress</h2>
        <div class="section-line"></div>
        <span style="font-size:12px; color:var(--gray-400);">This week</span>
    </div>

    <div class="dashboard-grid fade-in delay-2">
        <div class="dash-card accent-purple span-3">
            <div class="dash-card-header">
                <div>
                    <div class="card-icon">📊</div>
                    <div class="card-title">Calories Burned — Mon to Sun</div>
                </div>
            </div>
            <?php if (array_sum($weeklyData) == 0): ?>
                <div class="empty-state" style="padding:30px;">
                    <span class="empty-icon" style="font-size:40px;">📊</span>
                    <p class="empty-title">No workouts this week yet</p>
                    <p class="empty-desc">Log a workout to see your weekly progress here.</p>
                </div>
            <?php else: ?>
                <div class="chart-container" style="height:240px;">
                    <canvas id="weeklyChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ROW 5 — Monthly Trend + Distribution
    ════════════════════════════════════════════════════════ -->
    <div class="section-title fade-in delay-1">
        <span class="section-icon">🗓️</span>
        <h2>Monthly Trends</h2>
        <div class="section-line"></div>
        <span style="font-size:12px; color:var(--gray-400);">Last 30 days</span>
    </div>

    <div class="dashboard-grid two-col fade-in delay-2">
        <!-- Monthly Workout Trend -->
        <div class="dash-card accent-blue">
            <div class="dash-card-header">
                <div class="card-icon">📉</div>
                <div class="card-title">Monthly Calories Trend</div>
            </div>
            <?php if (empty($monthlyTrend)): ?>
                <div class="empty-state" style="padding:24px;"><span class="empty-icon" style="font-size:36px;">📉</span><p class="empty-desc">No data yet.</p></div>
            <?php else: ?>
                <div class="chart-container" style="height:240px;"><canvas id="monthlyChart"></canvas></div>
            <?php endif; ?>
        </div>

        <!-- Exercise Distribution Pie -->
        <div class="dash-card accent-cyan">
            <div class="dash-card-header">
                <div class="card-icon">🥧</div>
                <div class="card-title">Exercise Distribution</div>
            </div>
            <?php if (empty($distribution)): ?>
                <div class="empty-state" style="padding:24px;"><span class="empty-icon" style="font-size:36px;">🥧</span><p class="empty-desc">No data yet.</p></div>
            <?php else: ?>
                <div class="chart-container" style="height:240px;"><canvas id="distributionChart"></canvas></div>
            <?php endif; ?>
        </div>

        <!-- Duration Trend -->
        <div class="dash-card accent-green">
            <div class="dash-card-header">
                <div class="card-icon">⏱️</div>
                <div class="card-title">Duration Trend (minutes)</div>
            </div>
            <?php if (empty($durationTrend)): ?>
                <div class="empty-state" style="padding:24px;"><span class="empty-icon" style="font-size:36px;">⏱️</span><p class="empty-desc">No data yet.</p></div>
            <?php else: ?>
                <div class="chart-container" style="height:240px;"><canvas id="durationChart"></canvas></div>
            <?php endif; ?>
        </div>

        <!-- Calories Trend Area Chart -->
        <div class="dash-card accent-orange">
            <div class="dash-card-header">
                <div class="card-icon">🌡️</div>
                <div class="card-title">Calories Trend (area)</div>
            </div>
            <?php if (empty($calTrend)): ?>
                <div class="empty-state" style="padding:24px;"><span class="empty-icon" style="font-size:36px;">🌡️</span><p class="empty-desc">No data yet.</p></div>
            <?php else: ?>
                <div class="chart-container" style="height:240px;"><canvas id="calTrendChart"></canvas></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ROW 6 — Goal Achievement Doughnut + Wellness Trackers
    ════════════════════════════════════════════════════════ -->
    <div class="section-title fade-in delay-1">
        <span class="section-icon">💧</span>
        <h2>Wellness Tracking</h2>
        <div class="section-line"></div>
    </div>

    <div class="dashboard-grid fade-in delay-2">

        <!-- Goal Achievement Doughnut -->
        <div class="dash-card accent-rainbow">
            <div class="dash-card-header">
                <div class="card-icon">🎯</div>
                <div class="card-title">Daily Goal Progress</div>
            </div>
            <div style="position:relative; height:200px; display:flex; align-items:center; justify-content:center;">
                <canvas id="goalChart"></canvas>
                <div style="position:absolute; text-align:center; pointer-events:none;">
                    <div style="font-size:28px; font-weight:800; color:var(--gray-800);"><?php echo $calPct; ?>%</div>
                    <div style="font-size:11px; color:var(--gray-400); text-transform:uppercase;">of goal</div>
                </div>
            </div>
            <?php if ($calPct >= 100): ?>
                <div class="goal-achieved" style="margin-top:8px;">
                    <span class="celebrate-emoji" style="font-size:28px;">🎉</span>
                    <div class="celebrate-text" style="font-size:15px;">Goal Achieved!</div>
                </div>
            <?php else: ?>
                <p style="text-align:center; font-size:13px; color:var(--gray-500); margin-top:8px;">
                    <?php echo number_format($calRemaining); ?> kcal to your daily goal
                </p>
            <?php endif; ?>
        </div>

        <!-- Water Intake Tracker -->
        <div class="dash-card accent-cyan">
            <div class="dash-card-header">
                <div class="card-icon">💧</div>
                <div class="card-title">Water Intake</div>
            </div>
            <div class="water-tracker">
                <div style="font-size:11px; color:var(--gray-400); margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">
                    Today's intake (resets daily)
                </div>
                <!-- Water glasses visual -->
                <div class="water-glasses">
                    <?php for ($g = 0; $g < 8; $g++): ?>
                        <div class="water-glass <?php echo $g < $waterGlasses ? 'filled' : ''; ?>"></div>
                    <?php endfor; ?>
                </div>
                <div style="margin:10px 0;">
                    <span class="water-count" id="water-current" data-value="<?php echo $waterMl; ?>">
                        <?php echo $waterMl; ?>
                    </span>
                    <span style="font-size:14px; color:var(--gray-400);"> ml</span>
                </div>
                <p style="font-size:12px; color:var(--gray-400); margin-bottom:10px;">
                    (~<?php echo $waterGlasses; ?> glasses · Goal: 8 glasses / 2000 ml)
                </p>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width:<?php echo min(100, round($waterMl/2000*100)); ?>%;
                         background:linear-gradient(90deg, var(--cyan), var(--blue));"></div>
                </div>
                <div class="water-controls" style="margin-top:14px;">
                    <button id="water-sub" title="Remove 250ml">−</button>
                    <span style="font-size:12px; color:var(--gray-400);">250 ml / tap</span>
                    <button id="water-add" title="Add 250ml">＋</button>
                </div>
            </div>
        </div>

        <!-- Sleep Duration Tracker -->
        <div class="dash-card accent-purple">
            <div class="dash-card-header">
                <div class="card-icon">🌙</div>
                <div class="card-title">Sleep Duration</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:11px; color:var(--gray-400); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px;">
                    Last night's sleep
                </div>
                <div class="sleep-input-group" style="justify-content:center;">
                    <button onclick="adjustSleep(-0.5)" style="width:36px; height:36px; border-radius:50%; border:1.5px solid var(--gray-200); background:var(--glass-bg); font-size:18px; cursor:pointer; font-family:var(--font);">−</button>
                    <input type="number" id="sleep-input" step="0.5" min="0" max="24"
                           value="<?php echo $sleepHours; ?>" style="width:80px; padding:10px; text-align:center; font-size:22px; font-weight:700; border-radius:10px; border:1.5px solid var(--gray-200); font-family:var(--font); background:rgba(255,255,255,0.6);">
                    <button onclick="adjustSleep(0.5)" style="width:36px; height:36px; border-radius:50%; border:1.5px solid var(--gray-200); background:var(--glass-bg); font-size:18px; cursor:pointer; font-family:var(--font);">＋</button>
                </div>
                <p style="font-size:14px; color:var(--gray-400);">hours</p>
                <div class="sleep-quality" style="margin-top:10px;">
                    <span id="sleep-quality-badge" class="quality-badge <?php echo $sleepClass; ?>">
                        <?php echo $sleepLabel; ?>
                    </span>
                </div>
                <div style="margin-top:12px;">
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width:<?php echo min(100, round($sleepHours/8*100)); ?>%;
                             background:linear-gradient(90deg, var(--purple), var(--blue));"></div>
                    </div>
                    <p style="font-size:11px; color:var(--gray-400); margin-top:4px;">Recommended: 7–9 hours</p>
                </div>
                <button class="save-btn" id="save-sleep" style="margin-top:12px;">Save Sleep</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ROW 7 — Achievements
    ════════════════════════════════════════════════════════ -->
    <div class="section-title fade-in delay-1">
        <span class="section-icon">🏅</span>
        <h2>Achievement Badges</h2>
        <div class="section-line"></div>
        <span style="font-size:12px; color:var(--gray-400);"><?php echo count($achievements); ?> earned</span>
    </div>

    <div class="dash-card accent-rainbow fade-in delay-2">
        <div class="badges-grid">
            <?php foreach ($allBadges as $badge): ?>
                <?php $earned = in_array($badge['name'], $earnedNames); ?>
                <div class="badge-item <?php echo $earned ? '' : 'locked'; ?>"
                     title="<?php echo htmlspecialchars($badge['desc']); ?>">
                    <span class="badge-icon"><?php echo $badge['icon']; ?></span>
                    <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                    <?php if ($earned):
                        $earnedAt = '';
                        foreach ($achievements as $a) {
                            if ($a['badge_name'] === $badge['name']) {
                                $earnedAt = date('M d', strtotime($a['earned_at']));
                                break;
                            }
                        }
                    ?>
                        <div class="badge-date">✅ <?php echo $earnedAt; ?></div>
                    <?php else: ?>
                        <div class="badge-date">🔒 Locked</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ROW 8 — Heatmap
    ════════════════════════════════════════════════════════ -->
    <div class="section-title fade-in delay-1">
        <span class="section-icon">🗓️</span>
        <h2>Exercise Heatmap</h2>
        <div class="section-line"></div>
        <span style="font-size:12px; color:var(--gray-400);">Last 90 days</span>
    </div>

    <div class="dash-card accent-purple fade-in delay-2">
        <div class="heatmap-container">
            <div style="display:flex; gap:6px; align-items:flex-start;">
                <div class="heatmap-labels">
                    <span>Mon</span><span>Tue</span><span>Wed</span>
                    <span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                </div>
                <div id="heatmap-grid-container" style="overflow-x:auto;"></div>
            </div>
        </div>
        <p style="font-size:11px; color:var(--gray-400); margin-top:10px;">
            Darker squares = more workouts that day. Hover to see details.
        </p>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ROW 9 — Recent Activity Timeline
    ════════════════════════════════════════════════════════ -->
    <div class="section-title fade-in delay-1">
        <span class="section-icon">⏰</span>
        <h2>Recent Activity</h2>
        <div class="section-line"></div>
        <a href="exercise_list.php" style="font-size:12px; color:var(--purple); font-weight:600; text-decoration:none;">View all →</a>
    </div>

    <div class="dash-card accent-blue fade-in delay-2">
        <?php if (empty($timeline)): ?>
            <div class="empty-state">
                <span class="empty-icon">🏃</span>
                <div class="empty-title">No exercises yet</div>
                <p class="empty-desc">Log your first workout to see your activity timeline here.</p>
                <a href="add_exercise.php" class="btn btn-primary">Log Your First Workout</a>
            </div>
        <?php else: ?>
            <?php
            $grouped = array();
            foreach ($timeline as $item) {
                $grouped[$item['relative_date']][] = $item;
            }
            ?>
            <div class="timeline">
                <?php foreach ($grouped as $dateLabel => $items): ?>
                    <div class="timeline-group">
                        <div class="timeline-date"><?php echo htmlspecialchars($dateLabel); ?></div>
                        <?php foreach ($items as $item):
                            global $activity_icons;
                            $icon = isset($activity_icons[$item['activity_type']]) ? $activity_icons[$item['activity_type']] : '🏃';
                        ?>
                            <div class="timeline-item">
                                <span class="tl-icon"><?php echo $icon; ?></span>
                                <div class="tl-content">
                                    <div class="tl-activity"><?php echo htmlspecialchars($item['activity_type']); ?></div>
                                    <div class="tl-details">
                                        <?php echo $item['duration']; ?> min
                                        <?php if ($item['notes']): ?>
                                            · <?php echo htmlspecialchars(mb_strimwidth($item['notes'], 0, 50, '…')); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="tl-calories">🔥 <?php echo number_format($item['calories_burned']); ?> kcal</div>
                                <a href="exercise_details.php?id=<?php echo $item['exercise_id']; ?>"
                                   class="btn btn-small btn-edit" style="margin-left:8px;">View</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom navigation shortcut -->
    <div style="text-align:center; margin-top:32px; padding:24px; background:var(--glass-bg); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border:1px solid var(--glass-border); border-radius:var(--radius-xl);">
        <p style="color:var(--gray-400); font-size:14px; margin-bottom:16px;">Manage your exercise records</p>
        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="exercise_list.php" class="btn btn-secondary">📋 All Records</a>
            <a href="add_exercise.php" class="btn btn-primary">+ Log Workout</a>
            <a href="export_report.php" target="_blank" class="btn btn-secondary">📄 Export PDF</a>
        </div>
    </div>

</div><!-- end page-wrapper -->

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- Core utilities -->
<script src="../../assets/js/script.js"></script>
<!-- Dashboard-specific JS -->
<script src="../../assets/js/dashboard.js"></script>

<script>
// ── Initialise everything once DOM is ready ───────────────────

document.addEventListener('DOMContentLoaded', function () {

    // Calorie progress ring + bar
    initProgressRing('calorie-ring', <?php echo $calPct; ?>, '<?php echo $calRingColor; ?>');
    setTimeout(function(){
        var bar = document.getElementById('cal-bar');
        if (bar) bar.style.width = '<?php echo $calPct; ?>%';
    }, 400);

    // Steps progress ring + bar
    initProgressRing('steps-ring', <?php echo $stepPct; ?>, 'var(--cyan)');
    setTimeout(function(){
        var bar = document.getElementById('step-bar');
        if (bar) bar.style.width = '<?php echo $stepPct; ?>%';
    }, 400);

    // Weekly chart
    initWeeklyChart('weeklyChart', <?php echo $weekLabels; ?>, <?php echo $weekValues; ?>);

    // Monthly trend
    initMonthlyTrendChart('monthlyChart', <?php echo $monthLabels; ?>, <?php echo $monthValues; ?>);

    // Distribution pie
    initDistributionChart('distributionChart', <?php echo $distLabels; ?>, <?php echo $distValues; ?>);

    // Duration trend
    initDurationTrendChart('durationChart', <?php echo $durLabels; ?>, <?php echo $durValues; ?>);

    // Calories trend area
    initCaloriesTrendChart('calTrendChart', <?php echo $calTrendLbls; ?>, <?php echo $calTrendVals; ?>);

    // Goal doughnut
    initGoalChart('goalChart', <?php echo $todayCal; ?>, <?php echo $calRemaining; ?>);

    // Heatmap
    initHeatmap('heatmap-grid-container', <?php echo $heatmapJson; ?>);

    // Water glasses initial state
    updateWaterGlasses(<?php echo $waterMl; ?>);

    // Goal achievement confetti
    checkGoalAchievement(<?php echo $todayCal; ?>, <?php echo $calorieGoal; ?>);
});

// Sleep adjustment helper (called from inline onclick)
function adjustSleep(delta) {
    var input = document.getElementById('sleep-input');
    var val = parseFloat(input.value) || 0;
    val = Math.max(0, Math.min(24, val + delta));
    input.value = val.toFixed(1);
}
</script>

</body>
</html>