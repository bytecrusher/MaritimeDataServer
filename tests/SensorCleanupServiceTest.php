<?php

require_once dirname(__DIR__) . '/app/Application/SensorCleanupService.php';

function assertCleanupCandidate($expected, $typeName, $lastReading, $hasActiveAlert, $cutoff, $message)
{
    $actual = SensorCleanupService::isCleanupCandidate(
        $typeName,
        $lastReading,
        $hasActiveAlert,
        $cutoff
    );
    if ($actual !== $expected) {
        fwrite(STDERR, "FAILED: {$message}\n");
        exit(1);
    }
}

$cutoff = strtotime('2026-06-24 12:00:00');

assertCleanupCandidate(true, 'ADC', null, false, $cutoff, 'A sensor without readings should be removable.');
assertCleanupCandidate(true, 'BME280', '2026-05-01 12:00:00', false, $cutoff, 'An inactive sensor should be removable.');
assertCleanupCandidate(false, 'GPS', '2026-07-20 12:00:00', false, $cutoff, 'A current sensor should remain.');
assertCleanupCandidate(false, 'WakeupStan', '2026-05-01 12:00:00', false, $cutoff, 'Wakeup events must remain protected.');
assertCleanupCandidate(false, 'OtaStatus', null, false, $cutoff, 'OTA status sensors must remain protected.');
assertCleanupCandidate(false, 'ADC', '2026-05-01 12:00:00', true, $cutoff, 'Sensors with an active alert must remain protected.');

echo "SensorCleanupService tests passed.\n";
