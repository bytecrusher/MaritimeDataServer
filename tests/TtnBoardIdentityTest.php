<?php
require_once __DIR__ . '/../app/Application/TtnBoardIdentity.php';
require_once __DIR__ . '/../app/Application/TtnPayloadDecoder.php';

function identityCheck($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function rejectedWithoutWrites(PDO $pdo, callable $attempt): void {
    $before = $pdo->query('SELECT * FROM boardConfig ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    try {
        $attempt();
        throw new RuntimeException('Expected identity rejection.');
    } catch (DomainException $expected) {
        identityCheck($before === $pdo->query('SELECT * FROM boardConfig ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), 'Rejected identity changed board data.');
        identityCheck(!$pdo->inTransaction(), 'Failed resolution left a transaction open.');
    }
}
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE boardConfig (id INTEGER PRIMARY KEY, macAddress TEXT CHECK(length(macAddress)<=30), name TEXT, ownerUserId INTEGER, ttnAppId TEXT, ttnDevId TEXT, onDashboard INTEGER, updateDataTimer INTEGER, offlineDataTimer INTEGER)');
$insert = $pdo->prepare('INSERT INTO boardConfig (id, macAddress, ownerUserId, ttnAppId, ttnDevId) VALUES (?, ?, ?, ?, ?)');
$insert->execute(array(1, 'AA:BB:CC:DD:EE:01', 7, 'app-a', 'device-a'));
$insert->execute(array(2, 'AA:BB:CC:DD:EE:02', 8, 'app-b', 'device-b'));
$insert->execute(array(3, 'AA:BB:CC:DD:EE:03', 8, '', ''));
$insert->execute(array(4, 'fakeMacAddresslegacy', 7, 'app-a', '70b3d57ed0050630'));
$a = TtnBoardIdentity::identity('app-a', 'device-a', '70B3D57ED0050630');
// Keep legacy EUI on a separate application so it is not an ambiguous duplicate.
$pdo->exec("UPDATE boardConfig SET ttnAppId='legacy-app' WHERE id=4");
identityCheck((int)TtnBoardIdentity::resolve($pdo, $a, 'AA:BB:CC:DD:EE:01')['id'] === 1, 'Existing binding not preserved.');
identityCheck((int)TtnBoardIdentity::resolve($pdo, $a, null)['id'] === 1, 'MAC-less legacy uplink must resolve by TTN.');
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::resolve($pdo, $a, 'AA:BB:CC:DD:EE:02'));
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::resolve($pdo, $a, 'AA:BB:CC:DD:EE:03'));
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::resolve($pdo, TtnBoardIdentity::identity('app-a', 'wrong', null), 'AA:BB:CC:DD:EE:01'));
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::resolve($pdo, TtnBoardIdentity::identity('other-app', 'device-a', null), 'AA:BB:CC:DD:EE:01'));
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::resolve($pdo, $a, 'AA:BB:CC:DD:EE:99'));

// A compact frame's MAC is attacker-controlled, not identity evidence.
$bytes = array(4, 0, 0xAA, 0xBB, 0xCC, 0xDD, 0xEE, 2, 0, 0, 0);
$decoded = TtnPayloadDecoder::decodeKnownPayload(3, base64_encode(pack('C*', ...$bytes)));
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::resolve($pdo, $a, $decoded['macAddress']));

// A fresh device may create its own unowned board, but not claim an existing one.
$new = TtnBoardIdentity::identity('new-app', 'new-device', null);
$fresh = TtnBoardIdentity::resolve($pdo, $new, 'AA:BB:CC:DD:EE:05');
identityCheck($fresh['ownerUserId'] === null && $fresh['ttnAppId'] === 'new-app', 'Unsafe first import.');
identityCheck(TtnBoardIdentity::resolve($pdo, $new, 'AA:BB:CC:DD:EE:05')['id'] === $fresh['id'], 'Repeated first import duplicated board.');

