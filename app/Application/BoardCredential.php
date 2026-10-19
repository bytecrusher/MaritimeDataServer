<?php

final class BoardCredential
{
    public static function boardId($token, array $hashes): ?int
    {
        if (!is_string($token) || strlen($token) < 32) return null;
        $digest = hash('sha256', $token);
        foreach ($hashes as $id => $hash) {
            if ((int)$id > 0 && is_string($hash) && hash_equals($hash, $digest)) return (int)$id;
        }
        return null;
    }

    public static function resolve(PDO $pdo, $token, $mac, array $hashes): array
    {
        $id = self::boardId($token, $hashes);
        if ($id === null || !is_string($mac)) throw new DomainException('Board credential required.');
        $query = $pdo->prepare('SELECT * FROM boardConfig WHERE id = ?');
        $query->execute(array($id));
        $board = $query->fetch(PDO::FETCH_ASSOC);
        $normalize = static fn($value) => strtoupper(str_replace(array(':', '-'), '', trim($value)));
        if (!$board || $normalize($board['macAddress']) !== $normalize($mac)) throw new DomainException('Board credential mismatch.');
        return $board;
    }
}
