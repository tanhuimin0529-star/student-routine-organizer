<?php
/**
 * Habit Garden data and business rules.
 * All SQL used by the Habit module lives in this file.
 */

function habit_get_categories(mysqli $conn): array
{
    $result = mysqli_query(
        $conn,
        'SELECT category_id, category_name, category_icon FROM habit_categories ORDER BY category_name ASC'
    );

    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

function habit_category_exists(mysqli $conn, int $categoryId): bool
{
    $stmt = mysqli_prepare($conn, 'SELECT 1 FROM habit_categories WHERE category_id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $categoryId);
    mysqli_stmt_execute($stmt);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 1;
    mysqli_stmt_close($stmt);

    return $exists;
}

function habit_get_habits_by_user(
    mysqli $conn,
    int $userId,
    string $keyword = '',
    string $status = 'All',
    string $sort = 'newest'
): array {
    $allowedStatuses = ['All', 'Active', 'Paused', 'Completed', 'Archived'];
    $status = in_array($status, $allowedStatuses, true) ? $status : 'All';
    $orderSql = match ($sort) {
        'oldest' => 'h.created_at ASC, h.habit_id ASC',
        'az' => 'h.habit_name ASC, h.habit_id DESC',
        default => 'h.created_at DESC, h.habit_id DESC',
    };

    $sql = "SELECT
                h.habit_id,
                h.user_id,
                h.category_id,
                h.habit_name,
                h.habit_description,
                h.target_frequency,
                h.frequency_type,
                h.start_date,
                h.status,
                h.created_at,
                c.category_name,
                c.category_icon,
                EXISTS (
                    SELECT 1
                    FROM habit_logs hl
                    WHERE hl.habit_id = h.habit_id
                      AND hl.log_date = CURDATE()
                      AND hl.completed = 1
                ) AS completed_today
            FROM habits h
            LEFT JOIN habit_categories c ON c.category_id = h.category_id
            WHERE h.user_id = ?";

    $term = '%' . $keyword . '%';
    if ($keyword !== '' && $status !== 'All') {
        $sql .= ' AND (h.habit_name LIKE ? OR COALESCE(h.habit_description, \'\') LIKE ?) AND h.status = ?';
        $stmt = mysqli_prepare($conn, $sql . ' ORDER BY ' . $orderSql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'isss', $userId, $term, $term, $status);
    } elseif ($keyword !== '') {
        $sql .= ' AND (h.habit_name LIKE ? OR COALESCE(h.habit_description, \'\') LIKE ?)';
        $stmt = mysqli_prepare($conn, $sql . ' ORDER BY ' . $orderSql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'iss', $userId, $term, $term);
    } elseif ($status !== 'All') {
        $sql .= ' AND h.status = ?';
        $stmt = mysqli_prepare($conn, $sql . ' ORDER BY ' . $orderSql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'is', $userId, $status);
    } else {
        $stmt = mysqli_prepare($conn, $sql . ' ORDER BY ' . $orderSql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'i', $userId);
    }

    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $rows;
}

function habit_get_habit_by_id(mysqli $conn, int $habitId, int $userId): ?array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT h.*, c.category_name, c.category_icon
         FROM habits h
         LEFT JOIN habit_categories c ON c.category_id = h.category_id
         WHERE h.habit_id = ? AND h.user_id = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $habitId, $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function habit_validate_payload(mysqli $conn, array $input, bool $rejectPastStartDate = false): array
{
    $name = trim((string) ($input['habit_name'] ?? ''));
    $description = trim((string) ($input['habit_description'] ?? ''));
    $categoryId = (int) ($input['category_id'] ?? 0);
    $frequency = (int) ($input['target_frequency'] ?? 0);
    $frequencyType = (string) ($input['frequency_type'] ?? '');
    $startDate = (string) ($input['start_date'] ?? '');
    $status = (string) ($input['status'] ?? 'Active');
    $errors = [];

    if ($name === '') {
        $errors['habit_name'] = 'Please enter a habit name.';
    } elseif (mb_strlen($name) > 100) {
        $errors['habit_name'] = 'Habit name must be 100 characters or fewer.';
    }

    if (mb_strlen($description) > 500) {
        $errors['habit_description'] = 'Description must be 500 characters or fewer.';
    }

    if (!habit_category_exists($conn, $categoryId)) {
        $errors['category_id'] = 'Please choose an available category.';
    }

    if ($frequency < 1 || $frequency > 99) {
        $errors['target_frequency'] = 'Target frequency must be between 1 and 99.';
    } elseif ($frequencyType === 'Daily' && $frequency !== 1) {
        $errors['target_frequency'] = 'A daily habit can be completed once per day, so use a target of 1.';
    }

    if (!in_array($frequencyType, ['Daily', 'Weekly', 'Monthly'], true)) {
        $errors['frequency_type'] = 'Please choose Daily, Weekly, or Monthly.';
    }

    $timezone = new DateTimeZone('Asia/Shanghai');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate, $timezone);
    if (!$date || $date->format('Y-m-d') !== $startDate) {
        $errors['start_date'] = 'Please choose a valid start date.';
    } elseif ($rejectPastStartDate && $date < new DateTimeImmutable('today', $timezone)) {
        $errors['start_date'] = 'Start date cannot be earlier than today.';
    }

    if (!in_array($status, ['Active', 'Paused', 'Completed', 'Archived'], true)) {
        $errors['status'] = 'Please choose a valid status.';
    }

    return $errors;
}

function habit_create(mysqli $conn, int $userId, array $input): bool
{
    $name = trim((string) $input['habit_name']);
    $description = trim((string) ($input['habit_description'] ?? ''));
    $categoryId = (int) $input['category_id'];
    $frequency = (int) $input['target_frequency'];
    $frequencyType = (string) $input['frequency_type'];
    $startDate = (string) $input['start_date'];
    $status = (string) ($input['status'] ?? 'Active');

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO habits
         (user_id, category_id, habit_name, habit_description, target_frequency, frequency_type, start_date, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iississs',
        $userId,
        $categoryId,
        $name,
        $description,
        $frequency,
        $frequencyType,
        $startDate,
        $status
    );
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $saved;
}

function habit_update(mysqli $conn, int $habitId, int $userId, array $input): bool
{
    $name = trim((string) $input['habit_name']);
    $description = trim((string) ($input['habit_description'] ?? ''));
    $categoryId = (int) $input['category_id'];
    $frequency = (int) $input['target_frequency'];
    $frequencyType = (string) $input['frequency_type'];
    $startDate = (string) $input['start_date'];
    $status = (string) $input['status'];

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE habits
         SET category_id = ?, habit_name = ?, habit_description = ?, target_frequency = ?,
             frequency_type = ?, start_date = ?, status = ?
         WHERE habit_id = ? AND user_id = ?'
    );
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ississsii',
        $categoryId,
        $name,
        $description,
        $frequency,
        $frequencyType,
        $startDate,
        $status,
        $habitId,
        $userId
    );
    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $saved;
}

