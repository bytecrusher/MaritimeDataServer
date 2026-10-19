-- Maritime Data Server
-- Incremental V2 migration after reviewing the refreshed dump
-- Date: 2026-04-25
--
-- Diese Migration behandelt nur die Punkte, die im neueren Dump noch offen sind.
-- Sie ist bewusst klein gehalten, weil viele V1-Anpassungen bereits im Datenbestand
-- sichtbar sind.

START TRANSACTION;

SET NAMES utf8mb4;

-- 1) boardConfig bleibt noch zu eng fuer reale Texte und TTN-Metadaten
ALTER TABLE `boardConfig`
  MODIFY `name` varchar(80) DEFAULT NULL,
  MODIFY `location` varchar(80) DEFAULT NULL,
  MODIFY `ttnAppId` varchar(191) DEFAULT NULL,
  MODIFY `ttnDevId` varchar(191) DEFAULT NULL;

-- 2) sensorConfig bleibt bei Namen/Ortsangaben noch knapp
ALTER TABLE `sensorConfig`
  MODIFY `name` varchar(80) DEFAULT NULL,
  MODIFY `locationOfMeasurement` varchar(120) DEFAULT NULL;

-- 3) sensorChannelConfig-Farbfelder als kurze Strings statt TEXT
ALTER TABLE `sensorChannelConfig`
  MODIFY `GaugeRedAreaLowColor` varchar(20) NOT NULL,
  MODIFY `GaugeRedAreaHighColor` varchar(20) NOT NULL,
  MODIFY `GaugeNormalAreaColor` varchar(20) NOT NULL,
  MODIFY `ChartColor` varchar(20) NOT NULL;

COMMIT;

-- Empfohlene manuelle Nacharbeiten:
--
-- 1) Die Tabelle migration_backup_invalid_securityTokens_20260425 ist im Dump
--    inzwischen Teil des Schemas. Wenn sie nicht mehr gebraucht wird:
--    DROP TABLE migration_backup_invalid_securityTokens_20260425;
--
-- 2) Pruefen, ob fuer securityTokens.identifier inzwischen ein UNIQUE-Index
--    gesetzt werden soll:
--    ALTER TABLE securityTokens ADD UNIQUE KEY uq_securityTokens_identifier (identifier);
