<?php

class DevicePowerState
{
    public static function normalize($value)
    {
        if (is_bool($value)) {
            return $value ? 'wakeup' : 'standby';
        }

        if (is_int($value) || is_float($value)) {
            return ((int)$value) === 0 ? 'standby' : 'wakeup';
        }

        if (!is_string($value)) {
            return null;
        }

        $normalizedValue = mb_strtolower(trim($value));
        if ($normalizedValue === '') {
            return null;
        }

        if (in_array($normalizedValue, array('always_online', 'always-online', 'always online', 'alwayson'), true)) {
            return 'always_online';
        }

        if (in_array($normalizedValue, array('1', 'true', 'yes', 'on', 'enabled', 'wakeup', 'wake', 'awake', 'active', 'online'), true)
            || str_contains($normalizedValue, 'wake')) {
            return 'wakeup';
        }

        if (in_array($normalizedValue, array('0', 'false', 'no', 'off', 'disabled', 'standby', 'sleep', 'sleeping'), true)
            || str_contains($normalizedValue, 'standby')) {
            return 'standby';
        }

        return null;
    }

    public static function fromSensorRow($sensorRow)
    {
        if (!is_array($sensorRow)) {
            return null;
        }

        // Combined event rows contain the newer transition in value3/value4.
        foreach (array('value3', 'value1') as $fieldName) {
            $normalizedState = self::normalize($sensorRow[$fieldName] ?? null);
            if ($normalizedState !== null) {
                return $normalizedState;
            }
        }

        if (trim((string)($sensorRow['value1'] ?? '')) === '0'
            && trim((string)($sensorRow['value2'] ?? '')) === '1'
            && trim((string)($sensorRow['value3'] ?? '')) === '0'
            && trim((string)($sensorRow['value4'] ?? '')) === '0') {
            return 'standby';
        }

        return null;
    }
}
