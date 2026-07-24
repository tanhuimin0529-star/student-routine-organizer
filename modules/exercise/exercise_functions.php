<?php
// ===================================================================
// exercise_functions.php
// This is the "Business Logic Layer" for the Exercise Tracker.
// All validation rules and all database queries for exercises are
// kept here so the presentation pages (list, add, edit...) stay clean.
// ===================================================================

// List of activity types allowed in the dropdown.
// Kept in one place so add and edit pages both use the same list.
$activity_types = array(
    "Jogging", "Walking", "Cycling", "Swimming",
    "Gym", "Yoga", "Football", "Badminton"
);

// Activity icons mapping
$activity_icons = array(
    "Jogging"   => "🏃",
    "Walking"   => "🚶",
    "Cycling"   => "🚴",
    "Swimming"  => "🏊",
    "Gym"       => "🏋️",
    "Yoga"      => "🧘",
    "Football"  => "⚽",
    "Badminton" => "🏸"
);


// -------------------------------------------------------------
// columnExists()
// Checks whether a column exists in a MySQL table. Used to guard
// against fatal errors when the DB schema hasn't been updated yet.
// -------------------------------------------------------------
function columnExists($conn, $table, $column) {
    static $cache = array();
    $key = $table . '.' . $column;
    if (isset($cache[$key])) return $cache[$key];

    $sql = "SELECT COUNT(*) AS cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { $cache[$key] = false; return false; }
    mysqli_stmt_bind_param($stmt, "ss", $table, $column);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    $cache[$key] = (int)$row['cnt'] > 0;
    return $cache[$key];
}


// -------------------------------------------------------------
// tableExists()
// Checks whether a table exists in the current database.
// Used to guard against fatal errors when schema is incomplete.
// -------------------------------------------------------------
function tableExists($conn, $table) {
    static $tcache = array();
    if (isset($tcache[$table])) return $tcache[$table];

    $sql = "SELECT COUNT(*) AS cnt
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { $tcache[$table] = false; return false; }
    mysqli_stmt_bind_param($stmt, "s", $table);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    $tcache[$table] = (int)$row['cnt'] > 0;
    return $tcache[$table];
}


// -------------------------------------------------------------
// validateExerciseInput()
// Checks the fields submitted from the Add/Edit form.
// Returns an array of error messages. Empty array = no errors.
// -------------------------------------------------------------
function validateExerciseInput($activity_type, $duration, $calories_burned, $exercise_date) {
    $errors = array();

    if (trim($activity_type) == "") {
        $errors[] = "Please select an activity type.";
    }

    if ($duration === "" || !is_numeric($duration) || $duration <= 0) {
        $errors[] = "Duration must be a positive number.";
    }

    if ($calories_burned === "" || !is_numeric($calories_burned) || $calories_burned < 0) {
        $errors[] = "Calories burned cannot be negative.";
    }

    // Basic date format check: must match YYYY-MM-DD and be a real date
    $date_parts = explode("-", $exercise_date);
    if (count($date_parts) != 3 || !checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
        $errors[] = "Please enter a valid exercise date.";
    }

    return $errors;
}


