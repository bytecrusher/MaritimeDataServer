<?php

final class TemperatureAlertTransfer
{
    public static function migrate(PDO $pdo, int $boardId, int $targetSensorId): bool
    {
        $pdo->beginTransaction();
        try {
            $lock = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $query = $pdo->prepare("SELECT c.* FROM sensorChannelConfig c JOIN sensorConfig s ON s.id=c.sensorConfigId
                JOIN sensorTypes t ON t.id=s.typId WHERE s.boardId=? AND LOWER(s.name)='status'
                AND LOWER(t.name)='digital' AND c.channelNr=3
                AND (c.AlertEnabled=1 OR LOWER(c.name) NOT IN ('ch3', 'value 3', 'status', ''))" . $lock);
            $query->execute(array($boardId));
            $sources = $query->fetchAll(PDO::FETCH_ASSOC);
            if (count($sources) !== 1) { $pdo->commit(); return false; }
            $source = $sources[0];
            $done = $pdo->prepare('SELECT sourceChannelId FROM temperatureChannelTransfers WHERE sourceChannelId=?');
            $done->execute(array($source['id']));
            if ($done->fetchColumn()) { $pdo->commit(); return true; }
            $query = $pdo->prepare('SELECT c.* FROM sensorChannelConfig c JOIN sensorConfig s ON s.id=c.sensorConfigId
                JOIN sensorTypes t ON t.id=s.typId WHERE s.id=? AND s.boardId=? AND t.name=\'DS18B20\' AND c.channelNr=1' . $lock);
            $query->execute(array($targetSensorId, $boardId));
            $target = $query->fetch(PDO::FETCH_ASSOC);
            if (!$target) throw new DomainException('Temperature target channel missing.');
            if (!empty($target['AlertEnabled'])) {
                foreach (array('AlertEnabled', 'AlertLowValue', 'AlertHighValue') as $field) {
                    if ((string)$target[$field] !== (string)$source[$field]) {
                        throw new DomainException('Conflicting active temperature alerts; legacy alert retained.');
                    }
                }
            }
            // Archive both originals and retain all source channel metadata, including alert state.
            $insert = $pdo->prepare('INSERT INTO temperatureChannelTransfers VALUES (?, ?, ?, ?)');
            $insert->execute(array($source['id'], $target['id'], json_encode($source, JSON_THROW_ON_ERROR), json_encode($target, JSON_THROW_ON_ERROR)));
            $fields = array_intersect(array_keys($target), array('name', 'description', 'locationOfMeasurement',
                'GaugeMinValue', 'GaugeMaxValue', 'GaugeRedAreaLowValue', 'GaugeRedAreaLowColor', 'GaugeRedAreaHighValue',
                'GaugeRedAreaHighColor', 'GaugeNormalAreaColor', 'GaugeStyle', 'AlertEnabled', 'AlertLowValue',
                'AlertHighValue', 'AlertState', 'LastAlertSentAt', 'DashboardOrderNr', 'onDashboard', 'ChartColor'));
            $update = $pdo->prepare('UPDATE sensorChannelConfig SET ' . implode(', ', array_map(static fn($f) => '`' . $f . '`=?', $fields)) . ' WHERE id=?');
            $values = array_values(array_map(static fn($f) => $source[$f], $fields));
            $values[] = $target['id'];
            $update->execute($values);
            // Preserve explicitly shared notification recipients without granting edit/view access.
            $query = $pdo->prepare('SELECT userId FROM sensor_permissions WHERE sensorId=? AND canReceiveAlerts=1');
            $query->execute(array($source['sensorConfigId']));
            foreach ($query->fetchAll(PDO::FETCH_COLUMN) as $userId) {
                $existing = $pdo->prepare('SELECT id FROM sensor_permissions WHERE sensorId=? AND userId=?');
                $existing->execute(array($targetSensorId, $userId));
                if ($existing->fetchColumn()) {
                    $pdo->prepare('UPDATE sensor_permissions SET canReceiveAlerts=1 WHERE sensorId=? AND userId=?')->execute(array($targetSensorId, $userId));
                } else {
                    $pdo->prepare("INSERT INTO sensor_permissions (sensorId,userId,role,canView,canEdit,canReceiveAlerts) VALUES (?,?,'observer',0,0,1)")->execute(array($targetSensorId, $userId));
                }
            }
            $pdo->prepare('UPDATE sensorChannelConfig SET AlertEnabled=0, onDashboard=0 WHERE id=?')->execute(array($source['id']));
            $pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}
