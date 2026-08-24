<?php
// Database functions used by the Admin account-management area.

function getAdminUserCounts($conn) {
    $counts = array(
        'total_users'   => 0,
        'student_users' => 0,
        'admin_users'   => 0
    );

    $sql = "SELECT COUNT(*) AS total_users,
                   SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) AS student_users,
                   SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS admin_users
            FROM users";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt || !mysqli_stmt_execute($stmt)) {
        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
        return $counts;
    }

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        $counts['total_users']   = (int) $row['total_users'];
        $counts['student_users'] = (int) $row['student_users'];
        $counts['admin_users']   = (int) $row['admin_users'];
    }

    return $counts;
}

function getRegisteredUsers($conn, $search = '', $role = '') {
    $search = trim((string) $search);
    $role = in_array($role, array('student', 'admin'), true) ? $role : '';
    $stmt = null;

    try {
        $columns = "SELECT user_id, name, email, role, created_at FROM users";
        $order = " ORDER BY created_at DESC, user_id DESC";

        if ($search !== '' && $role !== '') {
            $sql = $columns
                . " WHERE (name LIKE ? OR email LIKE ?) AND role = ?"
                . $order;
            $search_pattern = '%' . $search . '%';
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $search_pattern, $search_pattern, $role);
        } elseif ($search !== '') {
            $sql = $columns . " WHERE (name LIKE ? OR email LIKE ?)" . $order;
            $search_pattern = '%' . $search . '%';
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'ss', $search_pattern, $search_pattern);
        } elseif ($role !== '') {
            $sql = $columns . " WHERE role = ?" . $order;
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 's', $role);
        } else {
            $stmt = mysqli_prepare($conn, $columns . $order);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $users = array();

        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $users;
    } catch (mysqli_sql_exception $exception) {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }

        $message = str_replace(array("\r", "\n"), ' ', $exception->getMessage());
        error_log(
            '[Admin users] search mysqli error '
            . (int) $exception->getCode()
            . ': '
            . $message
        );
        return array();
    }
}

function getAllRegisteredUsers($conn) {
    return getRegisteredUsers($conn);
}

/**
 * Delete one managed user while protecting the acting Admin account and the
 * final remaining Admin account.
 *
 * @return string success, self, last_admin, not_found, or failure.
 */
function deleteRegisteredUser($conn, $target_user_id, $acting_admin_user_id) {
    $target_user_id = (int) $target_user_id;
    $acting_admin_user_id = (int) $acting_admin_user_id;
    $target_statement = null;
    $admin_statement = null;
    $delete_statement = null;

    if ($target_user_id < 1 || $acting_admin_user_id < 1) {
        return 'not_found';
    }

    if ($target_user_id === $acting_admin_user_id) {
        return 'self';
    }

    try {
        mysqli_begin_transaction($conn);

        $target_statement = mysqli_prepare(
            $conn,
            'SELECT user_id, role FROM users WHERE user_id = ? LIMIT 1 FOR UPDATE'
        );
        mysqli_stmt_bind_param($target_statement, 'i', $target_user_id);
        mysqli_stmt_execute($target_statement);
        $target_result = mysqli_stmt_get_result($target_statement);
        $target_user = mysqli_fetch_assoc($target_result);
        mysqli_stmt_close($target_statement);
        $target_statement = null;

        if (!$target_user) {
            mysqli_rollback($conn);
            return 'not_found';
        }

        if ((string) $target_user['role'] === 'admin') {
            $admin_statement = mysqli_prepare(
                $conn,
                "SELECT user_id FROM users WHERE role = 'admin' FOR UPDATE"
            );
            mysqli_stmt_execute($admin_statement);
            $admin_result = mysqli_stmt_get_result($admin_statement);
            $admin_count = mysqli_num_rows($admin_result);
            mysqli_stmt_close($admin_statement);
            $admin_statement = null;

            if ($admin_count <= 1) {
                mysqli_rollback($conn);
                return 'last_admin';
            }
        }

        $delete_statement = mysqli_prepare(
            $conn,
            'DELETE FROM users WHERE user_id = ?'
        );
        mysqli_stmt_bind_param($delete_statement, 'i', $target_user_id);
        mysqli_stmt_execute($delete_statement);
        $deleted_rows = mysqli_stmt_affected_rows($delete_statement);
        mysqli_stmt_close($delete_statement);
        $delete_statement = null;

        if ($deleted_rows !== 1) {
            mysqli_rollback($conn);
            return 'failure';
        }

        mysqli_commit($conn);
        return 'success';
    } catch (mysqli_sql_exception $exception) {
        foreach (array($target_statement, $admin_statement, $delete_statement) as $statement) {
            if ($statement instanceof mysqli_stmt) {
                mysqli_stmt_close($statement);
            }
        }

        try {
            mysqli_rollback($conn);
        } catch (mysqli_sql_exception $rollback_exception) {
            // Preserve the original database failure for the private log.
        }

        $message = str_replace(array("\r", "\n"), ' ', $exception->getMessage());
        error_log(
            '[Admin users] delete mysqli error '
            . (int) $exception->getCode()
            . ': '
            . $message
        );
        return 'failure';
    }
}

function getRecentRegisteredUsers($conn, $limit = 5) {
    $limit = max(1, min(20, (int) $limit));
    $stmt = null;

    try {
        $sql = "SELECT user_id, name, email, role, created_at
                FROM users
                ORDER BY created_at DESC, user_id DESC
                LIMIT ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $users = array();

        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $users;
    } catch (mysqli_sql_exception $exception) {
        if ($stmt instanceof mysqli_stmt) {
            mysqli_stmt_close($stmt);
        }

        $message = str_replace(array("\r", "\n"), " ", $exception->getMessage());
        error_log(
            "[Admin dashboard] recent registrations mysqli error "
            . (int) $exception->getCode()
            . ": "
            . $message
        );
        return array();
    }
}
?>
