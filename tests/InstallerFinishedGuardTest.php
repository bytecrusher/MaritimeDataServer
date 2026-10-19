<?php

$root = sys_get_temp_dir() . '/mds-installer-test-' . bin2hex(random_bytes(8));
mkdir($root . '/app/Support', 0700, true);
mkdir($root . '/config', 0700);
copy(__DIR__ . '/../app/Support/install_guard.func.php', $root . '/app/Support/install_guard.func.php');
try {
    foreach (array(true, 'true', 1, '1', 'yes', 'on', false, 'false', 0, '0') as $finished) {
        file_put_contents($root . '/config/config.json', json_encode(array('installFinished' => $finished)));
        foreach (array(false, true) as $json) {
            $code = 'require ' . var_export($root . '/app/Support/install_guard.func.php', true) . ';'
                . 'register_shutdown_function(function() { echo "|status=" . http_response_code(); });'
                . 'mds_deny_finished_install(' . ($json ? 'true' : 'false') . '); echo "CONTINUED";';
            $output = array();
            exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code), $output, $status);
            $text = implode("\n", $output);
            $blocked = filter_var($finished, FILTER_VALIDATE_BOOLEAN);
            if ($status !== 0 || ($blocked
                ? (!str_contains($text, '|status=403') || str_contains($text, 'CONTINUED'))
                : !str_contains($text, 'CONTINUED'))) {
                throw new RuntimeException('Installer lock failed for ' . var_export($finished, true));
            }
            if ($blocked && $json) {
                $body = json_decode(explode('|status=', $text)[0], true);
                if (($body['error'] ?? null) !== true) {
                    throw new RuntimeException('API lock response must be JSON.');
                }
            }
        }
    }
    foreach (array('api_createAdmin.php', 'api_checkDbConnection.php', 'index.php') as $endpoint) {
        $source = file_get_contents(__DIR__ . '/../public/install/' . $endpoint);
        $guard = strpos($source, 'mds_deny_finished_install(');
        $boundary = strpos($source, $endpoint === 'index.php' ? '<!DOCTYPE' : '$var_dbHostName');
        if ($guard === false || $boundary === false || $guard > $boundary) {
            throw new RuntimeException('Installer endpoint must guard before work: ' . $endpoint);
        }
    }
    echo "Installer finished guard tests passed.\n";
} finally {
    unlink($root . '/config/config.json');
    unlink($root . '/app/Support/install_guard.func.php');
    rmdir($root . '/config');
    rmdir($root . '/app/Support');
    rmdir($root . '/app');
    rmdir($root);
}
