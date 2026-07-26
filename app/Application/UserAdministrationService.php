<?php

require_once(__DIR__ . '/../Infrastructure/Database/dbConfig.func.php');
require_once(__DIR__ . '/../Infrastructure/Logging/writeToLogFunction.func.php');

class UserAdministrationService
{
    public static function normalizeDeletionRequest($actorUserId, $userIds)
    {
        $actorUserId = (int)$actorUserId;
        $normalized = array();
        foreach (is_array($userIds) ? $userIds : array() as $userId) {
            $userId = filter_var($userId, FILTER_VALIDATE_INT);
            if ($userId !== false && $userId > 0) {
                $normalized[(int)$userId] = (int)$userId;
            }
        }

        $normalized = array_values($normalized);
        sort($normalized, SORT_NUMERIC);
        if (empty($normalized)) {
            throw new InvalidArgumentException('Select at least one user.');
        }
        if (in_array($actorUserId, $normalized, true)) {
            throw new RuntimeException('You cannot delete your own user account here.');
        }

        return $normalized;
    }

    public static function deleteUsers($actorUserId, $userIds, PDO $pdo = null)
    {
        $actorUserId = (int)$actorUserId;
        $userIds = self::normalizeDeletionRequest($actorUserId, $userIds);
        $pdo = $pdo ?: dbConfig::getInstance();
        $startedTransaction = !$pdo->inTransaction();
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        try {
            if ($startedTransaction) {
                $pdo->beginTransaction();
            }

            $statement = $pdo->prepare(
                "SELECT id, userGroupAdmin FROM users WHERE id IN ($placeholders) FOR UPDATE"
            );
            $statement->execute($userIds);
            $users = $statement->fetchAll(PDO::FETCH_ASSOC);
            if (empty($users)) {
                throw new RuntimeException('The selected users no longer exist.');
            }

            $existingUserIds = array_map('intval', array_column($users, 'id'));
            $existingPlaceholders = implode(',', array_fill(0, count($existingUserIds), '?'));
            $selectedAdminCount = 0;
            foreach ($users as $user) {
                $selectedAdminCount += ((int)($user['userGroupAdmin'] ?? 0) === 1) ? 1 : 0;
            }
            if ($selectedAdminCount > 0) {
                $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE userGroupAdmin = 1")->fetchColumn();
                if (($adminCount - $selectedAdminCount) < 1) {
                    throw new RuntimeException('The last administrator cannot be deleted.');
                }
            }

            $counts = array(
                'users' => 0,
                'unassignedBoards' => 0,
                'securityTokens' => 0,
                'userRoles' => 0,
                'boardPermissions' => 0,
                'sensorPermissions' => 0,
            );

            $statement = $pdo->prepare(
                "UPDATE boardConfig SET ownerUserId = NULL WHERE ownerUserId IN ($existingPlaceholders)"
            );
            $statement->execute($existingUserIds);
            $counts['unassignedBoards'] = $statement->rowCount();

            if (self::tableExists($pdo, 'permission_audit_log')) {
                $statement = $pdo->prepare(
                    "UPDATE permission_audit_log
                     SET actorUserId = CASE WHEN actorUserId IN ($existingPlaceholders) THEN NULL ELSE actorUserId END,
                         targetUserId = CASE WHEN targetUserId IN ($existingPlaceholders) THEN NULL ELSE targetUserId END
                     WHERE actorUserId IN ($existingPlaceholders) OR targetUserId IN ($existingPlaceholders)"
                );
                $statement->execute(array_merge($existingUserIds, $existingUserIds, $existingUserIds, $existingUserIds));
            }

            $cleanupTables = array(
                'securityTokens' => array('table' => 'securityTokens', 'column' => 'userId'),
                'userRoles' => array('table' => 'user_roles', 'column' => 'userId'),
                'boardPermissions' => array('table' => 'board_permissions', 'column' => 'userId'),
                'sensorPermissions' => array('table' => 'sensor_permissions', 'column' => 'userId'),
            );
            foreach ($cleanupTables as $countKey => $cleanup) {
                if (!self::tableExists($pdo, $cleanup['table'])) {
                    continue;
                }
                $statement = $pdo->prepare(
                    "DELETE FROM `{$cleanup['table']}` WHERE `{$cleanup['column']}` IN ($existingPlaceholders)"
                );
                $statement->execute($existingUserIds);
                $counts[$countKey] = $statement->rowCount();
            }

            $statement = $pdo->prepare("DELETE FROM users WHERE id IN ($existingPlaceholders)");
            $statement->execute($existingUserIds);
            $counts['users'] = $statement->rowCount();
            if ($counts['users'] !== count($existingUserIds)) {
                throw new RuntimeException('Not all selected users could be deleted.');
            }

            if (self::tableExists($pdo, 'permission_audit_log')) {
                $audit = $pdo->prepare(
                    "INSERT INTO permission_audit_log
                     (actorUserId, targetUserId, resourceType, resourceId, action, oldValue, newValue)
                     VALUES (?, NULL, 'user', ?, 'delete', ?, NULL)"
                );
                foreach ($existingUserIds as $deletedUserId) {
                    $audit->execute(array(
                        $actorUserId,
                        $deletedUserId,
                        json_encode(array('deletedByAdmin' => true), JSON_UNESCAPED_SLASHES),
                    ));
                }
            }

            if ($startedTransaction) {
                $pdo->commit();
            }

            if ($startedTransaction) {
                writeToLogFunction::info('Users deleted by administrator.', __FILE__, array(
                    'actorUserId' => $actorUserId,
                    'deletedUserIds' => $existingUserIds,
                    'counts' => $counts,
                ));
            }

            return $counts;
        } catch (Throwable $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            writeToLogFunction::error('Bulk user deletion failed.', __FILE__, array(
                'actorUserId' => $actorUserId,
                'requestedUserIds' => $userIds,
                'error' => $e->getMessage(),
            ));
            throw $e;
        }
    }

    private static function tableExists(PDO $pdo, $tableName)
    {
        $statement = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
        );
        $statement->execute(array((string)$tableName));
        return (int)$statement->fetchColumn() > 0;
    }
}