// -------------------------------------------------------------
// addExerciseRecord()
// Inserts a new exercise row for the given user.
// Uses a prepared statement to stop SQL Injection.
// -------------------------------------------------------------
function addExerciseRecord($conn, $user_id, $activity_type, $duration, $calories_burned, $exercise_date, $notes) {

    $sql = "INSERT INTO exercise (user_id, activity_type, duration, calories_burned, exercise_date, notes)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "isiiss", $user_id, $activity_type, $duration, $calories_burned, $exercise_date, $notes);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// getExercisesForUser()
// Returns all exercise rows that belong to one user only.
// Supports simple search, date filter and sorting.
// -------------------------------------------------------------
function getExercisesForUser($conn, $user_id, $search = "", $filter_date = "", $sort = "newest") {

    // Step 1: get every exercise that belongs to this user
    $sql = "SELECT * FROM exercise WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $exercises = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $exercises[] = $row;
    }
    mysqli_stmt_close($stmt);

    // Step 2: apply search (by activity type and notes) in PHP
    $search = trim($search);
    if ($search != "") {
        $filtered = array();
        foreach ($exercises as $row) {
            // stripos = case-insensitive "contains" check
            if (stripos($row["activity_type"], $search) !== false ||
                stripos($row["notes"], $search) !== false) {
                $filtered[] = $row;
            }
        }
        $exercises = $filtered;
    }

    // Step 3: apply date filter in PHP
    $filter_date = trim($filter_date);
    if ($filter_date != "") {
        $filtered = array();
        foreach ($exercises as $row) {
            if ($row["exercise_date"] == $filter_date) {
                $filtered[] = $row;
            }
        }
        $exercises = $filtered;
    }

    // Step 4: sort the results depending on what the user picked
    if ($sort == "oldest") {
        usort($exercises, function($a, $b) {
            return strcmp($a["exercise_date"], $b["exercise_date"]);
        });
    } elseif ($sort == "calories") {
        usort($exercises, function($a, $b) {
            return $b["calories_burned"] - $a["calories_burned"];
        });
    } elseif ($sort == "duration") {
        usort($exercises, function($a, $b) {
            return $b["duration"] - $a["duration"];
        });
    } elseif ($sort == "activity") {
        usort($exercises, function($a, $b) {
            return strcmp($a["activity_type"], $b["activity_type"]);
        });
    } else {
        // default = newest first
        usort($exercises, function($a, $b) {
            return strcmp($b["exercise_date"], $a["exercise_date"]);
        });
    }

    return $exercises;
}


// -------------------------------------------------------------
// getExerciseById()
// Gets ONE exercise row, but only if it belongs to the given user.
// This stops one student from viewing another student's record.
// -------------------------------------------------------------
function getExerciseById($conn, $exercise_id, $user_id) {

    $sql = "SELECT * FROM exercise WHERE exercise_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $exercise_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exercise = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $exercise; // returns null if not found (or not owned by this user)
}


// -------------------------------------------------------------
// updateExerciseRecord()
// Updates an existing exercise, only if it belongs to the user.
// -------------------------------------------------------------
function updateExerciseRecord($conn, $exercise_id, $user_id, $activity_type, $duration, $calories_burned, $exercise_date, $notes) {

    $sql = "UPDATE exercise
            SET activity_type = ?, duration = ?, calories_burned = ?, exercise_date = ?, notes = ?
            WHERE exercise_id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "siissii", $activity_type, $duration, $calories_burned, $exercise_date, $notes, $exercise_id, $user_id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// deleteExerciseRecord()
// Deletes an exercise row, only if it belongs to the user.
// -------------------------------------------------------------
function deleteExerciseRecord($conn, $exercise_id, $user_id) {

    $sql = "DELETE FROM exercise WHERE exercise_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $exercise_id, $user_id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// getExerciseStats()
// Calculates the numbers shown in the statistics section.
// -------------------------------------------------------------
function getExerciseStats($conn, $user_id) {

    $stats = array(
        "total_workouts"    => 0,
        "total_calories"    => 0,
        "total_duration"    => 0,
        "most_frequent"     => "N/A",
        "monthly_count"     => 0
    );

    // Total workouts, total calories, total duration
    $sql = "SELECT COUNT(*) AS total_workouts, SUM(calories_burned) AS total_calories, SUM(duration) AS total_duration
            FROM exercise WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        $stats["total_workouts"] = (int)$row["total_workouts"];
        $stats["total_calories"] = $row["total_calories"] ? (int)$row["total_calories"] : 0;
        $stats["total_duration"] = $row["total_duration"] ? (int)$row["total_duration"] : 0;
    }

    // Most frequent activity type
    $sql = "SELECT activity_type, COUNT(*) AS how_many
            FROM exercise WHERE user_id = ?
            GROUP BY activity_type
            ORDER BY how_many DESC
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        $stats["most_frequent"] = $row["activity_type"];
    }

    // Exercises logged in the current month
    $sql = "SELECT COUNT(*) AS monthly_count
            FROM exercise
            WHERE user_id = ? AND MONTH(exercise_date) = MONTH(CURDATE()) AND YEAR(exercise_date) = YEAR(CURDATE())";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        $stats["monthly_count"] = (int)$row["monthly_count"];
    }

    return $stats;
}


