ALTER TABLE `sensorChannelConfig`
  ADD COLUMN `GaugeStyle` varchar(20) NOT NULL DEFAULT 'classic' AFTER `GaugeNormalAreaColor`,
  ADD COLUMN `AlertEnabled` tinyint NOT NULL DEFAULT '0' AFTER `GaugeStyle`,
  ADD COLUMN `AlertLowValue` decimal(12,4) DEFAULT NULL AFTER `AlertEnabled`,
  ADD COLUMN `AlertHighValue` decimal(12,4) DEFAULT NULL AFTER `AlertLowValue`,
  ADD COLUMN `AlertState` varchar(20) DEFAULT NULL AFTER `AlertHighValue`,
  ADD COLUMN `LastAlertSentAt` timestamp NULL DEFAULT NULL AFTER `AlertState`;

ALTER TABLE `users`
  ADD COLUMN `receive_offline_notifications` tinyint NOT NULL DEFAULT '0' AFTER `receive_notifications`,
  ADD COLUMN `receive_sensor_notifications` tinyint NOT NULL DEFAULT '0' AFTER `receive_offline_notifications`,
  ADD COLUMN `dashboardOnlineOnly` tinyint NOT NULL DEFAULT '0' AFTER `receive_sensor_notifications`,
  ADD COLUMN `preferredChartWindowDays` int NOT NULL DEFAULT '7' AFTER `dashboardOnlineOnly`,
  ADD COLUMN `eventTimelineWindowHours` int NOT NULL DEFAULT '24' AFTER `preferredChartWindowDays`;

UPDATE `users`
SET `receive_offline_notifications` = `receive_notifications`,
    `receive_sensor_notifications` = `receive_notifications`
WHERE 1;