// Secure first MAC association requires a preconfigured full TTN identity.
$legacy = TtnBoardIdentity::identity('legacy-app', 'legacy-device', '70B3D57ED0050630');
identityCheck(TtnBoardIdentity::resolve($pdo, $legacy, 'AA:BB:CC:DD:EE:04')['macAddress'] === 'AA:BB:CC:DD:EE:04', 'Legacy EUI migration failed.');
foreach (array(array('app-a', ''), array('', 'device-a'), array(null, null)) as $partial) {
    $update = $pdo->prepare('UPDATE boardConfig SET ttnAppId=?, ttnDevId=? WHERE id=3');
    $update->execute($partial);
    rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::resolve($pdo, $a, 'AA:BB:CC:DD:EE:03'));
}
$pdo->exec("UPDATE boardConfig SET ttnAppId='provisioned-app', ttnDevId='provisioned-device' WHERE id=3");
identityCheck((int)TtnBoardIdentity::resolve($pdo, TtnBoardIdentity::identity('provisioned-app', 'provisioned-device', null), 'AA:BB:CC:DD:EE:03')['id'] === 3, 'Provisioned first uplink failed.');
$noMac1 = TtnBoardIdentity::resolve($pdo, TtnBoardIdentity::identity('one', 'same', null), null);
$noMac2 = TtnBoardIdentity::resolve($pdo, TtnBoardIdentity::identity('two', 'same', null), null);
identityCheck($noMac1['macAddress'] !== $noMac2['macAddress'], 'Application-scoped placeholders must differ.');

$forwarded = array('macAddress' => 'AA:BB:CC:DD:EE:01', 'ttnIdentity' => array_merge($a, array('boardId' => 1)));
identityCheck(TtnBoardIdentity::forwardedBoardId($pdo, $forwarded) === 1, 'Valid forwarding failed.');
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::forwardedBoardId($pdo, array_replace($forwarded, array('macAddress' => 'AA:BB:CC:DD:EE:02'))));
$pdo->exec("UPDATE boardConfig SET ttnDevId='rebound' WHERE id=1");
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::forwardedBoardId($pdo, $forwarded));
$pdo->exec("UPDATE boardConfig SET ttnDevId='device-a' WHERE id=1");
$insert->execute(array(99, 'AA:BB:CC:DD:EE:01', 8, 'app-b', 'duplicate'));
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::resolve($pdo, $a, 'AA:BB:CC:DD:EE:01'));
$pdo->exec('DELETE FROM boardConfig WHERE id=99');
$insert->execute(array(99, 'AA:BB:CC:DD:EE:99', 8, 'app-a', '70B3D57ED0050630'));
rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::resolve($pdo, $a, null));
foreach (array(array('', 'device', null), array('app', '', null), array('app', array(), null), array('app', 'device', 'bad-eui')) as $invalid) {
    rejectedWithoutWrites($pdo, fn() => TtnBoardIdentity::identity(...$invalid));
}
echo "TTN board identity tests passed.\n";

// Load only the real webhook helpers, never its request/database entry point.
$webhook = file_get_contents(__DIR__ . '/../app/Http/Webhooks/TTN/ttn.php');
$helpers = strpos($webhook, 'function ttnWebhookSecretIsValid(');
identityCheck($helpers !== false, 'Webhook authentication helper missing.');
eval(substr($webhook, $helpers));
identityCheck(!ttnWebhookSecretIsValid(array(), ''), 'Unconfigured webhook must fail closed.');
identityCheck(!ttnWebhookSecretIsValid(array(), 'test-secret'), 'Missing secret accepted.');
identityCheck(!ttnWebhookSecretIsValid(array('X-MDS-Webhook-Secret' => 'wrong'), 'test-secret'), 'Wrong secret accepted.');
identityCheck(ttnWebhookSecretIsValid(array('x-mds-webhook-secret' => 'test-secret'), 'test-secret'), 'Valid secret rejected.');
identityCheck(!str_contains($webhook, 'updateBoardTTNIdentifiersIfEmpty('), 'Webhook must not silently claim an unbound MAC.');
$ingest = file_get_contents(__DIR__ . '/../app/Http/Ingest/receivejson.php');
$identityGate = strpos($ingest, 'TtnBoardIdentity::forwardedBoardId(');
$firstMutation = strpos($ingest, 'updateBoardFirmwareVersion(');
identityCheck($identityGate !== false && $firstMutation !== false && $identityGate < $firstMutation, 'Ingest identity check must precede mutations.');
echo "TTN webhook authentication and ingest guard tests passed.\n";
