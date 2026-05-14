<?php

require_once(__DIR__ . '/InternalPageService.php');
require_once(__DIR__ . '/dbUpdateData.php');
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
        } elseif ($save === 'serverSetting') {
            try {
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
        }

        return $result;
    }

    public static function handleBoardFormSubmission($post)
    {
        $result = array(
            'success_msg' => null,
            'error_msg' => null,
        );

        if (isset($post['submit_formBoards'])) {
            try {
                dbUpdateData::updateBoard($post);
                $result['success_msg'] = 'Board successfully updated.';
            } catch (Exception $e) {
                $result['error_msg'] = 'Error while saving board changes.';
            }
        } elseif (isset($post['submit_formBoards_remove'])) {
            try {
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
        $isAdmin = ((int) $userObj->getUserGroupAdmin() === 1);

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

        return array(
            'demoMode' => (bool) $config::$demoMode,
            'showQrCode' => $config::$ShowQrCode,
            'apiKey' => $config::$apiKey,
            'sendEmails' => $config::$sendEmails,
            'myBoards' => $userObj->getMyBoardsAll(),
            'allBoards' => $isAdmin ? $userObj->getAllBoardsAdmin() : array(),
            'allUsers' => $isAdmin ? myFunctions::getAllUsers() : array(),
            'timeZones' => self::getTimeZoneList(),
            'currentLogContent' => self::getCurrentLogContent(),
            'isAdmin' => $isAdmin,
            'notificationOverview' => $notificationOverview,
            'migrationStatus' => self::buildMigrationStatus(),
        );
    }

    public static function buildMigrationStatus()
    {
        $pdo = dbConfig::getInstance();
        $migrations = array(
            array(
                'file' => 'docs/db_design/migrations/2026-04-25_schema_hardening.sql',
                'label' => 'Schema hardening / text sensor data',
                'checks' => array(
                    array('type' => 'column_type', 'table' => 'sensorData', 'column' => 'value1', 'contains' => 'varchar(255)'),
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
                ),
            ),
            array(
                'file' => 'docs/db_design/migrations/2026-05-14_user_language.sql',
                'label' => 'User language setting',
                'checks' => array(
                    array('type' => 'column', 'table' => 'users', 'column' => 'language'),
                ),
            ),
        );

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
                if (!preg_match('/^[A-Za-z0-9_]+$/', $check['table'])) {
                    return false;
                }
                $statement = $pdo->query("SELECT COUNT(*) AS badRows FROM `" . $check['table'] . "` WHERE " . $check['where']);
                $row = $statement->fetch(PDO::FETCH_ASSOC);
                return ((int)($row['badRows'] ?? 0) === 0);
            }
        } catch (Throwable $e) {
            writeToLogFunction::exception($e, __FILE__, array('migrationCheck' => $check));
            return false;
        }

        return false;
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

    private static function getTimeZoneList()
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

    private static function getCurrentLogContent()
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
}
