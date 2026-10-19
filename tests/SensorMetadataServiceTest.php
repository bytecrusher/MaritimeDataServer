<?php

require_once dirname(__DIR__) . '/app/Application/SensorMetadataService.php';

function assertSensorMetadataSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

assertSensorMetadataSame(
    'bm94b97efef540bat',
    SensorMetadataService::stableAddress('94:B9:7E:FE:F5:40', 'bat'),
    'Stable sensor address must be deterministic.'
);
assertSensorMetadataSame(null, SensorMetadataService::stableAddress('invalid', 'bat'), 'Invalid MAC must be rejected.');
assertSensorMetadataSame(null, SensorMetadataService::stableAddress('94:B9:7E:FE:F5:40', 'unknown'), 'Unknown key must be rejected.');
assertSensorMetadataSame('temp', SensorMetadataService::keyForSensor('DS18B20', 'DS18B20'), 'Temperature mapping is missing.');
assertSensorMetadataSame('tank', SensorMetadataService::keyForSensor('ADC', 'Tanks'), 'Tank mapping is missing.');

$first = SensorMetadataService::metadataHash(array(
    array('key' => 'tank', 'name' => 'Fresh water'),
    array('key' => 'bat', 'name' => 'House battery'),
));
$second = SensorMetadataService::metadataHash(array(
    array('key' => 'bat', 'name' => 'House battery'),
    array('key' => 'tank', 'name' => 'Fresh water'),
));
assertSensorMetadataSame($first, $second, 'Metadata hash must not depend on response ordering.');
if (!preg_match('/^[0-9a-f]{8}$/', $first)) {
    throw new RuntimeException('Metadata hash must be an eight-character hexadecimal value.');
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE sensorTypes (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
$pdo->exec('CREATE TABLE sensorConfig (id INTEGER PRIMARY KEY, boardId INTEGER NOT NULL, typId INTEGER NOT NULL, name TEXT, sensorAddress TEXT)');
$pdo->exec("INSERT INTO sensorTypes (id, name) VALUES (1, 'ADC')");
$pdo->exec("INSERT INTO sensorConfig (id, boardId, typId, name) VALUES (10, 7, 1, 'MDS Battery')");

$pulled = SensorMetadataService::synchronize($pdo, 7, '94:B9:7E:FE:F5:40', array(
    array('key' => 'bat', 'name' => 'Device Battery'),
), false);
assertSensorMetadataSame('MDS Battery', $pulled[0]['name'], 'Initial device sync must preserve an existing MDS name.');
assertSensorMetadataSame('bm94b97efef540bat', $pulled[0]['sensorAddress'], 'Initial sync must bind the stable address.');

$pushed = SensorMetadataService::synchronize($pdo, 7, '94:B9:7E:FE:F5:40', array(
    array('key' => 'bat', 'name' => 'House Battery'),
), true);
assertSensorMetadataSame('House Battery', $pushed[0]['name'], 'Explicit device changes must update the MDS name.');

echo "SensorMetadataService tests passed.\n";
