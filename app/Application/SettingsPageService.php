<?php

require_once(__DIR__ . '/InternalPageService.php');
require_once(__DIR__ . '/dbUpdateData.php');
require_once(__DIR__ . '/myFunctions.func.php');
require_once(__DIR__ . '/NotificationService.php');
require_once(__DIR__ . '/../Infrastructure/Database/dbConfig.func.php');
require_once(__DIR__ . '/../Infrastructure/Logging/writeToLogFunction.func.php');

class SettingsPageService
{
    public static function resolveCurrentUserFromSession()
    {
        return InternalPageService::resolveCurrentUserFromSession();
    }

    public static function handleSettingsSave($userObj, $config, $save, $post)
    {
        $result = array(
            'userObj' => $userObj,
            'success_msg' => null,
            'error_msg' => null,
        );

        if ($save === 'personal_data') {
            self::runUserSetter($userObj, 'setName', $post, 'setName not saved.', $result);
            self::runUserSetter($userObj, 'setUserTimeZone', $post, 'setUserTimeZone not saved.', $result);
            self::runUserSetter($userObj, 'setLanguage', $post, 'setLanguage not saved.', $result);
            self::runUserSetter($userObj, 'setReceiveNotifications', $post, 'setReceiveNotifications not saved.', $result);
            $_SESSION['userObj'] = serialize($userObj);
            if (empty($result['error_msg'])) {
                $result['success_msg'] = mds_t('settings.saved_user');
            }
        } elseif ($save === 'email') {
            self::handleEmailSave($userObj, $post, $result);
        } elseif ($save === 'password') {
            self::handlePasswordSave($userObj, $post, $result);
        } elseif ($save === 'dashboard_data') {
            try {
                $userObj->setDashboardUpdateInterval($post);
                $_SESSION['userObj'] = serialize($userObj);
                $result['success_msg'] = mds_t('settings.saved_dashboard');
            } catch (Exception $e) {
                $result['error_msg'] = 'Dashboard settings not saved.';
                self::logException($result['error_msg'], $e);
            }
        } elseif ($save === 'users') {
            try {
                if (!myFunctions::isUserAdmin((int)$userObj->getId())) {
                    throw new RuntimeException('Admin permissions required.');
                }
                dbUpdateData::updateUserStatus($post);
                $result['success_msg'] = 'User Status updated.';
            } catch (Exception $e) {
                $result['error_msg'] = 'Error on update User Status.';
                self::logException($result['error_msg'], $e);
            }
        } elseif ($save === 'addNewUserToBoard') {
            try {
                $addNewBoardToUserReturn = dbUpdateData::addNewBoardToUser($post, $userObj->getId());
                if ($addNewBoardToUserReturn) {
                    $result['success_msg'] = 'Board added successfully.<br>Wait for the next Lora update.';
                } else {
                    $result['error_msg'] = 'Board not added.';
                    writeToLogFunction::write_to_log($result['error_msg'], $_SERVER["SCRIPT_FILENAME"]);
                }
            } catch (Exception $e) {
                $result['error_msg'] = $e->getMessage();
                self::logException($result['error_msg'], $e);
            }
        } elseif ($save === 'boardAccess') {
            try {
                self::handleBoardAccessSave($userObj, $post);
                $result['success_msg'] = mds_t('settings.access_saved');
            } catch (Exception $e) {
                $result['error_msg'] = $e->getMessage();
                self::logException('Board access settings not saved.', $e);
            }
        } elseif ($save === 'sensorAccess') {
            try {
                self::handleSensorAccessSave($userObj, $post);
                $result['success_msg'] = mds_t('settings.access_saved');
            } catch (Exception $e) {
                $result['error_msg'] = $e->getMessage();
                self::logException('Sensor access settings not saved.', $e);
            }
        } elseif ($save === 'serverSetting') {
            try {
                if (!myFunctions::isUserAdmin((int)$userObj->getId())) {
                    throw new RuntimeException('Admin permissions required.');
                }
                $config->saveServerSettings($post);
                $result['success_msg'] = mds_t('settings.saved_server');
            } catch (Exception $e) {
                $result['error_msg'] = $e->getMessage();
                self::logException('Server settings not saved.', $e);
            }
        } elseif ($save === 'testMailUser') {
            if (NotificationService::sendTestEmail($userObj->getEmail(), $config, 'user-settings')) {
                $result['success_msg'] = 'Test email sent to your user address.';
            } else {
                $result['error_msg'] = 'Test email could not be sent.';
            }
        } elseif ($save === 'testMailSystem') {
            $targetEmail = trim((string)($config::$systemEmailAddress ?: $config::$adminEmailAddress ?: $userObj->getEmail()));
            if (NotificationService::sendTestEmail($targetEmail, $config, 'admin-settings')) {
                $result['success_msg'] = 'System test email sent.';
            } else {
                $result['error_msg'] = 'System test email could not be sent.';
            }
        } elseif ($save === 'runAutomaticMigrations') {
            try {
                $migrationResult = self::runAutomaticMigrationActions($userObj);
                if (!empty($migrationResult['remainingChecks'])) {
                    $result['error_msg'] = mds_t(
                        'settings.migration_actions_remaining',
                        array(implode(', ', $migrationResult['remainingChecks']))
                    );
                } else {
                    $result['success_msg'] = mds_t(
                        'settings.migration_actions_success',
                        array((int)$migrationResult['executedActions'])
                    );
                }
            } catch (Throwable $e) {
                $result['error_msg'] = mds_t('settings.migration_actions_error') . ' ' . $e->getMessage();
                self::logException('Automatic migration actions failed.', $e);
            }
        }

        return $result;
    }

    public static function handleBoardFormSubmission($userObj, $post)
    {
        $result = array(
            'success_msg' => null,
            'error_msg' => null,
        );

        if (isset($post['submit_formBoards'])) {
            try {
                if (!myFunctions::canUserEditBoard((int)$userObj->getId(), (int)($post['id'] ?? 0))) {
                    throw new RuntimeException('Access denied.');
                }
                if (!myFunctions::isUserAdmin((int)$userObj->getId())) {
                    $post['ownerId'] = $userObj->getId();
                }
                dbUpdateData::updateBoard($post);
                $result['success_msg'] = 'Board successfully updated.';
            } catch (Exception $e) {
                $result['error_msg'] = 'Error while saving board changes.';
            }
        } elseif (isset($post['submit_formBoards_remove'])) {
            try {
                if (!myFunctions::canUserManageBoardAccess((int)$userObj->getId(), (int)($post['id'] ?? 0))) {
                    throw new RuntimeException('Access denied.');
                }
                dbUpdateData::removeBoardOwner($post);
                $result['success_msg'] = 'Board successfully removed.';
            } catch (Exception $e) {
                $result['error_msg'] = 'Error while removing board.';
            }
        }

        return $result;
    }

