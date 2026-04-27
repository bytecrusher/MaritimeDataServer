<?php

function mds_is_https_request()
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }

    return false;
}

function mds_cookie_options($expires = 0, $sameSite = 'Lax')
{
    $options = array(
        'expires' => (int)$expires,
        'path' => '/',
        'secure' => mds_is_https_request(),
        'httponly' => true,
        'samesite' => $sameSite,
    );

    return $options;
}

function mds_start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', mds_is_https_request() ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params(mds_cookie_options(0, 'Lax'));
    session_start();
}

function mds_set_remember_login_cookies($identifier, $securityToken, $ttlSeconds = 2592000)
{
    $expiresAt = time() + max(0, (int)$ttlSeconds);
    setcookie('identifier', $identifier, mds_cookie_options($expiresAt, 'Lax'));
    setcookie('securityToken', $securityToken, mds_cookie_options($expiresAt, 'Lax'));
}

function mds_clear_remember_login_cookies()
{
    $expiresAt = time() - 3600;
    setcookie('identifier', '', mds_cookie_options($expiresAt, 'Lax'));
    setcookie('securityToken', '', mds_cookie_options($expiresAt, 'Lax'));
}
