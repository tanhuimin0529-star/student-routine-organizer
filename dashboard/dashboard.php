<?php
// ===================================================================
// dashboard.php
// Minimal landing page shown right after login.
// This is a placeholder — the Dashboard module itself belongs to a
// teammate. This file only exists so login has somewhere to send the
// user, and so the Exercise Tracker button has a home. Feel free to
// replace this with the real Dashboard module once it is built.
// ===================================================================

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Routine Organizer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/exercise.css">
</head>

<body>

    <!-- Morphing blob background -->
    <div class="morph-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="blob blob-4"></div>
    </div>

    <nav class="navbar">
        <div class="nav-brand">Student Routine Organizer</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="../authentication/logout.php">Logout</a>
        </div>
    </nav>

    <div class="page-wrapper">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
        <p style="margin-bottom: 24px; color: var(--gray-400); font-size: 15px;">Choose a module below to get started.</p>

        <div class="stats-container">
            <a href="../modules/exercise/exercise_list.php" class="module-card">
                <span class="module-icon">🏋️</span>
                <h3>Exercise Tracker</h3>
                <p>Log workouts and track your progress</p>
            </a>

            <div class="module-card disabled">
                <span class="module-icon">📔</span>
                <h3>Diary Journal</h3>
                <p>Coming soon</p>
            </div>

            <div class="module-card disabled">
                <span class="module-icon">💰</span>
                <h3>Money Tracker</h3>
                <p>Coming soon</p>
            </div>

            <a href="../modules/habit/index.php" class="module-card">
                <span class="module-icon">✅</span>
                <h3>Habit Tracker</h3>
                <p>Build routines and track your progress</p>
            </a>
        </div>
    </div>

</body>

</html>