    public static function buildPageData($userObj, $config)
    {
        $isAdmin = myFunctions::isUserAdmin((int)$userObj->getId());
        $myBoards = array();
        $allBoards = array();
        $allUsers = array();
        $migrationStatus = array();
        $legacyStatus = array();
        $accessBoards = array();
        $canManageAccess = false;

        try {
            $notificationOverview = NotificationService::getNotificationStatusOverview($userObj->getId(), $isAdmin);
        } catch (Throwable $e) {
            writeToLogFunction::exception($e, __FILE__, array(
                'message' => 'Notification overview could not be loaded on settings page.',
            ));
            $notificationOverview = array(
                'jobStatus' => null,
                'offlineBoards' => array(),
                'activeSensorAlerts' => array(),
            );
        }

        try {
            $myBoards = $userObj->getMyBoardsAll();
            if (!is_array($myBoards)) {
                $myBoards = array();
            }
        } catch (Throwable $e) {
            writeToLogFunction::exception($e, __FILE__, array(
                'message' => 'User board list could not be loaded on settings page.',
                'userId' => (int)$userObj->getId(),
            ));
        }

        if ($isAdmin) {
            try {
                $allBoards = $userObj->getAllBoardsAdmin();
                if (!is_array($allBoards)) {
                    $allBoards = array();
                }
            } catch (Throwable $e) {
                writeToLogFunction::exception($e, __FILE__, array(
                    'message' => 'Admin board list could not be loaded on settings page.',
                    'userId' => (int)$userObj->getId(),
                ));
            }

        }

        try {
            $accessBoards = self::buildAccessBoardData((int)$userObj->getId());
            $canManageAccess = !empty($accessBoards);
        } catch (Throwable $e) {
            writeToLogFunction::exception($e, __FILE__, array(
                'message' => 'Access board data could not be loaded on settings page.',
                'userId' => (int)$userObj->getId(),
            ));
        }

        if ($isAdmin || $canManageAccess) {
            try {
                $allUsers = myFunctions::getAllUsers();
                if (!is_array($allUsers)) {
                    $allUsers = array();
                }
            } catch (Throwable $e) {
                writeToLogFunction::exception($e, __FILE__, array(
                    'message' => 'User list could not be loaded on settings page.',
                    'userId' => (int)$userObj->getId(),
                ));
            }
        }

        try {
            $migrationStatus = self::buildMigrationStatus();
        } catch (Throwable $e) {
            writeToLogFunction::exception($e, __FILE__, array(
                'message' => 'Migration status could not be loaded on settings page.',
                'userId' => (int)$userObj->getId(),
            ));
        }

        if ($isAdmin) {
            try {
                $legacyStatus = self::buildLegacyStatus();
            } catch (Throwable $e) {
                writeToLogFunction::exception($e, __FILE__, array(
                    'message' => 'Legacy status could not be loaded on settings page.',
                    'userId' => (int)$userObj->getId(),
                ));
            }
        }

        return array(
            'demoMode' => (bool) $config::$demoMode,
            'showQrCode' => $config::$ShowQrCode,
            'apiKey' => $config::$apiKey,
            'otaUpdateSecret' => $config::$otaUpdateSecret,
            'sendEmails' => $config::$sendEmails,
            'myBoards' => $myBoards,
            'allBoards' => $allBoards,
            'allUsers' => $allUsers,
            'accessBoards' => $accessBoards,
            'canManageAccess' => $canManageAccess,
            'timeZones' => self::getTimeZoneList(),
            'currentLogContent' => self::getCurrentLogContent(),
            'otaUpdateLogs' => self::getOtaUpdateLogOverview(),
            'isAdmin' => $isAdmin,
            'notificationOverview' => $notificationOverview,
            'migrationStatus' => $migrationStatus,
            'legacyStatus' => $legacyStatus,
        );
    }

    private static function handleBoardAccessSave($userObj, $post)
    {
        $boardId = (int)($post['boardId'] ?? 0);
        $targetUserId = (int)($post['targetUserId'] ?? 0);
        $action = (string)($post['permissionAction'] ?? 'save');

        if ($action === 'remove') {
            myFunctions::removeBoardPermission((int)$userObj->getId(), $boardId, $targetUserId);
            return;
        }

        $role = (string)($post['role'] ?? 'observer');
        myFunctions::saveBoardPermission((int)$userObj->getId(), $boardId, $targetUserId, $role);
    }

    private static function handleSensorAccessSave($userObj, $post)
    {
        $boardId = (int)($post['boardId'] ?? 0);
        $sensorId = (int)($post['sensorId'] ?? 0);
        $targetUserId = (int)($post['targetUserId'] ?? 0);
        $action = (string)($post['permissionAction'] ?? 'save');

        if ($action === 'remove') {
            myFunctions::removeSensorPermission((int)$userObj->getId(), $boardId, $sensorId, $targetUserId);
            return;
        }

        $role = (string)($post['role'] ?? 'observer');
        myFunctions::saveSensorPermission((int)$userObj->getId(), $boardId, $sensorId, $targetUserId, $role);
    }

    private static function buildAccessBoardData($userId)
    {
        $boards = myFunctions::getManageableBoards((int)$userId);
        foreach ($boards as $boardIndex => $board) {
            $boardId = (int)($board['id'] ?? 0);
            $boards[$boardIndex]['sensors'] = myFunctions::getAllSensorsOfBoardOld($boardId);
            $boards[$boardIndex]['boardPermissions'] = myFunctions::getBoardAccessList($boardId);
            $boards[$boardIndex]['sensorPermissions'] = myFunctions::getSensorAccessList($boardId);
        }

        return $boards;
    }

    public static function buildMigrationStatus()
    {
        $pdo = dbConfig::getInstance();
        $migrations = self::getMigrationDefinitions();

        foreach ($migrations as $migrationIndex => $migration) {
            $missingChecks = array();
            foreach ($migration['checks'] as $check) {
                if (!self::migrationCheckPassed($pdo, $check)) {
                    $missingChecks[] = self::describeMigrationCheck($check);
                }
            }

            $migrations[$migrationIndex]['applied'] = empty($missingChecks);
            $migrations[$migrationIndex]['missingChecks'] = $missingChecks;
        }

        return $migrations;
    }

