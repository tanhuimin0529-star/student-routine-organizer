<?php
// ===================================================================
// dashboard.php
// Main student dashboard shown right after login.
// Provides navigation to all integrated modules.
// ===================================================================

require_once __DIR__ . "/../includes/session_start.php";
require_once __DIR__ . "/../includes/cookie_consent.php";
require_once __DIR__ . "/../includes/shared_navbar.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit();
}

require_once __DIR__ . "/../config/database.php";

/**
 * Load small, generic activity summaries for the authenticated student.
 * Each SELECT is isolated so one unavailable module table does not prevent
 * the remaining Dashboard activity from being shown.
 */
function dashboardLoadRecentActivities($conn, $user_id) {
    $user_id = (int) $user_id;

    if ($user_id <= 0) {
        return array();
    }

    $queries = array(
        array(
            'module' => 'Exercise Tracker',
            'icon' => '🏋️',
            'description' => 'Logged an exercise activity',
            'sql' => 'SELECT created_at AS activity_at
                      FROM exercise
                      WHERE user_id = ?
                      ORDER BY created_at DESC
                      LIMIT 6'
        ),
        array(
            'module' => 'Diary Journal',
            'icon' => '📔',
            'description' => 'Added a diary entry',
            'sql' => 'SELECT created_at AS activity_at
                      FROM diary_entries
                      WHERE user_id = ?
                      ORDER BY created_at DESC
                      LIMIT 6'
        ),
        array(
            'module' => 'Money Tracker',
            'icon' => '💰',
            'description' => '',
            'sql' => 'SELECT transaction_type, created_at AS activity_at
                      FROM money_transactions
                      WHERE user_id = ?
                      ORDER BY created_at DESC
                      LIMIT 6'
        ),
        array(
            'module' => 'Habit Tracker',
            'icon' => '✅',
            'description' => 'Completed a habit',
            'sql' => 'SELECT CONCAT(hl.log_date, " ", hl.log_time) AS activity_at
                      FROM habit_logs hl
                      INNER JOIN habits h ON h.habit_id = hl.habit_id
                      WHERE h.user_id = ? AND hl.completed = 1
                      ORDER BY hl.log_date DESC, hl.log_time DESC
                      LIMIT 6'
        )
    );

    $activities = array();

    foreach ($queries as $query) {
        $statement = null;

        try {
            $statement = mysqli_prepare($conn, $query['sql']);
            mysqli_stmt_bind_param($statement, 'i', $user_id);
            mysqli_stmt_execute($statement);
            $result = mysqli_stmt_get_result($statement);

            while ($row = mysqli_fetch_assoc($result)) {
                $activity_at = isset($row['activity_at'])
                    ? (string) $row['activity_at']
                    : '';
                $sort_time = strtotime($activity_at);

                if ($sort_time === false) {
                    continue;
                }

                $description = $query['description'];

                if ($query['module'] === 'Money Tracker') {
                    $transaction_type = isset($row['transaction_type'])
                        ? (string) $row['transaction_type']
                        : '';

                    if ($transaction_type === 'Income') {
                        $description = 'Recorded an income transaction';
                    } elseif ($transaction_type === 'Expense') {
                        $description = 'Recorded an expense transaction';
                    } else {
                        $description = 'Recorded a transaction';
                    }
                }

                $activities[] = array(
                    'module' => $query['module'],
                    'icon' => $query['icon'],
                    'description' => $description,
                    'activity_at' => $activity_at,
                    'sort_time' => $sort_time
                );
            }
        } catch (mysqli_sql_exception $exception) {
            // Keep technical details private and let the other modules load.
            error_log(
                '[Dashboard recent activity]['
                . $query['module']
                . '] mysqli error '
                . (int) $exception->getCode()
            );
        } finally {
            if ($statement instanceof mysqli_stmt) {
                mysqli_stmt_close($statement);
            }
        }
    }

    usort($activities, function ($first, $second) {
        return $second['sort_time'] <=> $first['sort_time'];
    });

    return array_slice($activities, 0, 6);
}

$cookie_consent = getCookieConsentChoice();

if (
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'student' &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['cookie_choice'])
) {
    $choice = $_POST['cookie_choice'];

    if (setCookieConsentChoice($choice)) {

        if (
            $choice === 'accepted' &&
            isset($_SESSION['pending_remembered_email'])
        ) {
            setOptionalPreferenceCookie(
                'remembered_email',
                $_SESSION['pending_remembered_email']
            );

        } elseif ($choice === 'denied') {

            clearAllOptionalPreferenceCookies();
        }

        unset($_SESSION['pending_remembered_email']);
    }

    header("Location: dashboard.php");
    exit();
}

if (
    $cookie_consent === 'accepted' &&
    isset($_SESSION['pending_remembered_email'])
) {
    setOptionalPreferenceCookie(
        'remembered_email',
        $_SESSION['pending_remembered_email']
    );

    unset($_SESSION['pending_remembered_email']);

} elseif ($cookie_consent === 'denied') {

    unset($_SESSION['pending_remembered_email']);

    clearAllOptionalPreferenceCookies();
}

$show_cookie_consent =
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'student' &&
    $cookie_consent === null;

