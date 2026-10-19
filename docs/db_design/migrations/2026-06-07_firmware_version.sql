-- Erweitert boardConfig.firmwareVersion fuer reale Firmware-Strings.
-- Idempotent: kann mehrfach ausgefuehrt werden.

SET @sql := IF (
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'boardConfig'
      AND COLUMN_NAME = 'firmwareVersion'
      AND CHARACTER_MAXIMUM_LENGTH >= 64
  ),
  'SELECT ''boardConfig.firmwareVersion already has sufficient length''',
  'ALTER TABLE `boardConfig` MODIFY `firmwareVersion` varchar(64) DEFAULT NULL'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