    public static function buildLegacyStatus()
    {
        $pdo = dbConfig::getInstance();
        $legacyPasswordResetTokens = null;
        $legacyWakeupRows = null;
        $notificationColumns = array();

        try {
            $legacyPasswordResetTokens = self::countLegacyPasswordResetTokens($pdo);
        } catch (Throwable $e) {
            writeToLogFunction::exception($e, __FILE__, array(
                'message' => 'Legacy password reset token check failed.',
            ));
        }

        try {
            $legacyWakeupRows = self::countLegacyWakeupStandbyRows($pdo);
        } catch (Throwable $e) {
            writeToLogFunction::exception($e, __FILE__, array(
                'message' => 'Legacy WakeupStan row check failed.',
            ));
        }

        foreach (array('receive_offline_notifications', 'receive_sensor_notifications') as $notificationColumn) {
            try {
                $notificationColumns[$notificationColumn] = self::migrationCheckPassed($pdo, array('type' => 'column', 'table' => 'users', 'column' => $notificationColumn));
            } catch (Throwable $e) {
                writeToLogFunction::exception($e, __FILE__, array(
                    'message' => 'Legacy notification column check failed.',
                    'column' => $notificationColumn,
                ));
                $notificationColumns[$notificationColumn] = false;
            }
        }

        $missingNotificationColumns = array_keys(array_filter($notificationColumns, function ($exists) {
            return !$exists;
        }));

        return array(
            array(
                'label' => 'Deprecated user methods',
                'status' => 'clean',
                'details' => 'Obsolete methods removed from user domain class.',
                'action' => 'No action required.',
            ),
            array(
                'label' => 'Password reset legacy tokens',
                'status' => $legacyPasswordResetTokens === 0 ? 'clean' : 'action_required',
                'details' => $legacyPasswordResetTokens === null
                    ? 'Password reset legacy tokens could not be checked. See log.'
                    : $legacyPasswordResetTokens . ' unhashed password reset token rows found.',
                'action' => $legacyPasswordResetTokens === 0
                    ? 'No legacy reset tokens detected.'
                    : ($legacyPasswordResetTokens === null
                        ? 'Review the logged database error before removing the passwordCode fallback.'
                        : 'Wait until all old reset links have expired, then remove the legacy passwordCode fallback in dbUpdateData::readUserPasswordCode().'),
            ),
            array(
                'label' => 'Notification schema fallback',
                'status' => empty($missingNotificationColumns) ? 'clean' : 'action_required',
                'details' => empty($missingNotificationColumns)
                    ? 'users.receive_offline_notifications and users.receive_sensor_notifications are present.'
                    : 'Missing columns: ' . implode(', ', $missingNotificationColumns),
                'action' => empty($missingNotificationColumns)
                    ? 'Schema is ready to remove the fallback column checks.'
                    : 'Run docs/db_design/migrations/2026-04-26_notification_and_gauge_style_idempotent.sql on all environments.',
            ),
            array(
                'label' => 'WakeupStan legacy event rows',
                'status' => $legacyWakeupRows === 0 ? 'clean' : 'action_required',
                'details' => $legacyWakeupRows === null
                    ? 'WakeupStan legacy rows could not be checked. See log.'
                    : $legacyWakeupRows . ' rows still use the old 0|1|0|0 standby marker.',
                'action' => $legacyWakeupRows === 0
                    ? 'Legacy WakeupStan parser can be removed after one last review.'
                    : ($legacyWakeupRows === null
                        ? 'Review the logged database error before removing buildLegacyWakeupStandbyEntry() from InternalPageService.'
                        : 'Normalize existing WakeupStan rows before removing buildLegacyWakeupStandbyEntry() from InternalPageService.'),
            ),
        );
    }

    private static function getMigrationDefinitions()
    {
        return array(
            array(
                'file' => 'docs/db_design/migrations/2026-04-25_schema_hardening.sql',
                'label' => 'Schema hardening / text sensor data',
                'checks' => array(
                    array('type' => 'column_type', 'table' => 'sensorData', 'column' => 'value1', 'contains' => 'varchar(255)'),
                    array('type' => 'index', 'table' => 'securityTokens', 'index' => 'idx_securityTokens_userId_createdAt'),
                    array('type' => 'index', 'table' => 'sensorData', 'index' => 'idx_sensorData_sensorId_reading_time'),
                    array('type' => 'index', 'table' => 'boardConfig', 'index' => 'idx_boardConfig_ttnAppId_ttnDevId'),
                    array('type' => 'index', 'table' => 'sensorConfig', 'index' => 'idx_sensorConfig_boardId_typId_name'),
                ),
            ),
            array(
                'file' => 'docs/db_design/migrations/2026-04-25_data_cleanup.sql',
                'label' => 'Data cleanup / wakeup event normalization',
                'checks' => array(
                    array('type' => 'no_rows', 'table' => 'securityTokens', 'where' => "`userId` = 0 OR `securityToken` = ''"),
                    array('type' => 'no_rows', 'table' => 'users', 'where' => "`lastName` = 'HÃ¶che'"),
                    array('type' => 'no_rows', 'table' => 'sensorTypes', 'where' => "`name` = 'GPS' AND `description` = 'Coorinates'"),
                    array('type' => 'row_absent_or_value', 'table' => 'sensorTypes', 'whereColumn' => 'name', 'whereValue' => 'WakeupStan', 'column' => 'description', 'expected' => 'Wakeup / standby event'),
                    array('type' => 'no_rows', 'table' => 'sensorConfig', 'where' => "`name` = 'Wakeup unknown'"),
                ),
            ),
            array(
                'file' => 'docs/db_design/migrations/2026-04-26_notification_and_gauge_style_idempotent.sql',
                'label' => 'Notifications and gauge styles',
                'checks' => array(
                    array('type' => 'column', 'table' => 'sensorChannelConfig', 'column' => 'GaugeStyle'),
                    array('type' => 'column', 'table' => 'sensorChannelConfig', 'column' => 'AlertEnabled'),
                    array('type' => 'column', 'table' => 'sensorChannelConfig', 'column' => 'AlertLowValue'),
                    array('type' => 'column', 'table' => 'sensorChannelConfig', 'column' => 'AlertHighValue'),
                    array('type' => 'column', 'table' => 'sensorChannelConfig', 'column' => 'AlertState'),
                    array('type' => 'column', 'table' => 'sensorChannelConfig', 'column' => 'LastAlertSentAt'),
                    array('type' => 'column', 'table' => 'users', 'column' => 'receive_offline_notifications'),
                    array('type' => 'column', 'table' => 'users', 'column' => 'receive_sensor_notifications'),
                    array('type' => 'column', 'table' => 'users', 'column' => 'dashboardOnlineOnly'),
                    array('type' => 'column', 'table' => 'users', 'column' => 'preferredChartWindowDays'),
                    array('type' => 'column', 'table' => 'users', 'column' => 'eventTimelineWindowHours'),
                ),
            ),
            array(
                'file' => 'docs/db_design/migrations/2026-05-14_user_language.sql',
                'label' => 'User language setting',
                'checks' => array(
                    array('type' => 'column', 'table' => 'users', 'column' => 'language'),
                ),
            ),
            array(
                'file' => 'docs/db_design/migrations/2026-06-07_firmware_version.sql',
                'label' => 'Board firmware version',
                'checks' => array(
                    array('type' => 'column_type', 'table' => 'boardConfig', 'column' => 'firmwareVersion', 'contains' => 'varchar(64)'),
                ),
            ),
            array(
                'file' => 'docs/db_design/migrations/2026-07-06_roles_and_permissions.sql',
                'label' => 'Roles and board/sensor permissions',
                'checks' => array(
                    array('type' => 'table', 'table' => 'roles'),
                    array('type' => 'table', 'table' => 'user_roles'),
                    array('type' => 'table', 'table' => 'board_permissions'),
                    array('type' => 'table', 'table' => 'sensor_permissions'),
                    array('type' => 'table', 'table' => 'permission_audit_log'),
                    array('type' => 'index', 'table' => 'board_permissions', 'index' => 'uniq_board_permissions_board_user'),
                    array('type' => 'index', 'table' => 'sensor_permissions', 'index' => 'uniq_sensor_permissions_sensor_user'),
                ),
            ),
        );
    }

