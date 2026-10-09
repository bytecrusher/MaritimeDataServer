<?php

final class AccountActivation
{
    public static function issue(PDO $pdo, int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM accountActivationTokens WHERE userId=?')->execute(array($userId));
            $pdo->prepare('INSERT INTO accountActivationTokens (userId, tokenHash, expiresAt) VALUES (?, ?, ?)')
                ->execute(array($userId, hash('sha256', $token), time() + 86400));
            $pdo->commit();
            return $token;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function consume(PDO $pdo, int $userId, $token): bool
    {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare('DELETE FROM accountActivationTokens WHERE userId=? AND tokenHash=? AND expiresAt>=?');
            $delete->execute(array($userId, hash('sha256', $token), time()));
            if ($delete->rowCount() !== 1) { $pdo->rollBack(); return false; }
            $update = $pdo->prepare('UPDATE users SET active=1 WHERE id=? AND active=0');
            $update->execute(array($userId));
            $pdo->commit();
            return $update->rowCount() === 1;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}
