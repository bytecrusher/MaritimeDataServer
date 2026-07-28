<?php

class SensorGroupPolicy
{
    private const SINGLETON_TYPE_KEYS = array(
        'gps',
        'lora',
        'otastatus',
        'wakeup',
        'wakeuplog',
        'wakeupstan',
    );

    public static function isSingletonType($typeName)
    {
        return in_array(self::normalizeTypeKey($typeName), self::SINGLETON_TYPE_KEYS, true);
    }

    public static function findExistingSensorId(PDO $pdo, $boardId, $sensorTypeId)
    {
        $statement = $pdo->prepare(
            'SELECT id FROM sensorConfig WHERE boardId = ? AND typId = ? ORDER BY id LIMIT 1'
        );
        $statement->execute(array((int)$boardId, (int)$sensorTypeId));
        $sensorId = $statement->fetchColumn();

        return $sensorId === false ? null : (int)$sensorId;
    }

    public static function acquireCreationLock(PDO $pdo, $boardId, $sensorTypeId)
    {
        $lockName = self::creationLockName($boardId, $sensorTypeId);
        $statement = $pdo->prepare('SELECT GET_LOCK(?, 5)');
        $statement->execute(array($lockName));
        if ((int)$statement->fetchColumn() !== 1) {
            throw new RuntimeException('Unable to acquire singleton sensor-group lock.');
        }

        return $lockName;
    }

    public static function releaseCreationLock(PDO $pdo, $lockName)
    {
        if (!is_string($lockName) || $lockName === '') {
            return;
        }

        try {
            $statement = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $statement->execute(array($lockName));
        } catch (Throwable $exception) {
            // The connection releases advisory locks automatically at request end.
        }
    }

    private static function normalizeTypeKey($typeName)
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$typeName)));
    }

    private static function creationLockName($boardId, $sensorTypeId)
    {
        return 'mds_sensor_group_' . (int)$boardId . '_' . (int)$sensorTypeId;
    }
}