// =============================================================
//  NEW DASHBOARD FUNCTIONS
// =============================================================

// -------------------------------------------------------------
// getDashboardStats()
// Comprehensive all-time statistics for the dashboard
// -------------------------------------------------------------
function getDashboardStats($conn, $user_id) {
    $stats = array(
        "total_workouts"     => 0,
        "total_calories"     => 0,
        "total_duration"     => 0,
        "avg_duration"       => 0,
        "avg_calories"       => 0,
        "most_frequent"      => "N/A",
        "longest_workout"    => 0,
        "shortest_workout"   => 0,
        "avg_workouts_week"  => 0,
        "most_active_month"  => "N/A",
    );

    // Basic aggregates
    $sql = "SELECT COUNT(*) AS total, 
                   COALESCE(SUM(calories_burned), 0) AS total_cal,
                   COALESCE(SUM(duration), 0) AS total_dur,
                   COALESCE(AVG(duration), 0) AS avg_dur,
                   COALESCE(AVG(calories_burned), 0) AS avg_cal,
                   COALESCE(MAX(duration), 0) AS max_dur,
                   COALESCE(MIN(duration), 0) AS min_dur
            FROM exercise WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row && $row["total"] > 0) {
        $stats["total_workouts"]   = (int)$row["total"];
        $stats["total_calories"]   = (int)$row["total_cal"];
        $stats["total_duration"]   = (int)$row["total_dur"];
        $stats["avg_duration"]     = round((float)$row["avg_dur"], 1);
        $stats["avg_calories"]     = round((float)$row["avg_cal"], 1);
        $stats["longest_workout"]  = (int)$row["max_dur"];
        $stats["shortest_workout"] = (int)$row["min_dur"];

        // Average workouts per week
        $sql2 = "SELECT DATEDIFF(MAX(exercise_date), MIN(exercise_date)) AS span FROM exercise WHERE user_id = ?";
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, "i", $user_id);
        mysqli_stmt_execute($stmt2);
        $res2 = mysqli_stmt_get_result($stmt2);
        $r2 = mysqli_fetch_assoc($res2);
        mysqli_stmt_close($stmt2);
        $span = $r2 && $r2["span"] ? max(1, (int)$r2["span"]) : 1;
        $weeks = max(1, $span / 7);
        $stats["avg_workouts_week"] = round($stats["total_workouts"] / $weeks, 1);
    }

    // Most frequent activity
    $sql = "SELECT activity_type, COUNT(*) AS cnt FROM exercise WHERE user_id = ? GROUP BY activity_type ORDER BY cnt DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($row) $stats["most_frequent"] = $row["activity_type"];

    // Most active month
    $sql = "SELECT DATE_FORMAT(exercise_date, '%M %Y') AS month_name, COUNT(*) AS cnt
            FROM exercise WHERE user_id = ?
            GROUP BY YEAR(exercise_date), MONTH(exercise_date)
            ORDER BY cnt DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($row) $stats["most_active_month"] = $row["month_name"];

    return $stats;
}


// -------------------------------------------------------------
// getTodayStats()
// Today's exercise summary
// -------------------------------------------------------------
function getTodayStats($conn, $user_id) {
    $stats = array(
        "workout_count" => 0,
        "total_minutes" => 0,
        "total_calories" => 0,
        "most_frequent" => "N/A"
    );

    $sql = "SELECT COUNT(*) AS cnt, COALESCE(SUM(duration), 0) AS dur, COALESCE(SUM(calories_burned), 0) AS cal
            FROM exercise WHERE user_id = ? AND exercise_date = CURDATE()";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        $stats["workout_count"] = (int)$row["cnt"];
        $stats["total_minutes"] = (int)$row["dur"];
        $stats["total_calories"] = (int)$row["cal"];
    }

    $sql = "SELECT activity_type, COUNT(*) AS cnt FROM exercise WHERE user_id = ? AND exercise_date = CURDATE()
            GROUP BY activity_type ORDER BY cnt DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($row) $stats["most_frequent"] = $row["activity_type"];

    return $stats;
}


