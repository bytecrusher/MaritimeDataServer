<?php

final class SensorChannelPresentation
{
    public static function resolve(array $sensor, array $type, array $channel): array
    {
        $nr = (int)$channel['channelNr'];
        $unit = html_entity_decode((string)($type['siUnitVal' . $nr] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = strtolower(trim((string)($sensor['name'] ?? '')));
        $address = strtolower((string)($sensor['sensorAddress'] ?? ''));
        $isAdc = ($type['name'] ?? '') === 'ADC';
        // Stable firmware addresses survive user-renamed sensor groups.
        $tanks = $isAdc && ($name === 'tanks' || preg_match('/^bm[0-9a-f]{12}tank$/', $address));
        $battery = $isAdc && (in_array($name, array('battery', 'battery voltage'), true)
            || preg_match('/^bm[0-9a-f]{12}bat$/', $address));
        $technical = false;
        if ($tanks) {
            $technical = in_array($nr, array(2, 4), true);
            $unit = $technical ? 'ADC' : '%';
            if (!$technical) {
                $tank = $nr === 1 ? 1 : 2;
                if (in_array($channel['name'] ?? '', array('Tank ' . $tank, $nr === 1 ? 'ADC1' : 'level1'), true)) {
                    $channel['name'] = 'Tank ' . $tank . ' Füllstand';
                }
                // Repair the generic voltage scale without replacing custom ranges.
                if ((float)$channel['GaugeMinValue'] === 0.0 && (float)$channel['GaugeMaxValue'] === 20.0) {
                    $channel['GaugeMaxValue'] = 100;
                    $channel['GaugeRedAreaLowValue'] = 0;
                    $channel['GaugeRedAreaHighValue'] = 101;
                }
            }
        } elseif ($battery) {
            $unit = $nr === 1 ? 'V' : ($nr === 2 ? '%' : '');
        }
        return array('channel' => $channel, 'unit' => $unit, 'technical' => $technical,
            'chartKey' => ($tanks || ($battery && $nr !== 1)) ? 'other' : ($unit === 'V' ? 'adc' : ''));
    }
}