function habit_delete(mysqli $conn, int $habitId, int $userId): bool
{
    if (!habit_get_habit_by_id($conn, $habitId, $userId)) {
        return false;
    }

    $stmt = mysqli_prepare($conn, 'DELETE FROM habits WHERE habit_id = ? AND user_id = ?');
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $habitId, $userId);
    mysqli_stmt_execute($stmt);
    $deleted = mysqli_stmt_affected_rows($stmt) === 1;
    mysqli_stmt_close($stmt);

    return $deleted;
}

function habit_is_completed_today(mysqli $conn, int $habitId): bool
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT 1 FROM habit_logs WHERE habit_id = ? AND log_date = CURDATE() AND completed = 1 LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $habitId);
    mysqli_stmt_execute($stmt);
    $completed = mysqli_num_rows(mysqli_stmt_get_result($stmt)) === 1;
    mysqli_stmt_close($stmt);

    return $completed;
}

function habit_check_in(mysqli $conn, int $userId, int $habitId): array
{
    $habit = habit_get_habit_by_id($conn, $habitId, $userId);
    if (!$habit) {
        return ['type' => 'error', 'message' => 'That habit was not found.'];
    }

    if ($habit['status'] !== 'Active') {
        return ['type' => 'error', 'message' => 'Only active habits can be completed.'];
    }

    if (habit_is_completed_today($conn, $habitId)) {
        return ['type' => 'error', 'message' => 'This habit has already been completed today. Come back tomorrow to continue the streak.'];
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO habit_logs (habit_id, log_date, log_time, completed, log_note)
         VALUES (?, CURDATE(), CURTIME(), 1, NULL)'
    );
    if (!$stmt) {
        return ['type' => 'error', 'message' => 'The check-in could not be prepared. Please try again.'];
    }

    mysqli_stmt_bind_param($stmt, 'i', $habitId);
    $saved = mysqli_stmt_execute($stmt);
    $errorNumber = mysqli_stmt_errno($stmt);
    mysqli_stmt_close($stmt);

    if (!$saved) {
        if ($errorNumber === 1062) {
            return ['type' => 'error', 'message' => 'This habit has already been completed today.'];
        }
        return ['type' => 'error', 'message' => 'The check-in could not be saved. Please try again.'];
    }

    $newRewards = habit_unlock_rewards($conn, $userId, $habitId);
    $message = 'Nice work — today’s habit is complete.';
    if ($newRewards) {
        $message .= ' New achievement: ' . implode(', ', array_column($newRewards, 'reward_name')) . '.';
    }

    return ['type' => 'success', 'message' => $message];
}

