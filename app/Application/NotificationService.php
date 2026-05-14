<?php

require_once(__DIR__ . '/../Infrastructure/Database/dbConfig.func.php');
require_once(__DIR__ . '/../Infrastructure/Config/configuration.php');
require_once(__DIR__ . '/../Infrastructure/Logging/writeToLogFunction.func.php');
require_once(__DIR__ . '/../Domain/Board/get_data.php');

class NotificationService
{
    private const STATUS_FILE = __DIR__ . '/../../var/status/notification_status.json';

    public static function sendPendingNotifications()
    {
        $config = new configuration();
        self::writeJobStatus(array(
            'startedAt' => date('c'),
            'finishedAt' => null,
            'status' => 'running',
            'message' => 'Notification job started.',
        ));
        writeToLogFunction::info(
            'Notification job started.',
            __FILE__,
            array(
                'sendEmails' => (bool)$config::$sendEmails,
                'systemEmailAddress' => $config::$systemEmailAddress,
                'adminEmailAddress' => $config::$adminEmailAddress,
                'applicationName' => $config::$applicationName,
            )
        );
        if (!(bool)$config::$sendEmails) {
            writeToLogFunction::warning('Notification job skipped because sendEmails is disabled.', __FILE__);
            self::writeJobStatus(array(
                'startedAt' => date('c'),
                'finishedAt' => date('c'),
                'status' => 'skipped',
                'message' => 'sendEmails disabled',
                'offlineSent' => 0,
                'sensorAlertsSent' => 0,
            ));
            return array(
                'offlineSent' => 0,
                'sensorAlertsSent' => 0,
                'skipped' => 'sendEmails disabled',
            );
        }

        $result = array(
            'offlineSent' => self::sendOfflineBoardNotifications($config),
            'sensorAlertsSent' => self::sendSensorThresholdNotifications($config),
        );
        writeToLogFunction::info('Notification job finished.', __FILE__, $result);
        self::writeJobStatus(array(
            'startedAt' => date('c'),
            'finishedAt' => date('c'),
            'status' => 'success',
            'message' => 'Notification job finished.',
            'offlineSent' => $result['offlineSent'],
            'sensorAlertsSent' => $result['sensorAlertsSent'],
        ));
        return $result;
    }

    public static function sendTestEmail($toEmail, $config = null, $scope = 'user')
    {
        $config = $config ?: new configuration();
        $toEmail = trim((string)$toEmail);
        if ($toEmail === '') {
            writeToLogFunction::warning('Test email skipped because no target address was provided.', __FILE__, array('scope' => $scope));
            return false;
        }
        $subject = self::buildSubject($config, 'Test email');
        $message = implode("\n", array(
            'This is a test email from Maritime Data Server.',
            '',
            'Scope: ' . $scope,
            'Time: ' . date('d.m.Y H:i:s'),
            'Base URL: ' . ($config::$baseurl ?? ''),
        ));

        writeToLogFunction::info(
            'Attempting test notification email.',
            __FILE__,
            array('to' => $toEmail, 'scope' => $scope)
        );

        return self::sendEmail($toEmail, $subject, $message, $config);
    }

    public static function getNotificationStatusOverview($userId = null, $isAdmin = false)
    {
        return array(
            'jobStatus' => self::readJobStatus(),
            'offlineBoards' => self::getOfflineBoardOverview($userId, $isAdmin),
            'activeSensorAlerts' => self::getActiveSensorAlertOverview($userId, $isAdmin),
        );
    }

    private static function sendOfflineBoardNotifications($config)
    {
        $pdo = dbConfig::getInstance();
        $statement = $pdo->prepare(
            "SELECT boardConfig.*, users.email, users.receive_notifications
             FROM boardConfig
             INNER JOIN users ON users.id = boardConfig.ownerUserId
             WHERE boardConfig.offlineDataTimer != 0
               AND boardConfig.alarmOnUnavailable = 1
               AND boardConfig.alreadyNotified = 0
               AND users.receive_notifications = 1
               AND users.receive_offline_notifications = 1
             ORDER BY boardConfig.id"
        );
        $statement->execute();
        $boards = $statement->fetchAll(PDO::FETCH_ASSOC);
        writeToLogFunction::info(
            'Loaded offline notification candidates.',
            __FILE__,
            array('candidateCount' => count($boards))
        );

        $sentCount = 0;
        foreach ($boards as $boardRow) {
            $boardId = (int)$boardRow['id'];
            if (checkDeviceIsOnline($boardId)) {
                writeToLogFunction::debug(
                    'Skipping offline notification because board is reachable again.',
                    __FILE__,
                    array('boardId' => $boardId, 'boardName' => $boardRow['name'] ?? null)
                );
                continue;
            }

            $subject = self::buildSubject($config, 'Board offline: ' . ($boardRow['name'] ?: $boardRow['macAddress']));
            $message = self::buildOfflineMessage($boardRow);
            writeToLogFunction::info(
                'Attempting offline notification email.',
                __FILE__,
                array(
                    'boardId' => $boardId,
                    'boardName' => $boardRow['name'] ?? null,
                    'to' => $boardRow['email'] ?? null,
                    'offlineDataTimer' => $boardRow['offlineDataTimer'] ?? null,
                )
            );
            if (self::sendEmail($boardRow['email'], $subject, $message, $config)) {
                $sentCount++;
                self::markBoardOfflineNotificationSent($boardId);
                writeToLogFunction::info(
                    'Offline notification email sent.',
                    __FILE__,
                    array('boardId' => $boardId, 'to' => $boardRow['email'] ?? null)
                );
            }
        }

        return $sentCount;
    }

