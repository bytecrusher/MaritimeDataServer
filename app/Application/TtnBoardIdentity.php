<?php

final class TtnBoardIdentity
{
    public static function identity($appId, $deviceId, $devEui): array
    {
        foreach (array($appId, $deviceId, $devEui) as $value) {
            if ($value !== null && !is_string($value)) {
                throw new DomainException('Invalid TTN identity.');
            }
        }
        $identity = array('appId' => trim($appId ?? ''), 'deviceId' => trim($deviceId ?? ''),
            'devEui' => strtoupper(trim($devEui ?? '')));
        if ($identity['appId'] === '' || ($identity['deviceId'] === '' && $identity['devEui'] === '')) {
            throw new DomainException('Missing TTN identity.');
        }
        if ($identity['devEui'] !== '' && !preg_match('/^[0-9A-F]{16}$/', $identity['devEui'])) {
            throw new DomainException('Invalid TTN DevEUI.');
        }
        return $identity;
    }

    public static function assertBinding(array $board, array $identity): void
    {
        $app = trim((string)($board['ttnAppId'] ?? ''));
        $device = trim((string)($board['ttnDevId'] ?? ''));
        $matchesDevice = ($identity['deviceId'] !== '' && $device === $identity['deviceId'])
            || ($identity['devEui'] !== '' && strtoupper($device) === $identity['devEui']);
        if ($app !== $identity['appId'] || $device === '' || !$matchesDevice) {
            throw new DomainException('TTN identity does not match the configured board binding.');
        }
    }

    private static function macKey($mac): string
    {
        return strtoupper(str_replace(array(':', '-'), '', trim((string)$mac)));
    }

    private static function rows(PDO $pdo, string $sql, array $params): array
    {
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function resolve(PDO $pdo, array $identity, ?string $payloadMac): array
    {
        if ($payloadMac !== null && !preg_match('/^[0-9A-F]{12}$/', self::macKey($payloadMac))) {
            throw new DomainException('Invalid payload MAC.');
        }
        $mysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
        // Serialize TTN first imports as the legacy schema has no unique identity index.
        if ($mysql && (int)$pdo->query("SELECT GET_LOCK('mds-ttn-board-identity', 5)")->fetchColumn() !== 1) {
            throw new RuntimeException('TTN identity lock unavailable.');
        }
        try {
            $pdo->beginTransaction();
            $lock = $mysql ? ' FOR UPDATE' : '';
            $boards = self::rows($pdo, 'SELECT * FROM boardConfig WHERE ttnAppId = ? AND (ttnDevId = ? OR UPPER(ttnDevId) = ?)' . $lock,
                array($identity['appId'], $identity['deviceId'] ?: null, $identity['devEui'] ?: null));
            $macBoards = $payloadMac === null ? array() : self::rows($pdo,
                "SELECT * FROM boardConfig WHERE REPLACE(REPLACE(UPPER(macAddress), ':', ''), '-', '') = ?" . $lock,
                array(self::macKey($payloadMac)));
            if (count($boards) > 1 || count($macBoards) > 1) {
                throw new DomainException('Ambiguous TTN board identity.');
            }
            $board = $boards[0] ?? null;
            $macBoard = $macBoards[0] ?? null;
            if ($macBoard !== null) {
                // MAC knowledge is not proof of ownership, even for an empty binding.
                self::assertBinding($macBoard, $identity);
                if ($board === null || (int)$board['id'] !== (int)$macBoard['id']) {
                    throw new DomainException('Conflicting TTN and MAC board identities.');
                }
            }
            if ($board !== null) {
                self::assertBinding($board, $identity);
                if ($payloadMac !== null && self::macKey($board['macAddress']) !== self::macKey($payloadMac)) {
                    $oldMac = (string)$board['macAddress'];
                    if ($oldMac !== '' && stripos($oldMac, 'fakeMacAddress') !== 0) {
                        throw new DomainException('Payload MAC conflicts with the bound board.');
                    }
                    $update = $pdo->prepare('UPDATE boardConfig SET macAddress = ? WHERE id = ?');
                    $update->execute(array($payloadMac, $board['id']));
                    $board['macAddress'] = $payloadMac;
                }
            } else {
                // First import creates a new unowned board; it never claims an existing MAC.
                $mac = $payloadMac ?? ('fakeMacAddress' . substr(hash('sha256', $identity['appId'] . ':' . ($identity['deviceId'] ?: $identity['devEui'])), 0, 16));
                if (self::rows($pdo, 'SELECT id FROM boardConfig WHERE macAddress = ?' . $lock, array($mac))) {
                    throw new DomainException('Generated TTN board address already exists.');
                }
                $insert = $pdo->prepare('INSERT INTO boardConfig (macAddress, name, ttnAppId, ttnDevId, onDashboard, updateDataTimer, offlineDataTimer) VALUES (?, ?, ?, ?, 1, 15, 15)');
                $insert->execute(array($mac, '- new imported -', $identity['appId'], $identity['deviceId'] ?: $identity['devEui']));
                $board = self::rows($pdo, 'SELECT * FROM boardConfig WHERE id = ?', array($pdo->lastInsertId()))[0];
            }
            $pdo->commit();
            return $board;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        } finally {
            if ($mysql) {
                $pdo->query("SELECT RELEASE_LOCK('mds-ttn-board-identity')");
            }
        }
    }

    public static function forwardedBoardId(PDO $pdo, array $boardData): int
    {
        $context = $boardData['ttnIdentity'] ?? null;
        if (!is_array($context)) {
            throw new DomainException('Invalid forwarded TTN identity.');
        }
        $identity = self::identity($context['appId'] ?? null, $context['deviceId'] ?? null, $context['devEui'] ?? null);
        $boards = self::rows($pdo, 'SELECT * FROM boardConfig WHERE id = ?', array((int)($context['boardId'] ?? 0)));
        if (count($boards) !== 1 || self::macKey($boards[0]['macAddress']) !== self::macKey($boardData['macAddress'] ?? '')) {
            throw new DomainException('Forwarded TTN board no longer matches.');
        }
        self::assertBinding($boards[0], $identity);
        return (int)$boards[0]['id'];
    }
}