$recent_activities = dashboardLoadRecentActivities($conn, (int) $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dashboard - Student Routine Organizer
    </title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/css/exercise.css"
    >

    <style>

        .cookie-consent-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;

            display: flex;
            align-items: flex-end;
            justify-content: center;

            padding: 24px;

            background:
                rgba(15, 23, 42, 0.45);
        }

        .cookie-consent-card {
            width: min(620px, 100%);

            padding: 22px;

            border:
                1px solid rgba(255,255,255,.5);

            border-radius: 16px;

            background:
                rgba(255,255,255,.96);

            box-shadow:
                0 18px 48px rgba(15,23,42,.24);

            color: var(--gray-800);
        }

        .cookie-consent-card h2 {
            margin: 0 0 8px;
            font-size: 20px;
        }

        .cookie-consent-card p {
            margin: 0 0 18px;

            color: var(--gray-400);

            line-height: 1.6;
        }

        .cookie-consent-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .cookie-consent-actions button {
            min-width: 100px;
        }

    </style>

    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <?php renderSharedNavbarAssets('../'); ?>

    <link rel="stylesheet" href="../assets/css/system_ui.css">
</head>

<body class="global-dashboard-page system-ui-page">

<!-- Morphing blob background -->
<div class="morph-bg">

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>

</div>


<?php renderSharedStudentNavbar('../', '', 'dashboard'); ?>


<!-- Main Dashboard -->
<div class="page-wrapper">

    <h1>
        Welcome,
        <?php
        echo htmlspecialchars(
            $_SESSION['name']
        );
        ?>!
    </h1>

    <p
        style="
            margin-bottom:24px;
            color:var(--gray-400);
            font-size:15px;
        "
    >
        Choose a module below to get started.
    </p>


    <div class="stats-container">

        <!-- Exercise Tracker -->
        <a
            href="../modules/exercise/exercise_list.php"
            class="module-card"
        >

            <span class="module-icon">
                🏋️
            </span>

            <h3>
                Exercise Tracker
            </h3>

            <p>
                Log workouts and track your progress
            </p>

        </a>


        <!-- Diary Journal -->
        <a
            href="../modules/diary/index.php"
            class="module-card"
        >

            <span class="module-icon">
                📔
            </span>

            <h3>
                Diary Journal
            </h3>

            <p>
                Write and manage your personal journal
            </p>

        </a>


        <!-- Money Tracker -->
        <a
            href="../modules/money/index.php"
            class="module-card"
        >

            <span class="module-icon">
                💰
            </span>

            <h3>
                Money Tracker
            </h3>

            <p>
                Track income, expenses and monthly budgets
            </p>

        </a>


        <!-- Habit Tracker -->
        <a
            href="../modules/habit/index.php"
            class="module-card"
        >

            <span class="module-icon">
                ✅
            </span>

            <h3>
                Habit Tracker
            </h3>

            <p>
                Build routines and track your progress
            </p>

        </a>

    </div>

    <section class="dashboard-motivation-banner" aria-labelledby="dashboard-motivation-title">
        <span class="dashboard-motivation-accent" aria-hidden="true"></span>
        <div>
            <h2 id="dashboard-motivation-title">Stay consistent, stay amazing!</h2>
            <p>Small daily actions lead to meaningful progress.</p>
        </div>
    </section>

    <section class="dashboard-recent-activity" aria-labelledby="recent-activity-title">
        <header class="dashboard-section-heading">
            <div>
                <p class="dashboard-section-eyebrow">Your latest updates</p>
                <h2 id="recent-activity-title">Recent Activity</h2>
            </div>
            <?php if (!empty($recent_activities)) { ?>
                <span>Latest <?php echo count($recent_activities); ?></span>
            <?php } ?>
        </header>

        <?php if (empty($recent_activities)) { ?>
            <p class="dashboard-activity-empty">No recent activity yet.</p>
        <?php } else { ?>
            <div class="dashboard-activity-list">
                <?php foreach ($recent_activities as $activity) { ?>
                    <article class="dashboard-activity-item">
                        <span class="dashboard-activity-icon" aria-hidden="true">
                            <?php echo htmlspecialchars($activity['icon'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <div class="dashboard-activity-copy">
                            <strong><?php echo htmlspecialchars($activity['description'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars($activity['module'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <time datetime="<?php echo htmlspecialchars($activity['activity_at'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars(date('M j, Y · g:i A', $activity['sort_time']), ENT_QUOTES, 'UTF-8'); ?>
                        </time>
                    </article>
                <?php } ?>
            </div>
        <?php } ?>
    </section>

</div>


<!-- Cookie Consent -->
<?php if ($show_cookie_consent) { ?>

<div
    class="cookie-consent-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="cookie-consent-title"
>

    <div class="cookie-consent-card">

        <h2 id="cookie-consent-title">
            Cookie preferences
        </h2>

        <p>
            This website uses an essential session cookie to keep you signed in.
            Optional cookies remember simple preferences, such as your email,
            last activity, and preferred sort order, to improve usability.
        </p>

        <form
            method="POST"
            action="dashboard.php"
            class="cookie-consent-actions"
        >

            <button
                type="submit"
                name="cookie_choice"
                value="denied"
                class="btn btn-secondary"
            >
                Deny
            </button>

            <button
                type="submit"
                name="cookie_choice"
                value="accepted"
                class="btn btn-primary"
            >
                Accept
            </button>

        </form>

    </div>

</div>

<?php } ?>

</body>

</html>