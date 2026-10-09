<?php

final class SecurityPolicy
{
    public static function secretMatches($expected, $provided): bool
    {
        return is_string($expected) && trim($expected) !== '' && is_string($provided)
            && $provided !== '' && hash_equals($expected, $provided);
    }

    public static function canonicalUrl($value): string
    {
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_URL)) return '';
        $parts = parse_url($value);
        if (!in_array($parts['scheme'] ?? '', array('http', 'https'), true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) return '';
        return rtrim($value, '/');
    }

    public static function forwardingSignature(string $body, int $timestamp, string $secret): string
    {
        return hash_hmac('sha256', $timestamp . ':' . $body, $secret);
    }

    public static function validForwarding(string $body, $timestamp, $signature, $secret): bool
    {
        if (!is_string($secret) || trim($secret) === '' || !is_scalar($timestamp)
            || !ctype_digit((string)$timestamp) || abs(time() - (int)$timestamp) > 60) return false;
        return self::secretMatches(self::forwardingSignature($body, (int)$timestamp, $secret), $signature);
    }

    public static function assertSensorBoard(PDO $pdo, $sensorId, int $boardId): void
    {
        if (!is_scalar($sensorId) || !ctype_digit((string)$sensorId) || (int)$sensorId <= 0) {
            throw new DomainException('Invalid sensor identity.');
        }
        $query = $pdo->prepare('SELECT boardId FROM sensorConfig WHERE id = ?');
        $query->execute(array((int)$sensorId));
        if ((int)$query->fetchColumn() !== $boardId || $boardId <= 0) throw new DomainException('Sensor belongs to another board.');
    }
}
