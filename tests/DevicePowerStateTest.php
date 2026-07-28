<?php

require_once dirname(__DIR__) . '/app/Application/DevicePowerState.php';

function assertDeviceState($expected, $actual, $message)
{
    if ($expected === $actual) {
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
    fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
    exit(1);
}

assertDeviceState('wakeup', DevicePowerState::fromSensorRow(array(
    'value1' => 'Sleep standby',
    'value2' => '28.07.2026 10:00:00',
    'value3' => 'Wakeup Timer',
    'value4' => '28.07.2026 10:15:00',
)), 'The newer wakeup transition in value3 must define the current state.');

assertDeviceState('standby', DevicePowerState::fromSensorRow(array(
    'value1' => 'Standby',
    'value2' => '28.07.2026 10:16:00',
    'value3' => '',
    'value4' => '',
)), 'A standalone standby transition must remain standby.');

assertDeviceState('always_online', DevicePowerState::fromSensorRow(array(
    'value1' => 'Always online',
    'value2' => '28.07.2026 10:16:00',
)), 'Always-online rows must retain their persistent state.');

fwrite(STDOUT, "DevicePowerState tests passed.\n");
