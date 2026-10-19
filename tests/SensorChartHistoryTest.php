<?php
require_once dirname(__DIR__) . '/app/Application/SensorChartHistory.php';

function checkHistory($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$from = strtotime('2026-09-01T00:00:00Z');
$to = $from + 7 * 86400;
foreach (array('UTC', 'America/New_York', 'Asia/Tokyo') as $zone) {
    date_default_timezone_set($zone);
    checkHistory(SensorChartHistory::timestamp(array('val_date' => '02.09.2026', 'val_time' => '12:00:00')) === strtotime('2026-09-02T10:00:00Z'), 'Device timezone must not depend on PHP or browser timezone.');
}
checkHistory(SensorChartHistory::timestamp(array('val_date' => '31.02.2026', 'val_time' => '12:00:00', 'receivedTimestamp' => $from)) === $from, 'Invalid device date falls back to database instant.');
checkHistory(SensorChartHistory::timestamp(array()) === null, 'Missing timestamps must not create phantom points.');
checkHistory(SensorChartHistory::timestamp(array('val_date' => '29.03.2026', 'val_time' => '02:30:00', 'receivedTimestamp' => $from)) === $from, 'Nonexistent DST wall times must fall back to the receipt timestamp.');
checkHistory(SensorChartHistory::timestamp(array('val_date' => '01.01.2026', 'val_time' => '12:00:00')) === strtotime('2026-01-01T11:00:00Z'), 'Winter offset must differ from summer.');
function historyRows($from, $to) {
    for ($time = $from - 120, $id = 1; $time <= $to + 120; $time += 120, $id++) {
        yield array('id' => $id, 'receivedTimestamp' => $time, 'value1' => $id === 500 ? 999 : 12, 'value2' => $id === 600 ? null : 20, 'value3' => 0, 'value4' => 1);
    }
}
$result = SensorChartHistory::summarize(historyRows($from, $to), $from, $to);
checkHistory(count($result) <= 1000, 'Dense history response must stay bounded.');
checkHistory($result[0]['timestamp'] === $from * 1000, 'Seven days must include the start, not just the latest readings.');
checkHistory(end($result)['timestamp'] === $to * 1000, 'Window must include the latest point.');
checkHistory(in_array(999, array_column($result, 'value1'), true), 'Downsampling must retain peaks.');
checkHistory(in_array(null, array_column($result, 'value2'), true), 'Downsampling must retain missing values.');
checkHistory(count(SensorChartHistory::summarize(historyRows($from, $from + 240), $from, $from + 240)) === 3, 'Sparse data must remain complete.');
checkHistory(SensorChartHistory::summarize(array(), $from, $to) === array(), 'Empty history remains empty.');
echo "Sensor chart history tests passed.\n";
