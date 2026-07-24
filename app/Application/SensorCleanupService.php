<?php

require_once __DIR__ . '/../Infrastructure/Database/dbConfig.func.php';
require_once __DIR__ . '/../Infrastructure/Logging/writeToLogFunction.func.php';

class SensorCleanupService
{
    public const DEFAULT_STALE_DAYS = 30;

    public static function getBoardSensorOverview($boardId, $staleDays = self::DEFAULT_STALE_DAYS)
    {
        $boardId = (int)$boardId;
        $staleDays = max(1, (int)$staleDays);
        if ($boardId <= 0) {
            return array();
        }

        $pdo = dbConfig::getInstance();
        $hasAlertColumn = self::columnExists($pdo, 'sensorChannelConfig', 'AlertEnabled');
        $alertSelect = $hasAlertColumn
            ? 'COALESCE(sensorAlerts.hasActiveAlert, 0) AS hasActiveAlert'
            : '0 AS hasActiveAlert';
        $alertJoin = $hasAlertColumn
            ? "LEFT JOIN (
                 SELECT sensorConfigId, MAX(COALESCE(AlertEnabled, 0)) AS hasActiveAlert
                 FROM sensorChannelConfig
                 GROUP BY sensorConfigId
               ) AS sensorAlerts ON sensorAlerts.sensorConfigId = sensorConfig.id"
            : '';
        $statement = $pdo->prepare(
            "SELECT sensorConfig.id,
                    sensorConfig.name,
                    sensorConfig.onDashboard,
                    sensorTypes.name AS typeName,
                    (SELECT MAX(sensorData.reading_time)
                     FROM sensorData
                     WHERE sensorData.sensorId = sensorConfig.id) AS lastReading,
                    $alertSelect
             FROM sensorConfig
             INNER JOIN sensorTypes ON sensorTypes.id = sensorConfig.typId
             $alertJoin
             WHERE sensorConfig.boardId = ?
             ORDER BY sensorConfig.id"
        );
        $statement->execute(array($boardId));
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $cutoffTimestamp = time() - ($staleDays * 86400);

        foreach ($rows as $index => $row) {
            $candidate = self::isCleanupCandidate(
                $row['typeName'] ?? '',
                $row['lastReading'] ?? null,
                !empty($row['hasActiveAlert']),
                $cutoffTimestamp
            );
            $rows[$index]['cleanupCandidate'] = $candidate;
            $rows[$index]['cleanupReason'] = self::cleanupReason(
                $row['typeName'] ?? '',
                $row['lastReading'] ?? null,
                !empty($row['hasActiveAlert']),
                $cutoffTimestamp
            );
        }

        return $rows;
    }

    public static function isCleanupCandidate($typeName, $lastReading, $hasActiveAlert, $cutoffTimestamp)
    {
        if (self::isProtectedType($typeName) || $hasActiveAlert) {
            return false;
        }

        $lastReadingTimestamp = self::readingTimestamp($lastReading);
        return $lastReadingTimestamp === null || $lastReadingTimestamp < (int)$cutoffTimestamp;
    }

    public static function deleteUnusedBoardSensors($boardId, $actorUserId, $staleDays = self::DEFAULT_STALE_DAYS)
    {
        $boardId = (int)$boardId;
        $actorUserId = (int)$actorUserId;
        $staleDays = max(1, (int)$staleDays);
        if ($boardId <= 0 || $actorUserId <= 0) {
            throw new InvalidArgumentException('A valid board and actor are required.');
        }

        $pdo = dbConfig::getInstance();
        $counts = array();
        $sensorIds = array();
        try {
            $pdo->beginTransaction();

            $boardStatement = $pdo->prepare('SELECT id FROM boardConfig WHERE id = ? FOR UPDATE');
            $boardStatement->execute(array($boardId));
            if (!$boardStatement->fetchColumn()) {
                throw new RuntimeException('Board not found.');
            }

            // Determine candidates inside the transaction so the preview is checked again before deletion.
            $overview = self::getBoardSensorOverview($boardId, $staleDays);
            $sensorIds = array_values(array_map('intval', array_column(array_filter($overview, function ($row) {
                return !empty($row['cleanupCandidate']);
            }), 'id')));
            if (empty($sensorIds)) {
                $pdo->commit();
                return array('sensorConfig' => 0, 'sensorData' => 0, 'sensorChannelConfig' => 0, 'sensor_permissions' => 0);
            }

            $placeholders = implode(',', array_fill(0, count($sensorIds), '?'));

            if (self::tableExists($pdo, 'sensor_permissions')) {
                $statement = $pdo->prepare("DELETE FROM sensor_permissions WHERE sensorId IN ($placeholders)");
                $statement->execute($sensorIds);
                $counts['sensor_permissions'] = $statement->rowCount();
            } else {
                $counts['sensor_permissions'] = 0;
            }

            foreach (array('sensorData' => 'sensorId', 'sensorChannelConfig' => 'sensorConfigId') as $table => $column) {
                $statement = $pdo->prepare("DELETE FROM `$table` WHERE `$column` IN ($placeholders)");
                $statement->execute($sensorIds);
                $counts[$table] = $statement->rowCount();
            }

            $statement = $pdo->prepare("DELETE FROM sensorConfig WHERE boardId = ? AND id IN ($placeholders)");
            $statement->execute(array_merge(array($boardId), $sensorIds));
            $counts['sensorConfig'] = $statement->rowCount();
            if ($counts['sensorConfig'] !== count($sensorIds)) {
                throw new RuntimeException('Not all selected sensor configurations were deleted.');
            }

            $pdo->commit();
            writeToLogFunction::info('Unused board sensors and historical data deleted.', __FILE__, array(
                'boardId' => $boardId,
                'actorUserId' => $actorUserId,
                'staleDays' => $staleDays,
                'sensorIds' => $sensorIds,
                'deletedRows' => $counts,
            ));
            return $counts;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            writeToLogFunction::error('Unused sensor cleanup failed.', __FILE__, array(
                'boardId' => $boardId,
                'actorUserId' => $actorUserId,
                'staleDays' => $staleDays,
                'sensorIds' => $sensorIds,
                'deletedRowsBeforeRollback' => $counts,
                'error' => $e->getMessage(),
            ));
            throw new RuntimeException('Unused sensors could not be deleted.', 0, $e);
        }
    }

    private static function cleanupReason($typeName, $lastReading, $hasActiveAlert, $cutoffTimestamp)
    {
        if (self::isProtectedType($typeName)) {
            return 'protected';
        }
        if ($hasActiveAlert) {
            return 'alert';
        }
        $lastReadingTimestamp = self::readingTimestamp($lastReading);
        if ($lastReadingTimestamp === null) {
            return 'never';
        }
        return $lastReadingTimestamp < (int)$cutoffTimestamp ? 'stale' : 'current';
    }

    private static function isProtectedType($typeName)
    {
        return in_array(strtolower(trim((string)$typeName)), array('wakeupstan', 'otastatus'), true);
    }

    private static function readingTimestamp($lastReading)
    {
        if ($lastReading === null || trim((string)$lastReading) === '') {
            return null;
        }
        $timestamp = strtotime((string)$lastReading);
        return $timestamp === false ? null : $timestamp;
    }

    private static function tableExists(PDO $pdo, $tableName)
    {
        $statement = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
        );
        $statement->execute(array($tableName));
        return (int)$statement->fetchColumn() > 0;
    }

    private static function columnExists(PDO $pdo, $tableName, $columnName)
    {
        $statement = $pdo->prepare(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $statement->execute(array($tableName, $columnName));
        return (int)$statement->fetchColumn() > 0;
    }
}
