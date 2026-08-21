<?php
// ===================================================================
// diary_model.php
// Data access layer for Diary Journal entries.
//
// Callers must pass the logged-in user ID obtained from the shared
// session system. No function accepts or derives an owner from form data.
// ===================================================================

/**
 * Return all diary entries owned by one user.
 *
 * @return array|false Array of entries (possibly empty), or false on a
 *                     database/statement failure.
 */
function getDiaryEntriesForUser($conn, $user_id) {
    $sql = "SELECT diary_id, user_id, title, content, mood, entry_date, created_at, updated_at
            FROM diary_entries
            WHERE user_id = ?
            ORDER BY entry_date DESC, created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $entries = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $entries[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $entries;
}

/**
 * Return one diary entry only when it belongs to the given user.
 *
 * @return array|null|false Entry row, null when not found/not owned, or
 *                          false on a database/statement failure.
 */
function getDiaryEntryById($conn, $diary_id, $user_id) {
    $sql = "SELECT diary_id, user_id, title, content, mood, entry_date, created_at, updated_at
            FROM diary_entries
            WHERE diary_id = ? AND user_id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $diary_id, $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $entry = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $entry ? $entry : null;
}

/**
 * Create a diary entry for the given logged-in user.
 *
 * @return int|false New diary ID on success, or false on failure.
 */
function createDiaryEntry($conn, $user_id, $title, $content, $mood, $entry_date) {
    $sql = "INSERT INTO diary_entries (user_id, title, content, mood, entry_date)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "issss", $user_id, $title, $content, $mood, $entry_date);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $diary_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $diary_id;
}

/**
 * Update a diary entry only when it belongs to the given user.
 *
 * @return int|false Number of affected rows, or false on failure. A result
 *                   of 0 means no row changed (not found/not owned, or the
 *                   submitted values matched the stored values).
 */
function updateDiaryEntry($conn, $diary_id, $user_id, $title, $content, $mood, $entry_date) {
    $sql = "UPDATE diary_entries
            SET title = ?, content = ?, mood = ?, entry_date = ?
            WHERE diary_id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ssssii", $title, $content, $mood, $entry_date, $diary_id, $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected_rows;
}

/**
 * Delete a diary entry only when it belongs to the given user.
 *
 * @return int|false Number of deleted rows (0 or 1), or false on failure.
 */
function deleteDiaryEntry($conn, $diary_id, $user_id) {
    $sql = "DELETE FROM diary_entries
            WHERE diary_id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $diary_id, $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected_rows;
}
?>
