<?php
require_once dirname(__DIR__) . '/app/Application/TtnMeasurementSensorFactory.php';
function checkTemperature($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
foreach (array(null, '', 'missing', false, -127, -99.9, 126, INF, NAN) as $invalid) {
    checkTemperature(!TemperatureReading::valid($invalid), 'Invalid temperature must not be accepted.');
    $sensors = TtnMeasurementSensorFactory::buildStatusAndTemperatureSensors(array(), 1, 0, $invalid);
    checkTemperature(count($sensors) === 1 && $sensors[0]['sensorType'] === 'Digital', 'Missing temperature must not suppress independent status data or create a fake zero.');
}
foreach (array(0, '0', -55, 125, 21.6) as $valid) {
    $sensors = TtnMeasurementSensorFactory::buildStatusAndTemperatureSensors(array(), 1, 0, $valid);
    checkTemperature(count($sensors) === 2 && $sensors[1]['value1'] === $valid, 'Real temperatures including zero must survive unchanged.');
}
$defaults = TemperatureReading::channelDefaults(array('GaugeMinValue' => '0', 'GaugeMaxValue' => '1', 'AlertLowValue' => 4));
checkTemperature($defaults['GaugeMinValue'] === -55 && $defaults['GaugeMaxValue'] === 125 && $defaults['AlertLowValue'] === 4, 'Only placeholder range is repaired; alarms survive.');
$custom = array('GaugeMinValue' => -10, 'GaugeMaxValue' => 40);
checkTemperature(TemperatureReading::channelDefaults($custom) === $custom, 'Custom ranges must remain unchanged.');
$boolean = TemperatureReading::channelDefaults(array('GaugeMinValue' => 0, 'GaugeMaxValue' => 1, 'GaugeRedAreaLowValue' => 0, 'GaugeRedAreaHighValue' => 2));
checkTemperature($boolean['GaugeRedAreaLowValue'] === -10 && $boolean['GaugeRedAreaHighValue'] === 80, 'Boolean warning bands must not turn all real temperatures red.');
echo "Temperature reading tests passed.\n";
