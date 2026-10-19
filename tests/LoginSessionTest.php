<?php

// Run the actual POST handler with isolated user/database dependencies.
if (!isset($argv[1])) {
    foreach (array('wrong', 'inactive', 'unknown', 'csrf', 'limited', 'success') as $scenario) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $scenario, $status);
        if ($status !== 0) {
            exit($status);
        }
    }
    echo "Login session tests passed.\n";
    exit;
}
$scenario = $argv[1];
session_start();
$_SESSION = array('userId' => 99, 'userObj' => 'stale authenticated user');
$_POST = array('email' => 'test@example.invalid', 'password' => $scenario === 'wrong' ? 'wrong' : 'correct');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$originalSessionId = session_id();
class user {
    public function __construct($email) {}
    public function getError() { return ''; }
    public function userExist() { return $GLOBALS['scenario'] !== 'unknown'; }
    public function isActive() { return $GLOBALS['scenario'] !== 'inactive'; }
    public function getPassword() { return password_hash('correct', PASSWORD_DEFAULT); }
    public function getId() { return 7; }
    public function getLanguage() { return 'de'; }
}
function mds_verify_csrf_token($token) { return $GLOBALS['scenario'] !== 'csrf'; }
function mds_rate_limit_attempt(...$args) { return array('allowed' => $GLOBALS['scenario'] !== 'limited'); }
function mds_t($key) { return $key; }
function mds_set_current_language($language) {}
register_shutdown_function(function () use ($scenario, $originalSessionId) {
    $passed = $scenario === 'success'
        ? (($_SESSION['userId'] ?? null) === 7 && isset($_SESSION['userObj']) && session_id() !== $originalSessionId)
        : (!isset($_SESSION['userId']) && !isset($_SESSION['userObj']));
    session_destroy();
    if (!$passed) {
        fwrite(STDERR, 'Unexpected authentication state: ' . $scenario . PHP_EOL);
        exit(1);
    }
});
$source = file_get_contents(__DIR__ . '/../public/login.php');
$start = strpos($source, '$error_msg = "";');
$end = strpos($source, '$email_value = "";');
if ($start === false || $end === false || $end <= $start) {
    throw new RuntimeException('Login handler boundaries not found.');
}
eval(substr($source, $start, $end - $start));
