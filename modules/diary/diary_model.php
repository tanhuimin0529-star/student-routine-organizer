<?php
// ===================================================================
// diary_model.php
// Data access layer for Diary Journal entries.
//
// Callers must pass the logged-in user ID obtained from the shared
// session system. No function accepts or derives an owner from form data.
// ===================================================================

/**
 * Record a technical database failure in the private PHP error log.
 *
 * Callers receive only each model function's documented false result.
 */
function diaryModelLogDatabaseException($operation, $exception) {
    $log_message = str_replace(array("\r", "\n"), " ", $exception->getMessage());

    error_log(
        "[Diary database] "
        . $operation
        . " failed (mysqli error "
        . (int) $exception->getCode()
        . "): "
        . $log_message
    );
}

/**
 * Return all diary entries owned by one user.
 *
 * @return array|false Array of entries (possibly empty), or false on a
 *                     database/statement failure.
 */
function getDiaryEntriesForUser($conn, $user_id) {
    $sql = "SELECT diary_id, user_id, title, content, mood, weather, entry_date, is_favorite, created_at, updated_at
            FROM diary_entries
            WHERE user_id = ?
            ORDER BY entry_date DESC, created_at DESC";
    $stmt = null;

    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            return false;
        }

        $entries = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $entries[] = $row;
        }

        return $entries;
    } catch (mysqli_sql_exception $exception) {
        diaryModelLogDatabaseException("get diary entries", $exception);
        return false;
    } finally {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Return one diary entry only when it belongs to the given user.
 *
 * @return array|null|false Entry row, null when not found/not owned, or
 *                          false on a database/statement failure.
 */
function getDiaryEntryById($conn, $diary_id, $user_id) {
    $sql = "SELECT diary_id, user_id, title, content, mood, weather, entry_date, is_favorite, created_at, updated_at
            FROM diary_entries
            WHERE diary_id = ? AND user_id = ?
            LIMIT 1";
    $stmt = null;

    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ii", $diary_id, $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            return false;
        }

        $entry = mysqli_fetch_assoc($result);
        return $entry ? $entry : null;
    } catch (mysqli_sql_exception $exception) {
        diaryModelLogDatabaseException("get diary entry", $exception);
        return false;
    } finally {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Return one monthly reflection only when it belongs to the given user.
 *
 * The reflection month is stored as the first day of the month (YYYY-MM-01).
 *
 * @return array|null|false Reflection row, null when not found, or false on a
 *                          database/statement failure.
 */
function getDiaryMonthlyReflection($conn, $user_id, $reflection_month) {
    $sql = "SELECT reflection_id, user_id, reflection_month, content, created_at, updated_at
            FROM diary_monthly_reflections
            WHERE user_id = ? AND reflection_month = ?
            LIMIT 1";
    $stmt = null;

    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "is", $user_id, $reflection_month);
        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            return false;
        }

        $reflection = mysqli_fetch_assoc($result);
        return $reflection ? $reflection : null;
    } catch (mysqli_sql_exception $exception) {
        diaryModelLogDatabaseException("get monthly reflection", $exception);
        return false;
    } finally {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Create or update one monthly reflection for the given user and month.
 *
 * The unique (user_id, reflection_month) key makes the upsert atomic and
 * prevents duplicate reflections for the same user and month.
 *
 * @return bool True when the statement succeeds, otherwise false.
 */
function saveDiaryMonthlyReflection($conn, $user_id, $reflection_month, $content) {
    $sql = "INSERT INTO diary_monthly_reflections (user_id, reflection_month, content)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE content = VALUES(content)";
    $stmt = null;

    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "iss", $user_id, $reflection_month, $content);
        return mysqli_stmt_execute($stmt);
    } catch (mysqli_sql_exception $exception) {
        diaryModelLogDatabaseException("save monthly reflection", $exception);
        return false;
    } finally {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Create a diary entry for the given logged-in user.
 *
 * @return int|false New diary ID on success, or false on failure.
 */
function createDiaryEntry($conn, $user_id, $title, $content, $mood, $weather, $entry_date) {
    $sql = "INSERT INTO diary_entries (user_id, title, content, mood, weather, entry_date)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = null;
    $weather_value = $weather === '' ? null : $weather;

    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "isssss", $user_id, $title, $content, $mood, $weather_value, $entry_date);
        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        return mysqli_insert_id($conn);
    } catch (mysqli_sql_exception $exception) {
        diaryModelLogDatabaseException("create diary entry", $exception);
        return false;
    } finally {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Update a diary entry only when it belongs to the given user.
 *
 * @return int|false Number of affected rows, or false on failure. A result
 *                   of 0 means no row changed (not found/not owned, or the
 *                   submitted values matched the stored values).
 */
function updateDiaryEntry($conn, $diary_id, $user_id, $title, $content, $mood, $weather, $entry_date) {
    $sql = "UPDATE diary_entries
            SET title = ?, content = ?, mood = ?, weather = ?, entry_date = ?
            WHERE diary_id = ? AND user_id = ?";
    $stmt = null;
    $weather_value = $weather === '' ? null : $weather;

    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "sssssii", $title, $content, $mood, $weather_value, $entry_date, $diary_id, $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        return mysqli_stmt_affected_rows($stmt);
    } catch (mysqli_sql_exception $exception) {
        diaryModelLogDatabaseException("update diary entry", $exception);
        return false;
    } finally {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Set the favorite state of a diary entry owned by the given user.
 *
 * The updated_at assignment prevents a favorite-only change from being
 * treated as an edit to the journal entry.
 *
 * @return int|false Number of affected rows (0 or 1), or false when the
 *                   favorite value is invalid or the statement fails.
 */
function setDiaryEntryFavorite($conn, $diary_id, $user_id, $favorite) {
    if ($favorite !== 0 && $favorite !== 1) {
        return false;
    }

    $sql = "UPDATE diary_entries
            SET is_favorite = ?, updated_at = updated_at
            WHERE diary_id = ? AND user_id = ?";
    $stmt = null;

    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "iii", $favorite, $diary_id, $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        return mysqli_stmt_affected_rows($stmt);
    } catch (mysqli_sql_exception $exception) {
        diaryModelLogDatabaseException("set diary favorite", $exception);
        return false;
    } finally {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Delete a diary entry only when it belongs to the given user.
 *
 * @return int|false Number of deleted rows (0 or 1), or false on failure.
 */
function deleteDiaryEntry($conn, $diary_id, $user_id) {
    $sql = "DELETE FROM diary_entries
            WHERE diary_id = ? AND user_id = ?";
    $stmt = null;

    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ii", $diary_id, $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            return false;
        }

        return mysqli_stmt_affected_rows($stmt);
    } catch (mysqli_sql_exception $exception) {
        diaryModelLogDatabaseException("delete diary entry", $exception);
        return false;
    } finally {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}
?>
