-- Maritime Data Server
-- Data cleanup migration derived from docs/db_design/k51971_mds-git.sql
-- Date: 2026-04-25
--
-- Ziel:
-- - eindeutige Altlasten und Inkonsistenzen im bestehenden Datenbestand bereinigen
-- - problematische Platzhalter-/Leerstring-Daten normalisieren
-- - bestehende WakeupStan-Daten auf den aktuellen Ingest-Ansatz angleichen
--
-- Hinweise:
-- - Vor dem Einspielen ein Backup erstellen.
-- - Diese Migration archiviert entfernte Security-Token-Reste vor dem Loeschen.
-- - Fachlich mehrdeutige Daten werden bewusst nicht automatisch geloescht.

START TRANSACTION;

SET NAMES utf8mb4;

-- 1) Offensichtlich ungueltige / alte Security-Token-Reste sichern und entfernen
CREATE TABLE IF NOT EXISTS `migration_backup_invalid_securityTokens_20260425`
LIKE `securityTokens`;

INSERT INTO `migration_backup_invalid_securityTokens_20260425`
SELECT *
FROM `securityTokens`
WHERE `userId` = 0
   OR `securityToken` = '';

DELETE FROM `securityTokens`
WHERE `userId` = 0
   OR `securityToken` = '';

-- 2) Leere Platzhalterstrings auf NULL normalisieren
UPDATE `boardConfig`
SET
  `location` = NULLIF(TRIM(`location`), ''),
  `description` = NULLIF(TRIM(`description`), ''),
  `firmwareVersion` = NULLIF(TRIM(`firmwareVersion`), ''),
  `ttnAppId` = NULLIF(TRIM(`ttnAppId`), ''),
  `ttnDevId` = NULLIF(TRIM(`ttnDevId`), '');

UPDATE `sensorConfig`
SET
  `sensorAddress` = NULLIF(TRIM(`sensorAddress`), ''),
  `description` = NULLIF(TRIM(`description`), ''),
  `locationOfMeasurement` = NULLIF(TRIM(`locationOfMeasurement`), '');

UPDATE `sensorChannelConfig`
SET
  `name` = NULLIF(TRIM(`name`), ''),
  `description` = NULLIF(TRIM(`description`), ''),
  `locationOfMeasurement` = NULLIF(TRIM(`locationOfMeasurement`), '');

-- 3) Eindeutige Text-/Encoding-Korrekturen
UPDATE `users`
SET `lastName` = 'Höche'
WHERE `lastName` = 'HÃ¶che';

UPDATE `sensorTypes`
SET `description` = 'Coordinates'
WHERE `name` = 'GPS'
  AND `description` = 'Coorinates';

-- 4) WakeupStan an den aktuellen Ingest-Ansatz angleichen
UPDATE `sensorTypes`
SET
  `siUnitVal1` = '',
  `siUnitVal2` = '',
  `siUnitVal3` = '',
  `siUnitVal4` = '',
  `description` = 'Wakeup / standby event',
  `MaxNrOfValues` = CASE WHEN `MaxNrOfValues` < 4 THEN 4 ELSE `MaxNrOfValues` END
WHERE `name` = 'WakeupStan';

UPDATE `sensorConfig`
SET `name` = 'Standby enter'
WHERE `name` = 'Wakeup unknown'
  AND `typId` = (
    SELECT `id` FROM (
      SELECT `id` FROM `sensorTypes` WHERE `name` = 'WakeupStan' LIMIT 1
    ) AS `wakeuptype`
  );

UPDATE `sensorChannelConfig`
SET
  `name` = CASE `channelNr`
    WHEN 1 THEN 'Value1'
    WHEN 2 THEN 'Value2'
    WHEN 3 THEN 'Value3'
    WHEN 4 THEN 'Value4'
    ELSE `name`
  END,
  `description` = CASE `channelNr`
    WHEN 1 THEN 'Event value 1'
    WHEN 2 THEN 'Event value 2'
    WHEN 3 THEN 'Event value 3'
    WHEN 4 THEN 'Event value 4'
    ELSE `description`
  END,
  `onDashboard` = 0
WHERE `sensorConfigId` IN (
  SELECT `id` FROM (
    SELECT `id`
    FROM `sensorConfig`
    WHERE `typId` = (
      SELECT `id` FROM `sensorTypes` WHERE `name` = 'WakeupStan' LIMIT 1
    )
  ) AS `wakeupsensors`
);

COMMIT;

-- Empfohlene manuelle Nacharbeiten / Checks:
--
-- 1) Pruefen, ob noch fachlich gewollte Template-Saetze in sensorConfig mit
--    boardId IS NULL / typId IS NULL existieren und ob diese langfristig in eine
--    echte Vorlage-/Metadatenstruktur ausgelagert werden sollten.
--
-- 2) Pruefen, ob importierte Boards mit dem Platzhalternamen '- new imported -'
--    inzwischen sinnvoll benannt werden koennen.
--
-- 3) Optionaler Dubletten-Check fuer sensorConfig je Board + Typ + Name:
--    SELECT boardId, typId, name, COUNT(*) AS cnt
--    FROM sensorConfig
--    GROUP BY boardId, typId, name
--    HAVING COUNT(*) > 1;