function habit_get_completed_dates(mysqli $conn, int $userId, ?string $startDate = null, ?string $endDate = null): array
{
    $sql = 'SELECT DISTINCT hl.log_date
            FROM habit_logs hl
            INNER JOIN habits h ON h.habit_id = hl.habit_id
            WHERE h.user_id = ? AND hl.completed = 1';

    if ($startDate !== null && $endDate !== null) {
        $stmt = mysqli_prepare($conn, $sql . ' AND hl.log_date BETWEEN ? AND ? ORDER BY hl.log_date ASC');
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'iss', $userId, $startDate, $endDate);
    } else {
        $stmt = mysqli_prepare($conn, $sql . ' ORDER BY hl.log_date ASC');
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, 'i', $userId);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $dates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dates[] = $row['log_date'];
    }
    mysqli_stmt_close($stmt);

    return $dates;
}

function habit_get_current_streak(mysqli $conn, int $habitId): int
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT DISTINCT log_date
         FROM habit_logs
         WHERE habit_id = ? AND completed = 1
         ORDER BY log_date DESC'
    );
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'i', $habitId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $dates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dates[] = $row['log_date'];
    }
    mysqli_stmt_close($stmt);

    if (!$dates) {
        return 0;
    }

    $today = new DateTimeImmutable('today');
    $latest = new DateTimeImmutable($dates[0]);
    if ((int) $today->diff($latest)->format('%r%a') < -1) {
        return 0;
    }

    $streak = 0;
    $expected = $latest;
    foreach ($dates as $value) {
        $date = new DateTimeImmutable($value);
        if ($date->format('Y-m-d') !== $expected->format('Y-m-d')) {
            break;
        }
        $streak++;
        $expected = $expected->modify('-1 day');
    }

    return $streak;
}

function habit_get_longest_streak(mysqli $conn, int $userId): int
{
    $habits = habit_get_habits_by_user($conn, $userId);
    $best = 0;
    foreach ($habits as $habit) {
        $best = max($best, habit_get_current_streak($conn, (int) $habit['habit_id']));
    }

    return $best;
}