// -------------------------------------------------------------
// getWeeklyCalories()
// Returns calories for each day Mon-Sun of the current week
// -------------------------------------------------------------
function getWeeklyCalories($conn, $user_id) {
    $days = array("Mon" => 0, "Tue" => 0, "Wed" => 0, "Thu" => 0, "Fri" => 0, "Sat" => 0, "Sun" => 0);

    // Get start of week (Monday)
    $monday = date('Y-m-d', strtotime('monday this week'));
    $sunday = date('Y-m-d', strtotime('sunday this week'));

    $sql = "SELECT exercise_date, SUM(calories_burned) AS cal
            FROM exercise 
            WHERE user_id = ? AND exercise_date BETWEEN ? AND ?
            GROUP BY exercise_date";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $monday, $sunday);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $dayName = date('D', strtotime($row['exercise_date']));
        if (isset($days[$dayName])) {
            $days[$dayName] = (int)$row['cal'];
        }
    }
    mysqli_stmt_close($stmt);

    return $days;
}


// -------------------------------------------------------------
// getMonthlyTrend()
// Daily calories for the last 30 days
// -------------------------------------------------------------
function getMonthlyTrend($conn, $user_id) {
    $data = array();
    $startDate = date('Y-m-d', strtotime('-30 days'));

    $sql = "SELECT exercise_date, SUM(calories_burned) AS cal
            FROM exercise
            WHERE user_id = ? AND exercise_date >= ?
            GROUP BY exercise_date
            ORDER BY exercise_date ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $startDate);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = array(
            "date"     => date('M d', strtotime($row['exercise_date'])),
            "calories" => (int)$row['cal']
        );
    }
    mysqli_stmt_close($stmt);
    return $data;
}


// -------------------------------------------------------------
// getExerciseDistribution()
// Activity type frequency counts
// -------------------------------------------------------------
function getExerciseDistribution($conn, $user_id) {
    $data = array();

    $sql = "SELECT activity_type, COUNT(*) AS cnt
            FROM exercise WHERE user_id = ?
            GROUP BY activity_type
            ORDER BY cnt DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = array(
            "type"  => $row['activity_type'],
            "count" => (int)$row['cnt']
        );
    }
    mysqli_stmt_close($stmt);
    return $data;
}


// -------------------------------------------------------------
// getDurationTrend()
// Duration data points over the last 30 days
// -------------------------------------------------------------
function getDurationTrend($conn, $user_id) {
    $data = array();
    $startDate = date('Y-m-d', strtotime('-30 days'));

    $sql = "SELECT exercise_date, SUM(duration) AS dur
            FROM exercise
            WHERE user_id = ? AND exercise_date >= ?
            GROUP BY exercise_date
            ORDER BY exercise_date ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $startDate);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = array(
            "date"     => date('M d', strtotime($row['exercise_date'])),
            "duration" => (int)$row['dur']
        );
    }
    mysqli_stmt_close($stmt);
    return $data;
}


// -------------------------------------------------------------
// getCaloriesTrend()
// Same as monthly trend but explicitly named for the area chart
// -------------------------------------------------------------
function getCaloriesTrend($conn, $user_id) {
    return getMonthlyTrend($conn, $user_id);
}


// -------------------------------------------------------------
// getPersonalBests()
// Highest calories, longest workout, most active day, etc.
// -------------------------------------------------------------
function getPersonalBests($conn, $user_id) {
    $bests = array(
        "highest_calories" => 0,
        "longest_workout"  => 0,
        "most_active_day"  => "N/A",
        "most_frequent"    => "N/A"
    );

    // Highest calories in one session
    $sql = "SELECT MAX(calories_burned) AS max_cal FROM exercise WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($row && $row['max_cal']) $bests["highest_calories"] = (int)$row['max_cal'];

    // Longest workout
    $sql = "SELECT MAX(duration) AS max_dur FROM exercise WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($row && $row['max_dur']) $bests["longest_workout"] = (int)$row['max_dur'];

    // Most active day (by total calories)
    $sql = "SELECT exercise_date, SUM(calories_burned) AS total
            FROM exercise WHERE user_id = ?
            GROUP BY exercise_date ORDER BY total DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($row) $bests["most_active_day"] = date('M d, Y', strtotime($row['exercise_date']));

    // Most frequent exercise
    $sql = "SELECT activity_type, COUNT(*) AS cnt FROM exercise WHERE user_id = ?
            GROUP BY activity_type ORDER BY cnt DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($row) $bests["most_frequent"] = $row['activity_type'];

    return $bests;
}


