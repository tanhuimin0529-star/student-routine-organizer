<?php
// Keep all application date and time features on Malaysia Standard Time.
date_default_timezone_set('Asia/Kuala_Lumpur');

// Configure session security before PHP starts or resumes the session.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');

    $https_is_active =
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    // Preserve the configured lifetime, path, and domain while strengthening
    // the security attributes used by the session cookie.
    $cookie_params = session_get_cookie_params();
    session_set_cookie_params(array(
        'lifetime' => $cookie_params['lifetime'],
        'path' => $cookie_params['path'],
        'domain' => $cookie_params['domain'],
        'secure' => $https_is_active,
        'httponly' => true,
        'samesite' => 'Lax'
    ));

    session_start();
}

// Five minutes without a valid authenticated request ends the session.
if (!defined('AUTHENTICATED_SESSION_INACTIVITY_TIMEOUT')) {
    define('AUTHENTICATED_SESSION_INACTIVITY_TIMEOUT', 5 * 60);
}

if (!function_exists('destroyApplicationSessionSafely')) {
    /**
     * Clear server-side session data and expire the active session cookie.
     */
    function destroyApplicationSessionSafely() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $cookie_params = session_get_cookie_params();
            $expired_cookie = array(
                'expires' => time() - 42000,
                'path' => $cookie_params['path'],
                'secure' => $cookie_params['secure'],
                'httponly' => $cookie_params['httponly'],
                'samesite' => isset($cookie_params['samesite'])
                    ? $cookie_params['samesite']
                    : 'Lax'
            );

            if ($cookie_params['domain'] !== '') {
                $expired_cookie['domain'] = $cookie_params['domain'];
            }

            setcookie(session_name(), '', $expired_cookie);
        }

        session_destroy();
    }
}

if (isset($_SESSION['user_id'])) {
    $current_activity_time = time();
    $last_activity_time = isset($_SESSION['auth_last_activity'])
        && is_numeric($_SESSION['auth_last_activity'])
            ? (int) $_SESSION['auth_last_activity']
            : 0;

    if (
        $last_activity_time > 0
        && $current_activity_time >= $last_activity_time
        && ($current_activity_time - $last_activity_time) >= AUTHENTICATED_SESSION_INACTIVITY_TIMEOUT
    ) {
        $expired_session_role = isset($_SESSION['role'])
            ? (string) $_SESSION['role']
            : 'student';

        destroyApplicationSessionSafely();

        $application_directory = rawurlencode(basename(dirname(__DIR__)));
        $login_page = $expired_session_role === 'admin'
            ? 'admin_login.php'
            : 'login.php';

        header(
            'Location: /' . $application_directory
            . '/authentication/' . $login_page
            . '?msg=session_expired',
            true,
            303
        );
        exit();
    }

    // Every valid authenticated request refreshes the inactivity timer.
    $_SESSION['auth_last_activity'] = $current_activity_time;
}
?>
