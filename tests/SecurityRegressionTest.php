<?php
require_once __DIR__ . '/../app/Application/SecurityPolicy.php';
require_once __DIR__ . '/../app/Application/BoardCredential.php';
require_once __DIR__ . '/../app/Application/AccountActivation.php';
require_once __DIR__ . '/../app/Application/TemperatureAlertTransfer.php';
function secureCheck($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function denied(callable $call) {
    try { $call(); } catch (DomainException $expected) { return; }
    throw new RuntimeException('Expected rejection.');
}
foreach (array(array('', ''), array(null, ''), array('0e123', '0e456'), array('secret', array()), array(' ', ' ')) as $pair) {
    secureCheck(!SecurityPolicy::secretMatches(...$pair), 'Empty/type-juggled secret accepted.');
}
secureCheck(SecurityPolicy::secretMatches('legitimate', 'legitimate'), 'Valid key rejected.');
foreach (array('javascript:alert(1)', 'https://user:pass@example.org', 'https://example.org/?x=1', '//evil.test') as $url) {
    secureCheck(SecurityPolicy::canonicalUrl($url) === '', 'Invalid canonical URL accepted.');
}
secureCheck(SecurityPolicy::canonicalUrl('https://example.org/mds/') === 'https://example.org/mds', 'Subpath lost.');
$now = time();
$sig = SecurityPolicy::forwardingSignature('{}', $now, 'webhook-secret');
secureCheck(SecurityPolicy::validForwarding('{}', $now, $sig, 'webhook-secret'), 'Valid TTN forwarding rejected.');
secureCheck(!SecurityPolicy::validForwarding('{"tampered":true}', $now, $sig, 'webhook-secret'), 'Tampered body accepted.');
secureCheck(!SecurityPolicy::validForwarding('{}', $now - 120, $sig, 'webhook-secret'), 'Expired forwarding accepted.');
secureCheck(!SecurityPolicy::validForwarding('{}', $now, $sig, ''), 'Unconfigured forwarding accepted.');
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE boardConfig (id INTEGER PRIMARY KEY, macAddress TEXT)');
$pdo->exec("INSERT INTO boardConfig VALUES (1,'AA:BB:CC:DD:EE:01'),(2,'AA:BB:CC:DD:EE:02')");
$key = str_repeat('a', 64);
$keys = array(1 => hash('sha256', $key));
secureCheck((int)BoardCredential::resolve($pdo, $key, 'aa-bb-cc-dd-ee-01', $keys)['id'] === 1, 'Board key failed.');
denied(fn() => BoardCredential::resolve($pdo, $key, 'AA:BB:CC:DD:EE:02', $keys));
denied(fn() => BoardCredential::resolve($pdo, str_repeat('b', 64), 'AA:BB:CC:DD:EE:01', $keys));
secureCheck(BoardCredential::boardId($key, array()) === null, 'Revoked credential accepted.');
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, active INTEGER)');
$pdo->exec('CREATE TABLE accountActivationTokens (userId INTEGER PRIMARY KEY, tokenHash TEXT, expiresAt INTEGER)');
$pdo->exec('INSERT INTO users VALUES (1,0),(2,0)');
$token = AccountActivation::issue($pdo, 1);
secureCheck(!AccountActivation::consume($pdo, 1, ''), 'Tokenless activation accepted.');
secureCheck(!AccountActivation::consume($pdo, 2, $token), 'Cross-user activation accepted.');
secureCheck(AccountActivation::consume($pdo, 1, $token), 'Valid activation failed.');
$pdo->exec('UPDATE users SET active=0 WHERE id=1');
secureCheck(!AccountActivation::consume($pdo, 1, $token), 'Activation replay accepted.');
$old = AccountActivation::issue($pdo, 2);
$new = AccountActivation::issue($pdo, 2);
secureCheck(!AccountActivation::consume($pdo, 2, $old), 'Resent token not revoked.');
$pdo->exec('UPDATE accountActivationTokens SET expiresAt=0');
secureCheck(!AccountActivation::consume($pdo, 2, $new), 'Expired activation accepted.');
$pdo->exec('CREATE TABLE sensorConfig (id INTEGER PRIMARY KEY, boardId INTEGER, name TEXT, typId INTEGER)');
$pdo->exec("INSERT INTO sensorConfig VALUES (10,1,'Status',1),(11,1,'DS18B20',2),(20,2,'Status',1)");
SecurityPolicy::assertSensorBoard($pdo, 10, 1);
denied(fn() => SecurityPolicy::assertSensorBoard($pdo, 20, 1));
denied(fn() => SecurityPolicy::assertSensorBoard($pdo, '10 OR 1=1', 1));
$pdo->exec('CREATE TABLE sensorTypes (id INTEGER PRIMARY KEY, name TEXT)');
$pdo->exec("INSERT INTO sensorTypes VALUES (1,'Digital'),(2,'DS18B20')");
$pdo->exec('CREATE TABLE sensorChannelConfig (id INTEGER PRIMARY KEY, sensorConfigId INTEGER, channelNr INTEGER, name TEXT, GaugeMinValue INTEGER, AlertEnabled INTEGER, AlertLowValue REAL, AlertHighValue REAL, AlertState TEXT, LastAlertSentAt TEXT, onDashboard INTEGER)');
$pdo->exec("INSERT INTO sensorChannelConfig VALUES (30,10,3,'Battery temp',-20,1,2,70,'high','2026-10-01',1),(31,11,1,'Temperature',0,0,NULL,NULL,NULL,NULL,1)");
$pdo->exec('CREATE TABLE temperatureChannelTransfers (sourceChannelId INTEGER PRIMARY KEY, targetChannelId INTEGER, sourceSnapshot TEXT, targetSnapshot TEXT)');
$pdo->exec('CREATE TABLE sensor_permissions (id INTEGER PRIMARY KEY, sensorId INTEGER, userId INTEGER, role TEXT, canView INTEGER, canEdit INTEGER, canReceiveAlerts INTEGER)');
$pdo->exec("INSERT INTO sensor_permissions VALUES (1,10,9,'observer',1,0,1)");
secureCheck(TemperatureAlertTransfer::migrate($pdo, 1, 11), 'Alert migration failed.');
$moved = $pdo->query('SELECT * FROM sensorChannelConfig WHERE id=31')->fetch(PDO::FETCH_ASSOC);
secureCheck($moved['AlertEnabled'] === 1 && $moved['AlertHighValue'] == 70 && $moved['GaugeMinValue'] === -20 && $moved['AlertState'] === 'high', 'Alert/gauge/state lost.');
secureCheck((int)$pdo->query('SELECT AlertEnabled FROM sensorChannelConfig WHERE id=30')->fetchColumn() === 0, 'Old alert not retired.');
secureCheck((int)$pdo->query('SELECT canReceiveAlerts FROM sensor_permissions WHERE sensorId=11 AND userId=9')->fetchColumn() === 1, 'Shared alert recipient lost.');
$pdo->exec('UPDATE sensorChannelConfig SET AlertHighValue=80 WHERE id=31');
secureCheck(TemperatureAlertTransfer::migrate($pdo, 1, 11), 'Idempotent migration failed.');
secureCheck((int)$pdo->query('SELECT AlertHighValue FROM sensorChannelConfig WHERE id=31')->fetchColumn() === 80, 'Rerun overwrote later changes.');
$pdo->exec('DELETE FROM temperatureChannelTransfers');
$pdo->exec('UPDATE sensorChannelConfig SET AlertEnabled=1 WHERE id=30');
denied(fn() => TemperatureAlertTransfer::migrate($pdo, 1, 11));
secureCheck((int)$pdo->query('SELECT AlertEnabled FROM sensorChannelConfig WHERE id=30')->fetchColumn() === 1 && !$pdo->inTransaction(), 'Conflict lost legacy alarm or transaction.');

