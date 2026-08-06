<?php

require_once dirname(__DIR__) . '/app/Application/DeviceEventTimelineCalculator.php';

function eventAt($stateClass, $timestamp, $persistentOnline = false)
{
    return array(
        'label' => $stateClass === 'is-wakeup' ? 'Wakeup' : 'Standby',
        'stateClass' => $stateClass,
        'timestamp' => $timestamp,
        'persistentOnline' => $persistentOnline,
    );
}

function assertSameValue($expected, $actual, $message)
{
    if ($expected === $actual) {
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
    fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
    exit(1);
}

$windowStart = new DateTimeImmutable('2026-07-14 10:00:00');
$windowEnd = new DateTimeImmutable('2026-07-14 11:00:00');
$cycleEvents = array(
    eventAt('is-wakeup', '14.07.2026 10:00:00'),
    eventAt('is-standby', '14.07.2026 10:01:00'),
);
$cycleSummary = DeviceEventTimelineCalculator::buildWindowSummary($cycleEvents, $windowStart, $windowEnd);
assertSameValue(0.02, $cycleSummary['onlineHours'], 'Wakeup duration must end at the next standby event.');
assertSameValue(0.98, $cycleSummary['standbyHours'], 'Standby duration must continue to the end of the window.');

$openWakeupEvents = array(eventAt('is-wakeup', '14.07.2026 10:15:00'));
$openSummary = DeviceEventTimelineCalculator::buildWindowSummary($openWakeupEvents, $windowStart, $windowEnd);
assertSameValue(0.0, $openSummary['onlineHours'], 'A non-final wakeup event must not count as permanently online.');
$openDetails = DeviceEventTimelineCalculator::addDurationDetails($openWakeupEvents, $windowEnd);
assertSameValue(true, $openDetails[0]['durationOpen'], 'A non-final wakeup duration must remain open.');

$persistentEvents = array(eventAt('is-wakeup', '14.07.2026 10:15:00', true));
$persistentSummary = DeviceEventTimelineCalculator::buildWindowSummary($persistentEvents, $windowStart, $windowEnd);
assertSameValue(0.75, $persistentSummary['onlineHours'], 'Always-online state must continue to the end of the window.');

$crossMidnightEvents = array(
    eventAt('is-wakeup', '14.07.2026 23:30:00'),
    eventAt('is-standby', '15.07.2026 00:30:00'),
);
$dailySummary = DeviceEventTimelineCalculator::buildDailySummary(
    $crossMidnightEvents,
    array('2026-07-14', '2026-07-15'),
    new DateTimeImmutable('2026-07-14 00:00:00'),
    new DateTimeImmutable('2026-07-16 00:00:00')
);
assertSameValue(array(0.5, 0.5), $dailySummary['onlineDailyHours'], 'Online duration must be split at midnight.');
assertSameValue(array(0.0, 23.5), $dailySummary['standbyDailyHours'], 'Standby duration must be assigned to the correct day.');

$duplicates = array(
    eventAt('is-wakeup', '14.07.2026 10:00:00'),
    eventAt('is-wakeup', '14.07.2026 10:00:30'),
    eventAt('is-standby', '14.07.2026 10:01:00'),
);
$normalized = DeviceEventTimelineCalculator::normalizeSequence($duplicates);
assertSameValue(2, count($normalized), 'Repeated equal states must collapse into one transition.');
assertSameValue('14.07.2026 10:00:00', $normalized[0]['timestamp'], 'The first equal state must remain the start of its phase.');

$deviceCycleEvents = array(
    eventAt('is-standby', '14.07.2026 10:00:00'),
    eventAt('is-wakeup', '14.07.2026 10:15:00'),
    eventAt('is-standby', '14.07.2026 10:16:00'),
);
$deviceCycleSummary = DeviceEventTimelineCalculator::buildWindowSummary(
    $deviceCycleEvents,
    new DateTimeImmutable('2026-07-14 10:00:00'),
    new DateTimeImmutable('2026-07-14 10:16:00'),
    1920
);
assertSameValue(0.02, $deviceCycleSummary['onlineHours'], 'Wakeup time must run from wakeup to the following standby.');
assertSameValue(0.25, $deviceCycleSummary['standbyHours'], 'Standby time must run from standby to the following wakeup.');

$missingEventGap = array(
    eventAt('is-wakeup', '14.07.2026 10:00:00'),
    eventAt('is-standby', '14.07.2026 12:00:00'),
);
$missingEventSummary = DeviceEventTimelineCalculator::buildWindowSummary(
    $missingEventGap,
    new DateTimeImmutable('2026-07-14 10:00:00'),
    new DateTimeImmutable('2026-07-14 12:00:00'),
    1920
);
assertSameValue(0.0, $missingEventSummary['onlineHours'], 'A long event gap must not be counted as continuous wakeup time.');
$missingEventDetails = DeviceEventTimelineCalculator::addDurationDetails(
    $missingEventGap,
    new DateTimeImmutable('2026-07-14 12:00:00'),
    1920
);
assertSameValue(true, $missingEventDetails[0]['durationUnknown'], 'A long event gap must be marked as an unknown duration.');

$persistentGap = array(
    eventAt('is-wakeup', '14.07.2026 10:00:00', true),
    eventAt('is-standby', '14.07.2026 12:00:00'),
);
$persistentGapSummary = DeviceEventTimelineCalculator::buildWindowSummary(
    $persistentGap,
    new DateTimeImmutable('2026-07-14 10:00:00'),
    new DateTimeImmutable('2026-07-14 12:00:00'),
    1920
);
assertSameValue(2.0, $persistentGapSummary['onlineHours'], 'An explicit always-online phase must remain valid across a long gap.');

fwrite(STDOUT, "DeviceEventTimelineCalculator tests passed.\n");
