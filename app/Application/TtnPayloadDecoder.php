<?php

final class TtnPayloadDecoder
{
    public static function decodeKnownPayload($fPort, $encodedPayload)
    {
        $rawPayload = base64_decode((string)$encodedPayload, true);
        if ($rawPayload === false || $rawPayload === '') {
            return null;
        }

        $bytes = array_values(unpack('C*', $rawPayload));
        if ((int)$fPort === 3 && $bytes[0] === 4) {
            return self::decodeMeasurementsSchema4($bytes);
        }
        if ((int)$fPort === 1 && count($bytes) === 51 && $bytes[0] === 3) {
            return self::decodeMeasurementsSchema3($bytes);
        }

        if ((int)$fPort === 2 && count($bytes) === 34 && $bytes[0] === 1) {
            return self::decodeDeviceConfigSchema1($bytes);
        }

        return null;
    }

    private static function decodeMeasurementsSchema4(array $bytes)
    {
        if (count($bytes) < 11 || count($bytes) > 51 || ($bytes[1] & 0x80)) {
            return null;
        }
        // Expand the compact blocks to the tested schema-3 field layout.
        $expanded = array_fill(0, 51, 0);
        $expanded[1] = $bytes[8];
        $expanded[2] = $bytes[9];
        $expanded[24] = $bytes[10] & 0x31;
        $blocks = array(
            array(64, array(10, 11, 25), array('voltage', 'batteryCapacity')),
            array(1, array(12, 13), array('tempbattery')),
            array(2, range(3, 9), array('temperature', 'pressure', 'humidity', 'dewpoint')),
            array(4, array_merge(range(14, 21), range(30, 35)), array('longitude', 'latitude', 'position', 'speed', 'course', 'altitude')),
            array(8, range(36, 41), array('vedirectVoltage', 'vedirectCurrent', 'vedirectTemperature')),
            array(16, array(22, 23, 26, 27, 28, 29), array('level1', 'level2', 'tank1Adc', 'tank2Adc')),
            array(32, range(42, 50), array('standbyEpoch', 'wakeupEpoch', 'standbyCause', 'wakeupCause')),
        );
        $offset = 11;
        foreach ($blocks as [$bit, $targets]) {
            if (($bytes[1] & $bit) === 0) continue;
            if ($offset + count($targets) > count($bytes)) return null;
            foreach ($targets as $target) $expanded[$target] = $bytes[$offset++];
        }
        if ($offset !== count($bytes)) return null;
        $decoded = self::decodeMeasurementsSchema3($expanded);
        foreach ($blocks as [$bit, $targets, $fields]) {
            if ($bytes[1] & $bit) continue;
            foreach ($fields as $field) unset($decoded[$field]);
        }
        $decoded['payloadSchema'] = 4;
        $decoded['macAddress'] = self::formatMacAddress($bytes, 2);
        foreach (array(64 => 'measurementsPresent', 1 => 'temperaturePresent', 2 => 'environmentPresent',
            4 => 'gpsFix', 8 => 'vedirectPresent', 16 => 'tanksPresent', 32 => 'wakeupEventPresent') as $bit => $name) {
            $decoded[$name] = ($bytes[1] & $bit) !== 0;
        }
        return $decoded;
    }

    private static function decodeMeasurementsSchema3(array $bytes)
    {
        $status = $bytes[24];
        $causeCodes = $bytes[50];
        $standbyCauses = array('', 'Sleep standby');
        $wakeupCauses = array('', 'Wakeup EXT0', 'Wakeup EXT1', 'Wakeup Timer', 'Wakeup Touch', 'Wakeup ULP', 'Wakeup Other');
        $standbyCode = $causeCodes & 0x0f;
        $wakeupCode = ($causeCodes >> 4) & 0x0f;

        $longitude = self::readInt32($bytes, 14) / 1000000;
        $latitude = self::readInt32($bytes, 18) / 1000000;

        return array(
            'payloadType' => 'measurements',
            'payloadSchema' => 3,
            'counter' => self::readUint16($bytes, 1),
            'temperature' => self::readInt16($bytes, 3) / 10,
            'pressure' => self::readUint16($bytes, 5) / 10,
            'humidity' => $bytes[7],
            'dewpoint' => self::readInt16($bytes, 8) / 10,
            'voltage' => self::readUint16($bytes, 10) / 1000,
            'tempbattery' => self::readInt16($bytes, 12) / 10,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'position' => array('value' => 0, 'context' => array('lat' => $latitude, 'lng' => $longitude)),
            'level1' => $bytes[22],
            'level2' => $bytes[23],
            'mainPowerOn' => $status & 0x01,
            'alarm1' => $status & 0x01,
            'environmentPresent' => ($status & 0x04) !== 0,
            'vedirectPresent' => ($status & 0x08) !== 0,
            'relay' => ($status >> 4) & 0x03,
            'gpsFix' => ($status & 0x40) !== 0,
            'wakeupEventPresent' => ($status & 0x80) !== 0,
            'batteryCapacity' => $bytes[25],
            'tank1Adc' => self::readUint16($bytes, 26),
            'tank2Adc' => self::readUint16($bytes, 28),
            'speed' => self::readUint16($bytes, 30) / 100,
            'course' => self::readUint16($bytes, 32) / 100,
            'altitude' => self::readInt16($bytes, 34) / 10,
            'vedirectVoltage' => self::readUint16($bytes, 36) / 100,
            'vedirectCurrent' => self::readInt16($bytes, 38) / 100,
            'vedirectTemperature' => self::readInt16($bytes, 40) / 100,
            'standbyEpoch' => self::readUint32($bytes, 42),
            'wakeupEpoch' => self::readUint32($bytes, 46),
            'standbyCause' => $standbyCauses[$standbyCode] ?? ($standbyCode ? 'Standby Other' : ''),
            'wakeupCause' => $wakeupCauses[$wakeupCode] ?? ($wakeupCode ? 'Wakeup Other' : ''),
        );
    }