$root = dirname(__DIR__);
$configSource = file_get_contents($root . '/app/Infrastructure/Config/configuration.php');
secureCheck(!str_contains($configSource, 'self::$baseurl = $prefix'), 'Host-derived outbound URL returned.');
foreach (array('getGpsData.php','getBoardName.php') as $file) {
    $source = file_get_contents($root . '/app/Http/Api/' . $file);
    secureCheck(str_contains($source, 'canUserAccessSensor') && str_contains($source, "\$_SESSION['userId']"), 'GPS authorization guard missing.');
}
$source = file_get_contents($root . '/app/Http/Ingest/sensormetadata.php');
secureCheck(str_contains($source, 'BoardCredential::resolve') && !str_contains($source, 'config::$apiKey'), 'Global metadata access returned.');
$source = file_get_contents($root . '/app/Application/SettingsPageService.php');
secureCheck(str_contains($source, "'currentLogContent' => \$isAdmin ?") && str_contains($source, "'apiKey' => \$isAdmin ?"), 'Settings disclosures returned.');
secureCheck(strpos($source, '2026-07-28_singleton_sensor_groups.sql') < strpos($source, '2026-10-05_account_activation.sql'), 'Existing migration dispatcher indexes shifted.');
secureCheck(str_contains($source, 'foreach (array(7, 8) as $index)'), 'Security migrations are not executable from settings.');
$source = file_get_contents($root . '/app/Application/dbUpdateData.php');
secureCheck(str_contains($source, 'Board claiming requires administrator approval.'), 'Public identifier claim returned.');
$source = file_get_contents($root . '/public/install/api_createAdmin.php');
secureCheck(str_contains($source, "filter_var(\$_POST['demoMode'] ?? false, FILTER_VALIDATE_BOOLEAN)") && str_contains($source, 'strlen($var_apiKey) < 16'), 'Installer key/boolean validation missing.');
foreach (array('false', false, '0', 0) as $value) secureCheck(filter_var($value, FILTER_VALIDATE_BOOLEAN) === false, 'False demo mode became true.');
foreach (array('.htaccess', 'public/.htaccess') as $file) {
    $rules = file_get_contents($root . '/' . $file);
    secureCheck(str_contains($rules, 'Require all denied') && str_contains($rules, 'logs(?:/|$)'), 'Legacy secret/log protection missing.');
}
echo "Security regression tests passed (credentials, activation, forwarding, ownership, alarm migration, guards).\n";
