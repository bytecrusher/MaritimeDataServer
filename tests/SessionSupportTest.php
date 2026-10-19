<?php

$sessionPath = sys_get_temp_dir() . '/mds-session-test-' . bin2hex(random_bytes(6));
putenv('MDS_SESSION_SAVE_PATH=' . $sessionPath);

require_once dirname(__DIR__) . '/app/Support/session.func.php';

function failSessionTest($message)
{
    fwrite(STDERR, 'FAILED: ' . $message . PHP_EOL);
    exit(1);
}

$resolvedPath = mds_session_storage_path();
if ($resolvedPath !== $sessionPath || !is_dir($sessionPath) || !is_writable($sessionPath)) {
    failSessionTest('Dedicated session storage was not prepared.');
}

session_id(bin2hex(random_bytes(16)));
if (!mds_start_session() || session_status() !== PHP_SESSION_ACTIVE) {
    failSessionTest('PHP session could not be started.');
}

$_SESSION['session_test'] = 'ok';
session_write_close();

$sessionFiles = glob($sessionPath . '/sess_*') ?: array();
if (count($sessionFiles) !== 1 || strpos((string)file_get_contents($sessionFiles[0]), 'session_test') === false) {
    failSessionTest('PHP session data was not written to dedicated storage.');
}

foreach ($sessionFiles as $sessionFile) {
    unlink($sessionFile);
}
rmdir($sessionPath);
putenv('MDS_SESSION_SAVE_PATH');

echo "Session support tests passed.\n";
