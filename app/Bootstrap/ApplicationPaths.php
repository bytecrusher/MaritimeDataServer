<?php

namespace App\Bootstrap;

final class ApplicationPaths
{
    public static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function publicRoot(): string
    {
        return self::projectRoot() . '/public';
    }

    public static function legacyPublicRoot(): string
    {
        return self::projectRoot() . '/src';
    }

    public static function configRoot(): string
    {
        return self::projectRoot() . '/config';
    }

    public static function varRoot(): string
    {
        return self::projectRoot() . '/var';
    }
}
