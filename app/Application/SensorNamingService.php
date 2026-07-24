<?php

class SensorNamingService
{
    public static function submittedChannelName(array $post, $channelNr, $fallback = null)
    {
        $key = 'nameValue' . (int)$channelNr;
        if (!array_key_exists($key, $post)) {
            return $fallback;
        }

        $name = trim((string)$post[$key]);
        return $name !== '' ? $name : 'Value ' . (int)$channelNr;
    }

    public static function defaultChannelNames($sensorType, $sensorName)
    {
        $key = strtolower(trim((string)$sensorType)) . ':' . strtolower(trim((string)$sensorName));
        $channelNames = array(
            'adc:battery' => array(1 => 'Voltage', 2 => 'Capacity', 3 => 'Value 3', 4 => 'Value 4'),
            'adc:tanks' => array(1 => 'Tank 1', 2 => 'Tank 1 ADC', 3 => 'Tank 2', 4 => 'Tank 2 ADC'),
            'digital:status' => array(1 => 'Alarm', 2 => 'Relay', 3 => 'Temperature', 4 => 'Status'),
            'gps:gps' => array(1 => 'Latitude', 2 => 'Longitude', 3 => 'Speed', 4 => 'Course'),
            'bme280:environment' => array(1 => 'Temperature', 2 => 'Humidity', 3 => 'Pressure', 4 => 'Altitude'),
            'bme280:dewpoint' => array(1 => 'Dewpoint', 2 => 'Value 2', 3 => 'Value 3', 4 => 'Value 4'),
            'ds2438:vedirect' => array(1 => 'Voltage', 2 => 'Current', 3 => 'Temperature', 4 => 'Value 4'),
            'lora:lora' => array(1 => 'Gateway', 2 => 'RSSI', 3 => 'SNR', 4 => 'Counter'),
        );

        return $channelNames[$key] ?? array();
    }

    public static function defaultUsedChannelCount($sensorType, $sensorName)
    {
        $key = strtolower(trim((string)$sensorType)) . ':' . strtolower(trim((string)$sensorName));
        $channelCounts = array(
            'adc:battery' => 2,
            'adc:tanks' => 4,
            'digital:status' => 3,
            'gps:gps' => 4,
            'bme280:environment' => 4,
            'bme280:dewpoint' => 1,
            'ds2438:vedirect' => 3,
            'lora:lora' => 4,
        );

        return $channelCounts[$key] ?? null;
    }

    public static function legacyTypeChannelNames($sensorType)
    {
        $channelNames = array(
            'adc' => array(1 => 'ADC1', 2 => 'ADC2', 3 => 'level1', 4 => 'level2'),
            'digital' => array(1 => 'Ch1', 2 => 'Ch2', 3 => null, 4 => null),
            'gps' => array(1 => 'Lat', 2 => 'Lon', 3 => 'Alt', 4 => 'Spd'),
            'bme280' => array(1 => 'Temp', 2 => 'Hum', 3 => 'Pres', 4 => 'Dew.'),
            'ds2438' => array(1 => 'V', 2 => 'A', 3 => null, 4 => null),
            'lora' => array(1 => 'Gateway', 2 => 'RSSI', 3 => 'SNR', 4 => 'Counter'),
        );

        return $channelNames[strtolower(trim((string)$sensorType))] ?? array();
    }
}