// -------------------------------------------------------------
// getExerciseStreak()
// Calculates consecutive exercise days ending at today
// -------------------------------------------------------------
function getExerciseStreak($conn, $user_id) {
    $sql = "SELECT DISTINCT exercise_date FROM exercise WHERE user_id = ? ORDER BY exercise_date DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $dates = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $dates[] = $row['exercise_date'];
    }
    mysqli_stmt_close($stmt);

    if (count($dates) == 0) return 0;

    $streak = 0;
    $checkDate = date('Y-m-d'); // start from today

    // If today has no exercise, check from yesterday
    if (!in_array($checkDate, $dates)) {
        $checkDate = date('Y-m-d', strtotime('-1 day'));
    }

    foreach ($dates as $d) {
        if ($d == $checkDate) {
            $streak++;
            $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
        } elseif ($d < $checkDate) {
            break;
        }
    }

    return $streak;
}


// -------------------------------------------------------------
// getLongestStreak()
// Calculates the longest-ever consecutive exercise days
// -------------------------------------------------------------
function getLongestStreak($conn, $user_id) {
    $sql = "SELECT DISTINCT exercise_date FROM exercise WHERE user_id = ? ORDER BY exercise_date ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $dates = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $dates[] = $row['exercise_date'];
    }
    mysqli_stmt_close($stmt);

    if (count($dates) == 0) return 0;

    $maxStreak = 1;
    $currentStreak = 1;
    for ($i = 1; $i < count($dates); $i++) {
        $prev = strtotime($dates[$i - 1]);
        $curr = strtotime($dates[$i]);
        if (($curr - $prev) == 86400) { // exactly 1 day
            $currentStreak++;
            if ($currentStreak > $maxStreak) $maxStreak = $currentStreak;
        } else {
            $currentStreak = 1;
        }
    }

    return $maxStreak;
}


// -------------------------------------------------------------
// getHeatmapData()
// Workout counts per day for last 90 days
// -------------------------------------------------------------
function getHeatmapData($conn, $user_id) {
    $data = array();
    $startDate = date('Y-m-d', strtotime('-90 days'));

    $sql = "SELECT exercise_date, COUNT(*) AS cnt
            FROM exercise
            WHERE user_id = ? AND exercise_date >= ?
            GROUP BY exercise_date";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $startDate);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['exercise_date']] = (int)$row['cnt'];
    }
    mysqli_stmt_close($stmt);
    return $data;
}


// -------------------------------------------------------------
// getRecentTimeline()
// Last 10 exercises with relative date labels
// -------------------------------------------------------------
function getRecentTimeline($conn, $user_id) {
    $sql = "SELECT * FROM exercise WHERE user_id = ? ORDER BY exercise_date DESC, created_at DESC LIMIT 10";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $timeline = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $row['relative_date'] = getRelativeDate($row['exercise_date']);
        $timeline[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $timeline;
}

function getRelativeDate($date) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($date == $today) return "Today";
    if ($date == $yesterday) return "Yesterday";

    $diff = (strtotime($today) - strtotime($date)) / 86400;
    if ($diff <= 7) return date('l', strtotime($date)); // day name like "Monday"
    return date('M d', strtotime($date));
}


