<?php
require_once __DIR__ . '/../app/Application/TtnWebhookTarget.php';

function checkTtn($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$source = file_get_contents(__DIR__ . '/../app/Http/Webhooks/TTN/ttn.php');
$start = strpos($source, 'function ttnWebhookSecretIsValid(');
$end = strpos($source, 'function ttnSelectBestRxMetadata(');
// Exercise the pure helpers without booting the webhook or touching a database.
eval(substr($source, $start, $end - $start));
unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
checkTtn(!ttnWebhookSecretIsValid(array(), ''), 'Empty secret must fail closed.');
checkTtn(!ttnWebhookSecretIsValid(array(), 'secret'), 'Missing credential must fail.');
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
foreach (array('', '//evil.test', 'ftp://evil.test', 'https://user:pass@evil.test', "https://good.test\n", 'https://good.test?x=1', 'https://good.test/#x', 'https://good.test\\@evil.test') as $url) {
    checkTtn(TtnWebhookTarget::validate($url)==='', 'Unsafe URL accepted.');
}
checkTtn(TtnWebhookTarget::validate('https://example.test/mds/')==='https://example.test/mds', 'Subpath lost.');
$old = getenv('MDS_BASE_URL');
putenv('MDS_BASE_URL=https://example.test/mds');
$_SERVER['HTTP_HOST']='attacker.test';
$_SERVER['HTTP_X_FORWARDED_PROTO']='http';
checkTtn(TtnWebhookTarget::baseUrl()==='https://example.test/mds', 'Host influenced forwarding target.');
$old === false ? putenv('MDS_BASE_URL') : putenv('MDS_BASE_URL='.$old);
checkTtn(strpos($source, 'if (!ttnWebhookSecretIsValid(') < strpos($source, "file_get_contents('php://input')"), 'Authenticate before reading payload.');
checkTtn(strpos($source, '$url = $forwardingBaseUrl') !== false, 'Canonical forwarding target not wired.');
$simulator = file_get_contents(__DIR__.'/../public/tools/simulator/testttn.php');
checkTtn(strpos($simulator, '$normalizedUrl === $localTarget') !== false, 'Simulator must scope secret to canonical endpoint.');
checkTtn(strpos($simulator, 'CURLOPT_FOLLOWLOCATION, false') !== false, 'Simulator redirects must remain disabled.');
echo "TTN webhook security tests passed.\n";
