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

function mds_is_local_host()
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);

    return in_array($host, array('localhost', '127.0.0.1', '::1'), true)
        || str_ends_with($host, '.local');
}

function mds_enforce_https_if_possible()
{
    if (PHP_SAPI === 'cli' || mds_is_https_request() || mds_is_local_host()) {
        return;
    }

    if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
        return;
    }

    $target = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $target, true, (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') ? 302 : 307);
    exit();
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

function mds_get_csrf_token()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        mds_start_session();
    }

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function mds_verify_csrf_token($token)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        mds_start_session();
    }

    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals((string)$_SESSION['csrf_token'], $token);
}

function mds_csrf_input()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(mds_get_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function mds_rate_limit_attempt($bucket, $subject, $limit, $windowSeconds)
{
    $limit = max(1, (int)$limit);
    $windowSeconds = max(1, (int)$windowSeconds);

    $storageDir = dirname(__DIR__, 2) . '/var/status';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0775, true);
    }

    $storageFile = $storageDir . '/rate_limits.json';
    $now = time();
    $key = sha1($bucket . '|' . $subject);
    $payload = array();

    if (file_exists($storageFile)) {
        $decoded = json_decode((string)file_get_contents($storageFile), true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }

    foreach ($payload as $payloadKey => $entry) {
        $entryWindow = max(1, (int)($entry['window'] ?? $windowSeconds));
        $timestamps = array_values(array_filter(
            $entry['timestamps'] ?? array(),
            function ($timestamp) use ($now, $entryWindow) {
                return is_numeric($timestamp) && ((int)$timestamp >= ($now - $entryWindow));
            }
        ));

        if (empty($timestamps)) {
            unset($payload[$payloadKey]);
            continue;
        }

        $payload[$payloadKey]['timestamps'] = $timestamps;
    }

    $entry = $payload[$key] ?? array(
        'bucket' => $bucket,
        'window' => $windowSeconds,
        'timestamps' => array(),
    );

    $entry['timestamps'] = array_values(array_filter(
        $entry['timestamps'],
        function ($timestamp) use ($now, $windowSeconds) {
            return is_numeric($timestamp) && ((int)$timestamp >= ($now - $windowSeconds));
        }
    ));

    if (count($entry['timestamps']) >= $limit) {
        $oldest = (int)$entry['timestamps'][0];
        $retryAfter = max(1, ($oldest + $windowSeconds) - $now);
        $payload[$key] = $entry;
        @file_put_contents($storageFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return array(
            'allowed' => false,
            'retryAfter' => $retryAfter,
        );
    }

    $entry['timestamps'][] = $now;
    $payload[$key] = $entry;
    @file_put_contents($storageFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return array(
        'allowed' => true,
        'retryAfter' => 0,
    );
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