    private static function countLegacyPasswordResetTokens(PDO $pdo)
    {
        $statement = $pdo->query(
            "SELECT COUNT(*) AS legacyCount
             FROM users
             WHERE passwordCode IS NOT NULL
               AND passwordCode <> ''
               AND passwordCode NOT REGEXP '^[A-Fa-f0-9]{64}$'"
        );
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return (int)($row['legacyCount'] ?? 0);
    }

    private static function countLegacyWakeupStandbyRows(PDO $pdo)
    {
        $statement = $pdo->query(
            "SELECT COUNT(*) AS legacyCount
             FROM sensorData
             WHERE value1 = '0'
               AND value2 = '1'
               AND value3 = '0'
               AND value4 = '0'"
        );
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return (int)($row['legacyCount'] ?? 0);
    }

    public static function runAutomaticMigrationActions($userObj)
    {
        if (!myFunctions::isUserAdmin((int)$userObj->getId())) {
            throw new RuntimeException('Admin permissions required.');
        }

        $pdo = dbConfig::getInstance();
        $executedActions = 0;
        $migrations = self::getMigrationDefinitions();

        if (!self::migrationDefinitionPassed($pdo, $migrations[0])) {
            $executedActions += self::runSchemaHardeningActions($pdo);
        }
        if (!self::migrationDefinitionPassed($pdo, $migrations[1])) {
            $executedActions += self::runDataCleanupActions($pdo);
        }
        if (!self::migrationDefinitionPassed($pdo, $migrations[2])) {
            $executedActions += self::runNotificationAndGaugeActions($pdo);
        }
        if (!self::migrationDefinitionPassed($pdo, $migrations[3])) {
            $executedActions += self::runLanguageMigrationActions($pdo);
        }
        if (!self::migrationDefinitionPassed($pdo, $migrations[4])) {
            $executedActions += self::runFirmwareVersionMigrationActions($pdo);
        }
        if (!self::migrationDefinitionPassed($pdo, $migrations[5])) {
            $executedActions += self::runRolesAndPermissionsMigrationActions($pdo);
        }

        writeToLogFunction::write_to_log(
            'Automatic migration actions finished. Executed actions: ' . $executedActions,
            __FILE__
        );

        return array(
            'executedActions' => $executedActions,
            'remainingChecks' => self::getRemainingMigrationChecks($pdo),
        );
    }

    private static function migrationDefinitionPassed(PDO $pdo, array $migration)
    {
        foreach ($migration['checks'] as $check) {
            if (!self::migrationCheckPassed($pdo, $check)) {
                return false;
            }
        }

        return true;
    }

    private static function getRemainingMigrationChecks(PDO $pdo)
    {
        $remainingChecks = array();
        foreach (self::getMigrationDefinitions() as $migration) {
            foreach ($migration['checks'] as $check) {
                if (!self::migrationCheckPassed($pdo, $check)) {
                    $remainingChecks[] = $migration['label'] . ': ' . self::describeMigrationCheck($check);
                }
            }
        }

        return $remainingChecks;
    }

    private static function runSchemaHardeningActions(PDO $pdo)
    {
        $actions = 0;

        $schemaStatements = array(
            "ALTER TABLE `sensorData` MODIFY `value1` varchar(255) NOT NULL",
            "ALTER TABLE `sensorData` MODIFY `value2` varchar(255) DEFAULT NULL",
            "ALTER TABLE `sensorData` MODIFY `value3` varchar(255) DEFAULT NULL",
            "ALTER TABLE `sensorData` MODIFY `value4` varchar(255) DEFAULT NULL",
        );

        if (!self::migrationCheckPassed($pdo, array('type' => 'column_type', 'table' => 'sensorData', 'column' => 'value1', 'contains' => 'varchar(255)'))) {
            foreach ($schemaStatements as $statement) {
                self::executeMigrationStatement($pdo, $statement);
                $actions++;
            }
        }

        $actions += self::addIndexIfMissing($pdo, 'securityTokens', 'idx_securityTokens_userId_createdAt', '`userId`, `createdAt`');
        $actions += self::addIndexIfMissing($pdo, 'sensorData', 'idx_sensorData_sensorId_reading_time', '`sensorId`, `reading_time`');
        $actions += self::addIndexIfMissing($pdo, 'boardConfig', 'idx_boardConfig_ttnAppId_ttnDevId', '`ttnAppId`, `ttnDevId`');
        $actions += self::addIndexIfMissing($pdo, 'sensorConfig', 'idx_sensorConfig_boardId_typId_name', '`boardId`, `typId`, `name`');

        return $actions;
    }

