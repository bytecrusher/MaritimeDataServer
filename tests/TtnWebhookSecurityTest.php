<?php
require_once __DIR__ . '/../app/Application/SecurityPolicy.php';
function checkTtn($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$source = file_get_contents(__DIR__ . '/../app/Http/Webhooks/TTN/ttn.php');
$start = strpos($source, 'function ttnWebhookSecretIsValid(');
$end = strpos($source, 'function ttnSelectBestRxMetadata(');
// Test pure helpers without opening a database or processing telemetry.
eval(substr($source, $start, $end - $start));
unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
checkTtn(!ttnWebhookSecretIsValid(array(), ''), 'Empty secret accepted.');
checkTtn(!ttnWebhookSecretIsValid(array(), 'secret'), 'Missing credential accepted.');
foreach (array('X-MDS-Webhook-Secret','X-Webhook-Secret','X-TTN-Webhook-Secret','X-MDS-TTN-Secret','X-API-Key') as $header) {
    checkTtn(ttnWebhookSecretIsValid(array(strtolower($header) => 'secret'), 'secret'), 'Supported header rejected.');
    checkTtn(!ttnWebhookSecretIsValid(array($header => 'wrong'), 'secret'), 'Wrong credential accepted.');
    checkTtn(!ttnWebhookSecretIsValid(array($header => array('secret')), 'secret'), 'Array credential accepted.');
}
checkTtn(ttnWebhookSecretIsValid(array('Authorization'=>'Bearer secret'), 'secret'), 'Bearer rejected.');
$_SERVER['REDIRECT_HTTP_AUTHORIZATION']='Bearer secret';
checkTtn(ttnWebhookSecretIsValid(array(), 'secret'), 'CGI bearer rejected.');
unset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
checkTtn(ttnFilterHeadersForLogging(array('X-API-Key'=>'secret'))['X-API-Key']==='***', 'API key logged.');
$base = 'https://example.test/mds';
checkTtn(count(SecurityPolicy::simulatorHeaders($base.'/webhooks/ttn.php', $base, 'secret')) === 2, 'Local simulator missing authentication.');
foreach (array('https://attacker.test/webhooks/ttn.php', $base.'/webhooks/ttn.php?redirect=evil', 'https://example.test.evil/mds/webhooks/ttn.php', $base.'/other.php') as $target) {
    checkTtn(count(SecurityPolicy::simulatorHeaders($target, $base, 'secret')) === 1, 'Simulator leaks local credential.');
}
foreach (array('', "secret\r\nX-Evil: yes") as $secret) {
    try { SecurityPolicy::simulatorHeaders($base.'/webhooks/ttn.php', $base, $secret); throw new RuntimeException('Invalid secret accepted.'); }
    catch (DomainException $expected) {}
}
$guard = strpos($source, 'if (!ttnWebhookSecretIsValid(');
checkTtn($guard !== false && $guard < strpos($source, "file_get_contents('php://input')") && $guard < strpos($source, '$pdo2 = dbConfig::getInstance()'), 'Authenticate before reading body or connecting database.');
$simulator = file_get_contents(__DIR__.'/../public/tools/simulator/testttn.php');
checkTtn(str_contains($simulator, 'SecurityPolicy::simulatorHeaders') && str_contains($simulator, 'CURLOPT_FOLLOWLOCATION, false'), 'Safe simulator headers/redirect policy missing.');
echo "TTN webhook security tests passed.\n";
