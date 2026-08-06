<?php

final class SensorMetadataService
{
    private const DEFINITIONS = array(
        'bat' => array('type' => 'ADC', 'defaultName' => 'Battery'),
        'tank' => array('type' => 'ADC', 'defaultName' => 'Tanks'),
        'stat' => array('type' => 'Digital', 'defaultName' => 'Status'),
        'temp' => array('type' => 'DS18B20', 'defaultName' => 'Temperature'),
        'gps' => array('type' => 'GPS', 'defaultName' => 'GPS'),
        'env' => array('type' => 'BME280', 'defaultName' => 'Environment'),
        'dew' => array('type' => 'BME280', 'defaultName' => 'Dewpoint'),
        've' => array('type' => 'DS2438', 'defaultName' => 'VE.Direct'),
    );

    public static function stableAddress($macAddress, $key)
    {
        $normalizedMac = strtolower(preg_replace('/[^a-fA-F0-9]/', '', (string)$macAddress));
        $normalizedKey = strtolower(trim((string)$key));
        if (strlen($normalizedMac) !== 12 || !isset(self::DEFINITIONS[$normalizedKey])) {
            return null;
        }
        return 'bm' . $normalizedMac . $normalizedKey;
    }

    public static function keyForSensor($sensorType, $sensorName)
    {
        $type = strtolower(trim((string)$sensorType));
        $name = strtolower(trim((string)$sensorName));
        $mapping = array(
            'adc:battery' => 'bat',
            'adc:tanks' => 'tank',
            'digital:status' => 'stat',
            'ds18b20:ds18b20' => 'temp',
            'ds18b20:temperature' => 'temp',
            'gps:gps' => 'gps',
            'bme280:environment' => 'env',
            'bme280:dewpoint' => 'dew',
            'ds2438:vedirect' => 've',
            'ds2438:ve.direct' => 've',
        );
        return $mapping[$type . ':' . $name] ?? null;
    }

    public static function metadataHash(array $sensors)
    {
        $names = array();
        foreach ($sensors as $sensor) {
            $key = strtolower(trim((string)($sensor['key'] ?? '')));
            if (isset(self::DEFINITIONS[$key])) {
                $names[$key] = trim((string)($sensor['name'] ?? ''));
            }
        }
        ksort($names);
        $canonical = '';
        foreach ($names as $key => $name) {
            $canonical .= $key . '=' . $name . "\n";
        }
        $hash = 2166136261;
        for ($i = 0, $length = strlen($canonical); $i < $length; $i++) {
            $hash ^= ord($canonical[$i]);
            $hash = ($hash * 16777619) & 0xffffffff;
        }
        return sprintf('%08x', $hash);
    }

    public static function synchronize(PDO $pdo, $boardId, $macAddress, array $definitions, $pushNames)
    {
        $provided = array();
        foreach ($definitions as $definition) {
            $key = strtolower(trim((string)($definition['key'] ?? '')));
            if (!isset(self::DEFINITIONS[$key])) {
                continue;
            }
            $name = self::normalizeName($definition['name'] ?? self::DEFINITIONS[$key]['defaultName']);
            $provided[$key] = $name ?: self::DEFINITIONS[$key]['defaultName'];
        }

        $claimedSensorIds = array();
        foreach ($provided as $key => $name) {
            $sensorId = self::resolveOrCreateSensor($pdo, $boardId, $macAddress, $key, $name, $claimedSensorIds);
            if ($sensorId === null) {
                continue;
            }
            $claimedSensorIds[] = $sensorId;
            if ($pushNames) {
                $rename = $pdo->prepare('UPDATE sensorConfig SET name = ? WHERE id = ? AND boardId = ?');
                $rename->execute(array($name, $sensorId, $boardId));
            }
        }

        return self::loadManagedSensors($pdo, $boardId, $macAddress);
    }

    public static function loadManagedSensors(PDO $pdo, $boardId, $macAddress)
    {
        $result = array();
        $statement = $pdo->prepare('SELECT id, name, sensorAddress FROM sensorConfig WHERE boardId = ? AND sensorAddress = ? LIMIT 1');
        foreach (self::DEFINITIONS as $key => $definition) {
            $address = self::stableAddress($macAddress, $key);
            if ($address === null) {
                continue;
            }
            $statement->execute(array($boardId, $address));
            $sensor = $statement->fetch(PDO::FETCH_ASSOC);
            if ($sensor) {
                $result[] = array(
                    'key' => $key,
                    'name' => (string)($sensor['name'] ?: $definition['defaultName']),
                    'sensorAddress' => $address,
                );
            }
        }
        return $result;
    }

    private static function resolveOrCreateSensor(PDO $pdo, $boardId, $macAddress, $key, $name, array $claimedSensorIds)
    {
        $definition = self::DEFINITIONS[$key];
        $address = self::stableAddress($macAddress, $key);
        $byAddress = $pdo->prepare('SELECT id FROM sensorConfig WHERE boardId = ? AND sensorAddress = ? LIMIT 1');
        $byAddress->execute(array($boardId, $address));
        $sensorId = $byAddress->fetchColumn();
        if ($sensorId !== false) {
            return (int)$sensorId;
        }

        $candidates = $pdo->prepare(
            'SELECT sensorConfig.id, sensorConfig.name
             FROM sensorConfig
             INNER JOIN sensorTypes ON sensorTypes.id = sensorConfig.typId
             WHERE sensorConfig.boardId = ? AND sensorTypes.name = ?
             ORDER BY sensorConfig.id'
        );
        $candidates->execute(array($boardId, $definition['type']));
        $available = array_values(array_filter($candidates->fetchAll(PDO::FETCH_ASSOC), function ($candidate) use ($claimedSensorIds) {
            return !in_array((int)$candidate['id'], $claimedSensorIds, true);
        }));

        $matched = null;
        foreach ($available as $candidate) {
            if (strcasecmp((string)$candidate['name'], $definition['defaultName']) === 0 ||
                strcasecmp((string)$candidate['name'], $name) === 0) {
                $matched = $candidate;
                break;
            }
        }
        if ($matched === null && count($available) > 0) {
            $matched = $available[0];
        }

        if ($matched === null) {
            $myFunctions = new myFunctions();
            $created = false;
            $newId = $myFunctions->addSensorConfig($boardId, $definition['type'], $definition['defaultName'], $created);
            if (!$newId) {
                return null;
            }
            $matched = array('id' => $newId);
        }

        $bindAddress = $pdo->prepare(
            'UPDATE sensorConfig SET sensorAddress = ? WHERE id = ? AND boardId = ? AND (sensorAddress IS NULL OR sensorAddress = \'\')'
        );
        $bindAddress->execute(array($address, $matched['id'], $boardId));
        if ($bindAddress->rowCount() === 0) {
            return null;
        }
        return (int)$matched['id'];
    }

    private static function normalizeName($value)
    {
        $name = trim((string)$value);
        if ($name === '' || strlen($name) > 17 || preg_match('/[^\x20-\x7E]/', $name)) {
            return null;
        }
        return $name;
    }
}