    private static function sendSensorThresholdNotifications($config)
    {
        $pdo = dbConfig::getInstance();
        $statement = $pdo->prepare(
            "SELECT
                sensorChannelConfig.*,
                sensorConfig.boardId,
                sensorConfig.name AS sensorConfigName,
                boardConfig.name AS boardName,
                boardConfig.macAddress,
                boardConfig.ownerUserId,
                users.email,
                users.receive_notifications
             FROM sensorChannelConfig
             INNER JOIN sensorConfig ON sensorConfig.id = sensorChannelConfig.sensorConfigId
             INNER JOIN boardConfig ON boardConfig.id = sensorConfig.boardId
             INNER JOIN users ON users.id = boardConfig.ownerUserId
             WHERE sensorChannelConfig.AlertEnabled = 1
               AND users.receive_notifications = 1
               AND users.receive_sensor_notifications = 1
             ORDER BY sensorChannelConfig.id"
        );
        $statement->execute();
        $channels = $statement->fetchAll(PDO::FETCH_ASSOC);
        writeToLogFunction::info(
            'Loaded sensor alert candidates.',
            __FILE__,
            array('candidateCount' => count($channels))
        );

        $sentCount = 0;
        foreach ($channels as $channelRow) {
            $channelNr = (int)$channelRow['channelNr'];
            if ($channelNr < 1 || $channelNr > 4) {
                writeToLogFunction::warning(
                    'Skipping sensor alert with unsupported channel number.',
                    __FILE__,
                    array('channelConfigId' => $channelRow['id'] ?? null, 'channelNr' => $channelNr)
                );
                continue;
            }

            $latestData = self::getLatestSensorDataRow((int)$channelRow['sensorConfigId']);
            if (!$latestData) {
                writeToLogFunction::debug(
                    'Skipping sensor alert because no sensorData row exists yet.',
                    __FILE__,
                    array('channelConfigId' => $channelRow['id'] ?? null, 'sensorConfigId' => $channelRow['sensorConfigId'] ?? null)
                );
                continue;
            }

            $valueKey = 'value' . $channelNr;
            $currentValue = $latestData[$valueKey] ?? null;
            if (!is_numeric($currentValue)) {
                writeToLogFunction::debug(
                    'Skipping sensor alert because current value is not numeric.',
                    __FILE__,
                    array(
                        'channelConfigId' => $channelRow['id'] ?? null,
                        'sensorConfigId' => $channelRow['sensorConfigId'] ?? null,
                        'valueKey' => $valueKey,
                        'currentValue' => $currentValue,
                    )
                );
                self::updateChannelAlertState((int)$channelRow['id'], null);
                continue;
            }

            $currentValue = (float)$currentValue;
            $alertState = self::determineAlertState($channelRow, $currentValue);
            $previousState = $channelRow['AlertState'] ?? null;

            if ($alertState === null) {
                if (!empty($previousState)) {
                    self::updateChannelAlertState((int)$channelRow['id'], null);
                    writeToLogFunction::info(
                        'Sensor alert state reset to normal.',
                        __FILE__,
                        array(
                            'channelConfigId' => $channelRow['id'] ?? null,
                            'sensorConfigId' => $channelRow['sensorConfigId'] ?? null,
                            'previousState' => $previousState,
                            'currentValue' => $currentValue,
                        )
                    );
                }
                continue;
            }

            if ($previousState === $alertState) {
                writeToLogFunction::debug(
                    'Skipping sensor alert email because state is unchanged.',
                    __FILE__,
                    array(
                        'channelConfigId' => $channelRow['id'] ?? null,
                        'sensorConfigId' => $channelRow['sensorConfigId'] ?? null,
                        'alertState' => $alertState,
                        'currentValue' => $currentValue,
                    )
                );
                continue;
            }

            $subject = self::buildSubject(
                $config,
                'Sensor alert: ' . ($channelRow['boardName'] ?: $channelRow['macAddress']) . ' / ' . ($channelRow['name'] ?: ('Channel ' . $channelNr))
            );
            $message = self::buildSensorAlertMessage($channelRow, $latestData, $currentValue, $alertState);
            writeToLogFunction::info(
                'Attempting sensor alert email.',
                __FILE__,
                array(
                    'channelConfigId' => $channelRow['id'] ?? null,
                    'sensorConfigId' => $channelRow['sensorConfigId'] ?? null,
                    'boardName' => $channelRow['boardName'] ?? null,
                    'channelName' => $channelRow['name'] ?? null,
                    'alertState' => $alertState,
                    'currentValue' => $currentValue,
                    'to' => $channelRow['email'] ?? null,
                )
            );

            if (self::sendEmail($channelRow['email'], $subject, $message, $config)) {
                $sentCount++;
                self::updateChannelAlertState((int)$channelRow['id'], $alertState);
                writeToLogFunction::info(
                    'Sensor alert email sent.',
                    __FILE__,
                    array(
                        'channelConfigId' => $channelRow['id'] ?? null,
                        'sensorConfigId' => $channelRow['sensorConfigId'] ?? null,
                        'alertState' => $alertState,
                        'to' => $channelRow['email'] ?? null,
                    )
                );
            }
        }

        return $sentCount;
    }

