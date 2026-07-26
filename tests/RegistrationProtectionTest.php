<?php

$testRoot = sys_get_temp_dir() . '/mds-registration-protection-' . bin2hex(random_bytes(6));
$sessionPath = $testRoot . '/sessions';
$rateLimitFile = $testRoot . '/rate-limits.json';
putenv('MDS_SESSION_SAVE_PATH=' . $sessionPath);
putenv('MDS_RATE_LIMIT_STORAGE_FILE=' . $rateLimitFile);

require_once dirname(__DIR__) . '/app/Support/session.func.php';

function failRegistrationProtectionTest($message)
{
    fwrite(STDERR, 'FAILED: ' . $message . PHP_EOL);
    exit(1);
}

session_id(bin2hex(random_bytes(16)));
if (!mds_start_session()) {
    failRegistrationProtectionTest('Session could not be started.');
}

$token = mds_issue_form_challenge('register');
if ($token === '' || !mds_validate_form_challenge('register', $token, 0, 60)) {
    failRegistrationProtectionTest('A freshly issued form challenge was rejected.');
}
if (mds_validate_form_challenge('register', $token, 0, 60)) {
    failRegistrationProtectionTest('A form challenge could be used more than once.');
}

$tooFastToken = mds_issue_form_challenge('register');
if (mds_validate_form_challenge('register', $tooFastToken, 10, 60)) {
    failRegistrationProtectionTest('A form challenge bypassed its minimum age.');
}

$subject = 'visitor@example.test';
$first = mds_rate_limit_attempt('register-email', $subject, 2, 60, true, 1000);
$second = mds_rate_limit_attempt('register-email', $subject, 2, 60, true, 1001);
$blocked = mds_rate_limit_attempt('register-email', $subject, 2, 60, true, 1002);
if (!$first['allowed'] || !$second['allowed'] || $blocked['allowed'] || $blocked['retryAfter'] !== 58) {
    failRegistrationProtectionTest('Rate limit did not enforce the configured window.');
}

$otherSubject = mds_rate_limit_attempt('register-email', 'other@example.test', 2, 60, true, 1002);
$afterWindow = mds_rate_limit_attempt('register-email', $subject, 2, 60, true, 1061);
if (!$otherSubject['allowed'] || !$afterWindow['allowed']) {
    failRegistrationProtectionTest('Rate limit did not isolate subjects or expire attempts.');
}

$storedPayload = (string)file_get_contents($rateLimitFile);
if (strpos($storedPayload, $subject) !== false || strpos($storedPayload, 'other@example.test') !== false) {
    failRegistrationProtectionTest('Rate limit storage contains a clear-text subject.');
}
if (!is_array(json_decode($storedPayload, true))) {
    failRegistrationProtectionTest('Rate limit storage does not contain valid JSON.');
}

file_put_contents($rateLimitFile, 'invalid-json');
$corruptStorageResult = mds_rate_limit_attempt('register-client', '127.0.0.1', 2, 60, true, 2000);
if ($corruptStorageResult['allowed'] || $corruptStorageResult['storageAvailable']) {
    failRegistrationProtectionTest('Corrupt rate limit storage did not fail closed.');
}

session_write_close();
foreach (glob($sessionPath . '/sess_*') ?: array() as $sessionFile) {
    unlink($sessionFile);
}
unlink($rateLimitFile);
rmdir($sessionPath);
rmdir($testRoot);
putenv('MDS_SESSION_SAVE_PATH');
putenv('MDS_RATE_LIMIT_STORAGE_FILE');

echo "Registration protection tests passed.\n";
