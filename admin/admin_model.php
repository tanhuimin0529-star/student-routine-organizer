<?php
// Read-only database functions used by the basic Admin area.

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
        $counts['total_users']   = (int)$row['total_users'];
        $counts['student_users'] = (int)$row['student_users'];
        $counts['admin_users']   = (int)$row['admin_users'];
    }

    return $counts;
}

function getAllRegisteredUsers($conn) {
    $sql = "SELECT user_id, name, email, role FROM users ORDER BY user_id ASC";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt || !mysqli_stmt_execute($stmt)) {
        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
        return array();
    }

    $result = mysqli_stmt_get_result($stmt);
    $users = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $users;
}
?>