    private static function determineAlertState(array $channelRow, $currentValue)
    {
        $lowValue = $channelRow['AlertLowValue'] ?? null;
        $highValue = $channelRow['AlertHighValue'] ?? null;

        if ($lowValue !== null && $lowValue !== '' && is_numeric($lowValue) && $currentValue < (float)$lowValue) {
            return 'low';
        }

        if ($highValue !== null && $highValue !== '' && is_numeric($highValue) && $currentValue > (float)$highValue) {
            return 'high';
        }

        return null;
    }

    private static function getLatestSensorDataRow($sensorConfigId)
    {
        $pdo = dbConfig::getInstance();
        $statement = $pdo->prepare("SELECT * FROM sensorData WHERE sensorId = ? ORDER BY id DESC LIMIT 1");
        $statement->execute(array((int)$sensorConfigId));
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    private static function markBoardOfflineNotificationSent($boardId)
    {
        $pdo = dbConfig::getInstance();
        $statement = $pdo->prepare("UPDATE boardConfig SET alreadyNotified = 1 WHERE id = ?");
        $statement->execute(array((int)$boardId));
    }

    private static function updateChannelAlertState($channelConfigId, $alertState)
    {
        $pdo = dbConfig::getInstance();
        $statement = $pdo->prepare(
            "UPDATE sensorChannelConfig
             SET AlertState = :alertState,
                 LastAlertSentAt = :lastAlertSentAt
             WHERE id = :id"
        );
        $statement->execute(array(
            'alertState' => $alertState,
            'lastAlertSentAt' => $alertState === null ? null : date('Y-m-d H:i:s'),
            'id' => (int)$channelConfigId,
        ));
    }

    private static function sendEmail($toEmail, $subject, $message, $config)
    {
        $fromAddress = trim((string)($config::$systemEmailAddress ?: $config::$adminEmailAddress));
        if ($fromAddress === '') {
            $fromAddress = 'noreply@localhost';
        }

        $applicationName = trim((string)$config::$applicationName);
        if ($applicationName === '') {
            $applicationName = 'Maritime Data Server';
        }

        $mailHeaders = "From: " . $applicationName . "<" . $fromAddress . ">\r\n";
        $mailHeaders .= "Reply-To: " . $applicationName . " <" . $fromAddress . ">\r\n";
        $mailHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";

        writeToLogFunction::debug(
            'Calling PHP mail().',
            __FILE__,
            array(
                'to' => $toEmail,
                'from' => $fromAddress,
                'subject' => $subject,
            )
        );
        $sent = mail($toEmail, $subject, $message, $mailHeaders);
        if (!$sent) {
            writeToLogFunction::warning(
                'Notification email could not be sent.',
                $_SERVER["SCRIPT_FILENAME"] ?? __FILE__,
                array(
                    'to' => $toEmail,
                    'from' => $fromAddress,
                    'subject' => $subject,
                )
            );
        }

        return $sent;
    }

    private static function buildSubject($config, $suffix)
    {
        $applicationName = trim((string)$config::$applicationName);
        if ($applicationName === '') {
            $applicationName = 'Maritime Data Server';
        }

        return $applicationName . ' - ' . $suffix;
    }

    private static function buildOfflineMessage(array $boardRow)
    {
        return implode("\n", array(
            'A board is currently offline.',
            '',
            'Board: ' . ($boardRow['name'] ?: '- unnamed -'),
            'MAC: ' . $boardRow['macAddress'],
            'Offline timer: ' . (int)$boardRow['offlineDataTimer'] . ' minute(s)',
            'Time: ' . date('d.m.Y H:i:s'),
        ));
    }

    private static function buildSensorAlertMessage(array $channelRow, array $latestData, $currentValue, $alertState)
    {
        $threshold = $alertState === 'low' ? $channelRow['AlertLowValue'] : $channelRow['AlertHighValue'];
        $direction = $alertState === 'low' ? 'below' : 'above';

        return implode("\n", array(
            'A sensor channel reached a critical value.',
            '',
            'Board: ' . ($channelRow['boardName'] ?: '- unnamed -'),
            'MAC: ' . $channelRow['macAddress'],
            'Sensor: ' . ($channelRow['sensorConfigName'] ?: '- unnamed -'),
            'Channel: ' . ($channelRow['name'] ?: ('Channel ' . $channelRow['channelNr'])),
            'Current value: ' . $currentValue,
            'Threshold: ' . $threshold,
            'State: ' . $direction,
            'Measurement time: ' . ($latestData['reading_time'] ?? date('Y-m-d H:i:s')),
        ));
    }

    private static function getOfflineBoardOverview($userId, $isAdmin)
    {
        $pdo = dbConfig::getInstance();
        $sql = "SELECT
                    boardConfig.id,
                    boardConfig.name,
                    boardConfig.macAddress,
                    boardConfig.offlineDataTimer,
                    boardConfig.alarmOnUnavailable,
                    boardConfig.alreadyNotified,
                    boardConfig.ownerUserId
                FROM boardConfig
                WHERE boardConfig.offlineDataTimer != 0
                  AND boardConfig.alarmOnUnavailable = 1";
        $params = array();
        if (!$isAdmin && $userId !== null) {
            $sql .= " AND boardConfig.ownerUserId = :userId";
            $params['userId'] = (int)$userId;
        }
        $sql .= " ORDER BY boardConfig.id";
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function getActiveSensorAlertOverview($userId, $isAdmin)
    {
        $pdo = dbConfig::getInstance();
        if (!self::sensorAlertColumnsExist($pdo)) {
            writeToLogFunction::warning(
                'Sensor alert overview skipped because notification migration columns are missing.',
                __FILE__
            );
            return array();
        }

        $sql = "SELECT
                    sensorChannelConfig.id,
                    sensorChannelConfig.sensorConfigId,
                    sensorChannelConfig.name,
                    sensorChannelConfig.channelNr,
                    sensorChannelConfig.AlertEnabled,
                    sensorChannelConfig.AlertLowValue,
                    sensorChannelConfig.AlertHighValue,
                    sensorChannelConfig.AlertState,
                    sensorChannelConfig.LastAlertSentAt,
                    sensorConfig.boardId,
                    sensorConfig.name AS sensorConfigName,
                    boardConfig.name AS boardName,
                    boardConfig.ownerUserId
                FROM sensorChannelConfig
                INNER JOIN sensorConfig ON sensorConfig.id = sensorChannelConfig.sensorConfigId
                INNER JOIN boardConfig ON boardConfig.id = sensorConfig.boardId
                WHERE sensorChannelConfig.AlertEnabled = 1";
        $params = array();
        if (!$isAdmin && $userId !== null) {
            $sql .= " AND boardConfig.ownerUserId = :userId";
            $params['userId'] = (int)$userId;
        }
        $sql .= " ORDER BY boardConfig.id, sensorChannelConfig.channelNr";
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function sensorAlertColumnsExist(PDO $pdo)
    {
        static $columnsExist = null;
        if ($columnsExist !== null) {
            return $columnsExist;
        }

        try {
            $statement = $pdo->prepare(
                "SELECT COUNT(*) AS existingColumns
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'sensorChannelConfig'
                   AND COLUMN_NAME IN (
                     'AlertEnabled',
                     'AlertLowValue',
                     'AlertHighValue',
                     'AlertState',
                     'LastAlertSentAt'
                   )"
            );
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            $columnsExist = ((int)($row['existingColumns'] ?? 0) === 5);
        } catch (Throwable $e) {
            $columnsExist = false;
            writeToLogFunction::exception($e, __FILE__);
        }

        return $columnsExist;
    }

    private static function writeJobStatus(array $status)
    {
        $statusDirectory = dirname(self::STATUS_FILE);
        if (!is_dir($statusDirectory)) {
            mkdir($statusDirectory, 0775, true);
        }
        file_put_contents(self::STATUS_FILE, json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private static function readJobStatus()
    {
        if (!file_exists(self::STATUS_FILE)) {
            return null;
        }

        $jsonString = file_get_contents(self::STATUS_FILE);
        $status = json_decode((string)$jsonString, true);
        return is_array($status) ? $status : null;
    }
}
