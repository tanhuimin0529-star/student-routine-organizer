<?php

// Shared data and security helpers for the student password-reset flow.

const PASSWORD_RESET_TOKEN_TTL_SECONDS = 30 * 60;
const PASSWORD_RESET_RAW_TOKEN_BYTES = 32;

function passwordResetLogDatabaseError($context, $exception) {
    $message = str_replace(array("\r", "\n"), ' ', $exception->getMessage());
    error_log(
        '[Password reset] ' . $context
        . ' mysqli error ' . (int) $exception->getCode()
        . ': ' . $message
    );
}

function passwordResetIsLocalDevelopment() {
    $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
    $host = strtolower(trim($host));
    $remote_address = isset($_SERVER['REMOTE_ADDR'])
        ? strtolower(trim((string) $_SERVER['REMOTE_ADDR']))
        : '';

    if ($host !== '' && $host[0] === '[') {
        $closing_bracket = strpos($host, ']');
        $host_name = $closing_bracket === false
            ? $host
            : substr($host, 1, $closing_bracket - 1);
    } else {
        $host_name = explode(':', $host, 2)[0];
    }

    return in_array($host_name, array('localhost', '127.0.0.1', '::1'), true)
        && in_array($remote_address, array('127.0.0.1', '::1'), true);
}

function passwordResetTokenHasValidFormat($token) {
    return is_string($token)
        && preg_match('/\A[a-f0-9]{64}\z/D', $token) === 1;
}

function passwordResetHashToken($token) {
    return hash('sha256', $token);
}

function createStudentPasswordResetToken($conn, $email) {
    $lookup_statement = null;
    $invalidate_statement = null;
    $insert_statement = null;

    try {
        $lookup_statement = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users WHERE email = ? AND role = 'student' LIMIT 1"
        );
        mysqli_stmt_bind_param($lookup_statement, 's', $email);
        mysqli_stmt_execute($lookup_statement);
        $result = mysqli_stmt_get_result($lookup_statement);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($lookup_statement);
        $lookup_statement = null;

        // A missing account is deliberately indistinguishable from a successful request.
        if (!$user) {
            return array('success' => true, 'token' => null);
        }

        $raw_token = bin2hex(random_bytes(PASSWORD_RESET_RAW_TOKEN_BYTES));
        $token_hash = passwordResetHashToken($raw_token);
        $expires_at = date('Y-m-d H:i:s', time() + PASSWORD_RESET_TOKEN_TTL_SECONDS);
        $user_id = (int) $user['user_id'];

        mysqli_begin_transaction($conn);

        $invalidate_statement = mysqli_prepare(
            $conn,
            'UPDATE password_reset_tokens
             SET used_at = NOW()
             WHERE user_id = ? AND used_at IS NULL'
        );
        mysqli_stmt_bind_param($invalidate_statement, 'i', $user_id);
        mysqli_stmt_execute($invalidate_statement);
        mysqli_stmt_close($invalidate_statement);
        $invalidate_statement = null;

        $insert_statement = mysqli_prepare(
            $conn,
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (?, ?, ?)'
        );
        mysqli_stmt_bind_param($insert_statement, 'iss', $user_id, $token_hash, $expires_at);
        mysqli_stmt_execute($insert_statement);
        mysqli_stmt_close($insert_statement);
        $insert_statement = null;

        mysqli_commit($conn);

        return array('success' => true, 'token' => $raw_token);
    } catch (Throwable $exception) {
        foreach (array($lookup_statement, $invalidate_statement, $insert_statement) as $statement) {
            if ($statement instanceof mysqli_stmt) {
                mysqli_stmt_close($statement);
            }
        }

        try {
            mysqli_rollback($conn);
        } catch (Throwable $rollback_exception) {
            passwordResetLogDatabaseError('rollback token creation', $rollback_exception);
        }

        passwordResetLogDatabaseError('create token', $exception);
        return array('success' => false, 'token' => null);
    }
}

function findValidStudentPasswordResetToken($conn, $raw_token, $for_update = false) {
    if (!passwordResetTokenHasValidFormat($raw_token)) {
        return null;
    }

    $token_hash = passwordResetHashToken($raw_token);
    $statement = null;

    try {
        $sql = 'SELECT prt.reset_id, prt.user_id, prt.token_hash,
                       prt.expires_at, prt.used_at
                FROM password_reset_tokens AS prt
                INNER JOIN users AS u ON u.user_id = prt.user_id
                WHERE prt.token_hash = ? AND u.role = \'student\'
                LIMIT 1';

        if ($for_update) {
            $sql .= ' FOR UPDATE';
        }

        $statement = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($statement, 's', $token_hash);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $record = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);

        if (!$record || !hash_equals((string) $record['token_hash'], $token_hash)) {
            return null;
        }

        if ($record['used_at'] !== null) {
            return null;
        }

        $expiry_time = strtotime((string) $record['expires_at']);
        if ($expiry_time === false || $expiry_time <= time()) {
            return null;
        }

        return $record;
    } catch (Throwable $exception) {
        if ($statement instanceof mysqli_stmt) {
            mysqli_stmt_close($statement);
        }

        passwordResetLogDatabaseError('validate token', $exception);
        return null;
    }
}

function resetStudentPasswordWithToken($conn, $raw_token, $new_password_hash) {
    $password_statement = null;
    $used_statement = null;
    $invalidate_statement = null;

    try {
        mysqli_begin_transaction($conn);
        $record = findValidStudentPasswordResetToken($conn, $raw_token, true);

        if (!$record) {
            mysqli_rollback($conn);
            return false;
        }

        $user_id = (int) $record['user_id'];
        $reset_id = (int) $record['reset_id'];

        $password_statement = mysqli_prepare(
            $conn,
            'UPDATE users SET password = ? WHERE user_id = ?'
        );
        mysqli_stmt_bind_param($password_statement, 'si', $new_password_hash, $user_id);
        mysqli_stmt_execute($password_statement);
        mysqli_stmt_close($password_statement);
        $password_statement = null;

        $used_statement = mysqli_prepare(
            $conn,
            'UPDATE password_reset_tokens
             SET used_at = NOW()
             WHERE reset_id = ? AND user_id = ? AND used_at IS NULL'
        );
        mysqli_stmt_bind_param($used_statement, 'ii', $reset_id, $user_id);
        mysqli_stmt_execute($used_statement);
        mysqli_stmt_close($used_statement);
        $used_statement = null;

        $invalidate_statement = mysqli_prepare(
            $conn,
            'UPDATE password_reset_tokens
             SET used_at = NOW()
             WHERE user_id = ? AND reset_id <> ? AND used_at IS NULL'
        );
        mysqli_stmt_bind_param($invalidate_statement, 'ii', $user_id, $reset_id);
        mysqli_stmt_execute($invalidate_statement);
        mysqli_stmt_close($invalidate_statement);
        $invalidate_statement = null;

        mysqli_commit($conn);
        return true;
    } catch (Throwable $exception) {
        foreach (array($password_statement, $used_statement, $invalidate_statement) as $statement) {
            if ($statement instanceof mysqli_stmt) {
                mysqli_stmt_close($statement);
            }
        }

        try {
            mysqli_rollback($conn);
        } catch (Throwable $rollback_exception) {
            passwordResetLogDatabaseError('rollback password update', $rollback_exception);
        }

        passwordResetLogDatabaseError('reset password', $exception);
        return false;
    }
}

