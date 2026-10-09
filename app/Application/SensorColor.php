<?php

final class SensorColor
{
    public static function valid($value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/D', $value) === 1;
    }

    public static function forDisplay($value): string
    {
        return self::valid($value) ? strtolower($value) : '#287d8e';
    }

    public static function validatePost(array $post): array
    {
        foreach ($post as $key => $value) {
            if (preg_match('/^(?:Value[1-4])?(?:ChartColor|GaugeRedAreaLowColor|GaugeRedAreaHighColor|GaugeNormalAreaColor)$/D', $key)) {
                if (!self::valid($value)) throw new InvalidArgumentException('Invalid sensor color; use #RRGGBB.');
                $post[$key] = strtolower($value);
            }
        }
        return $post;
    }

    public static function channel(array $channel): array
    {
        foreach (array('ChartColor', 'GaugeRedAreaLowColor', 'GaugeRedAreaHighColor', 'GaugeNormalAreaColor') as $field) {
            if (array_key_exists($field, $channel)) $channel[$field] = self::forDisplay($channel[$field]);
        }
        return $channel;
    }
}