    private static function runDataCleanupActions(PDO $pdo)
    {
        $actions = 0;

        self::executeMigrationStatement($pdo, "CREATE TABLE IF NOT EXISTS `migration_backup_invalid_securityTokens_20260425` LIKE `securityTokens`");
        $actions++;

        self::executeMigrationStatement(
            $pdo,
            "INSERT INTO `migration_backup_invalid_securityTokens_20260425`
             SELECT `securityTokens`.*
             FROM `securityTokens`
             LEFT JOIN `migration_backup_invalid_securityTokens_20260425` backupTokens
               ON backupTokens.`id` = `securityTokens`.`id`
             WHERE (`securityTokens`.`userId` = 0 OR `securityTokens`.`securityToken` = '')
               AND backupTokens.`id` IS NULL"
        );
        $actions++;

        $cleanupStatements = array(
            "DELETE FROM `securityTokens` WHERE `userId` = 0 OR `securityToken` = ''",
            "UPDATE `boardConfig` SET `location` = NULLIF(TRIM(`location`), ''), `description` = NULLIF(TRIM(`description`), ''), `firmwareVersion` = NULLIF(TRIM(`firmwareVersion`), ''), `ttnAppId` = NULLIF(TRIM(`ttnAppId`), ''), `ttnDevId` = NULLIF(TRIM(`ttnDevId`), '')",
            "UPDATE `sensorConfig` SET `sensorAddress` = NULLIF(TRIM(`sensorAddress`), ''), `description` = NULLIF(TRIM(`description`), ''), `locationOfMeasurement` = NULLIF(TRIM(`locationOfMeasurement`), '')",
            "UPDATE `sensorChannelConfig` SET `name` = NULLIF(TRIM(`name`), ''), `description` = NULLIF(TRIM(`description`), ''), `locationOfMeasurement` = NULLIF(TRIM(`locationOfMeasurement`), '')",
            "UPDATE `users` SET `lastName` = 'Höche' WHERE `lastName` = 'HÃ¶che'",
            "UPDATE `sensorTypes` SET `description` = 'Coordinates' WHERE `name` = 'GPS' AND `description` = 'Coorinates'",
            "UPDATE `sensorTypes` SET `siUnitVal1` = '', `siUnitVal2` = '', `siUnitVal3` = '', `siUnitVal4` = '', `description` = 'Wakeup / standby event', `MaxNrOfValues` = CASE WHEN `MaxNrOfValues` < 4 THEN 4 ELSE `MaxNrOfValues` END WHERE `name` = 'WakeupStan'",
            "UPDATE `sensorConfig` SET `name` = 'Standby enter' WHERE `name` = 'Wakeup unknown'",
            "UPDATE `sensorChannelConfig` SET `name` = CASE `channelNr` WHEN 1 THEN 'Value1' WHEN 2 THEN 'Value2' WHEN 3 THEN 'Value3' WHEN 4 THEN 'Value4' ELSE `name` END, `description` = CASE `channelNr` WHEN 1 THEN 'Event value 1' WHEN 2 THEN 'Event value 2' WHEN 3 THEN 'Event value 3' WHEN 4 THEN 'Event value 4' ELSE `description` END, `onDashboard` = 0 WHERE `sensorConfigId` IN (SELECT `id` FROM (SELECT `id` FROM `sensorConfig` WHERE `typId` = (SELECT `id` FROM `sensorTypes` WHERE `name` = 'WakeupStan' LIMIT 1)) AS `wakeupsensors`)",
        );

        foreach ($cleanupStatements as $statement) {
            self::executeMigrationStatement($pdo, $statement);
            $actions++;
        }

        return $actions;
    }

    private static function runNotificationAndGaugeActions(PDO $pdo)
    {
        $actions = 0;

        $actions += self::addColumnIfMissing($pdo, 'sensorChannelConfig', 'GaugeStyle', "`GaugeStyle` varchar(20) NOT NULL DEFAULT 'classic' AFTER `GaugeNormalAreaColor`");
        $actions += self::addColumnIfMissing($pdo, 'sensorChannelConfig', 'AlertEnabled', "`AlertEnabled` tinyint NOT NULL DEFAULT '0' AFTER `GaugeStyle`");
        $actions += self::addColumnIfMissing($pdo, 'sensorChannelConfig', 'AlertLowValue', "`AlertLowValue` decimal(12,4) DEFAULT NULL AFTER `AlertEnabled`");
        $actions += self::addColumnIfMissing($pdo, 'sensorChannelConfig', 'AlertHighValue', "`AlertHighValue` decimal(12,4) DEFAULT NULL AFTER `AlertLowValue`");
        $actions += self::addColumnIfMissing($pdo, 'sensorChannelConfig', 'AlertState', "`AlertState` varchar(20) DEFAULT NULL AFTER `AlertHighValue`");
        $actions += self::addColumnIfMissing($pdo, 'sensorChannelConfig', 'LastAlertSentAt', "`LastAlertSentAt` timestamp NULL DEFAULT NULL AFTER `AlertState`");
        $actions += self::addColumnIfMissing($pdo, 'users', 'receive_offline_notifications', "`receive_offline_notifications` tinyint NOT NULL DEFAULT '0' AFTER `receive_notifications`");
        $actions += self::addColumnIfMissing($pdo, 'users', 'receive_sensor_notifications', "`receive_sensor_notifications` tinyint NOT NULL DEFAULT '0' AFTER `receive_offline_notifications`");
        $actions += self::addColumnIfMissing($pdo, 'users', 'dashboardOnlineOnly', "`dashboardOnlineOnly` tinyint NOT NULL DEFAULT '0' AFTER `receive_sensor_notifications`");
        $actions += self::addColumnIfMissing($pdo, 'users', 'preferredChartWindowDays', "`preferredChartWindowDays` int NOT NULL DEFAULT '7' AFTER `dashboardOnlineOnly`");
        $actions += self::addColumnIfMissing($pdo, 'users', 'eventTimelineWindowHours', "`eventTimelineWindowHours` int NOT NULL DEFAULT '24' AFTER `preferredChartWindowDays`");

        if (
            self::migrationCheckPassed($pdo, array('type' => 'column', 'table' => 'users', 'column' => 'receive_offline_notifications')) &&
            self::migrationCheckPassed($pdo, array('type' => 'column', 'table' => 'users', 'column' => 'receive_sensor_notifications'))
        ) {
            self::executeMigrationStatement($pdo, "UPDATE `users` SET `receive_offline_notifications` = `receive_notifications` WHERE COALESCE(`receive_offline_notifications`, 0) = 0");
            self::executeMigrationStatement($pdo, "UPDATE `users` SET `receive_sensor_notifications` = `receive_notifications` WHERE COALESCE(`receive_sensor_notifications`, 0) = 0");
            $actions += 2;
        }

        return $actions;
    }

