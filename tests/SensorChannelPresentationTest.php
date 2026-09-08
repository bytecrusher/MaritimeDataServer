<?php
require_once __DIR__ . '/../app/Application/SensorChannelPresentation.php';

function checkPresentation($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
$type = array('name' => 'ADC', 'siUnitVal1' => 'V', 'siUnitVal2' => 'V', 'siUnitVal3' => 'V', 'siUnitVal4' => 'V');
$sensor = array('name' => 'Renamed', 'sensorAddress' => 'bm94b97efef540tank');
foreach (range(1, 4) as $nr) {
    $channel = array('channelNr' => $nr, 'name' => 'Tank ' . ($nr < 3 ? 1 : 2), 'GaugeMinValue' => 0, 'GaugeMaxValue' => 20);
    $view = SensorChannelPresentation::resolve($sensor, $type, $channel);
    checkPresentation($view['chartKey'] === 'other', 'Tank values must not flatten voltage charts.');
    checkPresentation($view['technical'] === ($nr % 2 === 0), 'Only ADC diagnostics are hidden.');
    if ($nr % 2 === 1) {
        checkPresentation($view['unit'] === '%' && $view['channel']['GaugeMaxValue'] === 100, 'Fill level needs a percent scale.');
        checkPresentation(str_contains($view['channel']['name'], 'Füllstand'), 'Fill level needs a clear label.');
    }
}
$custom = array('channelNr' => 1, 'name' => 'Fresh water', 'GaugeMinValue' => 10, 'GaugeMaxValue' => 90);
$view = SensorChannelPresentation::resolve($sensor, $type, $custom);
checkPresentation($view['channel'] === $custom, 'Preserve custom names and limits.');
$battery = array('name' => 'Renamed battery', 'sensorAddress' => 'bm94b97efef540bat');
foreach (array(1 => 'adc', 2 => 'other') as $nr => $key) {
    $view = SensorChannelPresentation::resolve($battery, $type, array('channelNr' => $nr));
    checkPresentation($view['chartKey'] === $key && !$view['technical'], 'Separate capacity from voltage.');
}
$view = SensorChannelPresentation::resolve(array('name' => 'Custom ADC'), $type, $custom);
checkPresentation($view['channel'] === $custom && $view['unit'] === 'V' && !$view['technical'], 'Unrelated ADC channels are unchanged.');
echo "Sensor channel presentation tests passed.\n";
