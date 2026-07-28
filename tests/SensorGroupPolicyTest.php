<?php

require_once dirname(__DIR__) . '/app/Application/SensorGroupPolicy.php';

function assertSingletonPolicy($expected, $typeName, $message)
{
    $actual = SensorGroupPolicy::isSingletonType($typeName);
    if ($expected === $actual) {
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Type: ' . var_export($typeName, true) . PHP_EOL);
    exit(1);
}

foreach (array('GPS', 'Lora', 'WakeupStan', 'Wakeup', 'WakeupLog', 'OtaStatus', 'OTA Status') as $singletonType) {
    assertSingletonPolicy(true, $singletonType, 'Protected sensor groups must be singletons per board.');
}

foreach (array('ADC', 'Digital', 'DS18B20', 'BME280') as $repeatableType) {
    assertSingletonPolicy(false, $repeatableType, 'Regular sensor groups must remain repeatable.');
}

fwrite(STDOUT, "SensorGroupPolicy tests passed.\n");
