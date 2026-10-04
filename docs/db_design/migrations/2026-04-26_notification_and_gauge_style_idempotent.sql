-- Idempotente Migration fuer Notification- und Gauge-Style-Erweiterungen.
-- Dieses Skript kann auch auf Systemen laufen, in denen ein Teil der Spalten
-- bereits existiert.

-- --------------------------------------------------------
-- sensorChannelConfig
-- --------------------------------------------------------

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sensorChannelConfig'
      AND COLUMN_NAME = 'GaugeStyle'
  ),
  'SELECT ''sensorChannelConfig.GaugeStyle already exists''',
  'ALTER TABLE `sensorChannelConfig` ADD COLUMN `GaugeStyle` varchar(20) NOT NULL DEFAULT ''classic'' AFTER `GaugeNormalAreaColor`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sensorChannelConfig'
      AND COLUMN_NAME = 'AlertEnabled'
  ),
  'SELECT ''sensorChannelConfig.AlertEnabled already exists''',
  'ALTER TABLE `sensorChannelConfig` ADD COLUMN `AlertEnabled` tinyint NOT NULL DEFAULT ''0'' AFTER `GaugeStyle`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sensorChannelConfig'
      AND COLUMN_NAME = 'AlertLowValue'
  ),
  'SELECT ''sensorChannelConfig.AlertLowValue already exists''',
  'ALTER TABLE `sensorChannelConfig` ADD COLUMN `AlertLowValue` decimal(12,4) DEFAULT NULL AFTER `AlertEnabled`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sensorChannelConfig'
      AND COLUMN_NAME = 'AlertHighValue'
  ),
  'SELECT ''sensorChannelConfig.AlertHighValue already exists''',
  'ALTER TABLE `sensorChannelConfig` ADD COLUMN `AlertHighValue` decimal(12,4) DEFAULT NULL AFTER `AlertLowValue`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sensorChannelConfig'
      AND COLUMN_NAME = 'AlertState'
  ),
  'SELECT ''sensorChannelConfig.AlertState already exists''',
  'ALTER TABLE `sensorChannelConfig` ADD COLUMN `AlertState` varchar(20) DEFAULT NULL AFTER `AlertHighValue`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sensorChannelConfig'
      AND COLUMN_NAME = 'LastAlertSentAt'
  ),
  'SELECT ''sensorChannelConfig.LastAlertSentAt already exists''',
  'ALTER TABLE `sensorChannelConfig` ADD COLUMN `LastAlertSentAt` timestamp NULL DEFAULT NULL AFTER `AlertState`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- users
-- --------------------------------------------------------

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'receive_offline_notifications'
  ),
  'SELECT ''users.receive_offline_notifications already exists''',
  'ALTER TABLE `users` ADD COLUMN `receive_offline_notifications` tinyint NOT NULL DEFAULT ''0'' AFTER `receive_notifications`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'receive_sensor_notifications'
  ),
  'SELECT ''users.receive_sensor_notifications already exists''',
  'ALTER TABLE `users` ADD COLUMN `receive_sensor_notifications` tinyint NOT NULL DEFAULT ''0'' AFTER `receive_offline_notifications`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'dashboardOnlineOnly'
  ),
  'SELECT ''users.dashboardOnlineOnly already exists''',
  'ALTER TABLE `users` ADD COLUMN `dashboardOnlineOnly` tinyint NOT NULL DEFAULT ''0'' AFTER `receive_sensor_notifications`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'preferredChartWindowDays'
  ),
  'SELECT ''users.preferredChartWindowDays already exists''',
  'ALTER TABLE `users` ADD COLUMN `preferredChartWindowDays` int NOT NULL DEFAULT ''7'' AFTER `dashboardOnlineOnly`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'eventTimelineWindowHours'
  ),
  'SELECT ''users.eventTimelineWindowHours already exists''',
  'ALTER TABLE `users` ADD COLUMN `eventTimelineWindowHours` int NOT NULL DEFAULT ''24'' AFTER `preferredChartWindowDays`'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Rueckwaertskompatible Initialisierung:
-- Falls die neuen User-Schalter noch leer/0 sind, uebernehmen sie den bisherigen
-- globalen receive_notifications-Wert.
UPDATE `users`
SET `receive_offline_notifications` = `receive_notifications`
WHERE COALESCE(`receive_offline_notifications`, 0) = 0;

UPDATE `users`
SET `receive_sensor_notifications` = `receive_notifications`
WHERE COALESCE(`receive_sensor_notifications`, 0) = 0;
