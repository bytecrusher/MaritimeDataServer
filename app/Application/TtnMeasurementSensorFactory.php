<?php
require_once __DIR__ . '/TemperatureReading.php';

final class TtnMeasurementSensorFactory
{
    public static function buildStatusAndTemperatureSensors(
        array $commonSensorFields,
        $mainPowerOn,
        $relay,
        $temperature
    ) {
        $sensors = array(
            array_merge($commonSensorFields, array(
                'sensorType' => 'Digital',
                'type' => 'Digital',
                'sensorName' => 'Status',
                'name' => 'Status',
                'value1' => $mainPowerOn,
                'value2' => $relay,
            )),
        );
        if (TemperatureReading::valid($temperature)) {
            $sensors[] = array_merge($commonSensorFields, array(
                'sensorType' => 'DS18B20',
                'type' => 'DS18B20',
                'sensorName' => 'DS18B20',
                'name' => 'DS18B20',
                'value1' => $temperature,
            ));
        }
        return $sensors;
    }
}