// -------------------------------------------------------------
// getFitnessProfile()
// Get or create fitness_profile row for user
// -------------------------------------------------------------
function getFitnessProfile($conn, $user_id) {
    $sql = "SELECT * FROM fitness_profile WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $profile = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Auto-create if not exists
    if (!$profile) {
        $sql = "INSERT INTO fitness_profile (user_id) VALUES (?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Fetch again
        return getFitnessProfile($conn, $user_id);
    }

    // ------------------------------------------------------------------
    // Ensure the optional columns added by the new schema exist in the
    // row. If the database table was not yet re-imported after the
    // redesign, these keys may be missing — fall back to safe defaults.
    // ------------------------------------------------------------------
    if (!isset($profile['steps_date']))    $profile['steps_date']    = null;
    if (!isset($profile['water_intake_ml'])) $profile['water_intake_ml'] = 0;
    if (!isset($profile['current_steps'])) $profile['current_steps'] = 0;
    if (!isset($profile['sleep_hours']))   $profile['sleep_hours']   = 0;
    if (!isset($profile['daily_step_goal']))    $profile['daily_step_goal']    = 10000;
    if (!isset($profile['daily_calorie_goal'])) $profile['daily_calorie_goal'] = 500;
    if (!isset($profile['weight_kg']))   $profile['weight_kg']   = 0;
    if (!isset($profile['height_cm']))   $profile['height_cm']   = 0;

    // Try to reset daily steps/water if the stored date differs from today.
    // Only attempt if both new columns are actually present in the table.
    $stepsDateColumn    = columnExists($conn, 'fitness_profile', 'steps_date');
    $waterColumn        = columnExists($conn, 'fitness_profile', 'water_intake_ml');

    if ($stepsDateColumn && $waterColumn && $profile['steps_date'] != date('Y-m-d')) {
        $today = date('Y-m-d');
        $sql = "UPDATE fitness_profile SET current_steps = 0, water_intake_ml = 0, steps_date = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $today, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $profile['current_steps']   = 0;
        $profile['water_intake_ml'] = 0;
        $profile['steps_date']      = $today;
    }

    return $profile;
}


// -------------------------------------------------------------
// saveFitnessProfile()
// Update a single field in the fitness profile
// -------------------------------------------------------------
function saveFitnessProfile($conn, $user_id, $field, $value) {
    // Whitelist of allowed fields
    $allowed = array('height_cm', 'weight_kg', 'daily_calorie_goal', 'daily_step_goal',
                     'current_steps', 'water_intake_ml', 'sleep_hours');

    if (!in_array($field, $allowed)) {
        return false;
    }

    // Guard: check the column actually exists in the table before updating
    if (!columnExists($conn, 'fitness_profile', $field)) {
        return false;
    }

    // Ensure profile exists
    getFitnessProfile($conn, $user_id);

    // Update the field. Only include steps_date if that column exists.
    $today = date('Y-m-d');
    if (columnExists($conn, 'fitness_profile', 'steps_date')) {
        $sql = "UPDATE fitness_profile SET $field = ?, steps_date = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "dsi", $value, $today, $user_id);
    } else {
        $sql = "UPDATE fitness_profile SET $field = ?, updated_at = NOW() WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "di", $value, $user_id);
    }
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}


// -------------------------------------------------------------
// calculateBMI()
// weight(kg) / height(m)²
// Returns array with value, category, color class
// -------------------------------------------------------------
function calculateBMI($weight_kg, $height_cm) {
    if ($height_cm <= 0 || $weight_kg <= 0) {
        return array("value" => 0, "category" => "N/A", "class" => "");
    }

    $height_m = $height_cm / 100;
    $bmi = $weight_kg / ($height_m * $height_m);
    $bmi = round($bmi, 1);

    if ($bmi < 18.5) {
        $category = "Underweight";
        $class = "bmi-underweight";
    } elseif ($bmi < 25) {
        $category = "Normal";
        $class = "bmi-normal";
    } elseif ($bmi < 30) {
        $category = "Overweight";
        $class = "bmi-overweight";
    } else {
        $category = "Obese";
        $class = "bmi-obese";
    }

    return array("value" => $bmi, "category" => $category, "class" => $class);
}


