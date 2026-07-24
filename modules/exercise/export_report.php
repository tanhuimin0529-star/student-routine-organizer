<?php
// ===================================================================
// export_report.php
// Monthly exercise report — opens in a new tab and auto-prints
// ===================================================================

require_once "../../includes/session_check.php";
require_once "../../config/database.php";
require_once "exercise_functions.php";

// Month to export (defaults to current month)
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

$monthName   = date('F Y', mktime(0, 0, 0, $month, 1, $year));
$startDate   = sprintf('%04d-%02d-01', $year, $month);
$endDate     = date('Y-m-t', strtotime($startDate));

// Get exercises for that month
$exercises = getExercisesForUser($conn, $logged_in_user_id, '', '', 'oldest');
$monthEx   = array_filter($exercises, function($r) use ($startDate, $endDate) {
    return $r['exercise_date'] >= $startDate && $r['exercise_date'] <= $endDate;
});
$monthEx   = array_values($monthEx);

// Stats for this month
$totalCal  = array_sum(array_column($monthEx, 'calories_burned'));
$totalDur  = array_sum(array_column($monthEx, 'duration'));
$totalWkts = count($monthEx);

// Activity breakdown
$actBreak = array();
foreach ($monthEx as $e) {
    $t = $e['activity_type'];
    if (!isset($actBreak[$t])) $actBreak[$t] = 0;
    $actBreak[$t]++;
}
arsort($actBreak);

$dashStats = getDashboardStats($conn, $logged_in_user_id);
$userName  = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercise Report — <?php echo $monthName; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; color: #1F2937; background: #fff; padding: 32px; max-width: 800px; margin: 0 auto; }
        h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; }
        h2 { font-size: 16px; font-weight: 700; margin: 24px 0 10px; color: #374151; border-bottom: 2px solid #E5E7EB; padding-bottom: 6px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 3px solid #A78BFA; }
        .header-right { text-align: right; font-size: 13px; color: #6B7280; }
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-box { background: #F9FAFB; border-radius: 12px; padding: 18px; text-align: center; border: 1px solid #E5E7EB; }
        .stat-box .val { font-size: 28px; font-weight: 800; color: #1F2937; display: block; }
        .stat-box .lbl { font-size: 11px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 4px; display: block; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table th { background: #F3F4F6; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #6B7280; }
        table td { padding: 10px 12px; border-bottom: 1px solid #F3F4F6; color: #374151; }
        table tr:hover td { background: #F9FAFB; }
        .activity-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #EDE9FE; color: #5B21B6; }
        .breakdown { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
        .breakdown .bk-item { background: #F3F4F6; border-radius: 20px; padding: 6px 14px; font-size: 12px; color: #374151; }
        .footer-text { margin-top: 32px; padding-top: 16px; border-top: 1px solid #E5E7EB; font-size: 11px; color: #9CA3AF; text-align: center; }
        .print-btn { background: #A78BFA; color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: 'Poppins', sans-serif; margin-bottom: 24px; }
        .print-btn:hover { background: #7C3AED; }
        @media print {
            .print-btn { display: none; }
            body { padding: 16px; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>

<div class="header">
    <div>
        <div style="font-size:13px; color:#6B7280; margin-bottom:4px; font-weight:600; text-transform:uppercase; letter-spacing:0.8px;">Monthly Exercise Report</div>
        <h1><?php echo $monthName; ?></h1>
        <p style="font-size:13px; color:#6B7280; margin-top:6px;">Athlete: <strong><?php echo $userName; ?></strong></p>
    </div>
    <div class="header-right">
        <p><strong>Generated</strong><br><?php echo date('F j, Y'); ?></p>
        <p style="margin-top:8px;"><strong>Total Records</strong><br><?php echo $totalWkts; ?> workouts</p>
    </div>
</div>

<!-- Monthly Stats -->
<h2>📊 Monthly Summary</h2>
<div class="stats-row">
    <div class="stat-box">
        <span class="val"><?php echo $totalWkts; ?></span>
        <span class="lbl">Workouts</span>
    </div>
    <div class="stat-box">
        <span class="val"><?php echo number_format($totalCal); ?></span>
        <span class="lbl">Calories Burned</span>
    </div>
    <div class="stat-box">
        <span class="val"><?php echo $totalDur; ?></span>
        <span class="lbl">Total Minutes</span>
    </div>
</div>

<!-- Activity Breakdown -->
<?php if (!empty($actBreak)): ?>
<h2>🥧 Activity Breakdown</h2>
<div class="breakdown">
    <?php foreach ($actBreak as $type => $cnt): ?>
        <div class="bk-item"><strong><?php echo htmlspecialchars($type); ?></strong> — <?php echo $cnt; ?> session<?php echo $cnt != 1 ? 's' : ''; ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Exercise Table -->
<h2>📋 Exercise Log</h2>
<?php if (empty($monthEx)): ?>
    <p style="color:#6B7280; font-style:italic;">No exercises recorded this month.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Activity</th>
                <th>Duration</th>
                <th>Calories</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthEx as $i => $row): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo date('D, M d', strtotime($row['exercise_date'])); ?></td>
                    <td><span class="activity-badge"><?php echo htmlspecialchars($row['activity_type']); ?></span></td>
                    <td><?php echo $row['duration']; ?> min</td>
                    <td><?php echo number_format($row['calories_burned']); ?> kcal</td>
                    <td style="color:#9CA3AF;"><?php echo htmlspecialchars($row['notes'] ?: '—'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#F3F4F6;">
                <td colspan="3" style="font-weight:700; padding:10px 12px; font-size:12px;">TOTAL</td>
                <td style="font-weight:700;"><?php echo $totalDur; ?> min</td>
                <td style="font-weight:700;"><?php echo number_format($totalCal); ?> kcal</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<!-- All-time stats -->
<h2>🏆 All-Time Stats</h2>
<div class="stats-row">
    <div class="stat-box">
        <span class="val"><?php echo $dashStats['total_workouts']; ?></span>
        <span class="lbl">Total Workouts</span>
    </div>
    <div class="stat-box">
        <span class="val"><?php echo number_format($dashStats['total_calories']); ?></span>
        <span class="lbl">Total Calories</span>
    </div>
    <div class="stat-box">
        <span class="val"><?php echo $dashStats['total_duration']; ?></span>
        <span class="lbl">Total Minutes</span>
    </div>
</div>

<div class="footer-text">
    <p>🎓 Student Routine Organizer — Exercise Tracker Module</p>
    <p>This report was generated automatically from your exercise records on <?php echo date('F j, Y \a\t H:i'); ?>.</p>
</div>

</body>
</html>