function habit_get_stats(mysqli $conn, int $userId): array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            COUNT(CASE WHEN h.status = 'Active' THEN 1 END) AS active_habits,
            SUM(CASE WHEN h.status = 'Active' AND EXISTS (
                SELECT 1 FROM habit_logs today_log
                WHERE today_log.habit_id = h.habit_id
                  AND today_log.log_date = CURDATE()
                  AND today_log.completed = 1
            ) THEN 1 ELSE 0 END) AS completed_today,
            (SELECT COUNT(*)
             FROM habit_logs all_logs
             INNER JOIN habits all_habits ON all_habits.habit_id = all_logs.habit_id
             WHERE all_habits.user_id = ? AND all_logs.completed = 1) AS total_checkins,
            (SELECT COUNT(DISTINCT day_logs.log_date)
             FROM habit_logs day_logs
             INNER JOIN habits day_habits ON day_habits.habit_id = day_logs.habit_id
             WHERE day_habits.user_id = ? AND day_logs.completed = 1) AS unique_days,
            (SELECT COUNT(DISTINCT week_logs.log_date)
             FROM habit_logs week_logs
             INNER JOIN habits week_habits ON week_habits.habit_id = week_logs.habit_id
             WHERE week_habits.user_id = ?
               AND week_logs.completed = 1
               AND week_logs.log_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)) AS active_week_days
         FROM habits h
         WHERE h.user_id = ?"
    );

    $fallback = [
        'active_habits' => 0,
        'completed_today' => 0,
        'today_progress' => 0,
        'total_checkins' => 0,
        'unique_days' => 0,
        'active_week_days' => 0,
        'weekly_consistency' => 0,
        'longest_streak' => 0,
    ];

    if (!$stmt) {
        return $fallback;
    }

    mysqli_stmt_bind_param($stmt, 'iiii', $userId, $userId, $userId, $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
    mysqli_stmt_close($stmt);

    $active = (int) ($row['active_habits'] ?? 0);
    $completed = (int) ($row['completed_today'] ?? 0);
    $weekDays = (int) ($row['active_week_days'] ?? 0);

    return [
        'active_habits' => $active,
        'completed_today' => $completed,
        'today_progress' => $active > 0 ? (int) round($completed / $active * 100) : 0,
        'total_checkins' => (int) ($row['total_checkins'] ?? 0),
        'unique_days' => (int) ($row['unique_days'] ?? 0),
        'active_week_days' => $weekDays,
        'weekly_consistency' => (int) round($weekDays / 7 * 100),
        'longest_streak' => habit_get_longest_streak($conn, $userId),
    ];
}

function habit_get_reward_types(mysqli $conn): array
{
    $result = mysqli_query(
        $conn,
        "SELECT * FROM badge_types WHERE reward_type = 'Badge' ORDER BY tree_tier ASC, badge_type_id ASC"
    );

    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

function habit_get_user_rewards(mysqli $conn, int $userId): array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT hb.badge_id, hb.habit_id, hb.earned_date, bt.*
         FROM habit_badges hb
         INNER JOIN badge_types bt ON bt.badge_type_id = hb.badge_type_id
         WHERE hb.user_id = ? AND bt.reward_type = 'Badge'
         ORDER BY hb.earned_date DESC, bt.tree_tier DESC"
    );
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $rows;
}

function habit_reward_is_owned(mysqli $conn, int $userId, ?int $habitId, int $badgeTypeId): bool
{
    if ($habitId === null) {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT 1 FROM habit_badges WHERE user_id = ? AND badge_type_id = ? AND habit_id IS NULL LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $badgeTypeId);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT 1 FROM habit_badges WHERE user_id = ? AND habit_id = ? AND badge_type_id = ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'iii', $userId, $habitId, $badgeTypeId);
    }

    mysqli_stmt_execute($stmt);
    $owned = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);

    return $owned;
}

function habit_grant_reward(mysqli $conn, int $userId, ?int $habitId, int $badgeTypeId): bool
{
    if ($habitId === null) {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO habit_badges (user_id, habit_id, badge_type_id, earned_date, is_equipped)
             VALUES (?, NULL, ?, CURDATE(), 0)'
        );
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $badgeTypeId);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO habit_badges (user_id, habit_id, badge_type_id, earned_date, is_equipped)
             VALUES (?, ?, ?, CURDATE(), 0)'
        );
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'iii', $userId, $habitId, $badgeTypeId);
    }

    $saved = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $saved;
}

