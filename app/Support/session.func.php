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

function mds_session_storage_path()
{
    $configuredPath = trim((string)getenv('MDS_SESSION_SAVE_PATH'));
    $preferredPath = $configuredPath !== ''
        ? $configuredPath
        : dirname(__DIR__, 2) . '/var/sessions';

    $effectiveUserId = function_exists('posix_geteuid') ? (string)posix_geteuid() : (string)getmyuid();
    $fallbackPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . 'mds-sessions-'
        . substr(hash('sha256', dirname(__DIR__, 2) . '|' . $effectiveUserId), 0, 16);
    $candidates = array_values(array_unique(array($preferredPath, $fallbackPath)));

    foreach ($candidates as $sessionPath) {
        if (!is_dir($sessionPath) && !@mkdir($sessionPath, 0700, true) && !is_dir($sessionPath)) {
            continue;
        }
        if (is_writable($sessionPath)) {
            return $sessionPath;
        }
    }

    return null;
}

function mds_start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }

    $headersFile = '';
    $headersLine = 0;
    if (headers_sent($headersFile, $headersLine)) {
        if (class_exists('writeToLogFunction')) {
            writeToLogFunction::warning(
                'Session could not be started because response headers were already sent.',
                __FILE__,
                array(
                    'headersFile' => $headersFile,
                    'headersLine' => $headersLine,
                )
            );
        }
        return false;
    }

    $sessionPath = mds_session_storage_path();
    if ($sessionPath !== null) {
        ini_set('session.save_path', $sessionPath);
    } elseif (class_exists('writeToLogFunction')) {
        writeToLogFunction::error(
            'Dedicated session storage is unavailable; PHP session storage fallback will be used.',
            __FILE__,
            array('configuredPath' => (string)getenv('MDS_SESSION_SAVE_PATH'))
        );
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', mds_is_https_request() ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => mds_is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    $started = session_start();
    if (!$started && class_exists('writeToLogFunction')) {
        writeToLogFunction::error(
            'PHP session could not be started.',
            __FILE__,
            array(
                'sessionSavePath' => session_save_path(),
                'sessionName' => session_name(),
            )
        );
    }

    return $started;
}

function mds_start_session_if_present()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }

    $sessionName = session_name();
    if ($sessionName === '' || empty($_COOKIE[$sessionName])) {
        return false;
    }

    mds_start_session();
    return session_status() === PHP_SESSION_ACTIVE;
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

function mds_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mds_issue_form_challenge($purpose)
{
    if (session_status() !== PHP_SESSION_ACTIVE && !mds_start_session()) {
        return '';
    }

    $purpose = preg_replace('/[^a-z0-9_-]/i', '', (string)$purpose);
    if ($purpose === '') {
        return '';
    }

    $token = bin2hex(random_bytes(24));
    if (!isset($_SESSION['form_challenges']) || !is_array($_SESSION['form_challenges'])) {
        $_SESSION['form_challenges'] = array();
    }
    if (!isset($_SESSION['form_challenges'][$purpose]) || !is_array($_SESSION['form_challenges'][$purpose])) {
        $_SESSION['form_challenges'][$purpose] = array();
    }

    $now = time();
    $_SESSION['form_challenges'][$purpose] = array_filter(
        $_SESSION['form_challenges'][$purpose],
        function ($startedAt) use ($now) {
            return is_numeric($startedAt) && (int)$startedAt >= ($now - 7200);
        }
    );
    $_SESSION['form_challenges'][$purpose][$token] = $now;
    if (count($_SESSION['form_challenges'][$purpose]) > 10) {
        $_SESSION['form_challenges'][$purpose] = array_slice(
            $_SESSION['form_challenges'][$purpose],
            -10,
            null,
            true
        );
    }

    return $token;
}

function mds_validate_form_challenge($purpose, $token, $minimumAgeSeconds = 2, $maximumAgeSeconds = 7200)
{
    if (session_status() !== PHP_SESSION_ACTIVE && !mds_start_session()) {
        return false;
    }

    $purpose = preg_replace('/[^a-z0-9_-]/i', '', (string)$purpose);
    $token = (string)$token;
    $challenges = $_SESSION['form_challenges'][$purpose] ?? array();
    if ($purpose === '' || $token === '' || !is_array($challenges) || !isset($challenges[$token])) {
        return false;
    }

    $startedAt = (int)$challenges[$token];
    unset($_SESSION['form_challenges'][$purpose][$token]);
    $age = time() - $startedAt;
    return $age >= max(0, (int)$minimumAgeSeconds)
        && $age <= max((int)$minimumAgeSeconds, (int)$maximumAgeSeconds);
}

function mds_rate_limit_attempt($bucket, $subject, $limit, $windowSeconds, $consumeAttempt = true, $now = null)
{
    $limit = max(1, (int)$limit);
    $windowSeconds = max(1, (int)$windowSeconds);
    $now = $now === null ? time() : (int)$now;

    $key = sha1($bucket . '|' . $subject);
    $payload = array();

    $configuredStorageFile = trim((string)getenv('MDS_RATE_LIMIT_STORAGE_FILE'));
    if ($configuredStorageFile !== '') {
        $storageFiles = array($configuredStorageFile);
    } else {
        $effectiveUserId = function_exists('posix_geteuid') ? (string)posix_geteuid() : (string)getmyuid();
        $storageFiles = array(
            dirname(__DIR__, 2) . '/var/status/rate_limits.json',
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'mds-rate-limits-'
                . substr(hash('sha256', dirname(__DIR__, 2) . '|' . $effectiveUserId), 0, 16)
                . '.json',
        );
    }

    $handle = false;
    foreach (array_unique($storageFiles) as $storageFile) {
        $storageDir = dirname($storageFile);
        if (!is_dir($storageDir) && !@mkdir($storageDir, 0700, true) && !is_dir($storageDir)) {
            continue;
        }

        $candidateHandle = @fopen($storageFile, 'c+');
        if ($candidateHandle === false) {
            continue;
        }
        if (flock($candidateHandle, LOCK_EX)) {
            $handle = $candidateHandle;
            break;
        }

        fclose($candidateHandle);
    }

    if ($handle === false) {
        return array('allowed' => false, 'retryAfter' => $windowSeconds, 'storageAvailable' => false);
    }

    $contents = stream_get_contents($handle);
    $decoded = json_decode((string)$contents, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    } elseif (trim((string)$contents) !== '') {
        flock($handle, LOCK_UN);
        fclose($handle);
        return array('allowed' => false, 'retryAfter' => $windowSeconds, 'storageAvailable' => false);
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
        $storageAvailable = mds_write_rate_limit_payload($handle, $payload);
        flock($handle, LOCK_UN);
        fclose($handle);

        return array(
            'allowed' => false,
            'retryAfter' => $retryAfter,
            'storageAvailable' => $storageAvailable,
        );
    }

    if ($consumeAttempt) {
        $entry['timestamps'][] = $now;
        $payload[$key] = $entry;
        if (!mds_write_rate_limit_payload($handle, $payload)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return array('allowed' => false, 'retryAfter' => $windowSeconds, 'storageAvailable' => false);
        }
    }

    flock($handle, LOCK_UN);
    fclose($handle);

    return array(
        'allowed' => true,
        'retryAfter' => 0,
        'storageAvailable' => true,
    );
}

function mds_write_rate_limit_payload($handle, array $payload)
{
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false || !rewind($handle) || !ftruncate($handle, 0)) {
        return false;
    }

    $bytesWritten = fwrite($handle, $encoded);
    return $bytesWritten === strlen($encoded) && fflush($handle);
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
