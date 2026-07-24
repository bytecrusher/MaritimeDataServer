<?php

require_once dirname(__DIR__) . '/app/Application/SensorNamingService.php';

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

$legacyAdcNames = SensorNamingService::legacyTypeChannelNames('ADC');
assertSensorNamingValue('ADC1', $legacyAdcNames[1] ?? null, 'Legacy ADC defaults must be recognizable for safe repair.');
assertSensorNamingValue('level2', $legacyAdcNames[4] ?? null, 'All legacy ADC channels must be recognizable.');

fwrite(STDOUT, "Sensor naming tests passed.\n");
