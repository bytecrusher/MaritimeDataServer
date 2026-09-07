<?php

final class TemperatureReading
{
    public static function valid($value): bool
    {
        return is_numeric($value) && is_finite((float)$value)
            && (float)$value >= -55 && (float)$value <= 125;
    }

    public static function channelDefaults(array $channel): array
    {
        // Only repair the unmistakable boolean placeholder, not custom ranges/alarms.
        if ((float)($channel['GaugeMinValue'] ?? -1) === 0.0
            && (float)($channel['GaugeMaxValue'] ?? -1) === 1.0) {
            $channel['GaugeMinValue'] = -55;
            $channel['GaugeMaxValue'] = 125;
            if ((float)($channel['GaugeRedAreaLowValue'] ?? -1) === 0.0
                && (float)($channel['GaugeRedAreaHighValue'] ?? -1) === 2.0) {
                $channel['GaugeRedAreaLowValue'] = -10;
                $channel['GaugeRedAreaHighValue'] = 80;
            }
        }
        return $channel;
    }
}