    private static function decodeDeviceConfigSchema1(array $bytes)
    {
        $flags = $bytes[1];
        $firmwareVersion = '';
        for ($index = 14; $index < 22 && $bytes[$index] !== 0; $index++) {
            $firmwareVersion .= chr($bytes[$index]);
        }

        $operationModes = array('Off', 'Standby', 'PowerOn', 'Always');
        $environmentSensors = array('Off', 'BME280', 'VEdirect-Read', 'VEdirect-Send');

        return array(
            'payloadType' => 'deviceConfig',
            'payloadSchema' => 1,
            'standbyEnabled' => ($flags & 0x01) !== 0,
            'wifiDuringStandby' => ($flags & 0x02) !== 0,
            'wifiUploadEnabled' => ($flags & 0x04) !== 0,
            'dynamicSpreadingFactor' => ($flags & 0x08) !== 0,
            'mdnsEnabled' => ($flags & 0x10) !== 0,
            'webAuthenticationEnabled' => ($flags & 0x20) !== 0,
            'firmwareChannel' => ($flags & 0x40) !== 0 ? 'stable' : 'beta',
            'configVersion' => $bytes[2],
            'transmitIntervalMinutes' => $bytes[3],
            'standbySleepMinutes' => self::readUint16($bytes, 4),
            'autoUpdateIntervalHours' => $bytes[6],
            'transmitPriority' => $bytes[7] === 1 ? 'WifiFirst' : 'LoRaFirst',
            'loraOperationMode' => $operationModes[$bytes[8]] ?? 'Unknown',
            'spreadingFactor' => $bytes[9],
            'loraChannel' => $bytes[10],
            'serverMode' => $bytes[11],
            'temperatureSensor' => $bytes[12] === 1 ? 'DS18B20' : 'Off',
            'environmentSensor' => $environmentSensors[$bytes[13]] ?? 'Unknown',
            'firmwareVersion' => $firmwareVersion,
            'deviceId' => $bytes[22],
            'relayMode' => $bytes[23],
            'macAddress' => self::formatMacAddress($bytes, 24),
            'configHash' => strtoupper(str_pad(dechex(self::readUint32($bytes, 30)), 8, '0', STR_PAD_LEFT)),
        );
    }

    private static function readUint16(array $bytes, $offset)
    {
        return $bytes[$offset] | ($bytes[$offset + 1] << 8);
    }

    private static function readInt16(array $bytes, $offset)
    {
        $value = self::readUint16($bytes, $offset);
        return ($value & 0x8000) !== 0 ? $value - 0x10000 : $value;
    }

    private static function readUint32(array $bytes, $offset)
    {
        return $bytes[$offset]
            | ($bytes[$offset + 1] << 8)
            | ($bytes[$offset + 2] << 16)
            | ($bytes[$offset + 3] << 24);
    }

    private static function readInt32(array $bytes, $offset)
    {
        $value = self::readUint32($bytes, $offset);
        if ($value < 0) {
            return $value;
        }
        return $value > 0x7fffffff ? $value - 0x100000000 : $value;
    }

    private static function formatMacAddress(array $bytes, $offset)
    {
        $parts = array();
        for ($index = 0; $index < 6; $index++) {
            $parts[] = strtoupper(str_pad(dechex($bytes[$offset + $index]), 2, '0', STR_PAD_LEFT));
        }
        return implode(':', $parts);
    }
}