    private static function runLanguageMigrationActions(PDO $pdo)
    {
        $actions = self::addColumnIfMissing($pdo, 'users', 'language', "`language` varchar(5) NOT NULL DEFAULT 'en' AFTER `Timezone`");

        if (self::migrationCheckPassed($pdo, array('type' => 'column', 'table' => 'users', 'column' => 'language'))) {
            self::executeMigrationStatement($pdo, "UPDATE `users` SET `language` = 'en' WHERE `language` IS NULL OR `language` = ''");
            $actions++;
        }

        return $actions;
    }

    private static function runFirmwareVersionMigrationActions(PDO $pdo)
    {
        if (self::migrationCheckPassed($pdo, array('type' => 'column_type', 'table' => 'boardConfig', 'column' => 'firmwareVersion', 'contains' => 'varchar(64)'))) {
            return 0;
        }

        self::executeMigrationStatement($pdo, "ALTER TABLE `boardConfig` MODIFY `firmwareVersion` varchar(64) DEFAULT NULL");
        return 1;
    }

    private static function runRolesAndPermissionsMigrationActions(PDO $pdo)
    {
        $actions = 0;
        foreach (self::getRolesAndPermissionsMigrationStatements() as $statement) {
            self::executeMigrationStatement($pdo, $statement);
            $actions++;
        }

        return $actions;
    }