function habit_unlock_rewards(mysqli $conn, int $userId, int $habitId): array
{
    $stats = habit_get_stats($conn, $userId);
    $habitStreak = habit_get_current_streak($conn, $habitId);
    $newRewards = [];

    foreach (habit_get_reward_types($conn) as $reward) {
        $code = (string) $reward['reward_code'];
        $requirement = (int) $reward['requirement'];
        $relatedHabitId = null;
        $qualified = false;

        if (str_starts_with($code, 'streak_')) {
            $relatedHabitId = $habitId;
            $qualified = $habitStreak >= $requirement;
        } elseif (str_starts_with($code, 'checkins_')) {
            $qualified = $stats['total_checkins'] >= $requirement;
        }

        if (
            $qualified
            && !habit_reward_is_owned($conn, $userId, $relatedHabitId, (int) $reward['badge_type_id'])
            && habit_grant_reward($conn, $userId, $relatedHabitId, (int) $reward['badge_type_id'])
        ) {
            $newRewards[] = $reward;
        }
    }

    return $newRewards;
}

function habit_get_reward_states(mysqli $conn, int $userId): array
{
    $stats = habit_get_stats($conn, $userId);
    $ownedIds = [];
    foreach (habit_get_user_rewards($conn, $userId) as $owned) {
        $ownedIds[(int) $owned['badge_type_id']] = $owned;
    }

    $states = [];
    foreach (habit_get_reward_types($conn) as $reward) {
        $code = (string) $reward['reward_code'];
        $requirement = max(1, (int) $reward['requirement']);
        $metric = str_starts_with($code, 'streak_')
            ? $stats['longest_streak']
            : $stats['total_checkins'];
        $badgeTypeId = (int) $reward['badge_type_id'];
        $unlocked = $metric >= $requirement;
        $states[] = $reward + [
            'progress' => min($metric, $requirement),
            'unlocked' => $unlocked,
            'earned_date' => $unlocked ? ($ownedIds[$badgeTypeId]['earned_date'] ?? null) : null,
        ];
    }

    return $states;
}

function habit_get_garden_state(mysqli $conn, int $userId): array
{
    $stats = habit_get_stats($conn, $userId);
    return habit_garden_state_for_days($stats['unique_days']) + ['stats' => $stats];
}

function habit_garden_state_for_days(int $uniqueDays): array
{
    if ($uniqueDays >= 7) {
        return [
            'key' => 'flourishing',
            'label' => 'Flourishing garden',
            'description' => 'A full week of care has turned your routines into a thriving garden.',
            'asset' => '../../assets/habit/garden-stage-strong.png',
            'hero_asset' => '../../assets/habit/habit-garden-hero.png',
            'progress' => 100,
            'next_target' => null,
            'days' => $uniqueDays,
        ];
    }

    if ($uniqueDays >= 3) {
        return [
            'key' => 'growing',
            'label' => 'Growing garden',
            'description' => 'Your small routines are taking root and beginning to bloom.',
            'asset' => '../../assets/habit/garden-stage-growing.png',
            'hero_asset' => '../../assets/habit/garden-stage-growing-transparent.png',
            'progress' => (int) round($uniqueDays / 7 * 100),
            'next_target' => 7,
            'days' => $uniqueDays,
        ];
    }

    if ($uniqueDays >= 1) {
        return [
            'key' => 'sprout',
            'label' => 'New sprout',
            'description' => 'Your first completed day has planted a hopeful new sprout.',
            'asset' => '../../assets/habit/garden-stage-sprout.png',
            'hero_asset' => '../../assets/habit/garden-stage-sprout-transparent.png',
            'progress' => (int) round($uniqueDays / 3 * 100),
            'next_target' => 3,
            'days' => $uniqueDays,
        ];
    }

    return [
        'key' => 'seed',
        'label' => 'Ready to grow',
        'description' => 'Complete your first habit to water the seed and begin the garden.',
        'asset' => '../../assets/habit/garden-stage-seed.png',
        'hero_asset' => '../../assets/habit/garden-stage-seed-transparent.png',
        'progress' => 0,
        'next_target' => 1,
        'days' => 0,
    ];
}
