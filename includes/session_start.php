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
?>
