<?php

require_once(__DIR__ . '/../Infrastructure/Database/dbConfig.func.php');
require_once(__DIR__ . '/../Infrastructure/Config/configuration.php');
require_once(__DIR__ . '/../Infrastructure/Logging/writeToLogFunction.func.php');
require_once(__DIR__ . '/../Domain/Board/get_data.php');

class NotificationService
{
    private const STATUS_FILE = __DIR__ . '/../../var/status/notification_status.json';
    private static $lastDeliveryReport = null;

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
            array(
                'to' => self::maskEmailAddress($toEmail),
                'recipientDomain' => self::emailDomain($toEmail),
                'scope' => $scope,
            )
        );

        return self::sendTransactionalEmail($toEmail, $subject, $message, 'test-' . $scope, $config);
    }

    /**
     * Sends account-related mail independently from optional alert notifications.
     */
    public static function sendTransactionalEmail($toEmail, $subject, $message, $purpose, $config = null)
    {
        $config = $config ?: new configuration();
        $toEmail = trim((string)$toEmail);
        $subject = trim((string)$subject);
        $purpose = trim((string)$purpose) ?: 'transactional';

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            self::$lastDeliveryReport = array(
                'status' => 'invalid_recipient',
                'purpose' => $purpose,
                'message' => 'The recipient address is invalid.',
            );
            writeToLogFunction::warning(
                'Transactional email rejected because the recipient address is invalid.',
                __FILE__,
                array('purpose' => $purpose, 'to' => self::maskEmailAddress($toEmail))
            );
            return false;
        }

        writeToLogFunction::info(
            'Transactional email delivery requested.',
            __FILE__,
            array(
                'purpose' => $purpose,
                'to' => self::maskEmailAddress($toEmail),
                'recipientDomain' => self::emailDomain($toEmail),
            )
        );

        return self::sendEmail($toEmail, $subject, (string)$message, $config, $purpose);
    }

    public static function getLastDeliveryReport()
    {
        return self::$lastDeliveryReport;
    }

    public static function getMailConfigurationDiagnostics($config = null)
    {
        $config = $config ?: new configuration();
        $fromAddress = trim((string)($config::$systemEmailAddress ?: $config::$adminEmailAddress));
        $senderDomain = self::emailDomain($fromAddress);
        $requestHost = self::requestHost();

        return array(
            'sender' => self::maskEmailAddress($fromAddress),
            'senderDomain' => $senderDomain,
            'senderValid' => filter_var($fromAddress, FILTER_VALIDATE_EMAIL) !== false,
            'applicationHost' => $requestHost,
            'senderMatchesHost' => self::senderDomainMatchesHost($senderDomain, $requestHost),
            'mailFunctionAvailable' => function_exists('mail'),
            'sendmailPath' => (string)ini_get('sendmail_path'),
            'smtpHost' => (string)ini_get('SMTP'),
            'smtpPort' => (string)ini_get('smtp_port'),
        );
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
            "SELECT boardConfig.*
             FROM boardConfig
             WHERE boardConfig.offlineDataTimer != 0
               AND boardConfig.alarmOnUnavailable = 1
               AND boardConfig.alreadyNotified = 0
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

            $recipients = self::getBoardNotificationRecipients($boardId, 'offline');
            if (empty($recipients)) {
                writeToLogFunction::warning(
                    'Skipping offline notification because no recipient is configured.',
                    __FILE__,
                    array('boardId' => $boardId, 'boardName' => $boardRow['name'] ?? null)
                );
                continue;
            }

            $subject = self::buildSubject($config, 'Board offline: ' . ($boardRow['name'] ?: $boardRow['macAddress']));
            $message = self::buildOfflineMessage($boardRow);
            $boardSent = false;
            foreach ($recipients as $recipient) {
                writeToLogFunction::info(
                    'Attempting offline notification email.',
                    __FILE__,
                    array(
                        'boardId' => $boardId,
                        'boardName' => $boardRow['name'] ?? null,
                        'to' => $recipient['email'] ?? null,
                        'offlineDataTimer' => $boardRow['offlineDataTimer'] ?? null,
                    )
                );
                if (self::sendEmail($recipient['email'], $subject, $message, $config)) {
                    $sentCount++;
                    $boardSent = true;
                    writeToLogFunction::info(
                        'Offline notification email sent.',
                        __FILE__,
                        array('boardId' => $boardId, 'to' => $recipient['email'] ?? null)
                    );
                }
            }

            if ($boardSent) {
                self::markBoardOfflineNotificationSent($boardId);
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
                boardConfig.ownerUserId
             FROM sensorChannelConfig
             INNER JOIN sensorConfig ON sensorConfig.id = sensorChannelConfig.sensorConfigId
             INNER JOIN boardConfig ON boardConfig.id = sensorConfig.boardId
             WHERE sensorChannelConfig.AlertEnabled = 1
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
            $recipients = self::getSensorNotificationRecipients((int)$channelRow['sensorConfigId']);
            if (empty($recipients)) {
                writeToLogFunction::warning(
                    'Skipping sensor alert email because no recipient is configured.',
                    __FILE__,
                    array(
                        'channelConfigId' => $channelRow['id'] ?? null,
                        'sensorConfigId' => $channelRow['sensorConfigId'] ?? null,
                        'alertState' => $alertState,
                    )
                );
                continue;
            }

            $alertSent = false;
            foreach ($recipients as $recipient) {
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
                        'to' => $recipient['email'] ?? null,
                    )
                );

                if (self::sendEmail($recipient['email'], $subject, $message, $config)) {
                    $sentCount++;
                    $alertSent = true;
                    writeToLogFunction::info(
                        'Sensor alert email sent.',
                        __FILE__,
                        array(
                            'channelConfigId' => $channelRow['id'] ?? null,
                            'sensorConfigId' => $channelRow['sensorConfigId'] ?? null,
                            'alertState' => $alertState,
                            'to' => $recipient['email'] ?? null,
                        )
                    );
                }
            }

            if ($alertSent) {
                self::updateChannelAlertState((int)$channelRow['id'], $alertState);
            }
        }

        return $sentCount;
    }

    private static function getBoardNotificationRecipients($boardId, $notificationType)
    {
        $pdo = dbConfig::getInstance();
        $boardId = (int)$boardId;
        $notificationColumn = $notificationType === 'sensor'
            ? 'receive_sensor_notifications'
            : 'receive_offline_notifications';

        if (self::permissionsTablesExist($pdo)) {
            $statement = $pdo->prepare(
                "SELECT DISTINCT users.id, users.email
                 FROM users
                 INNER JOIN board_permissions ON board_permissions.userId = users.id
                 WHERE board_permissions.boardId = ?
                   AND board_permissions.canReceiveAlerts = 1
                   AND users.receive_notifications = 1
                   AND users.`$notificationColumn` = 1
                   AND users.active = 1
                 UNION
                 SELECT DISTINCT users.id, users.email
                 FROM users
                 INNER JOIN boardConfig ON boardConfig.ownerUserId = users.id
                 WHERE boardConfig.id = ?
                   AND users.receive_notifications = 1
                   AND users.`$notificationColumn` = 1
                   AND users.active = 1"
            );
            $statement->execute(array($boardId, $boardId));
            return self::deduplicateRecipients($statement->fetchAll(PDO::FETCH_ASSOC));
        }

        $statement = $pdo->prepare(
            "SELECT users.id, users.email
             FROM users
             INNER JOIN boardConfig ON boardConfig.ownerUserId = users.id
             WHERE boardConfig.id = ?
               AND users.receive_notifications = 1
               AND users.`$notificationColumn` = 1
               AND users.active = 1"
        );
        $statement->execute(array($boardId));
        return self::deduplicateRecipients($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    private static function getSensorNotificationRecipients($sensorId)
    {
        $pdo = dbConfig::getInstance();
        $sensorId = (int)$sensorId;
        if (self::permissionsTablesExist($pdo)) {
            $statement = $pdo->prepare(
                "SELECT DISTINCT users.id, users.email
                 FROM users
                 INNER JOIN sensor_permissions ON sensor_permissions.userId = users.id
                 WHERE sensor_permissions.sensorId = ?
                   AND sensor_permissions.canReceiveAlerts = 1
                   AND users.receive_notifications = 1
                   AND users.receive_sensor_notifications = 1
                   AND users.active = 1
                 UNION
                 SELECT DISTINCT users.id, users.email
                 FROM users
                 INNER JOIN sensorConfig ON sensorConfig.id = ?
                 INNER JOIN board_permissions ON board_permissions.boardId = sensorConfig.boardId
                   AND board_permissions.userId = users.id
                 WHERE board_permissions.canReceiveAlerts = 1
                   AND users.receive_notifications = 1
                   AND users.receive_sensor_notifications = 1
                   AND users.active = 1
                 UNION
                 SELECT DISTINCT users.id, users.email
                 FROM users
                 INNER JOIN sensorConfig ON sensorConfig.id = ?
                 INNER JOIN boardConfig ON boardConfig.id = sensorConfig.boardId
                   AND boardConfig.ownerUserId = users.id
                 WHERE users.receive_notifications = 1
                   AND users.receive_sensor_notifications = 1
                   AND users.active = 1"
            );
            $statement->execute(array($sensorId, $sensorId, $sensorId));
            return self::deduplicateRecipients($statement->fetchAll(PDO::FETCH_ASSOC));
        }

        $statement = $pdo->prepare(
            "SELECT users.id, users.email
             FROM users
             INNER JOIN sensorConfig ON sensorConfig.id = ?
             INNER JOIN boardConfig ON boardConfig.id = sensorConfig.boardId
               AND boardConfig.ownerUserId = users.id
             WHERE users.receive_notifications = 1
               AND users.receive_sensor_notifications = 1
               AND users.active = 1"
        );
        $statement->execute(array($sensorId));
        return self::deduplicateRecipients($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    private static function deduplicateRecipients(array $rows)
    {
        $recipients = array();
        foreach ($rows as $row) {
            $email = trim((string)($row['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $recipients[strtolower($email)] = array(
                'id' => isset($row['id']) ? (int)$row['id'] : null,
                'email' => $email,
            );
        }

        return array_values($recipients);
    }

    private static function permissionsTablesExist(PDO $pdo)
    {
        static $tablesExist = null;
        if ($tablesExist !== null) {
            return $tablesExist;
        }

        try {
            $statement = $pdo->prepare(
                "SELECT COUNT(*) AS tableCount
                 FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME IN ('board_permissions', 'sensor_permissions')"
            );
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            $tablesExist = ((int)($row['tableCount'] ?? 0) === 2);
        } catch (Throwable $e) {
            $tablesExist = false;
            writeToLogFunction::exception($e, __FILE__);
        }

        return $tablesExist;
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

    private static function sendEmail($toEmail, $subject, $message, $config, $purpose = 'notification')
    {
        $fromAddress = trim((string)($config::$systemEmailAddress ?: $config::$adminEmailAddress));
        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            self::$lastDeliveryReport = array(
                'status' => 'invalid_sender',
                'purpose' => $purpose,
                'message' => 'The configured sender address is invalid.',
            );
            writeToLogFunction::warning(
                'Email could not be sent because the configured sender address is invalid.',
                __FILE__,
                array(
                    'purpose' => $purpose,
                    'configuredSender' => self::maskEmailAddress($fromAddress),
                )
            );
            return false;
        }

        $applicationName = preg_replace('/[\r\n]+/', ' ', trim((string)$config::$applicationName));
        if ($applicationName === '') {
            $applicationName = 'Maritime Data Server';
        }
        $subject = preg_replace('/[\r\n]+/', ' ', trim((string)$subject));

        $mailHeaders = "From: " . $applicationName . " <" . $fromAddress . ">\r\n";
        $mailHeaders .= "Reply-To: " . $applicationName . " <" . $fromAddress . ">\r\n";
        $mailHeaders .= "MIME-Version: 1.0\r\n";
        $mailHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $diagnostics = array(
            'purpose' => $purpose,
            'to' => self::maskEmailAddress($toEmail),
            'recipientDomain' => self::emailDomain($toEmail),
            'from' => self::maskEmailAddress($fromAddress),
            'senderDomain' => self::emailDomain($fromAddress),
            'subject' => $subject,
            'phpSapi' => PHP_SAPI,
            'sendmailPath' => (string)ini_get('sendmail_path'),
            'smtpHost' => (string)ini_get('SMTP'),
            'smtpPort' => (string)ini_get('smtp_port'),
        );

        self::logSenderDomainWarning($fromAddress, $purpose);
        writeToLogFunction::info(
            'Calling PHP mail().',
            __FILE__,
            $diagnostics
        );

        error_clear_last();
        $sent = mail($toEmail, $subject, $message, $mailHeaders);
        $mailError = error_get_last();
        if (!$sent) {
            self::$lastDeliveryReport = array(
                'status' => 'rejected',
                'purpose' => $purpose,
                'message' => is_array($mailError) && !empty($mailError['message'])
                    ? (string)$mailError['message']
                    : 'PHP mail() rejected the message.',
            );
            writeToLogFunction::warning(
                'PHP mail() rejected the email.',
                $_SERVER["SCRIPT_FILENAME"] ?? __FILE__,
                array_merge($diagnostics, array(
                    'phpError' => is_array($mailError) ? (string)($mailError['message'] ?? '') : '',
                ))
            );
            return false;
        }

        writeToLogFunction::info(
            'PHP mail() accepted the email for local delivery.',
            __FILE__,
            array_merge($diagnostics, array(
                'deliveryNote' => 'Acceptance does not confirm delivery to the recipient mailbox.',
            ))
        );

        self::$lastDeliveryReport = array(
            'status' => 'accepted',
            'purpose' => $purpose,
            'message' => 'PHP accepted the message for local delivery; recipient delivery is not confirmed.',
        );

        return true;
    }

    private static function logSenderDomainWarning($fromAddress, $purpose)
    {
        $senderDomain = self::emailDomain($fromAddress);
        $requestHost = self::requestHost();
        if ($senderDomain === '' || $requestHost === '' || $requestHost === 'localhost') {
            return;
        }

        if (!self::senderDomainMatchesHost($senderDomain, $requestHost)) {
            writeToLogFunction::warning(
                'Configured email sender domain differs from the application host; SPF or DMARC may reject delivery.',
                __FILE__,
                array(
                    'purpose' => $purpose,
                    'senderDomain' => $senderDomain,
                    'applicationHost' => $requestHost,
                )
            );
        }
    }

    private static function maskEmailAddress($emailAddress)
    {
        $emailAddress = trim((string)$emailAddress);
        $separator = strrpos($emailAddress, '@');
        if ($separator === false) {
            return $emailAddress === '' ? '' : substr($emailAddress, 0, 1) . '***';
        }

        $localPart = substr($emailAddress, 0, $separator);
        $domain = substr($emailAddress, $separator + 1);
        return substr($localPart, 0, 1) . '***@' . $domain;
    }

    private static function emailDomain($emailAddress)
    {
        $separator = strrpos((string)$emailAddress, '@');
        return $separator === false ? '' : strtolower(substr((string)$emailAddress, $separator + 1));
    }

    private static function requestHost()
    {
        return strtolower((string)preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    }

    private static function senderDomainMatchesHost($senderDomain, $requestHost)
    {
        if ($senderDomain === '' || $requestHost === '' || $requestHost === 'localhost') {
            return true;
        }

        return $requestHost === $senderDomain
            || substr($requestHost, -(strlen($senderDomain) + 1)) === '.' . $senderDomain
            || substr($senderDomain, -(strlen($requestHost) + 1)) === '.' . $requestHost;
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
            if (self::permissionsTablesExist($pdo)) {
                $sql .= " AND (
                    boardConfig.ownerUserId = :userId
                    OR EXISTS (
                        SELECT 1
                        FROM board_permissions
                        WHERE board_permissions.boardId = boardConfig.id
                          AND board_permissions.userId = :permissionUserId
                          AND board_permissions.canReceiveAlerts = 1
                    )
                )";
                $params['userId'] = (int)$userId;
                $params['permissionUserId'] = (int)$userId;
            } else {
                $sql .= " AND boardConfig.ownerUserId = :userId";
                $params['userId'] = (int)$userId;
            }
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
            if (self::permissionsTablesExist($pdo)) {
                $sql .= " AND (
                    boardConfig.ownerUserId = :userId
                    OR EXISTS (
                        SELECT 1
                        FROM board_permissions
                        WHERE board_permissions.boardId = boardConfig.id
                          AND board_permissions.userId = :boardPermissionUserId
                          AND board_permissions.canReceiveAlerts = 1
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM sensor_permissions
                        WHERE sensor_permissions.sensorId = sensorConfig.id
                          AND sensor_permissions.userId = :sensorPermissionUserId
                          AND sensor_permissions.canReceiveAlerts = 1
                    )
                )";
                $params['userId'] = (int)$userId;
                $params['boardPermissionUserId'] = (int)$userId;
                $params['sensorPermissionUserId'] = (int)$userId;
            } else {
                $sql .= " AND boardConfig.ownerUserId = :userId";
                $params['userId'] = (int)$userId;
            }
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
