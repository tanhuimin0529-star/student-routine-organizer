<?php
// Shared helpers for the site's optional preference cookies.

function getCookieConsentChoice() {
    $choice = isset($_COOKIE['cookie_consent']) ? $_COOKIE['cookie_consent'] : null;
    return in_array($choice, array('accepted', 'denied'), true) ? $choice : null;
}

function optionalCookiesAllowed() {
    return getCookieConsentChoice() === 'accepted';
}

function getApplicationCookiePath() {
    return '/' . rawurlencode(basename(dirname(__DIR__)));
}

function isSecureCookieRequest() {
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower($_SERVER['HTTPS']) !== 'off';
}

function setCookieConsentChoice($choice) {
    if (!in_array($choice, array('accepted', 'denied'), true)) {
        return false;
    }

    setcookie('cookie_consent', $choice, array(
        'expires'  => time() + (365 * 24 * 60 * 60),
        'path'     => getApplicationCookiePath() . '/',
        'secure'   => isSecureCookieRequest(),
        'httponly' => true,
        'samesite' => 'Lax'
    ));
    $_COOKIE['cookie_consent'] = $choice;
    return true;
}

function getOptionalCookiePaths() {
    $base = getApplicationCookiePath();
    return array(
        'remembered_email' => $base . '/authentication',
        'last_activity'    => $base . '/modules/exercise',
        'preferred_sort'   => $base . '/modules/exercise'
    );
}

function setOptionalPreferenceCookie($name, $value) {
    $paths = getOptionalCookiePaths();
    if (!optionalCookiesAllowed() || !isset($paths[$name])) {
        return false;
    }

    setcookie($name, $value, array(
        'expires'  => time() + (30 * 24 * 60 * 60),
        'path'     => $paths[$name],
        'secure'   => isSecureCookieRequest(),
        'httponly' => true,
        'samesite' => 'Lax'
    ));
    $_COOKIE[$name] = $value;
    return true;
}

function clearOptionalPreferenceCookie($name) {
    $paths = getOptionalCookiePaths();
    if (!isset($paths[$name])) {
        return;
    }

    $base = getApplicationCookiePath();
    $cookie_paths = array_unique(array($paths[$name], $paths[$name] . '/', $base . '/'));

    foreach ($cookie_paths as $path) {
        setcookie($name, '', array(
            'expires'  => time() - 3600,
            'path'     => $path,
            'secure'   => isSecureCookieRequest(),
            'httponly' => true,
            'samesite' => 'Lax'
        ));
    }

    unset($_COOKIE[$name]);
}

function clearAllOptionalPreferenceCookies() {
    foreach (array_keys(getOptionalCookiePaths()) as $name) {
        clearOptionalPreferenceCookie($name);
    }
}
?>
