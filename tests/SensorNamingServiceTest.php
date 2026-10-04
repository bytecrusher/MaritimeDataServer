<?php

require_once dirname(__DIR__) . '/app/Application/SensorNamingService.php';
require_once dirname(__DIR__) . '/app/Application/TtnMeasurementSensorFactory.php';

function assertSensorNamingValue($expected, $actual, $message)
{
    if ($expected === $actual) {
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
    fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
    exit(1);
}

$post = array('name' => 'ADC', 'nameValue1' => 'Batterie 1', 'nameValue2' => ' Kapazitaet ');
assertSensorNamingValue('Batterie 1', SensorNamingService::submittedChannelName($post, 1), 'The channel name must not be replaced by the sensor group name.');
assertSensorNamingValue('Kapazitaet', SensorNamingService::submittedChannelName($post, 2), 'Submitted channel names must be trimmed.');
assertSensorNamingValue(null, SensorNamingService::submittedChannelName($post, 3), 'Missing channel fields must preserve the stored name.');
assertSensorNamingValue('Value 4', SensorNamingService::submittedChannelName(array('nameValue4' => ' '), 4), 'An explicitly empty channel name needs a visible fallback.');

$batteryNames = SensorNamingService::defaultChannelNames('ADC', 'Battery');
assertSensorNamingValue('Voltage', $batteryNames[1] ?? null, 'Battery channel 1 must have a semantic default name.');
assertSensorNamingValue('Capacity', $batteryNames[2] ?? null, 'Battery channel 2 must have a semantic default name.');
assertSensorNamingValue(2, SensorNamingService::defaultUsedChannelCount('ADC', 'Battery'), 'Battery uses two payload channels.');

$tankNames = SensorNamingService::defaultChannelNames('ADC', 'Tanks');
assertSensorNamingValue('Tank 2 ADC', $tankNames[4] ?? null, 'Tank channel names must describe their payload values.');
assertSensorNamingValue(4, SensorNamingService::defaultUsedChannelCount('ADC', 'Tanks'), 'Tanks use all four payload channels.');

$statusNames = SensorNamingService::defaultChannelNames('Digital', 'Status');
assertSensorNamingValue('Main Power', $statusNames[1] ?? null, 'Status channel 1 must describe the battery main switch input.');
assertSensorNamingValue('Relay', $statusNames[2] ?? null, 'Status channel 2 must describe the relay state.');
assertSensorNamingValue(null, $statusNames[3] ?? null, 'The DS18B20 temperature must not be assigned to the Digital status group.');
assertSensorNamingValue(2, SensorNamingService::defaultUsedChannelCount('Digital', 'Status'), 'Status uses only main power and relay channels.');

$ds18b20Names = SensorNamingService::defaultChannelNames('DS18B20', 'DS18B20');
assertSensorNamingValue('Temperature', $ds18b20Names[1] ?? null, 'The DS18B20 value must have a temperature channel.');
assertSensorNamingValue(1, SensorNamingService::defaultUsedChannelCount('DS18B20', 'DS18B20'), 'A named DS18B20 payload uses one channel.');

$ttnSensors = TtnMeasurementSensorFactory::buildStatusAndTemperatureSensors(
    array('date' => '06.08.2026', 'time' => '12:00:00', 'transmissionPath' => '2'),
    1,
    2,
    18.75
);
assertSensorNamingValue('Digital', $ttnSensors[0]['sensorType'] ?? null, 'TTN status values must retain the Digital type.');
assertSensorNamingValue(null, $ttnSensors[0]['value3'] ?? null, 'TTN status must no longer contain the DS18B20 temperature.');
assertSensorNamingValue('DS18B20', $ttnSensors[1]['sensorType'] ?? null, 'TTN temperature must target the DS18B20 type.');
assertSensorNamingValue(18.75, $ttnSensors[1]['value1'] ?? null, 'TTN temperature must be forwarded as DS18B20 value 1.');

$legacyAdcNames = SensorNamingService::legacyTypeChannelNames('ADC');
assertSensorNamingValue('ADC1', $legacyAdcNames[1] ?? null, 'Legacy ADC defaults must be recognizable for safe repair.');
assertSensorNamingValue('level2', $legacyAdcNames[4] ?? null, 'All legacy ADC channels must be recognizable.');

fwrite(STDOUT, "Sensor naming tests passed.\n");