// -------------------------------------------------------------
// checkAchievements()
// Check and award badges
// -------------------------------------------------------------
function checkAchievements($conn, $user_id) {
    // Silently skip if the achievements table hasn't been created yet
    if (!tableExists($conn, 'achievements')) return;

    $badges = array();
    $stats  = getDashboardStats($conn, $user_id);
    $streak = getExerciseStreak($conn, $user_id);

    // First Workout
    if ($stats['total_workouts'] >= 1) {
        $badges[] = array('name' => 'First Workout', 'icon' => '🎯', 'desc' => 'Logged your very first exercise!');
    }

    // 7-Day Streak
    if ($streak >= 7) {
        $badges[] = array('name' => '7-Day Streak', 'icon' => '🔥', 'desc' => 'Exercised 7 days in a row!');
    }

    // 1000 Calories
    if ($stats['total_calories'] >= 1000) {
        $badges[] = array('name' => '1000 Calories', 'icon' => '💪', 'desc' => 'Burned over 1000 total calories!');
    }

    // 10 Workouts
    if ($stats['total_workouts'] >= 10) {
        $badges[] = array('name' => '10 Workouts', 'icon' => '🏅', 'desc' => 'Completed 10 workouts!');
    }

    // 5000 Calories
    if ($stats['total_calories'] >= 5000) {
        $badges[] = array('name' => '5000 Calories', 'icon' => '🔥', 'desc' => 'Burned over 5000 total calories!');
    }

    // Marathon Minutes (1000 min)
    if ($stats['total_duration'] >= 1000) {
        $badges[] = array('name' => 'Marathon Minutes', 'icon' => '⏱️', 'desc' => 'Over 1000 minutes of exercise!');
    }

    // Save new badges
    foreach ($badges as $b) {
        $sql = "INSERT IGNORE INTO achievements (user_id, badge_name, badge_icon, description) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $b['name'], $b['icon'], $b['desc']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}


// -------------------------------------------------------------
// getAchievements()
// Get all earned badges for user
// -------------------------------------------------------------
function getAchievements($conn, $user_id) {
    // Return empty array if table doesn't exist yet
    if (!tableExists($conn, 'achievements')) return array();

    $sql = "SELECT * FROM achievements WHERE user_id = ? ORDER BY earned_at ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $badges = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $badges[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $badges;
}


// -------------------------------------------------------------
// getMotivationalQuote()
// Returns a random motivational fitness quote
// -------------------------------------------------------------
function getMotivationalQuote() {
    $quotes = array(
        "The only bad workout is the one that didn't happen. 💪",
        "Your body can stand almost anything. It's your mind you have to convince. 🧠",
        "Fitness is not about being better than someone else. It's about being better than you used to be. 🌟",
        "The pain you feel today will be the strength you feel tomorrow. 🔥",
        "Don't stop when you're tired. Stop when you're done. 🏁",
        "Success isn't always about greatness. It's about consistency. 📈",
        "Take care of your body. It's the only place you have to live. 🏠",
        "Exercise is a celebration of what your body can do, not a punishment for what you ate. 🎉",
        "The only way to do great work is to love what you do. ❤️",
        "Small daily improvements are the key to staggering long-term results. 📊",
        "Believe you can, and you're halfway there. ✨",
        "Sweat is just fat crying. 💧",
        "Your health is an investment, not an expense. 💎",
        "A one-hour workout is 4% of your day. No excuses. ⏰",
        "Dream big. Start small. Act now. 🚀"
    );

    return $quotes[array_rand($quotes)];
}


// -------------------------------------------------------------
// All possible badges (for locked/unlocked display)
// -------------------------------------------------------------
function getAllPossibleBadges() {
    return array(
        array('name' => 'First Workout',    'icon' => '🎯', 'desc' => 'Log your very first exercise'),
        array('name' => '7-Day Streak',     'icon' => '🔥', 'desc' => 'Exercise 7 days in a row'),
        array('name' => '1000 Calories',    'icon' => '💪', 'desc' => 'Burn over 1000 total calories'),
        array('name' => '10 Workouts',      'icon' => '🏅', 'desc' => 'Complete 10 workouts'),
        array('name' => '5000 Calories',    'icon' => '🔥', 'desc' => 'Burn over 5000 total calories'),
        array('name' => 'Marathon Minutes', 'icon' => '⏱️', 'desc' => 'Over 1000 minutes of exercise'),
    );
}
?>
