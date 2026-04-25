-- Maritime Data Server
-- Schema hardening migration derived from docs/db_design/k51971_mds-git.sql
-- Date: 2026-04-25
--
-- Ziel:
-- - textbasierte Sensor-Events (z. B. Wakeup/Standby) sicher speichern
-- - enge Typ-/Namensfelder verbreitern
-- - inkonsistente Zeichensaetze angleichen
-- - session/token- und zeitbezogene Tabellen leicht haerten
--
-- Hinweise:
-- - Vor dem Einspielen ein Backup erstellen.
-- - Diese Migration ist bewusst nicht destruktiv und loescht keine Daten.
-- - Die UNIQUE-Constraint fuer securityTokens.identifier wird absichtlich nicht
--   automatisch gesetzt, weil bestehende Dubletten eine Migration abbrechen koennten.

START TRANSACTION;

SET NAMES utf8mb4;

-- 1) Board-Metadaten robuster dimensionieren
ALTER TABLE `boardConfig`
  MODIFY `name` varchar(80) DEFAULT NULL,
  MODIFY `location` varchar(80) DEFAULT NULL,
  MODIFY `description` varchar(255) DEFAULT NULL,
  MODIFY `firmwareVersion` varchar(32) DEFAULT NULL,
  MODIFY `ttnAppId` varchar(191) DEFAULT NULL,
  MODIFY `ttnDevId` varchar(191) DEFAULT NULL;

-- 2) Sensor-Typen fuer neue ingest-Typen oeffnen
ALTER TABLE `sensorTypes`
  MODIFY `name` varchar(64) NOT NULL,
  MODIFY `siUnitVal1` varchar(32) NOT NULL,
  MODIFY `siUnitVal2` varchar(32) NOT NULL,
  MODIFY `siUnitVal3` varchar(32) NOT NULL,
  MODIFY `siUnitVal4` varchar(32) NOT NULL,
  MODIFY `description` varchar(255) NOT NULL;

-- 3) Sensor-Konfigurationen auf etwas realistischere Laengen bringen
ALTER TABLE `sensorConfig`
  MODIFY `sensorAddress` varchar(64) DEFAULT NULL,
  MODIFY `name` varchar(80) DEFAULT NULL,
  MODIFY `description` varchar(255) DEFAULT NULL,
  MODIFY `locationOfMeasurement` varchar(120) DEFAULT NULL;

-- 4) Channel-Konfiguration vereinheitlichen und auf utf8mb4 ziehen
ALTER TABLE `sensorChannelConfig`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  MODIFY `name` varchar(80) DEFAULT NULL,
  MODIFY `description` varchar(255) DEFAULT NULL,
  MODIFY `locationOfMeasurement` varchar(120) DEFAULT NULL,
  MODIFY `GaugeRedAreaLowColor` varchar(20) NOT NULL,
  MODIFY `GaugeRedAreaHighColor` varchar(20) NOT NULL,
  MODIFY `GaugeNormalAreaColor` varchar(20) NOT NULL,
  MODIFY `ChartColor` varchar(20) NOT NULL;

-- 5) Sensor-Daten fuer textbasierte Events und laengere Werte haerten
ALTER TABLE `sensorData`
  MODIFY `value1` varchar(255) NOT NULL,
  MODIFY `value2` varchar(255) DEFAULT NULL,
  MODIFY `value3` varchar(255) DEFAULT NULL,
  MODIFY `value4` varchar(255) DEFAULT NULL;

-- 6) users.updatedAt soll sich wie ein echtes updatedAt verhalten
ALTER TABLE `users`
  MODIFY `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 7) Leichte Index-Haertung fuer haeufige Zugriffe
ALTER TABLE `securityTokens`
  ADD INDEX `idx_securityTokens_userId_createdAt` (`userId`, `createdAt`);

ALTER TABLE `sensorData`
  ADD INDEX `idx_sensorData_sensorId_reading_time` (`sensorId`, `reading_time`);

ALTER TABLE `boardConfig`
  ADD INDEX `idx_boardConfig_ttnAppId_ttnDevId` (`ttnAppId`, `ttnDevId`);

ALTER TABLE `sensorConfig`
  ADD INDEX `idx_sensorConfig_boardId_typId_name` (`boardId`, `typId`, `name`);

COMMIT;

-- Empfohlene manuelle Nacharbeiten / Checks:
--
-- 1) identifier-Dubletten pruefen, bevor ein UNIQUE-Index gesetzt wird:
--    SELECT identifier, COUNT(*) AS cnt
--    FROM securityTokens
--    GROUP BY identifier
--    HAVING COUNT(*) > 1;
--
-- 2) Danach optional haerten:
--    ALTER TABLE securityTokens ADD UNIQUE KEY uq_securityTokens_identifier (identifier);
--
-- 3) Historische Template-/Dummy-Zeilen in sensorConfig mit boardId IS NULL
--    fachlich pruefen. Sie sind nicht zwingend falsch, wirken aber wie Altlasten.
--
-- 4) Bereits abgeschnittene sensorData-Werte aus der Vergangenheit werden durch
--    diese Migration nicht rekonstruiert; sie verhindert nur kuenftige Abschneidungen.
