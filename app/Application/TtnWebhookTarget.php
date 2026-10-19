<?php

final class TtnWebhookTarget
{
    public static function baseUrl(): string
    {
        $path = dirname(__DIR__, 2) . '/config/config.json';
        $config = is_file($path) ? json_decode(file_get_contents($path), true) : array();
        return self::validate(getenv('MDS_BASE_URL') ?: ($config['canonicalBaseUrl'] ?? ''));
    }

    public static function validate($url): string
    {
        if (!is_string($url) || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            return '';
        }
        $parts = parse_url($url);
        if (!$parts || !in_array($parts['scheme'] ?? '', array('http', 'https'), true)
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])) {
            return '';
        }
        return rtrim($url, '/');
    }
}
