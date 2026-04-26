<?php

require_once(__DIR__ . '/../Infrastructure/Database/dbConfig.func.php');
require_once(__DIR__ . '/../Infrastructure/Config/configuration.php');
require_once(__DIR__ . '/../Infrastructure/Logging/writeToLogFunction.func.php');
require_once(__DIR__ . '/../Domain/Board/get_data.php');

class NotificationService
{
    public static function sendPendingNotifications()
    {
        $config = new configuration();
        if (!(bool)$config::$sendEmails) {
            return array(
                'offlineSent' => 0,
                'sensorAlertsSent' => 0,
                'skipped' => 'sendEmails disabled',
            );
        }

        return array(
            'offlineSent' => self::sendOfflineBoardNotifications($config),
            'sensorAlertsSent' => self::sendSensorThresholdNotifications($config),
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
             ORDER BY boardConfig.id"
        );
        $statement->execute();
        $boards = $statement->fetchAll(PDO::FETCH_ASSOC);

        $sentCount = 0;
        foreach ($boards as $boardRow) {
            $boardId = (int)$boardRow['id'];
            if (checkDeviceIsOnline($boardId)) {
                continue;
            }

            $subject = self::buildSubject($config, 'Board offline: ' . ($boardRow['name'] ?: $boardRow['macAddress']));
            $message = self::buildOfflineMessage($boardRow);
            if (self::sendEmail($boardRow['email'], $subject, $message, $config)) {
                $sentCount++;
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
                boardConfig.ownerUserId,
                users.email,
                users.receive_notifications
             FROM sensorChannelConfig
             INNER JOIN sensorConfig ON sensorConfig.id = sensorChannelConfig.sensorConfigId
             INNER JOIN boardConfig ON boardConfig.id = sensorConfig.boardId
             INNER JOIN users ON users.id = boardConfig.ownerUserId
             WHERE sensorChannelConfig.AlertEnabled = 1
               AND users.receive_notifications = 1
             ORDER BY sensorChannelConfig.id"
        );
        $statement->execute();
        $channels = $statement->fetchAll(PDO::FETCH_ASSOC);

        $sentCount = 0;
        foreach ($channels as $channelRow) {
            $channelNr = (int)$channelRow['channelNr'];
            if ($channelNr < 1 || $channelNr > 4) {
                continue;
            }

            $latestData = self::getLatestSensorDataRow((int)$channelRow['sensorConfigId']);
            if (!$latestData) {
                continue;
            }

            $valueKey = 'value' . $channelNr;
            $currentValue = $latestData[$valueKey] ?? null;
            if (!is_numeric($currentValue)) {
                self::updateChannelAlertState((int)$channelRow['id'], null);
                continue;
            }

            $currentValue = (float)$currentValue;
            $alertState = self::determineAlertState($channelRow, $currentValue);
            $previousState = $channelRow['AlertState'] ?? null;

            if ($alertState === null) {
                if (!empty($previousState)) {
                    self::updateChannelAlertState((int)$channelRow['id'], null);
                }
                continue;
            }

            if ($previousState === $alertState) {
                continue;
            }

            $subject = self::buildSubject(
                $config,
                'Sensor alert: ' . ($channelRow['boardName'] ?: $channelRow['macAddress']) . ' / ' . ($channelRow['name'] ?: ('Channel ' . $channelNr))
            );
            $message = self::buildSensorAlertMessage($channelRow, $latestData, $currentValue, $alertState);

            if (self::sendEmail($channelRow['email'], $subject, $message, $config)) {
                $sentCount++;
                self::updateChannelAlertState((int)$channelRow['id'], $alertState);
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

        $sent = mail($toEmail, $subject, $message, $mailHeaders);
        if (!$sent) {
            writeToLogFunction::warning(
                'Notification email could not be sent.',
                $_SERVER["SCRIPT_FILENAME"] ?? __FILE__,
                array('to' => $toEmail, 'subject' => $subject)
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
}
