<?php
// This file prints the top part of every Exercise Tracker page
// (HTML head + sticky navbar with dark mode toggle).
// It is included by add_exercise, edit_exercise, exercise_list,
// and exercise_details — but NOT by dashboard.php (self-contained).
require_once __DIR__ . "/shared_navbar.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Exercise Tracker — log and track your workouts">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' — Exercise Tracker' : 'Exercise Tracker'; ?></title>

    <!-- Poppins from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="../../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../../assets/css/theme.css">
    <?php renderSharedNavbarAssets('../../'); ?>

    <!-- Core styles -->
    <link rel="stylesheet" href="../../assets/css/exercise.css">

    <!-- Dashboard styles (only on dashboard page — guarded by $page_title) -->
    <?php if (isset($load_dashboard_css) && $load_dashboard_css): ?>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <?php endif; ?>
</head>
<body>

<!-- ── Animated blob backgrounds ─────────────────────── -->
<div class="morph-bg">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="blob blob-5"></div>
    <div class="blob blob-6"></div>
</div>


<?php
$exercise_secondary_active = isset($page_title) && $page_title === 'Exercise List'
    ? 'records'
    : '';
renderIntegratedModuleHeader('../../', 'exercise', $exercise_secondary_active);
?>

<div class="page-wrapper">
<!-- ↑ page-wrapper is closed in footer.php -->