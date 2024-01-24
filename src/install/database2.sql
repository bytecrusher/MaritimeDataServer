-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Erstellungszeit: 24. Dez 2022 um 13:12
-- Server-Version: 8.0.31
-- PHP-Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `mds`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `boardConfig`
--

CREATE TABLE `boardConfig` (
  `id` int NOT NULL,
  `ownerUserId` int DEFAULT NULL,
  `macAddress` varchar(30) NOT NULL,
  `name` varchar(30) DEFAULT NULL,
  `location` varchar(20) DEFAULT NULL,
  `description` varchar(50) DEFAULT NULL,
  `performUpdate` tinyint DEFAULT '0',
  `firmwareVersion` varchar(10) DEFAULT NULL,
  `alarmOnUnavailable` tinyint DEFAULT '0',
  `updateDataTimer` int DEFAULT NULL,
  `boardTypeId` int DEFAULT NULL,
  `offlineDataTimer` int DEFAULT '15',
  `alreadyNotified` tinyint NOT NULL DEFAULT '0',
  `ttnAppId` text NOT NULL,
  `ttnDevId` text CHARACTER SET utf16 COLLATE utf16_general_ci NOT NULL,
  `onDashboard` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `boardType`
--

CREATE TABLE `boardType` (
  `id` int NOT NULL,
  `name` varchar(20) NOT NULL,
  `description` varchar(30) NOT NULL,
  `image` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `securityTokens`
--

CREATE TABLE `securityTokens` (
  `id` int UNSIGNED NOT NULL,
  `userId` int NOT NULL,
  `identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `securityToken` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensorConfig`
--

CREATE TABLE `sensorConfig` (
  `id` int NOT NULL,
  `boardId` int DEFAULT NULL,
  `sensorAddress` varchar(20) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `typId` int DEFAULT NULL,
  `locationOfMeasurement` varchar(20) DEFAULT NULL,
  `nameValue1` varchar(10) DEFAULT NULL,
  `Value1GaugeMinValue` int NOT NULL DEFAULT '0',
  `Value1GaugeMaxValue` int NOT NULL DEFAULT '20',
  `Value1GaugeRedAreaLowValue` int NOT NULL,
  `Value1GaugeRedAreaLowColor` text NOT NULL,
  `Value1GaugeRedAreaHighValue` int NOT NULL,
  `Value1GaugeRedAreaHighColor` text NOT NULL,
  `Value1GaugeNormalAreaColor` text NOT NULL,
  `Value1DashboardOrderNr` int DEFAULT '1',
  `Value1onDashboard` tinyint(1) NOT NULL,
  `nameValue2` varchar(10) DEFAULT NULL,
  `Value2GaugeMinValue` int NOT NULL DEFAULT '0',
  `Value2GaugeMaxValue` int NOT NULL DEFAULT '20',
  `Value2GaugeRedAreaLowValue` int NOT NULL,
  `Value2GaugeRedAreaLowColor` text NOT NULL,
  `Value2GaugeRedAreaHighValue` int NOT NULL,
  `Value2GaugeRedAreaHighColor` text NOT NULL,
  `Value2GaugeNormalAreaColor` text NOT NULL,
  `Value2DashboardOrderNr` int DEFAULT '1',
  `Value2onDashboard` tinyint(1) NOT NULL,
  `nameValue3` varchar(10) DEFAULT NULL,
  `Value3GaugeMinValue` int NOT NULL DEFAULT '0',
  `Value3GaugeMaxValue` int NOT NULL DEFAULT '20',
  `Value3GaugeRedAreaLowValue` int NOT NULL,
  `Value3GaugeRedAreaLowColor` text NOT NULL,
  `Value3GaugeRedAreaHighValue` int NOT NULL,
  `Value3GaugeRedAreaHighColor` text NOT NULL,
  `Value3GaugeNormalAreaColor` text NOT NULL,
  `Value3DashboardOrderNr` int DEFAULT '1',
  `Value3onDashboard` tinyint(1) NOT NULL,
  `nameValue4` varchar(10) DEFAULT NULL,
  `Value4GaugeMinValue` int NOT NULL DEFAULT '0',
  `Value4GaugeMaxValue` int NOT NULL DEFAULT '20',
  `Value4GaugeRedAreaLowValue` int NOT NULL,
  `Value4GaugeRedAreaLowColor` text NOT NULL,
  `Value4GaugeRedAreaHighValue` int NOT NULL,
  `Value4GaugeRedAreaHighColor` text NOT NULL,
  `Value4GaugeNormalAreaColor` text NOT NULL,
  `Value4DashboardOrderNr` int DEFAULT '1',
  `Value4onDashboard` tinyint(1) NOT NULL,
  `onDashboard` tinyint DEFAULT '0',
  `ttnPayloadId` int DEFAULT NULL COMMENT 'Position in der TTN Payload.',
  `NrOfUsedSensors` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensorData`
--

CREATE TABLE `sensorData` (
  `id` int UNSIGNED NOT NULL,
  `sensorId` int NOT NULL,
  `value1` varchar(20) NOT NULL,
  `value2` varchar(20) DEFAULT NULL,
  `value3` varchar(10) DEFAULT NULL,
  `value4` varchar(10) DEFAULT NULL,
  `val_date` varchar(10) NOT NULL COMMENT 'When was record added.',
  `val_time` varchar(10) NOT NULL COMMENT 'When was record added.',
  `reading_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `transmissionPath` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensorTypes`
--

CREATE TABLE `sensorTypes` (
  `id` int NOT NULL,
  `name` varchar(10) NOT NULL,
  `siUnitVal1` varchar(10) NOT NULL,
  `siUnitVal2` varchar(10) NOT NULL,
  `siUnitVal3` varchar(10) NOT NULL,
  `siUnitVal4` varchar(10) NOT NULL,
  `oneWireFamilyCode` varchar(2) NOT NULL,
  `description` varchar(20) NOT NULL,
  `MaxNrOfValues` int NOT NULL COMMENT 'How many values are expected.',
  `hasAddress` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `sensorTypes`
--

INSERT INTO `sensorTypes` (`id`, `name`, `siUnitVal1`, `siUnitVal2`, `siUnitVal3`, `siUnitVal4`, `oneWireFamilyCode`, `description`, `MaxNrOfValues`, `hasAddress`) VALUES
(1, 'DS18B20', '&deg;C', '', '', '', '28', 'Temperature', 1, 1),
(2, 'DS2438', '&deg;C', 'V', 'A', '', '26', 'Battery monitor', 3, 1),
(3, 'ADC', 'V', 'V', 'V', 'V', '', 'input from ADC', 4, 0),
(4, 'Digital', '1/0', '1/0', '1/0', '1/0', '', 'input from Digital', 4, 0),
(5, 'BME280', '&deg;C', '%', 'mbar', '&deg;C', '', 'Temp, Hum, Pres, Dew', 4, 0),
(6, 'GPS', 'Lat', 'Lon', 'Alt', 'Spd', '', 'Coorinates', 4, 0),
(7, 'Lora', 'Gtw', 'db', 'snr', '#', '', 'TTN data', 4, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `ttnDataLoraBoatMonitor`
--

CREATE TABLE `ttnDataLoraBoatMonitor` (
  `id` int NOT NULL,
  `datetime` datetime NOT NULL,
  `app_id` text NOT NULL,
  `dev_id` text NOT NULL,
  `ttn_timestamp` text NOT NULL,
  `gtw_id` text NOT NULL,
  `gtw_rssi` float NOT NULL,
  `gtw_snr` float NOT NULL,
  `gtw_channel_index` int NOT NULL,
  `gtw_bandwidth` float NOT NULL,
  `gtw_sf` float NOT NULL,
  `dev_raw_payload` text NOT NULL,
  `dev_counter` int NOT NULL,
  `dev_value_1` float NOT NULL,
  `dev_value_2` float NOT NULL,
  `dev_value_3` float NOT NULL,
  `dev_value_4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint NOT NULL DEFAULT '0',
  `userGroupAdmin` tinyint NOT NULL DEFAULT '0',
  `passwordCode` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `passwordCodeTime` date DEFAULT NULL,
  `dashboardUpdateInterval` int NOT NULL DEFAULT '15',
  `Timezone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Europe/Berlin',
  `receive_notifications` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `boardConfig`
--
ALTER TABLE `boardConfig`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `macAddress` (`macAddress`),
  ADD KEY `ownerUserId` (`ownerUserId`),
  ADD KEY `boardConfig_ibfk_2_idx` (`boardTypeId`);

--
-- Indizes für die Tabelle `boardType`
--
ALTER TABLE `boardType`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `securityTokens`
--
ALTER TABLE `securityTokens`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `sensorConfig`
--
ALTER TABLE `sensorConfig`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sensorId` (`sensorAddress`),
  ADD KEY `typId` (`typId`),
  ADD KEY `boardId` (`boardId`);

--
-- Indizes für die Tabelle `sensorData`
--
ALTER TABLE `sensorData`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sensorId` (`sensorId`);

--
-- Indizes für die Tabelle `sensorTypes`
--
ALTER TABLE `sensorTypes`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `ttnDataLoraBoatMonitor`
--
ALTER TABLE `ttnDataLoraBoatMonitor`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `boardConfig`
--
ALTER TABLE `boardConfig`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `boardType`
--
ALTER TABLE `boardType`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `securityTokens`
--
ALTER TABLE `securityTokens`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensorConfig`
--
ALTER TABLE `sensorConfig`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensorData`
--
ALTER TABLE `sensorData`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensorTypes`
--
ALTER TABLE `sensorTypes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT für Tabelle `ttnDataLoraBoatMonitor`
--
ALTER TABLE `ttnDataLoraBoatMonitor`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `boardConfig`
--
ALTER TABLE `boardConfig`
  ADD CONSTRAINT `boardConfig_ibfk_1` FOREIGN KEY (`ownerUserId`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `boardConfig_ibfk_2` FOREIGN KEY (`boardTypeId`) REFERENCES `boardType` (`id`);

--
-- Constraints der Tabelle `sensorConfig`
--
ALTER TABLE `sensorConfig`
  ADD CONSTRAINT `sensorConfig_ibfk_2` FOREIGN KEY (`typId`) REFERENCES `sensorTypes` (`id`),
  ADD CONSTRAINT `sensorConfig_ibfk_4` FOREIGN KEY (`boardId`) REFERENCES `boardConfig` (`id`);

--
-- Constraints der Tabelle `sensorData`
--
ALTER TABLE `sensorData`
  ADD CONSTRAINT `sensorData_ibfk_2` FOREIGN KEY (`sensorId`) REFERENCES `sensorConfig` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