    private static function getRolesAndPermissionsMigrationStatements()
    {
        return array(
            "CREATE TABLE IF NOT EXISTS `roles` (
                `id` int NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                `description` varchar(255) DEFAULT NULL,
                `isGlobal` tinyint NOT NULL DEFAULT '1',
                `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_roles_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `user_roles` (
                `id` int NOT NULL AUTO_INCREMENT,
                `userId` int NOT NULL,
                `roleId` int NOT NULL,
                `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_user_roles_user_role` (`userId`, `roleId`),
                KEY `idx_user_roles_roleId` (`roleId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `board_permissions` (
                `id` int NOT NULL AUTO_INCREMENT,
                `boardId` int NOT NULL,
                `userId` int NOT NULL,
                `role` varchar(20) NOT NULL DEFAULT 'observer',
                `canView` tinyint NOT NULL DEFAULT '1',
                `canEdit` tinyint NOT NULL DEFAULT '0',
                `canManageUsers` tinyint NOT NULL DEFAULT '0',
                `canReceiveAlerts` tinyint NOT NULL DEFAULT '1',
                `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_board_permissions_board_user` (`boardId`, `userId`),
                KEY `idx_board_permissions_userId` (`userId`),
                KEY `idx_board_permissions_role` (`role`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `sensor_permissions` (
                `id` int NOT NULL AUTO_INCREMENT,
                `sensorId` int NOT NULL,
                `userId` int NOT NULL,
                `role` varchar(20) NOT NULL DEFAULT 'observer',
                `canView` tinyint NOT NULL DEFAULT '1',
                `canEdit` tinyint NOT NULL DEFAULT '0',
                `canReceiveAlerts` tinyint NOT NULL DEFAULT '1',
                `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_sensor_permissions_sensor_user` (`sensorId`, `userId`),
                KEY `idx_sensor_permissions_userId` (`userId`),
                KEY `idx_sensor_permissions_role` (`role`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `permission_audit_log` (
                `id` int NOT NULL AUTO_INCREMENT,
                `actorUserId` int DEFAULT NULL,
                `targetUserId` int DEFAULT NULL,
                `resourceType` varchar(20) NOT NULL,
                `resourceId` int NOT NULL,
                `action` varchar(30) NOT NULL,
                `oldValue` text DEFAULT NULL,
                `newValue` text DEFAULT NULL,
                `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_permission_audit_resource` (`resourceType`, `resourceId`),
                KEY `idx_permission_audit_actor` (`actorUserId`),
                KEY `idx_permission_audit_target` (`targetUserId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "INSERT INTO `roles` (`name`, `description`, `isGlobal`) VALUES
                ('admin', 'System administrator', 1),
                ('user', 'Regular authenticated user', 1),
                ('owner', 'Board or sensor owner', 0),
                ('observer', 'Read-only observer with notifications', 0)
             ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `isGlobal` = VALUES(`isGlobal`)",
            "INSERT IGNORE INTO `user_roles` (`userId`, `roleId`)
             SELECT `users`.`id`, `roles`.`id`
             FROM `users`
             INNER JOIN `roles` ON `roles`.`name` = CASE WHEN `users`.`userGroupAdmin` = 1 THEN 'admin' ELSE 'user' END",
            "INSERT INTO `board_permissions` (`boardId`, `userId`, `role`, `canView`, `canEdit`, `canManageUsers`, `canReceiveAlerts`)
             SELECT `id`, `ownerUserId`, 'owner', 1, 1, 1, 1
             FROM `boardConfig`
             WHERE `ownerUserId` IS NOT NULL AND `ownerUserId` > 0
             ON DUPLICATE KEY UPDATE
                `role` = 'owner',
                `canView` = 1,
                `canEdit` = 1,
                `canManageUsers` = 1,
                `canReceiveAlerts` = 1",
        );
    }

    private static function addColumnIfMissing(PDO $pdo, $tableName, $columnName, $columnDefinition)
    {
        if (self::migrationCheckPassed($pdo, array('type' => 'column', 'table' => $tableName, 'column' => $columnName))) {
            return 0;
        }

        self::executeMigrationStatement($pdo, "ALTER TABLE `" . $tableName . "` ADD COLUMN " . $columnDefinition);
        return 1;
    }

    private static function addIndexIfMissing(PDO $pdo, $tableName, $indexName, $indexColumns)
    {
        if (self::migrationCheckPassed($pdo, array('type' => 'index', 'table' => $tableName, 'index' => $indexName))) {
            return 0;
        }

        self::executeMigrationStatement($pdo, "ALTER TABLE `" . $tableName . "` ADD INDEX `" . $indexName . "` (" . $indexColumns . ")");
        return 1;
    }

    private static function executeMigrationStatement(PDO $pdo, $sql)
    {
        $pdo->exec($sql);
    }

    private static function migrationCheckPassed(PDO $pdo, array $check)
    {
        try {
            if ($check['type'] === 'table') {
                $statement = $pdo->prepare(
                    "SELECT COUNT(*) AS matchCount
                     FROM INFORMATION_SCHEMA.TABLES
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = :tableName"
                );
                $statement->execute(array('tableName' => $check['table']));
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return ((int)($row['matchCount'] ?? 0) > 0);
            }

            if ($check['type'] === 'column' || $check['type'] === 'column_type') {
                $statement = $pdo->prepare(
                    "SELECT COLUMN_TYPE
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = :tableName
                       AND COLUMN_NAME = :columnName"
                );
                $statement->execute(array('tableName' => $check['table'], 'columnName' => $check['column']));
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    return false;
                }
                if ($check['type'] === 'column_type') {
                    return str_contains(strtolower((string)$row['COLUMN_TYPE']), strtolower((string)$check['contains']));
                }
                return true;
            }

            if ($check['type'] === 'index') {
                $statement = $pdo->prepare(
                    "SELECT COUNT(*) AS matchCount
                     FROM INFORMATION_SCHEMA.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = :tableName
                       AND INDEX_NAME = :indexName"
                );
                $statement->execute(array('tableName' => $check['table'], 'indexName' => $check['index']));
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return ((int)($row['matchCount'] ?? 0) > 0);
            }

            if ($check['type'] === 'row_value') {
                if (!preg_match('/^[A-Za-z0-9_]+$/', $check['table'] . $check['whereColumn'] . $check['column'])) {
                    return false;
                }
                $sql = "SELECT `" . $check['column'] . "` AS checkValue FROM `" . $check['table'] . "` WHERE `" . $check['whereColumn'] . "` = :whereValue LIMIT 1";
                $statement = $pdo->prepare($sql);
                $statement->execute(array('whereValue' => $check['whereValue']));
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return $row && (string)$row['checkValue'] === (string)$check['expected'];
            }

            if ($check['type'] === 'row_absent_or_value') {
                if (!preg_match('/^[A-Za-z0-9_]+$/', $check['table'] . $check['whereColumn'] . $check['column'])) {
                    return false;
                }
                $sql = "SELECT `" . $check['column'] . "` AS checkValue FROM `" . $check['table'] . "` WHERE `" . $check['whereColumn'] . "` = :whereValue LIMIT 1";
                $statement = $pdo->prepare($sql);
                $statement->execute(array('whereValue' => $check['whereValue']));
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return !$row || (string)$row['checkValue'] === (string)$check['expected'];
            }

            if ($check['type'] === 'no_rows') {
                $sql = self::sqlForNoRowsMigrationCheck($check);
                if ($sql === null) {
                    return false;
                }
                $statement = $pdo->prepare($sql);
                $statement->execute();
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return ((int)($row['badRows'] ?? 0) === 0);
            }
        } catch (Throwable $e) {
            writeToLogFunction::exception($e, __FILE__, array('migrationCheck' => $check));
            return false;
        }

        return false;
    }

    private static function sqlForNoRowsMigrationCheck(array $check)
    {
        $key = ($check['table'] ?? '') . '|' . ($check['where'] ?? '');
        $queries = array(
            "securityTokens|`userId` = 0 OR `securityToken` = ''" =>
                "SELECT COUNT(*) AS badRows FROM `securityTokens` WHERE `userId` = 0 OR `securityToken` = ''",
            "users|`lastName` = 'HÃ¶che'" =>
                "SELECT COUNT(*) AS badRows FROM `users` WHERE `lastName` = 'HÃ¶che'",
            "sensorTypes|`name` = 'GPS' AND `description` = 'Coorinates'" =>
                "SELECT COUNT(*) AS badRows FROM `sensorTypes` WHERE `name` = 'GPS' AND `description` = 'Coorinates'",
            "sensorConfig|`name` = 'Wakeup unknown'" =>
                "SELECT COUNT(*) AS badRows FROM `sensorConfig` WHERE `name` = 'Wakeup unknown'",
        );

        return $queries[$key] ?? null;
    }

    private static function describeMigrationCheck(array $check)
    {
        if ($check['type'] === 'table') {
            return 'table ' . $check['table'];
        }
        if ($check['type'] === 'column' || $check['type'] === 'column_type') {
            return 'column ' . $check['table'] . '.' . $check['column'];
        }
        if ($check['type'] === 'index') {
            return 'index ' . $check['table'] . '.' . $check['index'];
        }
        if ($check['type'] === 'row_value') {
            return 'data check ' . $check['table'] . '.' . $check['column'];
        }
        if ($check['type'] === 'row_absent_or_value') {
            return 'data check ' . $check['table'] . '.' . $check['column'];
        }
        if ($check['type'] === 'no_rows') {
            return 'cleanup check ' . $check['table'];
        }

        return 'unknown check';
    }

    public static function buildPrivacyExportData($userObj)
    {
        $pdo = dbConfig::getInstance();
        $userId = (int)$userObj->getId();
        $boards = $userObj->getMyBoardsAll();
        $boardIds = array();
        foreach ($boards as $board) {
            if (isset($board['id'])) {
                $boardIds[] = (int)$board['id'];
            }
        }

        $sensorConfigs = array();
        $sensorData = array();

        if (!empty($boardIds)) {
            $placeholders = implode(', ', array_fill(0, count($boardIds), '?'));

            $statement = $pdo->prepare(
                "SELECT sensorConfig.*, sensorTypes.name AS sensorTypeName
                 FROM sensorConfig
                 LEFT JOIN sensorTypes ON sensorTypes.id = sensorConfig.typId
                 WHERE sensorConfig.boardId IN ($placeholders)
                 ORDER BY sensorConfig.boardId, sensorConfig.id"
            );
            $statement->execute($boardIds);
            $sensorConfigs = $statement->fetchAll(PDO::FETCH_ASSOC);

            $statement = $pdo->prepare(
                "SELECT sensorData.*, sensorConfig.boardId
                 FROM sensorData
                 INNER JOIN sensorConfig ON sensorConfig.id = sensorData.sensorId
                 WHERE sensorConfig.boardId IN ($placeholders)
                 ORDER BY sensorData.id DESC"
            );
            $statement->execute($boardIds);
            $sensorData = $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        return array(
            'exportedAt' => gmdate('c'),
            'user' => array(
                'id' => $userId,
                'email' => $userObj->getEmail(),
                'firstName' => $userObj->getFirstName(),
                'lastName' => $userObj->getLastName(),
                'timezone' => $userObj->getTimezone(),
                'receiveNotifications' => (int)$userObj->getReceiveNotifications(),
                'receiveOfflineNotifications' => (int)$userObj->getReceiveOfflineNotifications(),
                'receiveSensorNotifications' => (int)$userObj->getReceiveSensorNotifications(),
                'dashboardOnlineOnly' => (int)$userObj->getDashboardOnlineOnly(),
                'preferredChartWindowDays' => (int)$userObj->getPreferredChartWindowDays(),
                'eventTimelineWindowHours' => (int)$userObj->getEventTimelineWindowHours(),
                'language' => $userObj->getLanguage(),
            ),
            'boards' => $boards,
            'sensorConfig' => $sensorConfigs,
            'sensorData' => $sensorData,
        );
    }

    private static function handleEmailSave($userObj, $post, &$result)
    {
        $password = $post['password'] ?? '';
        $email = trim($post['email'] ?? '');
        $email2 = trim($post['email2'] ?? '');

        if ($email !== $email2) {
            $result['error_msg'] = 'The entered email addresses are not the same.';
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['error_msg'] = 'The entered email address are not valid.';
            return;
        }
        if (!password_verify($password, $userObj->getPassword())) {
            $result['error_msg'] = 'Wrong password.';
            return;
        }

        try {
            $userObj->setEmail($post);
            $_SESSION['userObj'] = serialize($userObj);
            $result['success_msg'] = 'E-Mail address successfully saved.';
        } catch (Exception $e) {
            $result['error_msg'] = 'Email address not successfully saved.';
            self::logException($result['error_msg'], $e);
        }
    }

    private static function handlePasswordSave($userObj, $post, &$result)
    {
        $passwordAlt = $post['passwordOld'] ?? '';
        $passwordNew = trim($post['passwordNew'] ?? '');
        $passwordNew2 = trim($post['passwordNew2'] ?? '');

        if ($passwordNew !== $passwordNew2) {
            $result['error_msg'] = 'The entered passwords are not the same.';
            return;
        }
        if ($passwordNew === '') {
            $result['error_msg'] = 'Empty password is not allowed.';
            return;
        }
        if (!password_verify($passwordAlt, $userObj->getPassword())) {
            $result['error_msg'] = 'Please enter correct password.';
            return;
        }

        $password_hash = password_hash($passwordNew, PASSWORD_DEFAULT);
        try {
            $userObj->setUserPassword($password_hash);
            $_SESSION['userObj'] = serialize($userObj);
            $result['success_msg'] = 'Password successfully saved.';
        } catch (Exception $e) {
            $result['error_msg'] = 'Password reset not successfully.';
            self::logException($result['error_msg'], $e);
        }
    }

    private static function runUserSetter($userObj, $method, $post, $logMessage, &$result)
    {
        try {
            $userObj->$method($post);
        } catch (Exception $e) {
            $result['error_msg'] = $e->getMessage();
            self::logException($logMessage, $e);
        }
    }

    private static function logException($message, $exception)
    {
        writeToLogFunction::write_to_log($message, $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log($exception->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
    }

    public static function getTimeZoneList()
    {
        $allZones = array();
        $timestamp = time();
        foreach (timezone_identifiers_list() as $key => $live_zone) {
            date_default_timezone_set($live_zone);
            $allZones[$key]['zone'] = $live_zone;
            $allZones[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
        }
        date_default_timezone_set('Europe/Berlin');
        return $allZones;
    }

    public static function getCurrentLogContent()
    {
        $format = 'log';
        date_default_timezone_set('Europe/Berlin');
        $months = array(1 => 'Januar', 2 => 'Februar', 3 => 'Maerz', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember');
        $month = date('n');
        $year = date('Y');
        $logDirectory = dirname(__DIR__, 2) . "/var/log";
        $filename = $logDirectory . "/log_" . $months[$month] . "_$year.$format";

        if (!is_dir($logDirectory)) {
            return 'Log directory not found: ' . $logDirectory;
        }

        if (!is_readable($logDirectory)) {
            return 'Log directory is not readable: ' . $logDirectory;
        }

        if (!file_exists($filename)) {
            return 'Log file not found.';
        }

        $data = file_get_contents($filename);
        if ($data === false) {
            return 'Error while opening Log file.';
        }

        return $data;
    }

    public static function getOtaUpdateLogOverview($limit = 100)
    {
        $logDirectory = dirname(__DIR__, 2) . "/var/ota/logs";
        $filename = $logDirectory . "/log.csv";
        $overview = array(
            'path' => $filename,
            'entries' => array(),
            'message' => '',
        );

        if (!is_dir($logDirectory)) {
            $overview['message'] = 'OTA log directory not found: ' . $logDirectory;
            return $overview;
        }

        if (!is_readable($logDirectory)) {
            $overview['message'] = 'OTA log directory is not readable: ' . $logDirectory;
            return $overview;
        }

        if (!is_file($filename)) {
            $overview['message'] = 'OTA log file not found.';
            return $overview;
        }

        $handle = fopen($filename, 'r');
        if ($handle === false) {
            $overview['message'] = 'Error while opening OTA log file.';
            return $overview;
        }

        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            $message = trim((string)$row[2]);
            $overview['entries'][] = array(
                'datetime' => trim((string)$row[0]),
                'request' => trim((string)$row[1]),
                'message' => $message,
                'status' => self::classifyOtaLogMessage($message),
            );
        }
        fclose($handle);

        $overview['entries'] = array_slice(array_reverse($overview['entries']), 0, max(1, (int)$limit));
        return $overview;
    }

    private static function classifyOtaLogMessage($message)
    {
        $normalizedMessage = strtolower((string)$message);

        if (str_contains($normalizedMessage, 'send file')) {
            return 'sent';
        }
        if (str_contains($normalizedMessage, 'already current')) {
            return 'current';
        }
        if (str_contains($normalizedMessage, 'disabled')) {
            return 'disabled';
        }
        if (str_contains($normalizedMessage, 'validation failed') || str_contains($normalizedMessage, 'not configured')) {
            return 'rejected';
        }
        if (str_contains($normalizedMessage, 'not found')) {
            return 'missing';
        }
        if (str_contains($normalizedMessage, 'otaRequest: resolved')) {
            return 'checked';
        }

        return 'info';
    }
}
