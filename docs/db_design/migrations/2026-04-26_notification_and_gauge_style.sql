ALTER TABLE `sensorChannelConfig`
  ADD COLUMN `GaugeStyle` varchar(20) NOT NULL DEFAULT 'classic' AFTER `GaugeNormalAreaColor`,
  ADD COLUMN `AlertEnabled` tinyint NOT NULL DEFAULT '0' AFTER `GaugeStyle`,
  ADD COLUMN `AlertLowValue` decimal(12,4) DEFAULT NULL AFTER `AlertEnabled`,
  ADD COLUMN `AlertHighValue` decimal(12,4) DEFAULT NULL AFTER `AlertLowValue`,
  ADD COLUMN `AlertState` varchar(20) DEFAULT NULL AFTER `AlertHighValue`,
  ADD COLUMN `LastAlertSentAt` timestamp NULL DEFAULT NULL AFTER `AlertState`;
