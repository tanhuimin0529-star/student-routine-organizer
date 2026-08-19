<?php
// ===================================================================
// dashboard.php
// Minimal landing page shown right after login.
// This is a placeholder — the Dashboard module itself belongs to a
// teammate. This file only exists so login has somewhere to send the
// user, and so the Exercise Tracker button has a home. Feel free to
// replace this with the real Dashboard module once it is built.
// ===================================================================

require_once __DIR__ . "/../includes/session_start.php";
require_once __DIR__ . "/../includes/cookie_consent.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit();
}

$cookie_consent = getCookieConsentChoice();

if (isset($_SESSION['role']) && $_SESSION['role'] === 'student' &&
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cookie_choice'])) {
    $choice = $_POST['cookie_choice'];

    if (setCookieConsentChoice($choice)) {
        if ($choice === 'accepted' && isset($_SESSION['pending_remembered_email'])) {
            setOptionalPreferenceCookie('remembered_email', $_SESSION['pending_remembered_email']);
        } elseif ($choice === 'denied') {
            clearAllOptionalPreferenceCookies();
        }

        unset($_SESSION['pending_remembered_email']);
    }

    header("Location: dashboard.php");
    exit();
}

if ($cookie_consent === 'accepted' && isset($_SESSION['pending_remembered_email'])) {
    setOptionalPreferenceCookie('remembered_email', $_SESSION['pending_remembered_email']);
    unset($_SESSION['pending_remembered_email']);
} elseif ($cookie_consent === 'denied') {
    unset($_SESSION['pending_remembered_email']);
    clearAllOptionalPreferenceCookies();
}

$show_cookie_consent = isset($_SESSION['role']) && $_SESSION['role'] === 'student' && $cookie_consent === null;
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
    <style>
        .cookie-consent-overlay {
            position: fixed; inset: 0; z-index: 1000; display: flex;
            align-items: flex-end; justify-content: center; padding: 24px;
            background: rgba(15, 23, 42, 0.45);
        }
        .cookie-consent-card {
            width: min(620px, 100%); padding: 22px; border: 1px solid rgba(255,255,255,.5);
            border-radius: 16px; background: rgba(255,255,255,.96);
            box-shadow: 0 18px 48px rgba(15,23,42,.24); color: var(--gray-800);
        }
        .cookie-consent-card h2 { margin: 0 0 8px; font-size: 20px; }
        .cookie-consent-card p { margin: 0 0 18px; color: var(--gray-400); line-height: 1.6; }
        .cookie-consent-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .cookie-consent-actions button { min-width: 100px; }
    </style>
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

        <div class="module-card disabled">
            <span class="module-icon">✅</span>
            <h3>Habit Tracker</h3>
            <p>Coming soon</p>
        </div>
    </div>
</div>

<?php if ($show_cookie_consent) { ?>
<div class="cookie-consent-overlay" role="dialog" aria-modal="true" aria-labelledby="cookie-consent-title">
    <div class="cookie-consent-card">
        <h2 id="cookie-consent-title">Cookie preferences</h2>
        <p>
            This website uses an essential session cookie to keep you signed in. Optional cookies remember
            simple preferences, such as your email, last activity, and preferred sort order, to improve usability.
        </p>
        <form method="POST" action="dashboard.php" class="cookie-consent-actions">
            <button type="submit" name="cookie_choice" value="denied" class="btn btn-secondary">Deny</button>
            <button type="submit" name="cookie_choice" value="accepted" class="btn btn-primary">Accept</button>
        </form>
    </div>
</div>
<?php } ?>

</body>
</html>
